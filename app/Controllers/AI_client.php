<?php

namespace App\Controllers;

use App\Controllers\Security_Controller;
use App\Libraries\AI_assistant;

class AI_client extends Security_Controller {

    public $AI_assistant;
    public $AI_agents_model;
    public $AI_chats_model;
    public $AI_chat_messages_model;

    function __construct() {
        parent::__construct();
        $this->AI_assistant = new AI_assistant();
        $this->AI_agents_model = model("App\Models\AI_agents_model");
        $this->AI_chats_model = model("App\Models\AI_chats_model");
        $this->AI_chat_messages_model = model("App\Models\AI_chat_messages_model");
    }

    function process_quick_action() {

        // Check if user has permission to access quick assistant
        if (!$this->permission_manager->can_access_quick_assistant()) {
            app_redirect("forbidden");
        }
        $agent_id = $this->request->getPost("agent_id");
        $action = $this->request->getPost("action");
        $messages = $this->request->getPost("messages");
        $messages = @json_decode($messages, true);

        if (is_array($messages)) {
            foreach ($messages as $key => $message) {

                if (is_array($message) && ($action == "summarize" || $action == "improve" ||  $action == "improve_selection" || $action == "describe" || $action == "key_points")) {
                    $settings_name = $action;
                    if ($action == "improve_selection") {
                        $settings_name = "improve";
                    }

                    $message["content"] = get_setting("ai_system_prompt_for_" . $settings_name) . ". Do not ask any questions or make any initial remarks. Do not return markdown format. Return plain text format. Here's the text:\n\n " . get_array_value($message, "content");
                    $messages[$key] = $message;
                }
            }
        }


        $get_direct_response = false;

        if (!$agent_id) {
            // if no agent id provided, assume that it's a quick assistant action
            $agent_id = get_setting("quick_assistant_ai_agent");

            // there has several actions like: improve, improve_selection, write_with_agent, custom_prompt, summarize, describe, key_points
            // there will be agent id only for the write_with_agent action
            // for the rest of the actions, for only the custom_prompt action, get the conditional response
            // for others, get direct response
            if ($action !== "custom_prompt") {
                $get_direct_response = true;
            }
        }

        if (!$agent_id) {
            echo "error: " . app_lang("error_occurred");
            exit;
        }

        if (!$this->permission_manager->can_access_this_ai_agent($agent_id)) {
            echo "error: " . app_lang("ai_agent_permission_error_message");
            exit;
        }

        // Close session to prevent cookie conflicts during streaming
        session_write_close();

        // Set headers for Server-Sent Events
        // This will handle the streaming response
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        ini_set('output_buffering', '0');
        ini_set('zlib.output_compression', '0');
        ini_set('implicit_flush', '1');

        // Disable time limit and output buffering
        set_time_limit(0);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $result = $this->AI_assistant->get_ai_agent_response($messages, $agent_id, $get_direct_response);

        // Check if there was an error
        if (is_array($result) && isset($result['error'])) {
            send_stream_message_to_the_browser("error: " . $result['error']);
        }

        exit; // Prevent CodeIgniter from sending its own response with cookies
    }

    function ai_chat_list() {

        if (!$this->permission_manager->can_access_ai_chatbox()) {
            app_redirect("forbidden");
        }

        $options = array(
            "login_user_id" => $this->login_user->id,
            "accessible_ai_agents" => $this->permission_manager->get_accessible_ai_agents()
        );

        $view_data["chats"] = $this->AI_chats_model->get_ai_chat_list($options)->getResult();

        return $this->template->view("ai_client/chat/tabs", $view_data);
    }

    function get_chatbox_agents() {
        if (!$this->permission_manager->can_access_ai_chatbox()) {
            app_redirect("forbidden");
        }

        $ai_chatbox_agents = get_setting("ai_chatbox_agents");
        if (!$ai_chatbox_agents) {
            show_404();
        }

        $options = array(
            "agent_ids" => $ai_chatbox_agents,
            "accessible_ai_agents" => $this->permission_manager->get_accessible_ai_agents()
        );

        $view_data["agents"] = $this->AI_agents_model->get_agents_for_chatbox($options)->getResult();

        return $this->template->view("ai_client/chat/ai_agents", $view_data);
    }

    function ai_chat() {
        if (!$this->permission_manager->can_access_ai_chatbox()) {
            app_redirect("forbidden");
        }

        $type = $this->request->getPost("type");

        if ($type === "new") {
            $this->validate_submitted_data(array(
                "ai_agent_id" => "required|numeric"
            ));
        } else {
            $this->validate_submitted_data(array(
                "ai_chat_id" => "required|numeric"
            ));
        }

        $ai_agent_id = $this->request->getPost("ai_agent_id");
        $ai_chat_id = $this->request->getPost("ai_chat_id");

        $chat_info = null;
        if ($ai_chat_id) {
            $chat_info = $this->AI_chats_model->get_one($ai_chat_id);

            if ($chat_info && $chat_info->id) {
                $ai_chat_id = $chat_info->id;
                $ai_agent_id = $chat_info->ai_agent_id;
            }
        }

        if (!$ai_agent_id) {
            echo "access_denied";
            return;
        }

        if (!$this->permission_manager->can_access_this_ai_agent($ai_agent_id)) {
            echo "access_denied";
            return;
        }

        $view_data = array();
        $view_data["type"] = $type;
        $view_data["chat_info"] = $chat_info;
        $view_data["ai_agent_id"] = $ai_agent_id;
        $view_data["ai_chat_id"] = $ai_chat_id;
        $view_data["ai_agent_info"] = $this->AI_agents_model->get_one($ai_agent_id);

        return $this->template->view("ai_client/chat/ai_chat", $view_data);
    }

    function send_message_to_agent() {

        if (!$this->permission_manager->can_access_ai_chatbox()) {
            app_redirect("forbidden");
        }

        $this->validate_submitted_data([
            "ai_agent_id" => "required|numeric",
            "chat_id" => "numeric",
            "message" => "required"
        ]);

        $chat_id = $this->request->getPost("chat_id");
        $ai_agent_id = $this->request->getPost("ai_agent_id");
        $message = $this->request->getPost("message");

        if (!$ai_agent_id || !$this->permission_manager->can_access_this_ai_agent($ai_agent_id)) {
            return app_lang("access_denied");
        }

        // Close session to prevent cookie conflicts during streaming
        session_write_close();

        // Set headers for Server-Sent Events
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        ini_set('output_buffering', '0');
        ini_set('zlib.output_compression', '0');
        ini_set('implicit_flush', '1');

        // Disable time limit and output buffering
        set_time_limit(0);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $chat_info = null;
        if ($chat_id) {
            $chat_info = $this->AI_chats_model->get_one($chat_id);
        }

        // If chat does NOT exist, create chat first
        if (!$chat_info) {
            $chat_data = array(
                "title" => substr($message, 0, 250) . (strlen($message) > 250 ? "..." : ""),
                "ai_agent_id" => $ai_agent_id,
                "created_by" => $this->login_user->id,
                "created_at" => get_current_utc_time()
            );

            $chat_id = $this->AI_chats_model->ci_save($chat_data);

            if (!$chat_id) {
                send_stream_message_to_the_browser("error: " . app_lang("error_occurred"));
                exit;
            }

            $first_message_data = array(
                "chat_id" => $chat_id,
                "message" => app_lang("initial_ai_chat_message"),
                "user_id" => 0,
                "ai_agent_id" => $ai_agent_id,
                "created_at" => get_current_utc_time()
            );

            $this->AI_chat_messages_model->ci_save($first_message_data);
        } else {
            $chat_id = $chat_info->id;
        }

        $message_data = array(
            "chat_id" => $chat_id,
            "message" => $message,
            "user_id" => $this->login_user->id,
            "ai_agent_id" => 0,
            "created_at" => get_current_utc_time()
        );

        $message_id = $this->AI_chat_messages_model->ci_save($message_data);

        send_stream_message_to_the_browser("data: " . json_encode(array('chat_id' => $chat_id, 'data' => $this->_load_messages($chat_id, $this->request->getPost("last_message_id"), 0, $this->login_user->id))));
        send_stream_message_to_the_browser("init_agent_response");

        // Generate and save agent reply
        $agent_message_id = $this->_generate_agent_reply($chat_id, $ai_agent_id, $message);

        if ($agent_message_id) {
            send_stream_message_to_the_browser("data: " . json_encode(array('last_message_id' => $agent_message_id)));
        } else {
            send_stream_message_to_the_browser("error: " . app_lang("error_occurred"));
        }

        exit;
    }

    private function _generate_agent_reply($chat_id, $ai_agent_id, $message) {

        $options = array(
            "chat_id" => $chat_id,
            "limit" => "10"
        );

        $final_messages = [];
        $messages = $this->AI_chat_messages_model->get_details($options)->result;

        foreach ($messages as $message) {

            if ($message->user_id) { // User message
                $final_messages[] = ['role' => 'user', 'content' => $message->message];
            }

            if ($message->ai_agent_id) { // Agent message
                $final_messages[] = ['role' => 'assistant', 'content' => $message->message, 'response_id' => $message->response_id];
            }
        }

        $reply = $this->AI_assistant->get_ai_agent_response($final_messages, $ai_agent_id);

        // Check if there was an error
        if (is_array($reply) && isset($reply['error'])) {
            return false;
        }

        if (!$reply) {
            return false;
        }

        $agent_message_data = array(
            "chat_id"     => $chat_id,
            "message"     => get_array_value($reply, 'response'),
            "user_id"     => 0,
            "ai_agent_id" => $ai_agent_id,
            "created_at"  => get_current_utc_time(),
            "response_id" => get_array_value($reply, 'response_id')
        );

        return $this->AI_chat_messages_model->ci_save($agent_message_data);
    }

    //load messages in chat view
    function view_ai_chat() {
        if (!$this->permission_manager->can_access_ai_chatbox()) {
            app_redirect("forbidden");
        }

        $this->validate_submitted_data(array(
            "chat_id" => "required|numeric",
            "last_message_id" => "numeric",
            "top_message_id" => "numeric",
            "is_first_load" => "numeric"
        ));

        $chat_id = $this->request->getPost("chat_id");
        $last_message_id = $this->request->getPost("last_message_id");
        $top_message_id = $this->request->getPost("top_message_id");

        $chat_info = $this->AI_chats_model->get_one($chat_id);
        if (!$chat_info || !$this->permission_manager->can_access_this_ai_agent($chat_info->ai_agent_id)) {
            show_404();
        }

        echo $this->_load_messages($chat_id, $last_message_id, $top_message_id);
    }

    //prepare the chat box messages 
    private function _load_messages($chat_id, $last_message_id, $top_message_id) {
        $replies_options = array(
            "chat_id" => $chat_id,
            "last_message_id" => $last_message_id,
            "top_message_id" => $top_message_id,
        );

        $view_data["replies"] = $this->AI_chat_messages_model->get_details($replies_options)->result;
        $view_data["chat_id"] = $chat_id;

        return $this->template->view("ai_client/chat/ai_chat_message_items", $view_data);
    }
}
