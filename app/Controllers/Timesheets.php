<?php

namespace App\Controllers;

use App\Libraries\Dropdown_list;

class Timesheets extends Security_Controller {

    public function __construct() {
        parent::__construct();
    }

    /* get all projects list according to the login user */

    private function _get_all_projects_dropdown_list_for_timesheets_filter() {
        $options = array();

        if (!$this->can_manage_all_projects()) {
            $options["user_id"] = $this->login_user->id;
        }

        $projects = $this->Projects_model->get_details($options)->getResult();

        $projects_dropdown = array(array("id" => "", "text" => "- " . app_lang("project") . " -"));
        foreach ($projects as $project) {
            $projects_dropdown[] = array("id" => $project->id, "text" => $project->title);
        }

        return $projects_dropdown;
    }

    /* prepare dropdown list */

    private function _prepare_members_dropdown_for_timesheet_filter($members) {
        $where = array("user_type" => "staff");

        if ($members != "all" && is_array($members) && count($members)) {
            $where["where_in"] = array("id" => $members);
        }

        return $this->Users_model->get_id_and_text_dropdown(
            array("first_name", "last_name"),
            $where,
            "- " . app_lang("member") . " -"
        );
    }

    /* prepare project members dropdown */

    private function _get_project_members_dropdown_list_for_filter($project_id) {

        $project_members_dropdown = array(array("id" => "", "text" => "- " . app_lang("member") . " -"));
        $project_members = $this->Project_members_model->get_project_members_id_and_text_dropdown($project_id);

        return array_merge($project_members_dropdown, $project_members);
    }

    /* load all time sheets view  */

    function index($user_id = 0) {
        validate_numeric_value($user_id);
        $this->access_only_team_members();

        $view_data = $this->_prepare_common_timesheet_view_data($user_id);

        $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("timesheets", $this->login_user->is_admin, $this->login_user->user_type);
        $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("timesheets", $this->login_user->is_admin, $this->login_user->user_type);

        if ($user_id) {
            return $this->template->view("timesheets/index", $view_data);
        } else {
            return $this->template->rander("timesheets/index", $view_data);
        }
    }

    /* load all timesheets summary view */

    function all_timesheet_summary($user_id = 0) {
        $this->access_only_team_members();

        $view_data = $this->_prepare_common_timesheet_view_data($user_id);

        $group_by_dropdown = array(
            array("id" => "", "text" => "- " . app_lang("group_by") . " -"),
            array("id" => "member", "text" => app_lang("member")),
            array("id" => "project", "text" => app_lang("project")),
            array("id" => "task", "text" => app_lang("task"))
        );

        if ($user_id) {
            array_splice($group_by_dropdown, 1, 1);
        }

        $view_data['group_by_dropdown'] = json_encode($group_by_dropdown);

        $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("timesheets", $this->login_user->is_admin, $this->login_user->user_type);

        return $this->template->view("timesheets/summary_list", $view_data);
    }

    // stop timer modal
    function stop_timer_modal_form($context_id) {

        validate_numeric_value($context_id);
        $this->access_only_team_members();

        $context = $this->request->getPost("context");

        if (!$context || $context === "general") {
            app_redirect("forbidden");
        }

        $view_data = array(
            "context"    => $context,
            "context_id" => $context_id
        );

        $task_id = $this->request->getPost("task_id");
        $open_task_id = 0;

        // Only project context supports task dropdown
        if ($context === "project") {

            $view_data["tasks_dropdown"] = $this->_get_timesheet_tasks_dropdown($context_id);

            $options = array(
                "project_id" => $context_id,
                "task_status_id" => 2,
                "assigned_to" => $this->login_user->id
            );

            $task_info = $this->Tasks_model->get_details($options)->getRow();

            $open_task_id = $this->request->getPost("task_id");

            $task_id = $open_task_id ?: ($task_info->id ?? 0);
        }

        $view_data["task_id"] = $task_id;
        $view_data["open_task_id"] = $open_task_id;

        return $this->template->view('timesheets/stop_timer_modal_form', $view_data);
    }

    private function _get_timesheet_tasks_dropdown($project_id, $return_json = false) {
        $tasks_dropdown = array("" => "-");
        $tasks_dropdown_json = array(array("id" => "", "text" => "- " . app_lang("task") . " -"));

        $show_assigned_tasks_only_user_id = $this->show_assigned_tasks_only_user_id();
        if (!$show_assigned_tasks_only_user_id) {
            $timesheet_manage_permission = get_array_value($this->login_user->permissions, "timesheet_manage_permission");
            if (!$timesheet_manage_permission || $timesheet_manage_permission === "own") {
                //show only own tasks when the permission is no/own
                $show_assigned_tasks_only_user_id = $this->login_user->id;
            }
        }

        $options = array(
            "project_id" => $project_id,
            "show_assigned_tasks_only_user_id" => $show_assigned_tasks_only_user_id
        );

        $tasks = $this->Tasks_model->get_details($options)->getResult();

        foreach ($tasks as $task) {
            $tasks_dropdown_json[] = array("id" => $task->id, "text" => $task->id . " - " . $task->title);
            $tasks_dropdown[$task->id] = $task->id . " - " . $task->title;
        }

        if ($return_json) {
            return json_encode($tasks_dropdown_json);
        } else {
            return $tasks_dropdown;
        }
    }

    /* start/stop timer */

    function timer($context_id = 0, $timer_status = "start") {
        validate_numeric_value($context_id);
        $this->access_only_team_members();

        $context = $this->request->getPost("context");
        $task_id = $this->request->getPost("task_id");
        $note = $this->request->getPost("note");

        $this->validate_submitted_data(array(
            "task_id" => "numeric"
        ));

        // Block general context explicitly
        if ($context === "general") {
            app_redirect("forbidden");
        }

        if ($context == "general_task") {
            $context_id = 0;
        }

        $data = array(
            "context" => $context,
            "context_id" => $context_id,
            "user_id" => $this->login_user->id,
            "status" => $timer_status,
            "note" => $note ?: "",
            "task_id" => $task_id ?: 0
        );

        // Check active timers
        $user_has_any_timer_except_this_context = $this->Timesheets_model->user_has_any_timer_except_this_context($context, $context_id, $this->login_user->id);

        $user_has_any_open_timer_on_this_task = false;

        if ($task_id) {
            $user_has_any_open_timer_on_this_task = $this->Timesheets_model->user_has_any_open_timer_on_this_task($task_id, $this->login_user->id);
        }

        if ($timer_status === "start" && $user_has_any_timer_except_this_context && !get_setting("users_can_start_multiple_timers_at_a_time")) {
            app_redirect("forbidden");
        } else if ($timer_status === "start" && $user_has_any_open_timer_on_this_task) {
            app_redirect("forbidden");
        }

        $this->Timesheets_model->process_timer($data);
        if ($timer_status === "start") {
            if ($this->request->getPost("task_timer")) {
                echo modal_anchor(get_uri("timesheets/stop_timer_modal_form/" . $context_id), "<i data-feather='clock' class='icon-16'></i> " . app_lang('stop_timer'), array("class" => "btn btn-danger", "title" => app_lang('stop_timer'), "data-post-task_id" => $task_id, "data-post-context" => $context, "data-post-context_id" => $context_id));
            } else {
                echo json_encode(array("success" => true));
            }
        } else {
            echo json_encode(array("success" => true));
        }
    }

    /* load timesheets view for a project */

    function project_timesheets($project_id) {
        validate_numeric_value($project_id);

        $this->init_project_permission_checker($project_id);
        $this->init_project_settings($project_id); //since we'll check this permission project wise

        if (!$this->can_view_timesheet($project_id)) {
            app_redirect("forbidden");
        }

        $view_data['project_id'] = $project_id;

        //client can't add log or update settings
        $view_data['can_add_log'] = false;

        if ($this->login_user->user_type === "staff") {
            $view_data['can_add_log'] = true;
        }

        $view_data['project_members_dropdown'] = json_encode($this->_get_project_members_dropdown_list_for_filter($project_id));
        $view_data['tasks_dropdown'] = $this->_get_timesheet_tasks_dropdown($project_id, true);

        $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("timesheets", $this->login_user->is_admin, $this->login_user->user_type);
        $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("timesheets", $this->login_user->is_admin, $this->login_user->user_type);

        $view_data["show_members_dropdown"] = true;
        $timesheet_access_info = $this->get_access_info("timesheet_manage_permission");
        $timesheet_access_type = $timesheet_access_info->access_type;

        if (!$timesheet_access_type || $timesheet_access_type === "own") {
            $view_data["show_members_dropdown"] = false;
        }

        return $this->template->view("projects/timesheets/index", $view_data);
    }

    /* load timelog add/edit modal */

    function modal_form() {
        $this->access_only_team_members();
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "project_id" => "numeric"
        ));

        $view_data['time_format_24_hours'] = get_setting("time_format") == "24_hours" ? true : false;
        $model_info = $this->Timesheets_model->get_one($this->request->getPost('id'));
        $project_id = $this->request->getPost('project_id') ? $this->request->getPost('project_id') : ($model_info->context === "project" ? $model_info->context_id : 0);

        if ($model_info->id) {
            $this->check_timelog_update_permission($model_info->id);
            $this->_check_timesheet_context_access($model_info->context, $model_info->context_id, $model_info->task_id);
        }

        $view_data["show_project_timesheet"] = get_setting("module_project_timesheet") ? true : false;
        $view_data["show_ticket_timesheet"] = get_setting("module_ticket_timesheet") && $this->can_access_tickets() ? true : false;
        $view_data["show_general_task_timesheet"] = get_setting("module_general_task_timesheet") ? true : false;

        if (!$model_info->id && !$view_data["show_project_timesheet"] && !$view_data["show_ticket_timesheet"] && !$view_data["show_general_task_timesheet"]) {
            app_redirect("forbidden");
        }

        //set the login user as a default selected member
        if (!$model_info->user_id) {
            $model_info->user_id = $this->login_user->id;
        }

        if (!$model_info->id) {
            //set today's date 
            $model_info->start_time = get_current_utc_time("Y-m-d H:00:00");
            $model_info->end_time = get_current_utc_time("Y-m-d H:00:00");
        }

        //get related data
        $related_data = $this->_prepare_all_related_data_for_timelog($project_id);
        $show_members_dropdown = get_array_value($related_data, "show_members_dropdown");
        $view_data["tasks_dropdown"] = get_array_value($related_data, "tasks_dropdown");
        $view_data["project_members_dropdown"] = get_array_value($related_data, "project_members_dropdown");

        $view_data["model_info"] = $model_info;

        if ($model_info->id) {
            $show_members_dropdown = false; //don't allow to edit the user on update.
        }

        $view_data["project_id"] = $project_id;
        $view_data['show_members_dropdown'] = $show_members_dropdown;
        $view_data["projects_dropdown"] = $this->_get_projects_dropdown();

        $view_data["custom_fields"] = $this->Custom_fields_model->get_combined_details("timesheets", $view_data['model_info']->id, $this->login_user->is_admin, $this->login_user->user_type)->getResult();

        $selected_type = $view_data["show_project_timesheet"] ? "project" : ($view_data["show_general_task_timesheet"] ? "general_task" : ($view_data["show_ticket_timesheet"] ? "ticket" : ""));

        if ($model_info->id && $model_info->context) {
            if ($model_info->context === "project") {
                $selected_type = "project";
            } elseif ($model_info->context === "ticket") {
                $selected_type = "ticket";
            } else {
                $selected_type = "general_task";
            }
        }

        $view_data["selected_type"] = $selected_type;

        $ticket_options = array("status" => "open");

        if ($this->allowed_ticket_types) {
            $ticket_options["ticket_types"] = $this->allowed_ticket_types;
        }

        if (!$this->login_user->is_admin) {
            $ticket_options["show_assigned_tickets_only_user_id"] = $this->show_assigned_tickets_only_user_id();
        }

        $tickets = $view_data["show_ticket_timesheet"] ? $this->Tickets_model->get_details($ticket_options)->getResult() : array();

        $ticket_dropdown = array();
        foreach ($tickets as $ticket) {
            $ticket_dropdown[] = array("id" => $ticket->id, "text" => $ticket->id . " - " . $ticket->title);
        }

        $view_data['tickets_dropdown'] = json_encode($ticket_dropdown);
        return $this->template->view('timesheets/modal_form', $view_data);
    }

    private function _prepare_all_related_data_for_timelog($project_id = 0) {
        //we have to check if any defined project exists, then go through with the project id
        $show_members_dropdown = false;
        if ($project_id) {
            $tasks_dropdown = $this->_get_timesheet_tasks_dropdown($project_id, true);

            //prepare members dropdown list
            $allowed_members = $this->_get_members_to_manage_timesheet();
            $project_members = "";

            if ($allowed_members === "all") {
                $project_members = $this->Project_members_model->get_project_members_dropdown_list($project_id)->getResult(); //get all members of this project
            } else {
                $project_members = $this->Project_members_model->get_project_members_dropdown_list($project_id, $allowed_members)->getResult();
            }

            $project_members_dropdown = array();
            if ($project_members) {
                foreach ($project_members as $member) {

                    if ($member->user_id !== $this->login_user->id) {
                        $show_members_dropdown = true; //user can manage other users time.
                    }

                    $project_members_dropdown[] = array("id" => $member->user_id, "text" => $member->member_name);
                }
            }
        } else {
            //we have show an empty dropdown when there is no project_id defined
            $tasks_dropdown = json_encode(array(array("id" => "", "text" => "-")));
            $project_members_dropdown = $this->_get_members_dropdown_for_timelog(true);
            $show_members_dropdown = count($project_members_dropdown) > 1;
        }

        return array(
            "project_members_dropdown" => $project_members_dropdown,
            "tasks_dropdown" => $tasks_dropdown,
            "show_members_dropdown" => $show_members_dropdown
        );
    }

    function get_general_task_related_data() {
        $this->access_only_team_members();

        $members_dropdown = $this->_get_members_dropdown_for_timelog(true);

        $options = $this->_get_other_context_task_options();

        $tasks_dropdown = array();
        $tasks = $this->Tasks_model->get_details($options)->getResult();
        foreach ($tasks as $task) {
            $tasks_dropdown[] = array("id" => $task->id, "text" => $task->id . " - " . $task->title);
        }

        return json_encode(array(
            "members_dropdown" => $members_dropdown,
            "tasks_dropdown" => $tasks_dropdown
        ));
    }

    private function _get_members_dropdown_for_timelog($admin_can_select_all_only = false) {
        $members = $this->_get_members_to_manage_timesheet();
        $where = array("deleted" => 0, "status" => "active", "user_type" => "staff");

        if ($admin_can_select_all_only && !$this->login_user->is_admin) {
            $where["id"] = $this->login_user->id;
        } else if (is_array($members) && count($members)) {
            $where["where_in"] = array("id" => $members);
        } else if ($members !== "all") {
            $where["id"] = $this->login_user->id;
        }

        return $this->Users_model->get_id_and_text_dropdown(array("first_name", "last_name"), $where,  "- " . app_lang("member") . " -");
    }

    private function _get_other_context_task_options() {
        $options = array(
            "exclude_contexts" => array("project", "general")
        );

        if ($this->login_user->is_admin) {
            return $options;
        } else {
            $options["specific_user_id"] = $this->login_user->id;
        }

        $permissions = $this->login_user->permissions;
        $context_options = array();

        if ($this->can_view_clients()) {
            $context_options["client"] = array(
                "show_own_clients_only_user_id" => $this->show_own_clients_only_user_id()
            );

            if (get_array_value($permissions, "client") === "specific") {
                $context_options["client"]["client_groups"] = get_array_value($permissions, "client_specific");
            }
        }

        if ($this->can_access_this_lead()) {
            $context_options["lead"] = array(
                "show_own_leads_only_user_id" => $this->show_own_leads_only_user_id()
            );
        }

        if ($this->can_view_invoices()) {
            $context_options["invoice"] = array();
        }

        if ($this->permission_manager->can_view_estimates()) {
            $context_options["estimate"] = array(
                "show_own_estimates_only_user_id" => $this->show_own_estimates_only_user_id()
            );
        }

        if ($this->login_user->is_admin || get_array_value($permissions, "order")) {
            $context_options["order"] = array();
        }

        if ($this->login_user->is_admin || get_array_value($permissions, "contract")) {
            $context_options["contract"] = array();
        }

        if ($this->permission_manager->can_view_proposals()) {
            $context_options["proposal"] = array(
                "show_own_proposals_only_user_id" => $this->show_own_proposals_only_user_id()
            );
        }

        if ($this->can_view_subscriptions()) {
            $context_options["subscription"] = array();
        }

        if ($this->can_access_expenses()) {
            $context_options["expense"] = array();
        }

        if ($this->can_access_tickets()) {
            $context_options["ticket"] = array(
                "show_assigned_tickets_only_user_id" => $this->show_assigned_tickets_only_user_id()
            );

            if (get_array_value($permissions, "ticket") === "specific") {
                $context_options["ticket"]["ticket_types"] = get_array_value($permissions, "ticket_specific");
            }
        }

        $options["context_options"] = $context_options ? $context_options : array("no_access" => array());

        return $options;
    }

    private function _check_timesheet_context_access($context, $context_id = 0, $task_id = 0) {


        if ($context === "project") {
            validate_numeric_value($context_id);

            if (!$context_id) {
                $this->_show_access_denied();
            }

            $this->init_project_permission_checker($context_id);
            $this->init_project_settings($context_id);

            if (!get_setting("module_project_timesheet") || !$this->can_view_timesheet($context_id)) {
                $this->_show_access_denied();
            }
        } else if ($context === "ticket") {
            validate_numeric_value($context_id);

            if (!$context_id) {
                $this->_show_access_denied();
            }

            if (!get_setting("module_ticket_timesheet") || !$this->can_access_tickets($context_id)) {
                $this->_show_access_denied();
            }
        } else if ($context === "general_task") {
            validate_numeric_value($task_id);

            if (!get_setting("module_general_task_timesheet") || !$this->_can_access_other_context_task($task_id)) {
                $this->_show_access_denied();
            }
        } else {
            $this->_show_access_denied();
        }
    }

    private function _can_access_other_context_task($task_id = 0) {
        if (!$task_id) {
            return false;
        }

        $task_info = $this->Tasks_model->get_one($task_id);

        if (!$task_info->id || $task_info->context === "general" || $task_info->context === "project") {
            return false;
        }

        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions;

        if ($task_info->client_id && $this->can_view_clients($task_info->client_id)) {
            return true;
        } else if ($task_info->lead_id && $this->can_access_this_lead($task_info->lead_id)) {
            return true;
        } else if ($task_info->invoice_id && $this->can_view_invoices($task_info->invoice_id)) {
            return true;
        } else if ($task_info->estimate_id && $this->permission_manager->can_view_estimates($task_info->estimate_id)) {
            return true;
        } else if ($task_info->order_id && get_array_value($permissions, "order")) {
            return true;
        } else if ($task_info->contract_id && get_array_value($permissions, "contract")) {
            return true;
        } else if ($task_info->proposal_id && $this->permission_manager->can_view_proposals($task_info->proposal_id)) {
            return true;
        } else if ($task_info->subscription_id && $this->can_view_subscriptions($task_info->subscription_id)) {
            return true;
        } else if ($task_info->expense_id && $this->can_access_expenses()) {
            return true;
        } else if ($task_info->ticket_id && $this->can_access_tickets($task_info->ticket_id)) {
            return true;
        }
    }

    private function _show_access_denied() {
        echo json_encode(array("success" => false, 'message' => app_lang("access_denied")));
        exit;
    }

    function get_all_related_data_of_selected_project_for_timelog($project_id = "") {
        $this->access_only_team_members();

        validate_numeric_value($project_id);
        if ($project_id) {
            $related_data = $this->_prepare_all_related_data_for_timelog($project_id);

            echo json_encode(array(
                "project_members_dropdown" => get_array_value($related_data, "project_members_dropdown"),
                "tasks_dropdown" => json_decode(get_array_value($related_data, "tasks_dropdown"))
            ));
        }
    }

    /* insert/update a timelog */

    function save() {
        $this->access_only_team_members();
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "project_id" => "numeric",
            "task_id" => "numeric",
            "ticket_id" => "numeric"
        ));

        $id = $this->request->getPost('id');

        $start_date_time = "";
        $end_date_time = "";
        $hours = "";

        $start_time = $this->request->getPost('start_time');
        $end_time = $this->request->getPost('end_time');
        $note = $this->request->getPost("note");
        $task_id = $this->request->getPost("task_id");
        $context = $this->request->getPost("context");

        if ($start_time) {
            //start time and end time mode
            //convert to 24hrs time format
            if (get_setting("time_format") != "24_hours") {
                $start_time = convert_time_to_24hours_format($start_time);
                $end_time = convert_time_to_24hours_format($end_time);
            }

            //join date with time
            $start_date_time = $this->request->getPost('start_date') . " " . $start_time;
            $end_date_time = $this->request->getPost('end_date') . " " . $end_time;

            //add time offset
            $start_date_time = convert_date_local_to_utc($start_date_time);
            $end_date_time = convert_date_local_to_utc($end_date_time);
        } else {
            //date and hour mode
            $date = $this->request->getPost("date");
            $start_date_time = $date . " 00:00:00";
            $start_date_time = convert_date_local_to_utc($start_date_time);
            $end_date_time = $start_date_time;

            //prepare hours
            $hours = convert_humanize_data_to_hours($this->request->getPost("hours"));
            if (!$hours) {
                echo json_encode(array("success" => false, 'message' => app_lang("hour_log_time_error_message")));
                return false;
            }
        }

        $project_id = $this->request->getPost('project_id');
        $ticket_id = $this->request->getPost('ticket_id');

        $data = array(
            "context_id" => $context === 'project' ? $project_id : ($context === 'ticket' ? $ticket_id : 0),
            "start_time" => $start_date_time,
            "end_time" => $end_date_time,
            "note" => $note ? $note : "",
            "task_id" => $task_id ? $task_id : 0,
            "hours" => $hours
        );

        //save user_id only on insert and it will not be editable
        if (!$id) {
            //insert mode
            $data["user_id"] = $this->request->getPost('user_id') ? $this->request->getPost('user_id') : $this->login_user->id;
            $data["context"] = $context;
        }

        if ($id) {
            $existing_info = $this->Timesheets_model->get_one($id);
            $this->_check_timesheet_context_access($existing_info->context, $existing_info->context_id, get_array_value($data, "task_id"));
        } else {
            $this->_check_timesheet_context_access($context, get_array_value($data, "context_id"), get_array_value($data, "task_id"));
        }

        $this->check_timelog_update_permission($id, $context, get_array_value($data, "context_id"), get_array_value($data, "user_id"));

        $save_id = $this->Timesheets_model->ci_save($data, $id);
        if ($save_id) {

            save_custom_fields("timesheets", $save_id, $this->login_user->is_admin, $this->login_user->user_type);

            echo json_encode(array("success" => true, "data" => $this->_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /* delete/undo a timelog */

    function delete() {
        $this->access_only_team_members();

        $this->validate_submitted_data(array(
            "id" => "required|numeric"
        ));

        $id = $this->request->getPost('id');

        $this->check_timelog_update_permission($id);

        if ($this->request->getPost('undo')) {
            if ($this->Timesheets_model->delete($id, true)) {
                echo json_encode(array("success" => true, "data" => $this->_row_data($id), "message" => app_lang('record_undone')));
            } else {
                echo json_encode(array("success" => false, app_lang('error_occurred')));
            }
        } else {
            if ($this->Timesheets_model->delete($id)) {
                echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
            }
        }
    }

    private function check_timelog_update_permission($log_id = null, $context = null, $context_id = null, $user_id = null) {
        $user_id = $user_id ? $user_id : 0;
        $context_id = $context_id ? $context_id : 0;

        if ($log_id) {
            $info = $this->Timesheets_model->get_one($log_id);

            if (!$info->id) {
                $this->_show_access_denied();
            }

            $user_id = $info->user_id;
            $context = $info->context;
            $context_id = $info->context_id;
        }

        if ($context !== "project") {
            if ($this->login_user->is_admin || $user_id === $this->login_user->id) {
                return true;
            }

            $this->_show_access_denied();
        }

        $members = $this->_get_members_to_manage_timesheet();

        if ($members === "all") {
            return true;
        } else if (is_array($members) && count($members) && in_array($user_id, $members)) {
            //permission: no / own / specific / specific_excluding_own
            $timesheet_manage_permission = get_array_value($this->login_user->permissions, "timesheet_manage_permission");

            if (!$timesheet_manage_permission && $log_id) { //permission: no
                $this->_show_access_denied();
            }

            if ($timesheet_manage_permission === "specific_excluding_own" && $log_id && $user_id === $this->login_user->id) { //permission: specific_excluding_own
                $this->_show_access_denied();
            }

            //permission: own / specific
            return true;
        } else if ($members === "own_project_members" || $members === "own_project_members_excluding_own") {
            if ($context !== "project") {
                $this->_show_access_denied();
            }

            $project_id = $context_id;

            if ($this->Project_members_model->is_user_a_project_member($project_id, $user_id) || $this->Project_members_model->is_user_a_project_member($project_id, $this->login_user->id)) { //check if the login user and timelog user is both on same project
                if ($members === "own_project_members") {
                    return true;
                } else if ($this->login_user->id !== $user_id) {
                    //can't edit own but can edit other user's of project
                    //no need to check own condition here for new timelogs since it's already checked before
                    return true;
                }
            }
        }

        $this->_show_access_denied();
    }

    /* list of timesheets, prepared for datatable  */

    function list_data($user_id = 0) {

        $project_id = $this->request->getPost("project_id");

        $this->init_project_permission_checker($project_id);
        $this->init_project_settings($project_id); //since we'll check this permission project wise

        if (!$this->can_view_timesheet($project_id, true)) {
            app_redirect("forbidden");
        }

        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("timesheets", $this->login_user->is_admin, $this->login_user->user_type);

        $user_id = $user_id ? $user_id : $this->request->getPost("user_id");
        validate_numeric_value($user_id);

        $ticket_module = get_setting("module_ticket_timesheet");
        $project_module = get_setting("module_project_timesheet");
        $general_task_module = get_setting("module_general_task_timesheet");

        $enabled_modules = array_filter([
            "ticket" => $ticket_module,
            "project" => $project_module,
            "general_task" => $general_task_module
        ]);

        $context = null;

        if (count($enabled_modules) == 1) {
            $context = array_key_first($enabled_modules);
        }

        $options = array(
            "project_id" => $project_id,
            "status" => "none_open",
            "user_id" => $user_id,
            "start_date" => $this->request->getPost("start_date"),
            "end_date" => $this->request->getPost("end_date"),
            "task_id" => $this->request->getPost("task_id"),
            "client_id" => $this->request->getPost("client_id"),
            "custom_fields" => $custom_fields,
            "custom_field_filter" => $this->prepare_custom_field_filter_values("timesheets", $this->login_user->is_admin, $this->login_user->user_type)
        );

        if ($context) {
            $options["context"] = $context;
        }

        //get allowed member ids
        $members = $this->_get_members_to_manage_timesheet();
        if ($members != "all" && $this->login_user->user_type == "staff") {
            //if user has permission to access all members, query param is not required
            //client can view all timesheet
            $options["allowed_members"] = $members;
        }

        $all_options = append_server_side_filtering_commmon_params($options);

        $result = $this->Timesheets_model->get_details($all_options);

        //by this, we can handel the server side or client side from the app table prams.
        if (get_array_value($all_options, "server_side")) {
            $list_data = get_array_value($result, "data");
        } else {
            $list_data = $result->getResult();
            $result = array();
        }

        $result_data = array();
        foreach ($list_data as $data) {
            $result_data[] = $this->_make_row($data, $custom_fields);
        }

        $result["data"] = $result_data;

        echo json_encode($result);
    }

    /* return a row of timesheet list  table */

    private function _row_data($id) {
        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("timesheets", $this->login_user->is_admin, $this->login_user->user_type);

        $options = array("id" => $id, "custom_fields" => $custom_fields);
        $data = $this->Timesheets_model->get_details($options)->getRow();
        return $this->_make_row($data, $custom_fields);
    }

    /* prepare a row of timesheet list table */

    private function _make_row($data, $custom_fields) {
        $image_url = get_avatar($data->logged_by_avatar);
        $user = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt=''></span> $data->logged_by_user";

        $start_time = $data->start_time;
        $end_time = $data->end_time;
        $task_title = modal_anchor(get_uri("tasks/view"), $data->task_title, array("title" => app_lang('task_info') . " #$data->task_id", "data-post-id" => $data->task_id, "data-modal-lg" => "1"));

        if ($data->context == "ticket") {
            $task_title = anchor(get_uri("tickets/view/" . $data->context_id), $data->ticket_title ? $data->ticket_title : "-");
        }

        $context_title = "-";
        if ($data->context == "project") {
            $context_title = anchor(get_uri("projects/view/" . $data->context_id), $data->project_title);
        }

        $client_name = "-";
        if ($data->timesheet_client_company_name) {
            $client_name = anchor(get_uri("clients/view/" . $data->timesheet_client_id), $data->timesheet_client_company_name);
        }

        $duration = convert_seconds_to_time_format($data->hours ? (round(($data->hours * 60), 0) * 60) : (abs(strtotime($end_time) - strtotime($start_time))));

        $row_data = array(
            get_team_member_profile_link($data->user_id, $user),
            $context_title,
            $client_name,
            $task_title,
            $data->start_time,
            ($data->hours || get_setting("users_can_input_only_total_hours_instead_of_period")) ? format_to_date($data->start_time) : format_to_datetime($data->start_time),
            $data->end_time,
            $data->hours ? format_to_date($data->end_time) : format_to_datetime($data->end_time),
            $duration,
            to_decimal_format(convert_time_string_to_decimal($duration), false), //alwasy return dot for excel.
            to_decimal_format(convert_time_string_to_decimal($duration)), //alwasy return dot to export.
            $data->note
        );

        foreach ($custom_fields as $field) {
            $cf_id = "cfv_" . $field->id;
            $row_data[] = $this->template->view("custom_fields/output_" . $field->field_type, array("value" => $data->$cf_id));
        }

        $options = modal_anchor(get_uri("timesheets/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_timelog'), "data-post-id" => $data->id))
            . js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_timelog'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("timesheets/delete"), "data-action" => "delete"));

        $timesheet_manage_permission = get_array_value($this->login_user->permissions, "timesheet_manage_permission");
        if ($data->user_id === $this->login_user->id && ($timesheet_manage_permission === "own_project_members_excluding_own" || $timesheet_manage_permission === "specific_excluding_own")) {
            $options = "";
        }

        $row_data[] = $options;

        return $row_data;
    }

    /* load timesheets summary view for a project */

    function summary($project_id) {
        validate_numeric_value($project_id);

        $this->init_project_permission_checker($project_id);
        $this->init_project_settings($project_id); //since we'll check this permission project wise

        if (!$this->can_view_timesheet($project_id)) {
            app_redirect("forbidden");
        }

        $view_data['project_id'] = $project_id;

        $view_data['group_by_dropdown'] = json_encode(
            array(
                array("id" => "", "text" => "- " . app_lang("group_by") . " -"),
                array("id" => "member", "text" => app_lang("member")),
                array("id" => "task", "text" => app_lang("task"))
            )
        );

        $view_data['project_members_dropdown'] = json_encode($this->_get_project_members_dropdown_list_for_filter($project_id));
        $view_data['tasks_dropdown'] = $this->_get_timesheet_tasks_dropdown($project_id, true);
        $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("timesheets", $this->login_user->is_admin, $this->login_user->user_type);

        $view_data["show_members_dropdown"] = true;
        $timesheet_access_info = $this->get_access_info("timesheet_manage_permission");
        $timesheet_access_type = $timesheet_access_info->access_type;

        if (!$timesheet_access_type || $timesheet_access_type === "own") {
            $view_data["show_members_dropdown"] = false;
        }

        return $this->template->view("projects/timesheets/summary_list", $view_data);
    }

    /* list of timesheets summary, prepared for datatable  */

    function summary_list_data($user_id = 0) {

        $project_id = $this->request->getPost("project_id");

        //client can't view all projects timesheet. project id is required.
        if (!$project_id) {
            $this->access_only_team_members();
        }

        if ($project_id) {
            $this->init_project_permission_checker($project_id);
            $this->init_project_settings($project_id); //since we'll check this permission project wise

            if (!$this->can_view_timesheet($project_id, true)) {
                app_redirect("forbidden");
            }
        }


        $group_by = $this->request->getPost("group_by");
        $user_id = $user_id ? $user_id : $this->request->getPost("user_id");
        validate_numeric_value($user_id);

        $options = array(
            "project_id" => $project_id,
            "status" => "none_open",
            "user_id" => $user_id,
            "start_date" => $this->request->getPost("start_date"),
            "end_date" => $this->request->getPost("end_date"),
            "task_id" => $this->request->getPost("task_id"),
            "group_by" => $group_by,
            "client_id" => $this->request->getPost("client_id"),
            "custom_field_filter" => $this->prepare_custom_field_filter_values("timesheets", $this->login_user->is_admin, $this->login_user->user_type)
        );

        //get allowed member ids
        $members = $this->_get_members_to_manage_timesheet();
        if ($members != "all" && $this->login_user->user_type == "staff") {
            //if user has permission to access all members, query param is not required
            //client can view all timesheet
            $options["allowed_members"] = $members;
        }

        $list_data = $this->Timesheets_model->get_summary_details($options)->getResult();

        $result = array();
        foreach ($list_data as $data) {

            $member = "-";
            $task_title = "-";

            if ($group_by != "task") {
                $image_url = get_avatar($data->logged_by_avatar);
                $user = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt=''></span> $data->logged_by_user";

                $member = get_team_member_profile_link($data->user_id, $user);
            }

            $context_title = "-";
            if ($data->context == "project") {
                $context_title = anchor(get_uri("projects/view/" . $data->project_id), $data->project_title);
            }

            if ($group_by != "member") {
                $task_title = modal_anchor(get_uri("tasks/view"), $data->task_title, array("title" => app_lang('task_info') . " #$data->task_id", "data-post-id" => $data->task_id, "data-modal-lg" => "1"));
                if (!$data->task_title) {
                    $task_title = app_lang("not_specified");
                }
            }

            $duration = convert_seconds_to_time_format(abs($data->total_duration));

            $client_name = "-";
            if ($data->timesheet_client_company_name) {
                $client_name = anchor(get_uri("clients/view/" . $data->timesheet_client_id), $data->timesheet_client_company_name);
            }

            $result[] = array(
                $context_title,
                $client_name,
                $member,
                $task_title,
                $duration,
                to_decimal_format(convert_time_string_to_decimal($duration)),
                to_decimal_format(convert_time_string_to_decimal($duration), false), //alwasy return dot for excel.
            );
        }
        echo json_encode(array("data" => $result));
    }

    //show timesheets chart
    function chart($project_id = 0, $user_id = 0) {
        $view_data = $this->_prepare_common_timesheet_view_data($user_id, $project_id);
        return $this->template->view("timesheets/timesheet_chart", $view_data);
    }

    //timesheets chart data
    function chart_data($project_id = 0, $user_id = 0) {
        $data = $this->_prepare_timesheet_statistics_data($project_id, $user_id);
        $days_of_month = $data["days_of_month"];
        $timesheet_users_result = $data["timesheet_users_result"];
        $timesheets_result = $data["timesheets_result"];

        $timesheets = array();
        $timesheets_array = array();
        $ticks = array();

        $user_result = array();
        foreach ($timesheet_users_result as $user) {
            $time = convert_seconds_to_time_format($user->total_sec);
            $user_result[] = "<div class='user-avatar avatar-30 avatar-circle' data-bs-toggle='tooltip' title='" . $user->user_name . " - " . $time . "'><img alt='' src='" . get_avatar($user->user_avatar) . "'></div>";
        }


        for ($i = 1; $i <= $days_of_month; $i++) {
            $timesheets[$i] = 0;
        }

        foreach ($timesheets_result as $value) {
            $timesheets[$value->day * 1] = $value->total_sec / 60 / 60;
        }

        foreach ($timesheets as $value) {
            $timesheets_array[] = $value;
        }

        for ($i = 1; $i <= $days_of_month; $i++) {
            $ticks[] = $i;
        }

        echo json_encode(array("timesheets" => $timesheets_array, "ticks" => $ticks, "timesheet_users_result" => $user_result));
    }


    function show_my_open_timers() {
        $timers = $this->Timesheets_model->get_open_timers($this->login_user->id);
        $view_data["timers"] = $timers->getResult();
        return $this->template->view("timesheets/open_timers", $view_data);
    }

    function task_timesheet($task_id, $project_id) {
        validate_numeric_value($task_id);
        validate_numeric_value($project_id);

        $this->init_project_permission_checker($project_id);
        $this->init_project_settings($project_id);

        if (!$this->can_view_timesheet($project_id, true)) {
            app_redirect("forbidden");
        }
        $options = array(
            "project_id" => $project_id,
            "status" => "none_open",
            "task_id" => $task_id,
        );

        //get allowed member ids
        $members = $this->_get_members_to_manage_timesheet();
        if ($members != "all" && $this->login_user->user_type == "staff") {
            //if user has permission to access all members, query param is not required
            //client can view all timesheet
            $options["allowed_members"] = $members;
        }

        $view_data['task_timesheet'] = $this->Timesheets_model->get_details($options)->getResult();
        return $this->template->view("tasks/task_timesheet", $view_data);
    }

    // Daily timesheet activity
    function daily_timesheet_activity($project_id = 0, $user_id = 0) {
        validate_numeric_value($project_id);
        validate_numeric_value($user_id);
        $this->init_project_permission_checker($project_id);

        if (!$this->can_view_timesheet($project_id, true)) {
            app_redirect("forbidden");
        }

        $view_data = $this->_prepare_common_timesheet_view_data($user_id, $project_id);

        return $this->template->view("timesheets/daily_timesheet_activity", $view_data);
    }

    // Daily timesheet activity data
    function daily_timesheet_activity_data($project_id = 0, $user_id = 0) {
        $data = $this->_prepare_timesheet_statistics_data($project_id, $user_id);

        $timesheets_data_per_user = $data["timesheets_data_per_user"];
        $timesheet_users_result = $data["timesheet_users_result"];
        $days_of_month = $data["days_of_month"];
        $start_date = $data["start_date"];

        $user_timesheets = array();
        $user_info_map = array();

        foreach ($timesheet_users_result as $user) {
            $user_info_map[$user->user_id] = array(
                "name" => $user->user_name,
                "avatar" => get_avatar($user->user_avatar),
                "total_hours" => to_decimal_format(convert_time_string_to_decimal(convert_seconds_to_time_format(abs($user->total_sec))))
            );
        }

        // Initialize user_timesheets with 0 values for all days
        foreach ($timesheets_data_per_user as $entry) {
            $user_id = $entry->user_id;
            if (!isset($user_timesheets[$user_id])) {
                $user_timesheets[$user_id] = array_fill(1, $days_of_month, 0);
            }

            $day = (int)$entry->day;
            $hours = to_decimal_format(convert_time_string_to_decimal(convert_seconds_to_time_format(abs($entry->total_sec))));
            $user_timesheets[$user_id][$day] = $hours;
        }

        // Build final output
        $users_output = array();
        foreach ($user_timesheets as $user_id => $daywise_data) {
            $users_output[] = array(
                "id" => $user_id,
                "name" => isset($user_info_map[$user_id]) ? $user_info_map[$user_id]["name"] : "User #$user_id",
                "avatar" => isset($user_info_map[$user_id]) ? $user_info_map[$user_id]["avatar"] : "",
                "timesheets" => array_values($daywise_data),
                "total_hours" => isset($user_info_map[$user_id]) ? $user_info_map[$user_id]["total_hours"] : 0
            );
        }

        echo json_encode(array("users" => $users_output, "days_of_month" => $days_of_month, "start_date" => $start_date));
    }

    function get_own_projects_dropdown_list($user_id) {
        $projects = $this->Tasks_model->get_my_projects_dropdown_list($user_id)->getResult();
        $projects_dropdown = array(array("id" => "", "text" => "- " . app_lang("project") . " -"));
        foreach ($projects as $project) {
            if ($project->project_id && $project->project_title) {
                $projects_dropdown[] = array("id" => $project->project_id, "text" => $project->project_title);
            }
        }

        return $projects_dropdown;
    }

    // Prepare common timesheet view data
    private function _prepare_common_timesheet_view_data($user_id = 0, $project_id = 0) {
        validate_numeric_value($user_id);
        validate_numeric_value($project_id);

        $members = $this->_get_members_to_manage_timesheet();

        $view_data['members_dropdown'] = json_encode($this->_prepare_members_dropdown_for_timesheet_filter($members));

        $dropdown_list = new Dropdown_list($this);
        $view_data['clients_dropdown'] = $dropdown_list->get_clients_id_and_text_dropdown(array("blank_option_text" => "- " . app_lang("client") . " -"));

        if ($user_id) {
            $view_data['projects_dropdown'] = json_encode($this->get_own_projects_dropdown_list($user_id));
        } else {
            $view_data['projects_dropdown'] = json_encode($this->_get_all_projects_dropdown_list_for_timesheets_filter());
        }

        $view_data["user_id"] = $user_id;
        $view_data["project_id"] = $project_id;

        return $view_data;
    }

    // Prepare timesheet statistics data
    private function _prepare_timesheet_statistics_data($project_id = 0, $user_id = 0) {
        if (!$project_id) {
            $project_id = $this->request->getPost("project_id");
        }

        validate_numeric_value($project_id);
        validate_numeric_value($user_id);

        $this->init_project_permission_checker($project_id);
        $this->init_project_settings($project_id); //since we'll check this permission project wise

        if (!$this->can_view_timesheet($project_id, true)) {
            app_redirect("forbidden");
        }

        $start_date = $this->request->getPost("start_date");
        $end_date = $this->request->getPost("end_date");
        $user_id = $user_id ? $user_id : $this->request->getPost("user_id");

        $options = array(
            "start_date" => $start_date,
            "end_date" => $end_date,
            "user_id" => $user_id,
            "project_id" => $project_id
        );

        //get allowed member ids
        $members = $this->_get_members_to_manage_timesheet();
        if ($members != "all" && $this->login_user->user_type == "staff") {
            //if user has permission to access all members, query param is not required
            //client can view all timesheet
            $options["allowed_members"] = $members;
        }

        $statistics = $this->Timesheets_model->get_timesheet_statistics($options);

        $data = array();
        $data["start_date"] = $start_date;
        $data["days_of_month"] = date("t", strtotime($start_date));
        $data["user_id"] = $user_id;
        $data["project_id"] = $project_id;
        $data["timesheets_result"] = $statistics->timesheets_data;
        $data["timesheet_users_result"] = $statistics->timesheet_users_data;
        $data["timesheets_data_per_user"] = $statistics->timesheets_data_per_user;

        return $data;
    }

    function ticket_timesheet($ticket_id) {
        validate_numeric_value($ticket_id);

        $this->can_access_tickets($ticket_id);

        if (!$this->can_view_timesheet(0, true)) {
            app_redirect("forbidden");
        }

        $options = array(
            "status" => "none_open",
            "context" => "ticket",
            "context_id" => $ticket_id
        );

        if (!$this->login_user->is_admin) {
            $options["user_id"] = $this->login_user->id;
        }

        $view_data['ticket_timesheet'] = $this->Timesheets_model->get_details($options)->getResult();
        return $this->template->view("tickets/ticket_timesheet", $view_data);
    }
}
