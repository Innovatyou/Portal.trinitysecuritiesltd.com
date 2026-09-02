<?php

namespace App\Libraries;

use App\Libraries\ChatGPT;
use App\Libraries\Gemini;
use App\Controllers\Security_Controller;
use App\Libraries\AI_actions;

class AI_assistant {
    private $AI_Client;
    private $AI_actions;
    private $AI_agents_model;
    private $AI_vector_data_model;
    private $AI_training_data_sources_model;
    private $AI_vector_data_status_model;

    public function __construct() {
        $this->AI_actions = new AI_actions();
        $this->AI_agents_model = model("App\Models\AI_agents_model");
        $this->AI_vector_data_model = model("App\Models\AI_vector_data_model");
        $this->AI_training_data_sources_model = model("App\Models\AI_training_data_sources_model");
        $this->AI_vector_data_status_model = model("App\Models\AI_vector_data_status_model");
    }

    function set_ai_service($ai_service) {
        if ($ai_service === 'gemini') {
            $this->AI_Client = new Gemini();
        } else if ($ai_service === 'chatgpt') {
            $this->AI_Client = new ChatGPT();
        }
    }

    private function _save_response_log($data) {
        if (!$data) {
            return false;
        }

        $ci = new Security_Controller();

        unset($data['response']); // don't save the response in the log
        $data['created_by'] = $ci->login_user->id;
        $data['created_at'] = get_current_utc_time();
        $data['action'] = serialize($data['action']);

        $AI_response_logs_model = model('App\Models\AI_response_logs_model');
        $AI_response_logs_model->ci_save($data);
    }

    public function get_models_dropdown($ai_service = 'chatgpt') {
        $this->set_ai_service($ai_service);
        return $this->AI_Client->get_models_dropdown();
    }

    public function get_ai_agent_response($messages, $agent_id, $get_direct_response = false) {

        if (!is_array($messages)) {
            $messages = [
                [
                    'role' => 'user',
                    'content' => $messages
                ]
            ];
        }

        $agent_info = $this->AI_agents_model->get_one($agent_id);
        $this->set_ai_service($agent_info->ai_service);

        $input_array = [
            'model' => $agent_info->base_model,
        ];

        if ($agent_info->max_output_tokens) {
            $input_array['max_tokens'] = (int) $agent_info->max_output_tokens;
        }

        if ($agent_info->temperature) {
            $input_array['temperature'] = (float) $agent_info->temperature;
        }

        if ($get_direct_response) {
            $input_array['messages'] = $messages;
            return $this->_process_stream_response($input_array, $agent_id);
        }





        // get conditional response
        // get embedding of all user queries in a single line
        $user_messages = array_values(array_filter($messages, function ($message) {
            return $message['role'] === 'user';
        }));

        $user_content = end($user_messages);
        $user_content = get_array_value($user_content, 'content');

        if (strlen($user_content) < 30 && count($user_messages) > 1) {
            // only add the previous message if the last message is very short
            $user_content = $user_messages[count($user_messages) - 2]['content']
                . "\n" . $user_content;
        }

        // if ($agent_info->model_type == "image") {
        //     return $this->AI_Client->get_image_response($user_content, $agent_id);
        // } else if ($agent_info->model_type == "tts") {
        //     return $this->AI_Client->get_tts_response($user_content, $agent_id);
        // } else if ($agent_info->model_type == "video") {
        //     return $this->AI_Client->get_video_response($user_content, $agent_id);
        // }

        // so it's a text based agent, process accordingly

        $query_embedding = $this->AI_Client->get_embedding($user_content, $agent_info->embedding_model);
        $kb_context = "";

        $vector_store_file_path = get_ai_files_path($agent_info->ai_service) . "vector_store_" . $agent_id . ".jsonl";
        if (file_exists($vector_store_file_path)) {

            $best_match = $this->search_similar_vectors_in_jsonl($vector_store_file_path, $query_embedding);
            if (!empty($best_match)) {

                $vector_data_info = $this->AI_vector_data_model->get_one($best_match['vector_id']);
                $kb_context = $vector_data_info->response;
            }
        }

        $app_action_rule = "";

        if ($agent_info->app_actions) {

            $allowed_contexts = $agent_info->app_actions;
            $app_action_rule = '
                --------------------------------------------------
                MODE 1: ACTION MODE
                --------------------------------------------------

                If the user expresses intent to perform an application action 
                (such as creating, updating, deleting, assigning, completing, closing, or similar operational intent),
                AND the action context is within the allowed contexts (' . $allowed_contexts . '),

                You MUST respond with ONLY this exact JSON structure:
                {
                "type": "action",
                "action_context": "<one of ' . $allowed_contexts . '>"
                }

                STRICT RULES:
                - Output ONLY valid JSON.
                - Do NOT add explanations.
                - Do NOT add any extra text.
                - Do NOT wrap the JSON in ```json or markdown.
                - Do NOT say anything before or after the JSON.

                If the user expresses action intent but the context is NOT within (' . $allowed_contexts . '),
                respond ONLY with:
                I am sorry, I cannot perform this action.

                ';
        }

        $kb_context_rule = "";
        if ($kb_context) {
            $kb_context_rule = '
            --------------------------------------------------
            MODE 2: KNOWLEDGE MODE
            --------------------------------------------------

            If the user asks a knowledge-based question:

            You MUST answer using ONLY the context below.
            Do NOT invent information.
            If the answer is not found in the context, respond ONLY with:
            I do not have enough information to answer that.

            Context:
            ' . $kb_context . '
            ';
        }

        $system_prompt = '
            You are a multi-agent assistant operating in strict mode.
            You MUST follow all rules exactly.

            ' . $app_action_rule . '

            ' . $kb_context_rule . '

            --------------------------------------------------
            MODE 3: SMALL TALK MODE
            --------------------------------------------------

            If the user input is greeting or small talk,
            respond naturally.

            --------------------------------------------------
            PRIORITY RULE
            --------------------------------------------------

            If the input contains both greeting and action intent,
            ACTION MODE takes priority.
            Do not add any text formatting.

            --------------------------------------------------
            ADDITIONAL RULE
            --------------------------------------------------

            Previous sections are mandatory system rules.
            Also follow the following prompt and you can use your own knowledge unless you get any restriction: 

            ' . $agent_info->system_prompt . '
            ';

        $final_messages = [['role' => 'system', 'content' => $system_prompt]];
        $final_messages = array_merge($final_messages, $messages);
        $input_array['messages'] = $final_messages;

        return $this->_process_stream_response($input_array, $agent_id);
    }

    private function _process_stream_response($input_array, $agent_id = 0) {
        $full_response = '';
        $json_buffer = '';
        $tool_call_buffer = [];
        $in_json = false;
        $response_id = null;

        try {
            $this->AI_Client->stream_response($input_array, function ($chunk) use (&$full_response, &$json_buffer, &$in_json, &$tool_call_buffer, &$response_id) {

                // if the response is text, show directly to the browser
                // if the response is json, store it in a buffer
                // after completing the json buffer, send another request with the related tool calls function 
                // with that response, store in another buffer (chatgpt 4 or older version sends chunks here but on 5 there has another structure)
                // with or without chunk of tool call, run the related function in the system 
                // then show the final response to the browser

                $response_chunk = get_array_value($chunk, "response_chunk");
                $tool_calls_chunk = get_array_value($chunk, "tool_calls_chunk");
                $tool_calls = get_array_value($chunk, "tool_calls");
                $response_id = get_array_value($chunk, "response_id");

                // store tool_calls chunk response
                if ($tool_calls_chunk && is_array($tool_calls_chunk)) {

                    foreach ($tool_calls_chunk as $tool_call) {
                        $id = get_array_value($tool_call, 'id');
                        $name = get_array_value($tool_call, 'name');
                        $arguments = get_array_value($tool_call, 'arguments');
                        $target_id = $id;

                        // If the chunk has no ID, assume it's a continuation of the last one.
                        if ($target_id === null) {
                            $last_keys = array_keys($tool_call_buffer);
                            $target_id = end($last_keys);
                            if ($target_id === null) {
                                // This handles the very first chunk being a tool call without an ID.
                                $target_id = 'temp_' . uniqid();
                            }
                        }

                        // Initialize the buffer entry if it doesn't exist yet.
                        if (!isset($tool_call_buffer[$target_id])) {
                            $tool_call_buffer[$target_id] = [
                                'id' => $id ?? $target_id, // Use the original ID if available, otherwise the temp one.
                                'name' => null,
                                'arguments' => ''
                            ];
                        }

                        // Conditionally set the name, preserving the first one received.
                        if ($name !== null) {
                            $tool_call_buffer[$target_id]['name'] = $name;
                        }

                        // Append the arguments.
                        $tool_call_buffer[$target_id]['arguments'] .= $arguments;
                    }
                }

                // finalized tool calls response, not chunk
                if ($tool_calls) {
                    $tool_call_buffer[] = $tool_calls;
                }

                // json response
                if (($response_chunk
                        && (str_contains($response_chunk, '{') || str_contains($response_chunk, '```json') || str_contains($response_chunk, '```')))
                    || $in_json
                ) {
                    $json_buffer .= $response_chunk;
                    $in_json = true;
                }

                // normal text response
                if ($response_chunk !== '' && !$json_buffer) {
                    send_stream_message_to_the_browser("response: " . json_encode(['content' => $response_chunk]) . "\n\n");
                }

                $full_response .= $response_chunk;
            });
        } catch (\Exception $e) {
            log_message('error', 'Stream response error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }

        if ($json_buffer) {

            // got a action json, run another request to process it
            try {

                // Check if JSON is wrapped in markdown code blocks
                $trimmed_json = trim($json_buffer);
                if (preg_match('/^```json\s*(.*?)\s*```$/s', $trimmed_json, $matches)) {
                    $trimmed_json = trim($matches[1]);
                }

                $json = json_decode($trimmed_json, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($json['type'], $json['action_context'])) {
                    return $this->_handle_action_response($json, $input_array, $agent_id);
                }
            } catch (\Exception $e) {
                log_message('error', 'Error processing JSON action: ' . $e->getMessage());
            }
        }

        if ($tool_call_buffer) {

            // got the tool call response
            try {
                return $this->_process_tool_call_action($tool_call_buffer, $input_array, $agent_id);
            } catch (\Exception $e) {
                log_message('error', 'Error processing tool call action: ' . $e->getMessage());
            }
        }

        //save response log
        if (!empty($full_response)) {

            $this->_save_response_log([
                'response' => $full_response,
                'action' => $input_array
            ]);

            return array(
                'response' => $full_response,
                'response_id' => $response_id
            );
        }
    }

    // send request with the tool call functions
    private function _handle_action_response($action_data, $input_array, $agent_id) {

        // check if there is any context
        $context = get_array_value($action_data, 'action_context');
        if (!$context) {
            log_message('error', 'AI message invalid action');
            return ['error' => app_lang('ai_message_invalid_action')];
        }

        send_stream_message_to_the_browser("processing_tool_call");

        try {

            $app_action_context_info = $this->AI_actions->get_app_action_context_info($context);
            $tool_functions = get_array_value($app_action_context_info, 'tool_functions');
            if ($tool_functions) {
                $input_array['tools'] = $tool_functions;
            }

            $system_prompt = get_array_value($app_action_context_info, 'system_prompt');
            $input_array = $this->_update_system_message($input_array, $system_prompt);

            // Continue with the new messages
            return $this->_process_stream_response($input_array, $agent_id);
        } catch (\Exception $e) {
            log_message('error', 'Error processing action: ' . $e->getMessage());
            return ['error' => 'Error processing action: ' . $e->getMessage()];
        }
    }

    // process with tool call response in local system
    private function _process_tool_call_action($tool_call_buffer, $input_array, $agent_id) {

        $input_array['messages'] = array(); //unset previous messages
        unset($input_array['tools']); //unset previous tools

        foreach ($tool_call_buffer as $tool_call) {
            try {
                $args = json_decode($tool_call['arguments'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // log_message('debug', 'Final $tool_call: ' . json_encode($tool_call));
                    // log_message('debug', 'Final $args: ' . json_encode($args));

                    // Call the corresponding method in AI_actions
                    $method_name = $tool_call['name'];
                    if (method_exists($this->AI_actions, $method_name)) {
                        $result = $this->AI_actions->$method_name($args);

                        // Update system message for a humanize response
                        $system_prompt = 'You are a helpful assistant. 
                        Always respond with a concise, humanized summary of the tool results, even if simple. 
                        Prepare the response based on the tool call function arguments and tool call result.
                        Here is the tool call info: ' . json_encode($tool_call) . '. 
                        Here is the tool call result: ' . (is_string($result) ? $result : json_encode($result)) . '.';

                        $input_array = $this->_update_system_message($input_array, $system_prompt);

                        $input_array['messages'][] = [
                            'role' => 'user',
                            'content' => 'Please continue the conversation with the tool response.'
                        ];

                        // log_message('debug', 'Updated $input_array: ' . json_encode($input_array));

                        // Continue the conversation with the tool response
                        return $this->_process_stream_response($input_array, $agent_id);
                    } else {
                        log_message('error', 'Tool method not found: ' . $method_name);
                        return ['error' => 'Tool method not found'];
                    }
                } else {
                    log_message('error', 'Invalid JSON in tool arguments: ' . $tool_call['function']['arguments']);
                    return ['error' => 'Invalid JSON in tool arguments'];
                }
            } catch (\Exception $e) {
                log_message('error', 'Error handling tool call: ' . $e->getMessage());
                return ['error' => 'Error handling tool call: ' . $e->getMessage()];
            }
        }
    }

    function cosine_similarity(array $vec1, array $vec2): float {
        if (count($vec1) !== count($vec2)) {
            log_message('error', 'Vectors must be the same length');
            return 0.0;
        }

        $dot_product = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < count($vec1); $i++) {
            $dot_product += $vec1[$i] * $vec2[$i];
            $normA += $vec1[$i] ** 2;
            $normB += $vec2[$i] ** 2;
        }
        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }
        return $dot_product / (sqrt($normA) * sqrt($normB));
    }

    function search_similar_vectors_in_jsonl(string $jsonl_path, array $query_vec): array {
        $handle = fopen($jsonl_path, 'r');
        if (!$handle) {
            log_message('error', 'Cannot open file: ' . $jsonl_path);
            return [];
        }

        $best_match = [
            'vector_id' => null,
            'score' => -1,
            'context' => null
        ];

        while (($line = fgets($handle)) !== false) {
            $data = json_decode($line, true);

            if (!isset($data['embedding']) || !isset($data['vector_id'])) {
                continue; // Skip invalid lines
            }

            $score = $this->cosine_similarity($query_vec, $data['embedding']);

            // Track the best match
            if ($score > $best_match['score']) {
                $best_match = [
                    'vector_id' => $data['vector_id'] ?? null,
                    'score' => $score,
                    'context' => $data['context'] ?? null
                ];
            }
        }

        fclose($handle);

        // Return the best match if we have either a vector_id or a context
        return ($best_match['vector_id'] !== null || $best_match['context'] !== null) ? $best_match : [];
    }

    private function _update_system_message($input_array, $system_prompt) {

        // Remove any existing system messages first
        $input_array['messages'] = array_filter($input_array['messages'], function ($message) {
            return ($message['role'] ?? '') !== 'system';
        });

        // Add the new system message at the beginning of the messages array
        array_unshift($input_array['messages'], [
            'role' => 'system',
            'content' => $system_prompt
        ]);

        return $input_array;
    }

    private function _store_embedding_data_to_file($vector_id, $embedding, $file_path) {
        $embedding_data = array(
            "vector_id" => $vector_id,
            "embedding" => $embedding
        );

        file_put_contents($file_path, json_encode($embedding_data) . "\n", FILE_APPEND);
    }

    public function store_embedding_data($data, $agent_id) {
        $agent_info = $this->AI_agents_model->get_one($agent_id);
        $this->set_ai_service($agent_info->ai_service);

        $embedding = $this->AI_Client->get_embedding($data->prompt . "\n" . $data->response, $agent_info->embedding_model); // Generate embedding for the prompt and response
        if (!$embedding) {
            log_message('error', 'Failed to generate embedding for vector id: ' . $data->id);
            return false;
        }

        // store the embedding to the file
        $vector_store_file_name = "vector_store_" . $agent_id . ".jsonl";
        if ($data->context && $data->context != "kb_article") {
            $vector_store_file_name = "context_vector_store_" . $agent_id . ".jsonl";
        }

        $target_path = get_ai_files_path($agent_info->ai_service);
        if (!is_dir($target_path)) {
            if (!mkdir($target_path, 0755, true)) {
                die('Failed to create file folders.');
            }
        }

        $vector_store_file_name = $target_path . $vector_store_file_name;

        $this->_store_embedding_data_to_file($data->id, $embedding, $vector_store_file_name);

        // save status
        $ci = new Security_Controller();
        $vector_status_data = array(
            "vector_data_id" => $data->id,
            "agent_id" => $agent_id,
            "created_at" => get_current_utc_time(),
            "created_by" => $ci->login_user->id,
            "status" => "completed"
        );

        return $this->AI_vector_data_status_model->ci_save($vector_status_data);
    }

    public function save_training_data_from_file($file_id, $agent_info, $created_by) {

        $file_info = $this->AI_training_data_sources_model->get_one($file_id);

        $file_path = get_ai_files_path($agent_info->ai_service);
        $file_url = get_source_url_of_file(make_array_of_file($file_info), $file_path);

        $file_content = "";
        $file_ext = strtolower(pathinfo($file_info->file_name, PATHINFO_EXTENSION));
        if ($file_ext == "txt") {
            $file_content = file_get_contents($file_url);
        }

        $vector_data = array(
            "source_id" => $file_id,
            "prompt" => $file_info->title,
            "response" => $file_content,
            "created_at" => get_current_utc_time(),
            "created_by" => $created_by
        );

        $this->AI_vector_data_model->ci_save($vector_data);
    }

    /**
    public function create_fine_tuning_model($fine_tuning_info) {
        try {

            $job_id = $this->AI_Client->create_fine_tuning_model($this->_get_training_data_file_path($fine_tuning_info->training_file), $fine_tuning_info->base_model);
            if (!$job_id) {
                return false;
            }

            $fine_tuning_data = array(
                "job_id" => $job_id,
                "status" => "processing"
            );

            $this->AI_agents_model->ci_save($fine_tuning_data, $fine_tuning_info->id);

            echo json_encode(array("success" => true));
            return true;
        } catch (\Exception $e) {

            log_message('error', 'AI Assistant Error: ' . $e->getMessage());
            echo json_encode(array("success" => false, "message" => $e->getMessage()));
            return false;
        }
    }

    public function get_fine_tuning_base_models_dropdown() {
        return $this->AI_Client->get_fine_tuning_base_models_dropdown();
    }

    public function check_fine_tuning_status($fine_tuning_info) {
        try {
            if (!$fine_tuning_info->job_id) {
                return false;
            }

            $job_info = $this->AI_Client->get_fine_tuning_info($fine_tuning_info->job_id);
            if (!$job_info) {
                return false;
            }

            if (get_array_value($job_info, 'status') == 'succeeded' && get_array_value($job_info, 'model')) {

                $fine_tuning_data = array(
                    "status" => "completed"
                );

                $this->AI_agents_model->ci_save($fine_tuning_data, $fine_tuning_info->id);

                // save fine tunned_models
                $fine_tunned_models = get_setting('fine_tunned_models');
                $fine_tunned_models = $fine_tunned_models ? unserialize($fine_tunned_models) : array();
                $fine_tunned_models[] = get_array_value($job_info, 'model');

                $Settings_model = model("App\Models\Settings_model");
                $Settings_model->save_setting('fine_tunned_models', serialize($fine_tunned_models));
            } else if (get_array_value($job_info, 'status') == 'failed') {

                $fine_tuning_data = array(
                    "status" => "failed"
                );

                $this->AI_agents_model->ci_save($fine_tuning_data, $fine_tuning_info->id);
            }

            return true;
        } catch (\Exception $e) {

            log_message('error', 'AI Assistant Error: ' . $e->getMessage());
            return false;
        }
    }

    public function delete_fine_tuning_model($job_id) {
        if (!$job_id) {
            return false;
        }

        try {
            $this->AI_Client->delete_fine_tuning_model($job_id);
        } catch (\Exception $e) {
            log_message('error', 'AI Assistant Error: ' . $e->getMessage());
            return false;
        }
    }
     */
}
