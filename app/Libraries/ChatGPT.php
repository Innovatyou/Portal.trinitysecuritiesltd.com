<?php

namespace App\Libraries;

use OpenAI;

class ChatGPT {

    private $chatgpt_api_key;

    public function __construct() {
        require_once(APPPATH . "ThirdParty/OpenAI/autoload.php");

        $this->chatgpt_api_key = decode_id(get_setting('chatgpt_api_key'), "chatgpt_api_key");

        if (!get_setting('enable_chatgpt') || !$this->chatgpt_api_key || !get_setting('chatgpt_authorized')) {
            return false;
        }
    }

    private function _get_chatgpt_client() {
        return OpenAI::client($this->chatgpt_api_key);
    }

    public function get_embedding($text, $embedding_model = "text-embedding-3-small") {
        try {
            $client = $this->_get_chatgpt_client();
            $response = $client->embeddings()->create([
                'model' => $embedding_model,
                'input' => $text,
            ]);

            return $response['data'][0]['embedding'];
        } catch (\Exception $e) {
            log_message('error', 'ChatGPT API Embedding Error: ' . $e->getMessage());
            return false;
        }
    }


    public function get_response($input_array) {
        try {
            $client = $this->_get_chatgpt_client();

            if ($this->_is_gpt_5_model(get_array_value($input_array, 'model'))) {

                $input_array = $this->_process_input_data_for_gpt_5_model($input_array);
                $response = $client->responses()->create($input_array);
            } else {

                $response = $client->chat()->create($input_array);
            }

            return $this->_prepare_response($response);
        } catch (\Exception $e) {
            log_message('error', 'ChatGPT Response Error: ' . $e->getMessage());
            return false;
        }
    }

    private function _is_gpt_5_model($model) {
        return strpos($model, 'gpt-5') !== false;
    }

    private function _process_input_data_for_gpt_5_model($input_array) {
        $model = get_array_value($input_array, 'model');
        $is_gpt_5 = $this->_is_gpt_5_model($model);

        if ($is_gpt_5) {

            // Process for GPT-5 model

            $input = [];
            $messages = get_array_value($input_array, 'messages');

            // get only system and user prompts

            foreach ($messages as $message) {

                $role = get_array_value($message, 'role');
                $response_id = get_array_value($message, 'response_id');

                if ($role == "system" || $role == "user") {
                    $input[] = [
                        'role' => $role,
                        'content' => [
                            [
                                'type' => $response_id ? 'output_text' : 'input_text',
                                'text' => get_array_value($message, 'content')
                            ]
                        ]
                    ];
                }

                if ($response_id) {
                    $input_array['previous_response_id'] = $response_id;
                }
            }

            unset($input_array['messages']);
            $input_array['input'] = $input;

            if (isset($input_array['max_tokens'])) {
                $input_array['max_output_tokens'] = $input_array['max_tokens'];
                unset($input_array['max_tokens']);
            }

            $tools = [];
            if (isset($input_array['tools'])) {
                foreach ($input_array['tools'] as $tool) {

                    $_tool = array_merge(
                        ["type" => $tool["type"]],
                        $tool["function"]
                    );
                    $tools[] = $_tool;
                }

                $input_array['tools'] = $tools;
            }

            if (strpos($model, 'gpt-5-') !== false) {
                // specifically for gpt 5 versions
                unset($input_array['temperature']);
            }
        }

        return $input_array;
    }

    public function stream_response($input_array, $callback) {
        try {
            $client = $this->_get_chatgpt_client();

            if ($this->_is_gpt_5_model(get_array_value($input_array, 'model'))) {

                $input_array = $this->_process_input_data_for_gpt_5_model($input_array);
               // log_message("error", "ChatGPT 5 input array: " . json_encode($input_array));

                $stream = $client->responses()->createStreamed($input_array);

                foreach ($stream as $event) {

                    //log_message("error", "ChatGPT 5 event: " . json_encode($event));

                    if ($event->event === 'response.output_text.delta') {
                        $callback(['response_chunk' => $event->response->delta,]);
                    }

                    if ($event->event === 'response.completed') {
                        $callback(['response_id' => $event->response->response->id ?? '']);
                    }

                    if ($event->event === 'response.output_item.done' && isset($event->response->item->type) && $event->response->item->type === 'function_call') {
                        $callback([
                            'tool_calls' => [
                                'id' => $event->response->item->id ?? '',
                                'name' => $event->response->item->name ?? '',
                                'arguments' => $event->response->item->arguments ?? '',
                            ]
                        ]);
                    }

                    if (connection_aborted()) {
                        break;
                    }
                }
            } else {

                // GPT 4 or older versions
                // Enable streaming in the request
                $input_array['stream'] = true;

                $stream = $client->chat()->createStreamed($input_array);

                foreach ($stream as $response) {

                    $delta = $response->choices[0]->delta->toArray();

                    $tool_calls_chunk = array();
                    $tool_calls = $delta['tool_calls'] ?? null;

                    if ($tool_calls && is_array($tool_calls)) {
                        foreach ($tool_calls as $tool_call) {

                            $tool_calls_chunk[] = [
                                'id' => $tool_call['id'] ?? null,
                                'name' => $tool_call['function']['name'] ?? null,
                                'arguments' => $tool_call['function']['arguments'] ?? ''
                            ];
                        }
                    }

                    $callback([
                        'response_chunk' => $delta['content'] ?? null,
                        'tool_calls_chunk' => $tool_calls_chunk,
                        'response_id' => $response->id ?? ''
                    ]);

                    if (connection_aborted()) {
                        break;
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            log_message('error', 'ChatGPT Stream Error: ' . $e->getMessage());
            throw $e; // Re-throw to let caller handle the error
        }
    }

    private function _prepare_response($response) {
        $final_response = array(
            "response_id" => $response->id,
            "created_at" => date("Y-m-d H:i:s", $response->created),
            "model" => $response->model,
            "response" => $response->choices[0]->message->content,
            "input_token" => $response->usage->promptTokens,
            "output_token" => $response->usage->completionTokens,
            "action" => array()
        );

        $tool_calls = $response->choices[0]->message->toolCalls;

        if (!empty($tool_calls)) {
            // Tool call detected
            $tool_call = $tool_calls[0];
            $arguments = json_decode($tool_call->function->arguments, true);

            $final_response['action'] = array(
                "function_name" => $tool_call->function->name,
                "function_arguments" => $arguments
            );
        }

        return $final_response;
    }

    public function get_models_dropdown() {

        $models = array(

            // GPT-5 versions
            array(
                "type" => "text",
                "id"   => "gpt-5.4-2026-03-05",
                "name" => "GPT-5.4 (2026-03-05)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-5.4-pro-2026-03-05",
                "name" => "GPT-5.4 Pro (2026-03-05)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-5.2-2025-12-11",
                "name" => "GPT-5.2 (2025-12-11)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-5.2-pro-2025-12-11",
                "name" => "GPT-5.2 Pro (2025-12-11)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-5.1-2025-11-13",
                "name" => "GPT-5.1 (2025-11-13)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-5-2025-08-07",
                "name" => "GPT-5 (2025-08-07)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-5-pro-2025-10-06",
                "name" => "GPT-5 Pro (2025-10-06)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-5-mini-2025-08-07",
                "name" => "GPT-5 Mini (2025-08-07)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-5-nano-2025-08-07",
                "name" => "GPT-5 Nano (2025-08-07)"
            ),

            // GPT-4.1 versions
            array(
                "type" => "text",
                "id"   => "gpt-4.1-2025-04-14",
                "name" => "GPT-4.1 (2025-04-14)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-4.1-mini-2025-04-14",
                "name" => "GPT-4.1 Mini (2025-04-14)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-4.1-nano-2025-04-14",
                "name" => "GPT-4.1 Nano (2025-04-14)"
            ),

            // GPT-4o versions
            array(
                "type" => "text",
                "id"   => "gpt-4o-2024-08-06",
                "name" => "GPT-4o (2024-08-06)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-4o-mini-2024-07-18",
                "name" => "GPT-4o Mini (2024-07-18)"
            ),

            // Older GPT-4 version
            array(
                "type" => "text",
                "id"   => "gpt-4-0613",
                "name" => "GPT-4 (0613)"
            ),

            // GPT-3.5 Turbo versions
            array(
                "type" => "text",
                "id"   => "gpt-3.5-turbo-0125",
                "name" => "GPT-3.5 Turbo (0125)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-3.5-turbo-1106",
                "name" => "GPT-3.5 Turbo (1106)"
            ),
            array(
                "type" => "text",
                "id"   => "gpt-3.5-turbo-instruct",
                "name" => "GPT-3.5 Turbo Instruct"
            ),

            // // Image models
            // // GPT Image 1
            // array(
            //     "type" => "image",
            //     "id" => "gpt-image-1",
            //     "name" => "GPT Image 1",
            //     "default_params" => array(
            //         "size" => "1024x1024",
            //         "n" => 1
            //     ),
            //     "allowed_params" => array(
            //         "size" => array("1024x1024", "1024x1536", "1536x1024"),
            //         "n" => array(1, 2)
            //     ),
            // ),
            // // GPT Image 0721 Mini Alpha
            // array(
            //     "type" => "image",
            //     "id" => "gpt-image-0721-mini-alpha",
            //     "name" => "GPT Image 0721 Mini Alpha",
            //     "default_params" => array(
            //         "size" => "1024x1024",
            //         "n" => 1
            //     ),
            //     "allowed_params" => array(
            //         "size" => array("1024x1024", "1024x1536", "1536x1024"),
            //         "n" => array(1, 2)
            //     ),
            // ),
            // // DALL·E 2
            // array(
            //     "type" => "image",
            //     "id" => "dall-e-2",
            //     "name" => "DALL·E 2",
            //     "default_params" => array(
            //         "size" => "512x512",
            //         "n" => 1
            //     ),
            //     "allowed_params" => array(
            //         "size" => array("256x256", "512x512", "1024x1024"),
            //         "n" => array(1, 2, 3)
            //     ),
            // ),
            // // DALL·E 3
            // array(
            //     "type" => "image",
            //     "id" => "dall-e-3",
            //     "name" => "DALL·E 3",
            //     "default_params" => array(
            //         "size" => "1024x1024",
            //         "n" => 1
            //     ),
            //     "allowed_params" => array(
            //         "size" => array("1024x1024", "1024x1792", "1792x1024"),
            //         "n" => array(1, 2)
            //     ),
            // ),

            // // TTS Models
            // array(
            //     "type" => "tts",
            //     "id"   => "tts-1",
            //     "name" => "TTS-1 (Standard)"
            // ),
            // array(
            //     "type" => "tts",
            //     "id"   => "tts-1-hd",
            //     "name" => "TTS-1-HD (High Quality)"
            // ),
            // array(
            //     "type" => "tts",
            //     "id"   => "tts-1-1106",
            //     "name" => "TTS-1 (Latest)"
            // ),
            // array(
            //     "type" => "tts",
            //     "id"   => "tts-1-hd-1106",
            //     "name" => "TTS-1-HD (Latest HD)"
            // ),

            // // Video models
            // array(
            //     "type" => "video",
            //     "id"   => "sora",
            //     "name" => "Sora"
            // ),
        );

        // get fine-tuned models
        // $fine_tuned_models = get_setting("fine_tunned_models");
        // if ($fine_tuned_models) {
        //     $fine_tuned_models = unserialize($fine_tuned_models);
        //     foreach ($fine_tuned_models as $model) {
        //         $models[$model] = $model;
        //     }
        // }

        return $models;
    }

    /** 
    public function get_video_response($prompt, $agent_id, $quality = 'standard', $aspect_ratio = '16:9', $duration_seconds = 10) {
        try {


            $cmd = "curl -s https://api.openai.com/v1/models -H 'Authorization: Bearer {$this->chatgpt_api_key}'";
            $output = shell_exec($cmd);

            $data = json_decode($output, true);

            if (isset($data['data'])) {
                $models = array_column($data['data'], 'id');
                if (in_array('sora', $models)) {
                    echo "✅ Sora is available on your account\n";
                } else {
                    echo "❌ Sora is NOT available on your account\n";
                }
            }

            exit;

            $AI_agents_model = model("App\Models\AI_agents_model");
            $agent_info = $AI_agents_model->get_one($agent_id);

            // Validate duration (3–60s)
            $duration_seconds = max(3, min(60, (int)$duration_seconds));

            $post_fields = [
                "model" => $agent_info->base_model,
                "prompt" => $prompt,
                "quality" => $quality,
                "aspect_ratio" => $aspect_ratio,
                "duration" => $duration_seconds
            ];

            $ch = curl_init("https://api.openai.com/v1/video/generations");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer {$this->chatgpt_api_key}",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new \Exception("cURL error: " . curl_error($ch));
            }
            curl_close($ch);

            $data = json_decode($response, true);

            echo "<pre>";
            print_r($response);
            exit;

            if (isset($data['data'][0]['url'])) {
                $video_url = $data['data'][0]['url'];

                // Download the video
                $video_data = file_get_contents($video_url);
                if ($video_data === false) {
                    throw new \Exception("Failed to download video from $video_url");
                }

                // Save it locally
                $file_name = "video_" . uniqid() . ".mp4";
                $file_path = get_setting("temp_file_path") . $file_name;
                file_put_contents($file_path, $video_data);

                return [
                    "success" => true,
                    "file_path" => $file_path,
                    "file_name" => $file_name,
                    "video_url" => $video_url,
                    "prompt" => $prompt
                ];
            }

            return ["success" => false, "error" => $data];
        } catch (\Exception $e) {
            log_message("error", "Error in get_video_response: " . $e->getMessage());
            return ["success" => false, "error" => $e->getMessage()];
        }
    }
    
    public function get_tts_response($text, $agent_id, $voice = 'alloy', $response_format = 'mp3', $speed = 1.0) {
        try {
            $AI_agents_model = model("App\Models\AI_agents_model");
            $agent_info = $AI_agents_model->get_one($agent_id);

            // Validate agent info and base model
            if (!is_object($agent_info) || !isset($agent_info->base_model) || empty($agent_info->base_model)) {
                log_message('error', 'Invalid or missing agent configuration for agent_id: ' . $agent_id);
                return false;
            }

            $client = $this->_get_chatgpt_client();

            // Validate parameters
            $valid_voices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
            $valid_formats = ['mp3', 'opus', 'aac', 'flac'];

            if (!in_array($voice, $valid_voices)) {
                $voice = 'alloy'; // Default to alloy if invalid voice provided
            }

            if (!in_array($response_format, $valid_formats)) {
                $response_format = 'mp3'; // Default to mp3 if invalid format provided
            }

            // Ensure speed is within valid range (0.25 to 4.0)
            $speed = max(0.25, min(4.0, (float)$speed));

            // Make the API call
            $response = $client->audio()->speech([
                'model' => $agent_info->base_model,
                'input' => $text,
                'voice' => $voice,
                'response_format' => $response_format,
                'speed' => $speed
            ]);

            // Save the binary audio to a file
            $file_path = get_setting("temp_file_path") . "/chatgpt-tts.$response_format";
            file_put_contents($file_path, $response);
        } catch (\Exception $e) {
            log_message('error', 'TTS Error: ' . $e->getMessage());
            return false;
        }
    }

    function get_image_response($prompt, $agent_id) {
        try {
            $AI_agents_model = model("App\Models\AI_agents_model");
            $agent_info = $AI_agents_model->get_one($agent_id);
            $parameters = $agent_info->parameters;
            $parameters = json_decode($parameters, true);

            $client = $this->_get_chatgpt_client();
            $response = $client->Images()->create([
                "model" => $agent_info->base_model,
                "prompt" => $prompt,
                "size" => get_array_value($parameters, 'size'),
                "n" => get_array_value($parameters, 'n'),
                "response_format" => "b64_json"
            ]);

            echo "<img src='data:image/png;base64," . $response->data[0]->b64_json . "' alt='Generated AI Image'>";
            exit;
        } catch (\Exception $e) {
            log_message('error', 'ChatGPT Image Generation Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function get_fine_tuning_base_models_dropdown() {
        $models = array(
            // GPT-4.1 versions
            'gpt-4.1-nano-2025-04-14'  => 'GPT-4.1 Nano (2025-04-14)',
            'gpt-4.1-mini-2025-04-14'  => 'GPT-4.1 Mini (2025-04-14)',
            'gpt-4.1-2025-04-14'       => 'GPT-4.1 (2025-04-14)',

            // GPT-4o versions
            'gpt-4o-mini-2024-07-18'   => 'GPT-4o Mini (2024-07-18)',
            'gpt-4o-2024-08-06'        => 'GPT-4o (2024-08-06)',

            // GPT-3.5 Turbo versions 
            'gpt-3.5-turbo-1106'       => 'GPT-3.5 Turbo (1106)',
            'gpt-3.5-turbo-0125'       => 'GPT-3.5 Turbo (0125)',
        );

        $models = app_hooks()->apply_filters('app_filter_chatgpt_fine_tuning_base_models', $models);

        return $models;
    }
    
    public function create_fine_tuning_model($dataset_file_path, $base_model) {
        try {
            ini_set('max_execution_time', 300); //300 seconds 

            $client = $this->_get_chatgpt_client();
            $file_info = $client->files()->upload([
                'purpose' => 'fine-tune',
                'file'    => fopen($dataset_file_path, 'r'),
            ]);

            // Start a fine-tuning job using the selected fine-tuning base model
            $response = $client->fineTuning()->createJob([
                'training_file' => $file_info->id,
                'model'         => $base_model,
                'suffix'        => 'rise-' . date('Y-m-d'),
            ]);

            return $response->id;
        } catch (\Exception $e) {
            log_message('error', 'ChatGPT API Error: ' . $e->getMessage());
            return false;
        }
    }

    public function format_training_data($data) {
        $formatted_data = array(
            "messages" => array(
                array(
                    "role" => "user",
                    "content" => get_array_value($data, 'prompt')
                ),
                array(
                    "role" => "assistant",
                    "content" => get_array_value($data, 'response')
                )
            )
        );

        if (get_array_value($data, 'system_prompt')) {
            // add system prompt at the start of the messages
            array_unshift($formatted_data['messages'], array(
                "role" => "system",
                "content" => get_array_value($data, 'system_prompt')
            ));
        }

        return json_encode($formatted_data, JSON_UNESCAPED_UNICODE);
    }

    public function get_fine_tuning_info($job_id) {
        try {
            $client = $this->_get_chatgpt_client();
            $job = $client->fineTuning()->retrieveJob($job_id);

            return [
                'status'      => $job->status,
                'model'       => $job->fineTunedModel ?? null,
                'training_file' => $job->trainingFile ?? null
            ];
        } catch (\Exception $e) {
            log_message('error', 'ChatGPT API Error: ' . $e->getMessage());
            return false;
        }
    }

    public function delete_fine_tuning_model($job_id) {
        try {
            $client = $this->_get_chatgpt_client();
            $job_info = $this->get_fine_tuning_info($job_id);
            if (!$job_info) {
                return false;
            }

            // Cancel the fine-tuning job if running
            $client->fineTuning()->cancelJob($job_id);

            $model = get_array_value($job_info, 'model');
            if ($model) {
                $client->models()->delete($model);
            }

            $training_file = get_array_value($job_info, 'training_file');
            if ($training_file) {
                $client->files()->delete($training_file);
            }
        } catch (\Exception $e) {
            log_message('error', 'ChatGPT API Error: ' . $e->getMessage());
            return false;
        }
    }

    public function get_models_by_feature($type = null) {
        try {
            $client = $this->_get_chatgpt_client();
            $models = $client->models()->list();

            $grouped = [
                'text' => [],
                'audio' => [],
                'chat' => [],
                'media' => [],
                'embedding' => [],
                'vision' => [],
                'moderation' => [],
            ];

            // If a specific type is requested and it's not in our known types, return empty
            if ($type !== null && !array_key_exists($type, $grouped)) {
                return [];
            }

            foreach ($models->data as $model) {
                $id = $model->id;

                // Skip if the model ID is empty
                if (empty($id)) continue;

                // Normalize label
                $label = $this->prettify_model_label($id);

                // Categorize models
                if (preg_match('/^(gpt|ft:gpt)/', $id)) {
                    // Text generation models
                    $grouped['text'][$id] = $label;
                    $grouped['chat'][$id] = $label; // Also add to chat as they are often used for chat

                    // Vision models (e.g., gpt-4-vision-preview)
                    if (strpos($id, 'vision') !== false) {
                        $grouped['vision'][$id] = $label;
                    }
                }
                
                // Audio models
                elseif (strpos($id, 'whisper') !== false || strpos($id, 'tts') !== false) {
                    $grouped['audio'][$id] = $label;
                }
                // Embedding models
                elseif (strpos($id, 'embedding') !== false || strpos($id, 'similarity') !== false) {
                    $grouped['embedding'][$id] = $label;
                }
                // Moderation models
                elseif (strpos($id, 'moderation') !== false) {
                    $grouped['moderation'][$id] = $label;
                }
                // Media/Image models
                elseif (strpos($id, 'dall-e') !== false || strpos($id, 'image') !== false) {
                    $grouped['media'][$id] = $label;
                }

                // Image/Video tasks (DALL-E, Vision)
                if (str_starts_with($id, 'dall-e') || $id === 'gpt-4o') {
                    $grouped['media'][$id] = $label;
                }
            }

            // If a specific type was requested, return only that type's models
            if ($type !== null) {
                return $grouped[$type] ?? [];
            }

            return $grouped;
        } catch (\Exception $e) {
            log_message('error', 'ChatGPT Model Feature Map Error: ' . $e->getMessage());
            return [];
        }
    }

    private function prettify_model_label($id) {
        // Handle fine-tuned models
        if (str_starts_with($id, 'ft:')) {
            // Example: ft:gpt-3.5-turbo:org:custom-writer:timestamp
            $parts = explode(':', $id);
            $name = $parts[3] ?? $id; // custom name
            $cleaned = str_replace('-', ' ', $name);
            return 'Fine-tuned: ' . ucwords($cleaned);
        }

        // For base models like gpt-4.1-nano, o3-pro, etc.
        $cleaned = str_replace(['-', '_'], ' ', $id);
        $words = explode(' ', $cleaned);

        // Capitalize each word, but force "GPT" to be uppercase
        $formatted = array_map(function ($word) {
            return strtolower($word) === 'gpt' ? 'GPT' : ucfirst($word);
        }, $words);

        return implode(' ', $formatted);
    }
     */
}
