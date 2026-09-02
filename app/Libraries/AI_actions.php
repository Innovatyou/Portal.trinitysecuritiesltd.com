<?php

namespace App\Libraries;

use App\Controllers\Security_Controller;
use App\Libraries\Permission_manager;

class AI_actions {
    private $permission_manager;
    private $security_controller_instance;

    public function __construct() {
        $this->security_controller_instance = new Security_Controller();
        $this->permission_manager = new Permission_manager($this->security_controller_instance);
    }

    function get_app_action_context_info($context) {
        $contexts = array(
            "note" => array(
                "tool_functions" => array(
                    [
                        "type" => "function",
                        "function" => [
                            "name" => "create_a_note",
                            "description" => "Create a new note.",
                            "parameters" => [
                                "type" => "object",
                                "properties" => [
                                    "title" => ["type" => "string"],
                                    "description" => ["type" => "string"]
                                ],
                            ]
                        ]
                    ]
                ),
                "system_prompt" => "You are a helpful assistant that can create notes. 
                    - If the user clearly asks to create a note, you MUST call the `create_a_note` tool instead of responding with normal text or follow the previous responses. 
                    - Use `create_a_note` when asked to create a new note. 
                    - Either `title` or `description` field is required. 
                    - If the user has not provided the note title or description yet, **ask for it or automatically generate it**:
                        - The note **description** should be a concise summary of the user's last conversation if the user wants to, not just copied text.
                        - The note **title** should be a short, clear title reflecting the main topic of the conversation if not provided explicitly.
                    - Never call `create_a_note` with an empty or placeholder note title or description.",
            ),
            "todo" => array(
                "tool_functions" => array(
                    [
                        "type" => "function",
                        "function" => [
                            "name" => "create_a_todo",
                            "description" => "Create a new todo.",
                            "parameters" => [
                                "type" => "object",
                                "properties" => [
                                    "title" => ["type" => "string"],
                                    "description" => ["type" => "string"]
                                ],
                            ]
                        ]
                    ]
                ),
                "system_prompt" => "You are a helpful assistant that can create todo. 
                    - If the user clearly asks to create a todo, you MUST call the `create_a_todo` tool instead of responding with normal text or follow the previous responses. 
                    - Use `create_a_todo` when asked to create a new todo. 
                    - The `description` field is required. 
                        - If the user has not provided the todo description yet, **ask for it before calling the function**. 
                        - Never call `create_a_todo` with an empty or placeholder todo description. 
                        - If usesr has not provided the todo title, prepare it based on the todo description.
                        - Do not ask for any other fields unless the user provides them voluntarily.",
            )
        );

        /***
        $contexts = array(
            "project" => array(
                "tool_functions" => array(
                    [
                        "type" => "function",
                        "function" => [
                            "name" => "get_projects_overview",
                            "description" => "Analyze all projects and provide an overview.",
                        ]
                    ],
                    [
                        "type" => "function",
                        "function" => [
                            "name" => "get_a_project_summary",
                            "description" => "Analyze a specific project and provide a summary.",
                            "parameters" => [
                                "type" => "object",
                                "properties" => [
                                    "title" => ["type" => "string"]
                                ],
                            ]
                        ]
                    ],

                ),
                "system_prompt" => "You are a helpful assistant that can analyze project statuses and provide summaries. 
                    - Use `get_projects_overview` when asked for an overall summary. 
                    - Use `get_a_project_summary` when the user wants to know about a project.
                        - The `title` field is required. 
                        - If the user has not provided the project title yet, **ask for it before calling the function**. 
                        - Never call `get_a_project_summary` with an empty or placeholder project title. 
                        - Do not ask for any other fields unless the user provides them voluntarily.",
            ),
            "task" => array(
                "tool_functions" => array(
                    [
                        "type" => "function",
                        "function" => [
                            "name" => "get_all_tasks_summary",
                            "description" => "Analyze all tasks and provide an overall summary.",
                        ]
                    ]
                ),
                "system_prompt" => "You are a helpful assistant that can analyze task statuses and provide summaries. 
                    - Use `get_all_tasks_summary` when asked for an overall summary.",
            )
        );
         */

        return $contexts[$context] ?? null;
    }

    function create_a_note($args = []) {
        if (!$this->permission_manager->can_create_notes()) {
            return false;
        }

        $title = get_array_value($args, "title");
        $description = get_array_value($args, "description");

        if (!$title && !$description) {
            return "Please provide either title or description.";
        }

        $data = array(
            "title" => $title ? $title : $description,
            "description" => $description ? $description : $title,
            "created_by" => $this->permission_manager->login_user_id(),
            "created_at" => get_current_utc_time(),
        );

        $data = clean_data($data);

        $save_id =  $this->security_controller_instance->Notes_model->ci_save($data);

        if ($save_id) {
            return app_lang('record_saved');
        } else {
            return app_lang('error_occurred');
        }
    }

    function create_a_todo($args = []) {
        if (!$this->permission_manager->can_create_todo()) {
            return false;
        }

        $data = array(
            "title" => get_array_value($args, "title"),
            "description" => get_array_value($args, "description"),
            "created_by" => $this->permission_manager->login_user_id(),
            "created_at" => get_current_utc_time(),
            "sort" => $this->security_controller_instance->Todo_model->get_next_sort_value($this->permission_manager->login_user_id()),
        );

        $data = clean_data($data);

        $save_id =  $this->security_controller_instance->Todo_model->ci_save($data);

        if ($save_id) {
            return app_lang('record_saved');
        } else {
            return app_lang('error_occurred');
        }
    }

    /***
    function get_projects_overview($args = []) {
        $data = projects_overview_widget(true);

        return "The project overview is as follows:\n"
            . "Overall projects are " . get_array_value($data, "progress") . "% completed.\n"
            . get_array_value($data, "open_status_text") . ": " . get_array_value($data, "count_project_status")->open . "\n"
            . get_array_value($data, "completed_status_text") . ": " . get_array_value($data, "count_project_status")->completed . "\n"
            . get_array_value($data, "hold_status_text") . ": " . get_array_value($data, "count_project_status")->hold . "";
    }

    function get_a_project_summary($args = []) {

        if (!$this->permission_manager->is_team_member()) {
            app_redirect("forbidden");
        }

        if ($this->permission_manager->has_all_projects_restricted_role()) {
            app_redirect("forbidden");
        }

        $title = $args['title'];
        $title = trim($title);
        if (!$title) {
            return false;
        }

        $options = array(
            "title" => $title,
            "client_id" => $this->permission_manager->login_client_id(),
        );

        if (!$this->permission_manager->can_manage_all_projects()) {
            $options["user_id"] = $this->permission_manager->login_user_id();
        }

        $data = "";

        $project_info = $this->security_controller_instance->Projects_model->get_details($options)->getRow();
        if ($project_info) {

            $project_progress = $project_info->total_points ? round(($project_info->completed_points / $project_info->total_points) * 100) : 0;
            $data = "The project is " . $project_progress . "% complete.\nWhere the task counts are:\n";

            $task_statuses = $this->security_controller_instance->Tasks_model->get_task_statistics(array("project_id" => $project_info->id))->task_statuses;
            foreach ($task_statuses as $status) {
                $status_title = $status->key_name ? app_lang($status->key_name) : $status->title;
                $data .= $status_title . ": " . $status->total . "\n";
            }

            $info = $this->security_controller_instance->Timesheets_model->count_total_time($options);
            $data .= "Total project hours: " . to_decimal_format($info->timesheet_total / 60 / 60) . "\n";
        } else {
            show_404();
        }

        return $data;
    }

    function get_all_tasks_summary($args = []) {
        if (!$this->permission_manager->has_all_projects_restricted_role()) {

            $view_data = tasks_overview_widget("all_tasks_overview", true);
            $task_statuses = get_array_value($view_data, "task_statuses");
            $task_priorities = get_array_value($view_data, "task_priorities");
            $expired_tasks = get_array_value($view_data, "expired_tasks");

            $data = "Task counts based on statuses as follows: \n";

            foreach ($task_statuses as $status) {
                $status_title = $status->key_name ? app_lang($status->key_name) : $status->title;
                $data .= $status_title . ": " . $status->total . ". \n";
            }

            if ($expired_tasks) {
                $data .= app_lang("expired") . ": " . $expired_tasks . ". \n";
            }

            if ($task_priorities) {
                $data .= "\nPriority-wise data as follows: \n";

                foreach ($task_priorities as $task_priority) {
                    $data .= $task_priority->title . ": " . $task_priority->total . ". \n";
                }
            }

            return $data;
        }
    }
     */
}
