<?php

namespace App\Libraries;

require_once(APPPATH . "ThirdParty/guzzlehttp/autoload.php");

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Promise as GuzzleHttpPromise;

class Gemini {
    private $api_key;
    private $text_model = "gemini-2.5-flash-preview-05-20";
    private $api_base_url = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private $guzzle_http_client;

    public function __construct() {
        $this->api_key = decode_id(get_setting('gemini_api_key'), "gemini_api_key");
        $this->guzzle_http_client = new GuzzleHttpClient([
            'timeout' => 30.0,
            'connect_timeout' => 5.0,
            'http_errors' => false
        ]);

        if (empty($this->api_key)) {
            log_message('error', "Gemini API key is not set.");
            return false;
        }
    }

    private function _make_curl_request($url, $payload, $timeout = 30, $isAsync = false, $headers = []) {
        $options = [
            'json' => $payload,
            'timeout' => $timeout,
            'headers' => array_merge([
                'Content-Type' => 'application/json',
            ], $headers)
        ];

        if ($isAsync) {
            $promises = [
                $this->guzzle_http_client->postAsync($url, $options)
            ];

            // Wait for all promises to complete
            $responses = GuzzleHttpPromise\Utils::settle($promises)->wait();

            // Process the result
            $response = get_array_value($responses, 0);
            if ($response['state'] === 'fulfilled') {
                $result = $response['value']->getBody()->getContents();
                $data = json_decode($result, true);

                return $data;
            } else {
                log_message('error', "Curl Request Failed. Reason: " . $response['reason']);
                return false;
            }
        }

        try {
            $response = $this->guzzle_http_client->post($url, $options);
            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                log_message('error', "Gemini API Error: HTTP Code: {$statusCode} Response: " . $body);
                return false;
            }

            return json_decode($body, true);
        } catch (\Exception $e) {
            log_message('error', "Gemini API Request Failed: " . $e->getMessage());
            return false;
        }

        return json_decode($response, true);
    }

    public function get_response($input_array) {
        try {
            $model = $input_array['model'] ?? $this->text_model;
            $gemini_payload = $this->_build_gemini_payload($input_array);
            $url = "{$this->api_base_url}{$model}:generateContent?key={$this->api_key}";
            $responseData = $this->_make_curl_request($url, $gemini_payload);

            if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                return false;
            }

            return $this->_prepare_response($responseData);
        } catch (\Exception $e) {
            log_message('error', 'Gemini Response Error: ' . $e->getMessage());
            return false;
        }
    }

    public function stream_response($input_array, $callback) {
        try {
            $model = $input_array['model'] ?? $this->text_model;
            $gemini_payload = $this->_build_gemini_payload($input_array);
            $api_url = "{$this->api_base_url}{$model}:streamGenerateContent?alt=sse&key={$this->api_key}";
            $buffer_chunk = ''; // sometime there could be broken json, so we need to buffer it

            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gemini_payload));
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use ($callback, &$buffer_chunk) {

              //  log_message("debug", "Gemini Chunk: " . $chunk);

                // check for any error
                // the error will be a full json
                try {
                    $error = json_decode($chunk, true);
                    $error = get_array_value($error, 'error');
                    if (isset($error['message'])) {
                        log_message("error", "Gemini Error: " . $error['message']);
                        throw new \Exception($error['message']);
                    }
                } catch (\Exception $e) {
                    // normal response, nothing to do
                }

                $buffer_chunk .= $chunk;
                if (strpos($chunk, 'data:') === 0) {
                    $buffer_chunk = $chunk;
                }

                $json = trim(substr($buffer_chunk, 5));
                $data = json_decode($json, true);

                if (is_null($data) || !is_array($data)) {
                    return strlen($chunk);
                }

                // Initialize variables for content and tool calls
                $content_text = null;
                $tool_calls = [];

                // Check if the response contains candidate content
                if (isset($data['candidates'][0]['content']['parts'])) {

                    // Loop through the parts to find text and/or function calls
                    foreach ($data['candidates'][0]['content']['parts'] as $part) {

                        if (isset($part['text'])) {

                            $content_text = $part['text'];
                        } elseif (isset($part['functionCall'])) {

                            // Found a tool call, format it
                            $function_call = $part['functionCall'];

                            $tool_calls = [
                                'id' => $function_call['id'] ?? null,
                                'name' => $function_call['name'] ?? null,
                                'arguments' => json_encode($function_call['args']) ?? null,
                            ];
                        }
                    }
                }

                // $role = $data['candidates'][0]['content']['role'] ?? null;
                // $finish_reason = $data['candidates'][0]['finishReason'] ?? null;
                $response_id = $data['responseId'] ?? null;
                // $index = $data['candidates'][0]['index'] ?? 0;

                // Map the Gemini response to the ChatGPT-like format
                $callback([
                    'response_chunk' => $content_text,
                    'response_id' => $response_id,
                    'tool_calls' => $tool_calls
                ]);

                return strlen($chunk);
            });

            curl_exec($ch);
            curl_close($ch);

            return true;
        } catch (\Exception $e) {

            log_message('error', 'Gemini Streaming Error: ' . $e->getMessage());
            return false;
        }
    }

    private function _build_gemini_payload($input_array) {
        $gemini_payload = [];
        $contents = [];

        // Process the 'messages' array for Gemini's 'contents' and 'system_instruction'
        foreach ($input_array['messages'] as $message) {
            $role = $message['role'];
            $content = $message['content'];

            if ($role === 'system') {
                // Gemini handles system prompts with a dedicated instruction field.
                $gemini_payload['system_instruction']['parts'][0]['text'] = $content;
            } else {
                // Map the user and assistant roles to Gemini's format.
                $parts = [];
                // Check for multimodal content (e.g., text and images)
                if (is_array($content)) {
                    foreach ($content as $part) {
                        if (isset($part['text'])) {
                            $parts[] = ['text' => $part['text']];
                        } elseif (isset($part['inline_data'])) {
                            $parts[] = ['inlineData' => $part['inline_data']];
                        }
                    }
                } else {
                    $parts[] = ['text' => $content];
                }

                $contents[] = [
                    'role' => $role === 'user' ? 'user' : 'model',
                    'parts' => $parts
                ];
            }
        }
        $gemini_payload['contents'] = $contents;

        // Process other parameters and map them to Gemini's generationConfig
        $generationConfig = [];
        if (isset($input_array['max_tokens'])) {
            $generationConfig['maxOutputTokens'] = $input_array['max_tokens'];
        }
        if (isset($input_array['temperature'])) {
            $generationConfig['temperature'] = $input_array['temperature'];
        }
        if (isset($input_array['top_p'])) {
            $generationConfig['topP'] = $input_array['top_p'];
        }
        if (isset($input_array['top_k'])) {
            $generationConfig['topK'] = $input_array['top_k'];
        }

        // Use user-provided stop sequences, or a default if none are provided
        // If no stop sequences are provided, pass null or an empty array
        if (isset($input_array['stop_sequences'])) {
            $generationConfig['stopSequences'] = $input_array['stop_sequences'];
        }

        if (isset($input_array['thinking_budget'])) {
            $generationConfig['thinkingConfig'] = ['thinkingBudget' => $input_array['thinking_budget']];
        }

        if (!empty($generationConfig)) {
            $gemini_payload['generationConfig'] = $generationConfig;
        }

        // Add tool functions if they are present in the input array
        if (isset($input_array['tools']) && is_array($input_array['tools'])) {
            $function_declarations = [];
            foreach ($input_array['tools'] as $tool) {
                if (isset($tool['function'])) {
                    $function_declarations[] = $tool['function'];
                }
            }
            $gemini_payload['tools'] = [
                'function_declarations' => $function_declarations
            ];
        }

        return $gemini_payload;
    }

    public function get_embedding($text, $embedding_model = "gemini-embedding-001") {
        try {
            $url = "{$this->api_base_url}{$embedding_model}:embedContent?key={$this->api_key}";
            $payload = ['content' => ['parts' => [['text' => $text]]]];
            $responseData = $this->_make_curl_request($url, $payload);

            if (!isset($responseData['embedding']['values'])) {
                return false;
            }

            return $responseData['embedding']['values'];
        } catch (\Exception $e) {
            log_message('error', 'Gemini API Embedding Error: ' . $e->getMessage());
            return false;
        }
    }

    private function _prepare_response($responseData) {
        $final_response = [
            "response_id" => "generated-" . uniqid(),
            "created_at" => date("Y-m-d H:i:s"),
            "model" => $this->text_model,
            "response" => $responseData['candidates'][0]['content']['parts'][0]['text'],
            "input_token" => $responseData['usageMetadata']['promptTokenCount'],
            "output_token" => $responseData['usageMetadata']['candidatesTokenCount'],
            "action" => [],
        ];

        return $final_response;
    }

    public function get_models_dropdown() {
        return [
            // Text models
            [
                "type" => "text",
                "id"   => "gemini-2.5-pro",
                "name" => "Gemini 2.5 Pro"
            ],
            [
                "type" => "text",
                "id"   => "gemini-2.5-flash",
                "name" => "Gemini 2.5 Flash"
            ],
            [
                "type" => "text",
                "id"   => "gemini-2.5-flash-lite",
                "name" => "Gemini 2.5 Flash-Lite"
            ],

            // // Image models
            // [
            //     "type" => "image",
            //     "id"   => "gemini-2.5-flash-image-preview",
            //     "name" => "Gemini 2.5 Flash Image Preview (aka Nano Banana)"
            // ],
            // [
            //     "type" => "image",
            //     "id"   => "imagen-4.0-generate-001",
            //     "name" => "Imagen 4.0",
            //     "default_params" => array(
            //         "sampleImageSize" => "1K",
            //         "sampleCount" => 1,
            //         "aspectRatio" => "1:1",
            //         "personGeneration" => "allow_adult"
            //     ),
            //     "allowed_params" => array(
            //         "sampleImageSize" => array("1K", "2K"),
            //         "sampleCount" => array(1, 2, 3, 4),
            //         "aspectRatio" => array("1:1", "3:4", "4:3", "9:16", "16:9"),
            //         "personGeneration" => array("dont_allow", "allow_adult", "allow_all")
            //     ),
            // ],
            // [
            //     "type" => "image",
            //     "id"   => "imagen-4.0-ultra-generate-001",
            //     "name" => "Imagen 4.0 Ultra",
            //     "default_params" => array(
            //         "sampleImageSize" => "1K",
            //         "sampleCount" => 1,
            //         "aspectRatio" => "1:1",
            //         "personGeneration" => "allow_adult"
            //     ),
            //     "allowed_params" => array(
            //         "sampleImageSize" => array("1K", "2K"),
            //         "sampleCount" => array(1, 2, 3, 4),
            //         "aspectRatio" => array("1:1", "3:4", "4:3", "9:16", "16:9"),
            //         "personGeneration" => array("dont_allow", "allow_adult", "allow_all")
            //     ),
            // ],
            // [
            //     "type" => "image",
            //     "id"   => "imagen-4.0-fast-generate-001",
            //     "name" => "Imagen 4.0 Fast",
            //     "default_params" => array(
            //         "sampleCount" => 1,
            //         "aspectRatio" => "1:1",
            //         "personGeneration" => "allow_adult"
            //     ),
            //     "allowed_params" => array(
            //         "sampleCount" => array(1, 2, 3, 4),
            //         "aspectRatio" => array("1:1", "3:4", "4:3", "9:16", "16:9"),
            //         "personGeneration" => array("dont_allow", "allow_adult", "allow_all")
            //     ),
            // ],
            // [
            //     "type" => "image",
            //     "id"   => "imagen-3.0-generate-002",
            //     "name" => "Imagen 3",
            //     "default_params" => array(
            //         "sampleCount" => 1,
            //         "aspectRatio" => "1:1",
            //         "personGeneration" => "allow_adult"
            //     ),
            //     "allowed_params" => array(
            //         "sampleCount" => array(1, 2, 3, 4),
            //         "aspectRatio" => array("1:1", "3:4", "4:3", "9:16", "16:9"),
            //         "personGeneration" => array("dont_allow", "allow_adult", "allow_all")
            //     ),
            // ],

            // // TTS models
            // [
            //     "type" => "tts",
            //     "id"   => "gemini-2.5-flash-preview-tts",
            //     "name" => "Gemini 2.5 Flash Preview TTS"
            // ],
            // [
            //     "type" => "tts",
            //     "id"   => "gemini-2.5-pro-preview-tts",
            //     "name" => "Gemini 2.5 Pro Preview TTS"
            // ]
        ];
    }

    /** 
    function get_image_response($prompt, $agent_id) {
        try {

            ini_set('max_execution_time', 300);

            $AI_agents_model = model("App\Models\AI_agents_model");
            $agent_info = $AI_agents_model->get_one($agent_id);
            $parameters = $agent_info->parameters;
            $parameters = json_decode($parameters, true);

            if ($agent_info->base_model == "gemini-2.5-flash-image-preview") {
                // Generate image using Gemini (aka Nano Banana)
                $url = "{$this->api_base_url}{$agent_info->base_model}:generateContent?key={$this->api_key}";

                // Build the payload with proper structure for image generation
                $payload = [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'responseModalities' => ['IMAGE']
                    ]
                ];

                $response = $this->_make_curl_request($url, $payload, 300, true);
            } else {
                // Generate image using Imagen
                $url = "{$this->api_base_url}{$agent_info->base_model}:predict?key={$this->api_key}";

                // Build the payload with proper structure for image generation
                $payload = [
                    'instances' => [
                        [
                            'prompt' => $prompt
                        ]
                    ],
                    'parameters' => $parameters
                ];

                $response = $this->_make_curl_request($url, $payload, 300, true);
            }

            return $this->_process_image_response($response, $agent_info->base_model);
        } catch (\Exception $e) {
            log_message('error', 'Gemini Image Generation Error: ' . $e->getMessage());
            return false;
        }
    }

    private function _process_image_response($response, $model) {
        $images = [];

        if ($model == "gemini-2.5-flash-image-preview") {
            // Handle Gemini response format
            if (!isset($response['candidates'])) {
                log_message('error', 'Unexpected Gemini API response format: ' . json_encode($response));
                return false;
            }

            foreach ($response['candidates'] as $candidate) {
                if (isset($candidate['content']['parts'][0]['inlineData']['data'])) {
                    $images[] = $candidate['content']['parts'][0]['inlineData']['data'];
                }
            }
        } else {
            // Handle Imagen response format
            if (!isset($response['predictions']) || !is_array($response['predictions'])) {
                log_message('error', 'Unexpected Imagen API response format: ' . json_encode($response));
                return false;
            }

            foreach ($response['predictions'] as $prediction) {
                if (isset($prediction['bytesBase64Encoded'])) {
                    $images[] = $prediction['bytesBase64Encoded'];
                }
            }
        }

        if (empty($images)) {
            log_message('error', 'No valid images found in the API response');
            return false;
        }

        // For backward compatibility, return a single image in the old format if there's only one
        if (count($images) === 1) {
            echo "<img src='data:image/png;base64," . $images[0] . "' alt='Generated AI Image'>";
            return [
                'success' => true,
                'image_data' => $images[0],
                'html' => "<img src='data:image/png;base64," . $images[0] . "' alt='Generated AI Image'>"
            ];
        }

        // For multiple images, return an array of images
        $html = [];
        foreach ($images as $index => $image) {
            $html[] = "<img src='data:image/png;base64," . $image . "' alt='Generated AI Image " . ($index + 1) . "'>";
        }

        $result = [
            'success' => true,
            'images' => $images,
            'html' => implode("\n", $html)
        ];

        echo $result['html'];
        return $result;
    }

    function get_tts_response($prompt, $agent_id) {
        try {

            ini_set('max_execution_time', 300);

            $AI_agents_model = model("App\Models\AI_agents_model");
            $agent_info = $AI_agents_model->get_one($agent_id);
            $url = "{$this->api_base_url}{$agent_info->base_model}:generateContent?key={$this->api_key}";

            // Build the payload with proper structure for tts generation
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    "responseModalities" => ["AUDIO"],
                    "speechConfig" => [
                        "voiceConfig" => [
                            "prebuiltVoiceConfig" => [
                                "voiceName" => "Kore"
                            ]
                        ]
                    ]
                ]
            ];

            $response = $this->_make_curl_request($url, $payload, 300, true);

            return $this->_process_tts_response($response, $agent_info->base_model);
        } catch (\Exception $e) {
            log_message('error', 'Gemini TTS Generation Error: ' . $e->getMessage());
            return false;
        }
    }

    private function _convert_l16_to_wav($l16Data, $sampleRate = 24000, $channels = 1, $bitsPerSample = 16) {
        // These are standard calculations for a PCM WAV header
        $dataLength = strlen($l16Data);
        $fmtChunkSize = 16;
        $totalLength = 44 + $dataLength;
        $byteRate = $sampleRate * $channels * $bitsPerSample / 8;
        $blockAlign = $channels * $bitsPerSample / 8;

        // Build the WAV header step by step
        $header = '';

        // RIFF chunk
        $header .= pack('V', 0x52494646); // "RIFF"
        $header .= pack('V', $totalLength - 8);    // File size
        $header .= pack('V', 0x57415645); // "WAVE"
        $header .= pack('V', 0x666d7420); // "fmt "

        // fmt chunk
        $header .= pack('V', $fmtChunkSize);
        $header .= pack('v', 1);            // Audio format (1 for PCM)
        $header .= pack('v', $channels);
        $header .= pack('V', $sampleRate);
        $header .= pack('V', $byteRate);
        $header .= pack('v', $blockAlign);
        $header .= pack('v', $bitsPerSample);

        // data chunk
        $header .= pack('V', 0x64617461); // "data"
        $header .= pack('V', $dataLength);

        // Combine the header and the raw audio data
        return $header . $l16Data;
    }

    private function _process_tts_response($response) {
        try {
            // Check if we have valid response data
            if (empty($response)) {
                log_message('error', 'Empty response received from TTS API');
                return false;
            }

            $audioData = null;

            // Handle different response formats based on the model or API version
            if (isset($response['audioContent'])) {

                // Direct audio content in response
                $audioData = $response['audioContent'];
            } elseif (isset($response['candidates'][0]['content']['parts'][0]['inlineData']['data'])) {

                // Gemini-style nested response
                $audioData = $response['candidates'][0]['content']['parts'][0]['inlineData']['data'];
            } else {
                log_message('error', 'Unexpected TTS API response format: ' . json_encode($response));
                return false;
            }

            if (empty($audioData)) {
                log_message('error', 'No audio data found in the API response');
                return false;
            }

            $file_name = 'gemini-tts-' . uniqid() . '.wav';
            $file_path = get_setting("temp_file_path") . '/' . $file_name;

            // Decode base64 if needed and save the file
            if (!file_put_contents($file_path, $this->_convert_l16_to_wav(base64_decode($audioData)))) {
                log_message('error', 'Failed to save TTS audio file to: ' . $file_path);
                return false;
            }

            // Generate HTML audio player with the file URL
            $html = sprintf(
                '<audio controls><source src="%s" type="audio/wav">Your browser does not support the audio element.</audio>',
                $file_path
            );

            echo $html;
        } catch (\Exception $e) {
            log_message('error', 'Error processing TTS response: ' . $e->getMessage());
            return false;
        }
    }
     */
}
