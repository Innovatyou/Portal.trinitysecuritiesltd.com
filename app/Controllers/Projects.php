<?php

namespace App\Controllers;

use App\Libraries\Excel_import;
use App\Libraries\App_folders;
use App\Libraries\Dropdown_list;

class Projects extends Security_Controller {

    use Excel_import;
    use App_folders;

    protected $Project_settings_model;
    protected $Checklist_items_model;
    protected $Likes_model;
    protected $Pin_comments_model;
    protected $File_category_model;
    protected $Task_priority_model;
    private $clients_id_by_title = array();
    private $project_statuses_id_by_title = array();
    private $project_labels_id_by_title = array();
    private $project_members_id_by_name = array();

    public function __construct() {
        parent::__construct();
        if ($this->permission_manager->has_all_projects_restricted_role()) {
            app_redirect("forbidden");
        }

        $this->Project_settings_model = model('App\Models\Project_settings_model');
        $this->Checklist_items_model = model('App\Models\Checklist_items_model');
        $this->Likes_model = model('App\Models\Likes_model');
        $this->Pin_comments_model = model('App\Models\Pin_comments_model');
        $this->File_category_model = model('App\Models\File_category_model');
        $this->Task_priority_model = model("App\Models\Task_priority_model");
    }

    private function can_delete_projects($project_id = 0) {
        if ($this->login_user->user_type == "staff") {
            if ($this->can_manage_all_projects()) {
                return true;
            }

            $can_delete_projects = get_array_value($this->login_user->permissions, "can_delete_projects");
            $can_delete_only_own_created_projects = get_array_value($this->login_user->permissions, "can_delete_only_own_created_projects");

            if ($can_delete_projects) {
                return true;
            }

            if ($project_id) {
                $project_info = $this->Projects_model->get_one($project_id);
                if ($can_delete_only_own_created_projects && $project_info->created_by === $this->login_user->id) {
                    return true;
                }
            } else if ($can_delete_only_own_created_projects) { //no project given and the user has partial access
                return true;
            }
        }
    }

    private function can_add_remove_project_members() {
        if ($this->login_user->user_type == "staff") {
            if ($this->login_user->is_admin) {
                return true;
            } else {
                if (get_array_value($this->login_user->permissions, "show_assigned_tasks_only") !== "1") {
                    if ($this->can_manage_all_projects()) {
                        return true;
                    } else if (get_array_value($this->login_user->permissions, "can_add_remove_project_members") == "1") {
                        return true;
                    }
                }
            }
        }
    }

    private function can_create_milestones() {
        if ($this->login_user->user_type == "staff") {
            if ($this->can_manage_all_projects()) {
                return true;
            } else if (get_array_value($this->login_user->permissions, "can_create_milestones") == "1") {
                //check is user a project member
                return $this->is_user_a_project_member;
            }
        }
    }

    private function can_edit_milestones() {
        if ($this->login_user->user_type == "staff") {
            if ($this->can_manage_all_projects()) {
                return true;
            } else if (get_array_value($this->login_user->permissions, "can_edit_milestones") == "1") {
                //check is user a project member
                return $this->is_user_a_project_member;
            }
        }
    }

    private function can_delete_milestones() {
        if ($this->login_user->user_type == "staff") {
            if ($this->can_manage_all_projects()) {
                return true;
            } else if (get_array_value($this->login_user->permissions, "can_delete_milestones") == "1") {
                //check is user a project member
                return $this->is_user_a_project_member;
            }
        }
    }

    private function can_delete_files($uploaded_by = 0) {
        if ($this->login_user->user_type == "staff") {
            if ($this->can_manage_all_projects()) {
                return true;
            } else if (get_array_value($this->login_user->permissions, "can_delete_files") == "1") {
                //check is user a project member
                return $this->is_user_a_project_member;
            }
        } else {
            if (get_setting("client_can_delete_own_files_in_project") && $this->login_user->id == $uploaded_by) {
                return true;
            }
        }
    }

    private function can_view_files() {
        if ($this->login_user->user_type == "staff") {
            if ($this->can_manage_all_projects()) {
                return true;
            } else if ($this->can_add_files() || get_array_value($this->login_user->permissions, "can_view_files")) {
                return true;
            }
        } else {
            //check settings for client's project permission
            if (get_setting("client_can_view_project_files")) {
                return $this->is_clients_project;
            }
        }
    }

    private function can_add_files() {
        if ($this->login_user->user_type == "staff") {
            if ($this->can_manage_all_projects()) {
                return true;
            } else if (get_array_value($this->login_user->permissions, "can_upload_and_edit_files")) {
                return true;
            }
        } else {
            //check settings for client's project permission
            if (get_setting("client_can_add_project_files")) {
                return $this->is_clients_project;
            }
        }
    }

    private function can_comment_on_files() {
        if ($this->login_user->user_type == "staff") {
            if ($this->can_manage_all_projects()) {
                return true;
            } else {
                //check is user a project member
                return $this->is_user_a_project_member;
            }
        } else {
            //check settings for client's project permission
            if (get_setting("client_can_comment_on_files")) {
                //even the settings allow to create/edit task, the client can only comment on their own project's files
                return $this->is_clients_project;
            }
        }
    }

    private function can_view_gantt() {
        //check gantt module
        if (get_setting("module_gantt")) {
            if ($this->login_user->user_type == "staff") {
                if ($this->can_manage_all_projects()) {
                    return true;
                } else {
                    //check is user a project member
                    return $this->is_user_a_project_member;
                }
            } else {
                //check settings for client's project permission
                if (get_setting("client_can_view_gantt")) {
                    //even the settings allow to view gantt, the client can only view on their own project's gantt
                    return $this->is_clients_project;
                }
            }
        }
    }

    /* load project view */

    function index() {
        app_redirect("projects/all_projects");
    }

    function all_projects($status_id = 0, $project_id = 0) {
        validate_numeric_value($status_id);
        validate_numeric_value($project_id);
        $view_data['project_labels_dropdown'] = json_encode($this->make_labels_dropdown("project", "", true));

        $view_data["can_create_projects"] = $this->can_create_projects();

        $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("projects", $this->login_user->is_admin, $this->login_user->user_type);
        $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("projects", $this->login_user->is_admin, $this->login_user->user_type);

        $view_data["selected_status_id"] = $status_id;
        $view_data['project_statuses'] = $this->Project_status_model->get_details()->getResult();

        $view_data['project_id'] = $project_id;

        if ($this->login_user->user_type === "staff") {
            $view_data["can_edit_projects"] = $this->can_edit_projects();
            $view_data["can_delete_projects"] = $this->can_delete_projects();
            $view_data["can_access_clients"] = $this->can_access_clients();

            return $this->template->rander("projects/index", $view_data);
        } else {
            if (!$this->can_client_access("project", false)) {
                app_redirect("forbidden");
            }

            $view_data['client_id'] = $this->login_user->client_id;
            $view_data['can_edit_projects'] = get_setting("client_can_edit_projects");
            $view_data['page_type'] = "full";
            return $this->template->rander("clients/projects/index", $view_data);
        }
    }

    /* load project  add/edit modal */

    function modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "client_id" => "numeric",
        ));

        $project_id = $this->request->getPost('id');
        $client_id = $this->request->getPost('client_id');

        if ($project_id) {
            if (!$this->can_edit_projects($project_id)) {
                app_redirect("forbidden");
            }
        } else {
            if (!$this->can_create_projects()) {
                app_redirect("forbidden");
            }
        }


        $view_data["client_id"] = $client_id;
        $view_data['model_info'] = $this->Projects_model->get_one($project_id);
        if ($client_id) {
            $view_data['model_info']->client_id = $client_id;
        }

        //check if it's from estimate, order or proposal
        $view_data["context"] = $this->request->getPost('context');
        $view_data["context_id"] = $this->request->getPost('context_id');

        $view_data["custom_fields"] = $this->Custom_fields_model->get_combined_details("projects", $view_data['model_info']->id, $this->login_user->is_admin, $this->login_user->user_type)->getResult();

        $view_data['hide_clients_dropdown'] = false;

        if (!$this->login_user->is_admin && !get_array_value($this->login_user->permissions, "client") && !get_array_value($this->login_user->permissions, "client_specific")) {
            //user can't access clients. don't show clients dropdown
            $view_data['clients_dropdown'] = json_encode(array());
            $view_data['hide_clients_dropdown'] = true;
        } else {
            $dropdown_list = new Dropdown_list($this);
            $view_data['clients_dropdown'] = $dropdown_list->get_clients_id_and_text_dropdown();
        }

        $view_data['label_suggestions'] = $this->make_labels_dropdown("project", $view_data['model_info']->labels);
        $view_data['statuses'] = $this->Project_status_model->get_details()->getResult();
        $view_data["can_edit_projects"] = $this->can_edit_projects();

        return $this->template->view('projects/modal_form', $view_data);
    }

    /* insert or update a project */

    function save() {
        $this->validate_submitted_data(array(
            "title" => "required",
            "id" => "numeric",
            "context_id" => "numeric",
        ));

        $id = $this->request->getPost('id');

        if ($id) {
            if (!$this->can_edit_projects($id)) {
                app_redirect("forbidden");
            }
        } else {
            if (!$this->can_create_projects()) {
                app_redirect("forbidden");
            }
        }

        $status_id = $this->request->getPost('status_id');
        $project_type = $this->request->getPost('project_type');

        $labels = $this->request->getPost('labels');
        validate_list_of_numbers($labels);

        $data = array(
            "title" => $this->request->getPost('title'),
            "description" => $this->request->getPost('description'),
            "client_id" => ($project_type === "internal_project") ? 0 : $this->request->getPost('client_id'),
            "start_date" => $this->request->getPost('start_date'),
            "deadline" => $this->request->getPost('deadline'),
            "project_type" => $project_type,
            "price" => unformat_currency($this->request->getPost('price')),
            "labels" => $labels,
            "status_id" => $status_id ? $status_id : 1
        );

        $context = $this->request->getPost('context');
        $context_id_key = $context . "_id";
        $context_id = $this->request->getPost("context_id");

        if (!$id) {
            $data["created_date"] = get_current_utc_time();
            $data["created_by"] = $this->login_user->id;

            if ($context && $context_id) {
                $data["$context_id_key"] = $context_id;
            }
        }

        //created by client? overwrite the client id for safety
        if ($this->login_user->user_type === "clinet") {
            $data["client_id"] = $this->login_user->client_id;
        }

        $data = clean_data($data);

        //set null value after cleaning the data
        if (!$data["start_date"]) {
            $data["start_date"] = NULL;
        }

        if (!$data["deadline"]) {
            $data["deadline"] = NULL;
        }

        $save_id = $this->Projects_model->ci_save($data, $id);
        if ($save_id) {

            save_custom_fields("projects", $save_id, $this->login_user->is_admin, $this->login_user->user_type);

            //send notification
            if ($status_id == 2) {
                log_notification("project_completed", array("project_id" => $save_id));
            }

            if (!$id) {

                if ($this->login_user->user_type === "staff") {
                    //this is a new project and created by team members
                    //add default project member after project creation
                    $data = array(
                        "project_id" => $save_id,
                        "user_id" => $this->login_user->id,
                        "is_leader" => 1
                    );
                    $this->Project_members_model->save_member($data);
                }

                //created from estimate? save the project id
                if ($context == "estimate") {
                    $data = array("project_id" => $save_id);
                    $this->Estimates_model->ci_save($data, $context_id);
                }

                //created from order? save the project id
                if ($context == "order") {
                    $data = array("project_id" => $save_id);
                    $this->Orders_model->ci_save($data, $context_id);
                }

                //created from proposal? save the project id
                if ($context == "proposal") {
                    $data = array("project_id" => $save_id);
                    $this->Proposals_model->ci_save($data, $context_id);
                }

                log_notification("project_created", array("project_id" => $save_id));
            }
            echo json_encode(array("success" => true, "data" => $this->_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /* Show a modal to clone a project */

    function clone_project_modal_form() {

        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $project_id = $this->request->getPost('id');

        if (!$this->can_create_projects()) {
            app_redirect("forbidden");
        }


        $view_data['model_info'] = $this->Projects_model->get_one($project_id);


        $dropdown_list = new Dropdown_list($this);
        $view_data['clients_dropdown'] = $dropdown_list->get_clients_id_and_text_dropdown();

        $view_data['label_suggestions'] = $this->make_labels_dropdown("project", $view_data['model_info']->labels);

        $view_data["custom_fields"] = $this->Custom_fields_model->get_combined_details("projects", $view_data['model_info']->id, 1, "staff")->getResult(); //we have to keep this regarding as an admin user because non-admin user also can acquire the access to clone a project

        return $this->template->view('projects/clone_project_modal_form', $view_data);
    }

    /* create a new project from another project */

    function save_cloned_project() {

        ini_set('max_execution_time', 300); //300 seconds 

        $project_id = $this->request->getPost('project_id');
        $project_start_date = $this->request->getPost('start_date');

        if (!$this->can_create_projects()) {
            app_redirect("forbidden");
        }

        $this->validate_submitted_data(array(
            "title" => "required",
            "project_id" => "numeric",
        ));

        $copy_same_assignee_and_collaborators = $this->request->getPost("copy_same_assignee_and_collaborators");
        $copy_milestones = $this->request->getPost("copy_milestones");
        $change_the_milestone_dates_based_on_project_start_date = $this->request->getPost("change_the_milestone_dates_based_on_project_start_date");
        $move_all_tasks_to_to_do = $this->request->getPost("move_all_tasks_to_to_do");
        $copy_tasks_start_date_and_deadline = $this->request->getPost("copy_tasks_start_date_and_deadline");
        $change_the_tasks_start_date_and_deadline_based_on_project_start_date = $this->request->getPost("change_the_tasks_start_date_and_deadline_based_on_project_start_date");
        $project_type = $this->request->getPost('project_type');

        $labels = $this->request->getPost('labels');
        validate_list_of_numbers($labels);

        //prepare new project data
        $now = get_current_utc_time();
        $data = array(
            "title" => $this->request->getPost('title'),
            "description" => $this->request->getPost('description'),
            "client_id" => ($project_type === "internal_project") ? 0 : $this->request->getPost('client_id'),
            "start_date" => $project_start_date,
            "deadline" => $this->request->getPost('deadline'),
            "project_type" => $project_type,
            "price" => unformat_currency($this->request->getPost('price')),
            "created_date" => $now,
            "created_by" => $this->login_user->id,
            "labels" => $labels,
            "status_id" => 1,
        );

        if (!$data["start_date"]) {
            $data["start_date"] = NULL;
        }

        if (!$data["deadline"]) {
            $data["deadline"] = NULL;
        }


        //add new project
        $new_project_id = $this->Projects_model->ci_save($data);

        //old project info
        $old_project_info = $this->Projects_model->get_one($project_id);

        //add milestones
        //when the new milestones will be created the ids will be different. so, we have to convert the milestone ids. 
        $milestones_array = array();

        if ($copy_milestones) {
            $milestones = $this->Milestones_model->get_all_where(array("project_id" => $project_id, "deleted" => 0))->getResult();
            foreach ($milestones as $milestone) {
                $old_milestone_id = $milestone->id;

                //prepare new milestone data. remove id from existing data
                $milestone->project_id = $new_project_id;
                $milestone_data = (array) $milestone;
                unset($milestone_data["id"]);

                //add new milestone and keep a relation with new id and old id
                $milestones_array[$old_milestone_id] = $this->Milestones_model->ci_save($milestone_data);
            }
        } else if ($change_the_milestone_dates_based_on_project_start_date && $old_project_info->start_date && $project_start_date) {
            $milestones = $this->Milestones_model->get_all_where(array("project_id" => $project_id, "deleted" => 0))->getResult();
            foreach ($milestones as $milestone) {
                $old_milestone_id = $milestone->id;

                //prepare new milestone data. remove id from existing data
                $milestone->project_id = $new_project_id;

                $old_project_start_date = $old_project_info->start_date;
                $old_milestone_due_date = $milestone->due_date;

                $milestone_due_date_day_diff = get_date_difference_in_days($old_milestone_due_date, $old_project_start_date);
                $milestone->due_date = add_period_to_date($project_start_date, $milestone_due_date_day_diff, "days");

                $milestone_data = (array) $milestone;
                unset($milestone_data["id"]);

                //add new milestone and keep a relation with new id and old id
                $milestones_array[$old_milestone_id] = $this->Milestones_model->ci_save($milestone_data);
            }
        }

        //we'll keep all new task ids vs old task ids. by this way, we'll add the checklist easily 
        $task_ids = array();

        //add tasks
        //first, save tasks whose are not sub tasks 
        $tasks = $this->Tasks_model->get_all_where(array("project_id" => $project_id, "deleted" => 0, "parent_task_id" => 0))->getResult();
        foreach ($tasks as $task) {
            $task_data = $this->_prepare_new_task_data_on_cloning_project($new_project_id, $milestones_array, $task, $copy_same_assignee_and_collaborators, $copy_tasks_start_date_and_deadline, $move_all_tasks_to_to_do, $change_the_tasks_start_date_and_deadline_based_on_project_start_date, $old_project_info, $project_start_date);

            //add new task
            $new_taks_id = $this->Tasks_model->ci_save($task_data);

            //bind old id with new
            $task_ids[$task->id] = $new_taks_id;

            //save custom fields of task
            $this->_save_custom_fields_on_cloning_project($task, $new_taks_id);
        }

        //secondly, save sub tasks
        $tasks = $this->Tasks_model->get_all_where(array("project_id" => $project_id, "deleted" => 0, "parent_task_id !=" => 0))->getResult();
        foreach ($tasks as $task) {
            $task_data = $this->_prepare_new_task_data_on_cloning_project($new_project_id, $milestones_array, $task, $copy_same_assignee_and_collaborators, $copy_tasks_start_date_and_deadline, $move_all_tasks_to_to_do, $change_the_tasks_start_date_and_deadline_based_on_project_start_date, $old_project_info, $project_start_date);
            //add parent task
            $task_data["parent_task_id"] = $task_ids[$task->parent_task_id];

            //add new task
            $new_taks_id = $this->Tasks_model->ci_save($task_data);

            //bind old id with new
            $task_ids[$task->id] = $new_taks_id;

            //save custom fields of task
            $this->_save_custom_fields_on_cloning_project($task, $new_taks_id);
        }

        //save task dependencies
        $tasks = $this->Tasks_model->get_all_tasks_where_have_dependency($project_id)->getResult();
        foreach ($tasks as $task) {
            if (array_key_exists($task->id, $task_ids)) {
                //save blocked by tasks 
                if ($task->blocked_by) {
                    //find the newly created tasks
                    $new_blocked_by_tasks = "";
                    $blocked_by_tasks_array = explode(',', $task->blocked_by);
                    foreach ($blocked_by_tasks_array as $blocked_by_task) {
                        if (array_key_exists($blocked_by_task, $task_ids)) {
                            if ($new_blocked_by_tasks) {
                                $new_blocked_by_tasks .= "," . $task_ids[$blocked_by_task];
                            } else {
                                $new_blocked_by_tasks = $task_ids[$blocked_by_task];
                            }
                        }
                    }

                    //update newly created task
                    if ($new_blocked_by_tasks) {
                        $blocked_by_task_data = array("blocked_by" => $new_blocked_by_tasks);
                        $this->Tasks_model->ci_save($blocked_by_task_data, $task_ids[$task->id]);
                    }
                }

                //save blocking tasks 
                if ($task->blocking) {
                    //find the newly created tasks
                    $new_blocking_tasks = "";
                    $blocking_tasks_array = explode(',', $task->blocking);
                    foreach ($blocking_tasks_array as $blocking_task) {
                        if (array_key_exists($blocking_task, $task_ids)) {
                            if ($new_blocking_tasks) {
                                $new_blocking_tasks .= "," . $task_ids[$blocking_task];
                            } else {
                                $new_blocking_tasks = $task_ids[$blocking_task];
                            }
                        }
                    }

                    //update newly created task
                    if ($new_blocking_tasks) {
                        $blocking_task_data = array("blocking" => $new_blocking_tasks);
                        $this->Tasks_model->ci_save($blocking_task_data, $task_ids[$task->id]);
                    }
                }
            }
        }

        //add project members
        $project_members = $this->Project_members_model->get_all_where(array("project_id" => $project_id, "deleted" => 0))->getResult();

        foreach ($project_members as $project_member) {
            //prepare new project member data. remove id from existing data
            $project_member->project_id = $new_project_id;
            $project_member_data = (array) $project_member;
            unset($project_member_data["id"]);

            $project_member_data["user_id"] = $project_member->user_id;

            $this->Project_members_model->save_member($project_member_data);
        }

        //add check lists
        $check_lists = $this->Checklist_items_model->get_all_checklist_of_project($project_id)->getResult();
        foreach ($check_lists as $list) {
            if (array_key_exists($list->task_id, $task_ids)) {
                $checklist_data = array(
                    "title" => $list->title,
                    "task_id" => $task_ids[$list->task_id],
                    "is_checked" => 0
                );

                $this->Checklist_items_model->ci_save($checklist_data);
            }
        }

        $project_settings = $this->Project_settings_model->get_details(array("project_id" => $project_id))->getResult();
        foreach ($project_settings as $project_setting) {
            $setting = $project_setting->setting_name;
            $value = $project_setting->setting_value;
            if (!$value) {
                $value = "";
            }

            $this->Project_settings_model->save_setting($new_project_id, $setting, $value);
        }

        if ($new_project_id) {
            //save custom fields of project
            save_custom_fields("projects", $new_project_id, 1, "staff"); //we have to keep this regarding as an admin user because non-admin user also can acquire the access to clone a project

            log_notification("project_created", array("project_id" => $new_project_id));

            echo json_encode(array("success" => true, 'id' => $new_project_id, 'message' => app_lang('project_cloned_successfully')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    private function _prepare_new_task_data_on_cloning_project($new_project_id, $milestones_array, $task, $copy_same_assignee_and_collaborators, $copy_tasks_start_date_and_deadline, $move_all_tasks_to_to_do, $change_the_tasks_start_date_and_deadline_based_on_project_start_date, $old_project_info, $project_start_date) {
        //prepare new task data. 
        $task->project_id = $new_project_id;
        $milestone_id = get_array_value($milestones_array, $task->milestone_id);
        $task->milestone_id = $milestone_id ? $milestone_id : "";
        $task->status = "to_do";

        if (!$copy_same_assignee_and_collaborators) {
            $task->assigned_to = "";
            $task->collaborators = "";
        }

        $task_data = (array) $task;
        unset($task_data["id"]); //remove id from existing data

        if ($move_all_tasks_to_to_do) {
            $task_data["status"] = "to_do";
            $task_data["status_id"] = 1;
        }

        $task_data["created_by"] = $this->login_user->id;

        if (!$copy_tasks_start_date_and_deadline && !$change_the_tasks_start_date_and_deadline_based_on_project_start_date) {
            $task->start_date = NULL;
            $task->deadline = NULL;
        } else if ($change_the_tasks_start_date_and_deadline_based_on_project_start_date && $old_project_info->start_date && $project_start_date) {
            $old_project_start_date = $old_project_info->start_date;
            $old_task_start_date = $task->start_date;
            $old_task_end_date = $task->deadline;

            if ($old_task_start_date) {
                $start_date_day_diff = get_date_difference_in_days($old_task_start_date, $old_project_start_date);
                $task_data["start_date"] = add_period_to_date($project_start_date, $start_date_day_diff, "days");
            } else {
                $task_data["start_date"] = NULL;
            }

            if ($old_task_end_date) {
                $end_date_day_diff = get_date_difference_in_days($old_task_end_date, $old_project_start_date);
                $task_data["deadline"] = add_period_to_date($project_start_date, $end_date_day_diff, "days");
            } else {
                $task_data["deadline"] = NULL;
            }
        }

        return $task_data;
    }

    private function _save_custom_fields_on_cloning_project($task, $new_taks_id) {
        $old_custom_fields = $this->Custom_field_values_model->get_all_where(array("related_to_type" => "tasks", "related_to_id" => $task->id, "deleted" => 0))->getResult();

        //prepare new custom fields data
        foreach ($old_custom_fields as $field) {
            $field->related_to_id = $new_taks_id;

            $fields_data = (array) $field;
            unset($fields_data["id"]); //remove id from existing data
            //save custom field
            $this->Custom_field_values_model->ci_save($fields_data);
        }
    }

    /* delete a project */

    function delete() {
        $id = $this->request->getPost('id');

        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        if (!$this->can_delete_projects($id)) {
            app_redirect("forbidden");
        }

        if ($this->Projects_model->delete_project_and_sub_items($id)) {
            log_notification("project_deleted", array("project_id" => $id));

            try {
                app_hooks()->do_action("app_hook_data_delete", array(
                    "id" => $id,
                    "table" => get_db_prefix() . "projects",
                    "table_without_prefix" => "projects",
                ));
            } catch (\Exception $ex) {
                log_message('error', '[ERROR] {exception}', ['exception' => $ex]);
            }

            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    /* list of projcts, prepared for datatable  */

    function list_data($is_mobile = 0) {
        validate_numeric_value($is_mobile);
        $this->access_only_team_members();

        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("projects", $this->login_user->is_admin, $this->login_user->user_type);

        $status_ids = $this->request->getPost('status_id') ? implode(",", $this->request->getPost('status_id')) : "";

        $options = array(
            "status_ids" => $status_ids,
            "project_label" => $this->request->getPost("project_label"),
            "custom_fields" => $custom_fields,
            "start_date_from" => $this->request->getPost("start_date_from"),
            "start_date_to" => $this->request->getPost("start_date_to"),
            "deadline" => $this->request->getPost('deadline'),
            "custom_field_filter" => $this->prepare_custom_field_filter_values("projects", $this->login_user->is_admin, $this->login_user->user_type)
        );

        //only admin/ the user has permission to manage all projects, can see all projects, other team mebers can see only their own projects.
        if (!$this->can_manage_all_projects()) {
            $options["user_id"] = $this->login_user->id;
        }

        $all_options = append_server_side_filtering_commmon_params($options);

        $result = $this->Projects_model->get_details($all_options);

        //by this, we can handel the server side or client side from the app table prams.
        if (get_array_value($all_options, "server_side")) {
            $list_data = get_array_value($result, "data");
        } else {
            $list_data = $result->getResult();
            $result = array();
        }

        $result_data = array();
        foreach ($list_data as $data) {
            $result_data[] = $this->_make_row($data, $custom_fields, $is_mobile);
        }

        $result["data"] = $result_data;

        echo json_encode($result);
    }

    /* list of projcts, prepared for datatable  */

    function projects_list_data_of_team_member($team_member_id = 0) {
        validate_numeric_value($team_member_id);
        $this->access_only_team_members();

        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("projects", $this->login_user->is_admin, $this->login_user->user_type);

        $status_ids = $this->request->getPost('status_id') ? implode(",", $this->request->getPost('status_id')) : "";

        $options = array(
            "status_ids" => $status_ids,
            "start_date_from" => $this->request->getPost("start_date_from"),
            "start_date_to" => $this->request->getPost("start_date_to"),
            "custom_fields" => $custom_fields,
            "custom_field_filter" => $this->prepare_custom_field_filter_values("projects", $this->login_user->is_admin, $this->login_user->user_type)
        );

        //add can see all members projects but team members can see only ther own projects
        if (!$this->can_manage_all_projects() && $team_member_id != $this->login_user->id) {
            app_redirect("forbidden");
        }

        $options["user_id"] = $team_member_id;

        $list_data = $this->Projects_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data, $custom_fields);
        }
        echo json_encode(array("data" => $result));
    }

    function projects_list_data_of_client($client_id = 0) {
        validate_numeric_value($client_id);

        $this->access_only_team_members_or_client_contact($client_id);

        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("projects", $this->login_user->is_admin, $this->login_user->user_type);

        $status_ids = $this->request->getPost('status_id') ? implode(",", $this->request->getPost('status_id')) : "";

        $options = array(
            "client_id" => $client_id,
            "status_ids" => $status_ids,
            "project_label" => $this->request->getPost("project_label"),
            "custom_fields" => $custom_fields,
            "custom_field_filter" => $this->prepare_custom_field_filter_values("projects", $this->login_user->is_admin, $this->login_user->user_type)
        );

        $list_data = $this->Projects_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data, $custom_fields, 0, false);
        }
        echo json_encode(array("data" => $result));
    }

    /* return a row of project list  table */

    private function _row_data($id) {
        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("projects", $this->login_user->is_admin, $this->login_user->user_type);

        $options = array(
            "id" => $id,
            "custom_fields" => $custom_fields
        );

        $data = $this->Projects_model->get_details($options)->getRow();
        return $this->_make_row($data, $custom_fields);
    }

    /* prepare a row of project list table */

    private function _make_row($data, $custom_fields, $is_mobile = 0, $compact_view = true) {

        $progress = $data->total_points ? round(($data->completed_points / $data->total_points) * 100) : 0;

        $class = "bg-primary";
        if ($progress == 100) {
            $class = "progress-bar-success";
        }

        $progress_bar = "<div class='progress' title='$progress%'>
            <div  class='progress-bar $class' role='progressbar' aria-valuenow='$progress' aria-valuemin='0' aria-valuemax='100' style='width: $progress%'>
            </div>
            <span class='hide'>$progress%</span>
        </div>";
        $start_date = is_date_exists($data->start_date) ? format_to_date($data->start_date, false) : "-";
        $dateline = is_date_exists($data->deadline) ? format_to_date($data->deadline, false) : "-";
        $price = $data->price ? to_currency($data->price, $data->currency_symbol) : "-";

        //has deadline? change the color of date based on status
        if (is_date_exists($data->deadline)) {
            if ($progress !== 100 && $data->status_id == 1 && get_my_local_time("Y-m-d") > $data->deadline) {
                $dateline = "<span class='text-danger mr5'>" . $dateline . "</span> ";
            } else if ($progress !== 100 && $data->status_id == 1 && get_my_local_time("Y-m-d") == $data->deadline) {
                $dateline = "<span class='text-warning mr5'>" . $dateline . "</span> ";
            }
        }

        $title = anchor(get_uri("projects/view/" . $data->id), $data->title);
        if ($data->labels_list) {
            $project_labels = make_labels_view_data($data->labels_list, true);
            $title .= "<br />" . $project_labels;
        }

        $optoins = "";

        if (!$is_mobile && $compact_view) {
            $optoins .= anchor(get_uri("projects/compact_view/" . $data->id), "<i data-feather='sidebar' class='icon-16'></i>", array("title" => "", "class" => "action-option"));
        }

        if ($this->can_edit_projects($data->id)) {
            $optoins .= modal_anchor(get_uri("projects/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_project'), "data-post-id" => $data->id));
        }

        if ($this->can_delete_projects($data->id)) {
            $optoins .= js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_project'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("projects/delete"), "data-action" => "delete-confirmation"));
        }

        //show the project price to them who has permission to create projects
        if ($this->login_user->user_type == "staff" && !$this->can_create_projects()) {
            $price = "-";
        }

        $client_name = "-";
        if ($data->company_name) {
            $client_name = anchor(get_uri("clients/view/" . $data->client_id), $data->company_name);
        }

        $project_status = $data->title_language_key ? app_lang($data->title_language_key) : $data->status_title;

        if ($is_mobile) {
            if ($data->labels_list) {
                $project_labels = make_labels_view_data($data->labels_list);
            }

            $title_content = "
                            <div class='text-default'>
                                <div class='clearfix'>
                                    <div class='truncate-ellipsis w-100'>
                                        <span class='fw-bold'>" . $data->title . "</span>
                                    </div>
                                </div>
                                <div>" . ($data->company_name ? $data->company_name : "") . "</div>
                                <div class='clearfix mt5'>
                                    <span class='badge badge-default no-border'>" . $project_status . "</span>" . ($data->labels_list ? $project_labels : "") . "
                                    <div class='float-end spinning-btn'></div>
                                </div>
                            </div>";

            $link = js_anchor($title_content, array(
                "class" => "box-label",
                "data-action-url" => get_uri("projects/view/" . $data->id),
                "data-action" => "load_compact_view",
                "data-compact_view_id" => $data->id
            ));

            $title = "<div class='box-wrapper mini-list-item'>" . $link . "</div>";
        }

        $row_data = array(
            anchor(get_uri("projects/view/" . $data->id), $data->id),
            $title,
            $client_name,
            $price,
            $data->start_date,
            $start_date,
            $data->deadline,
            $dateline,
            $progress_bar,
            $project_status
        );

        foreach ($custom_fields as $field) {
            $cf_id = "cfv_" . $field->id;
            $row_data[] = $this->template->view("custom_fields/output_" . $field->field_type, array("value" => $data->$cf_id));
        }

        $row_data[] = $optoins;

        return $row_data;
    }

    /* load project details view */

    function view($project_id = 0, $tab = "", $folder_id = 0) {
        validate_numeric_value($project_id);
        $this->init_project_permission_checker($project_id);

        $view_data = $this->_get_project_info_data($project_id);

        $view_data["show_invoice_info"] = (get_setting("module_invoice") && $this->can_view_invoices()) ? true : false;

        $expense_access_info = $this->get_access_info("expense");
        $view_data["show_expense_info"] = (get_setting("module_expense") && $expense_access_info->access_type == "all") ? true : false;

        $access_contract = $this->get_access_info("contract");
        $view_data["show_contract_info"] = (get_setting("module_contract") && $access_contract->access_type == "all") ? true : false;

        $view_data["show_note_info"] = (get_setting("module_note")) ? true : false;

        $view_data["show_project_timer"] = get_setting("module_project_timesheet") ? true : false;

        $this->init_project_settings($project_id);
        $view_data["show_timesheet_info"] = $this->can_view_timesheet($project_id);

        $view_data["show_tasks"] = true;

        $view_data["show_gantt_info"] = $this->can_view_gantt();
        $view_data["show_milestone_info"] = $this->can_view_milestones();

        if ($this->login_user->user_type === "client") {
            if (!$this->can_client_access("project", false)) {
                app_redirect("forbidden");
            }

            $view_data["show_project_timer"] = false;
            $view_data["show_tasks"] = $this->client_can_view_tasks();

            if (!get_setting("client_can_edit_projects")) {
                $view_data["show_actions_dropdown"] = false;
            }

            $view_data["show_invoice_info"] = $this->can_client_access("invoice") ? true : false;
        }

        $view_data["show_files"] = $this->can_view_files();
        $view_data["can_comment_on_projects"] = $this->can_comment_on_projects();

        $view_data["tab"] = clean_data($tab);

        $view_data["files_tab"] = "";
        $view_data["folder_id"] = $folder_id;
        if ($tab == "file_manager") {
            $view_data["files_tab"] = "file_manager";
        }

        $view_data["is_starred"] = strpos($view_data['project_info']->starred_by, ":" . $this->login_user->id . ":") ? true : false;

        $view_data['can_edit_timesheet_settings'] = $this->can_edit_timesheet_settings($project_id);
        $view_data['can_edit_slack_settings'] = $this->can_edit_slack_settings();
        $view_data["can_create_projects"] = $this->can_create_projects();
        $view_data["can_edit_projects"] = $this->can_edit_projects($project_id);

        $view_data["show_actions_dropdown"] = $view_data["can_create_projects"] || $view_data["can_edit_projects"];

        $ticket_access_info = $this->get_access_info("ticket");
        $view_data["show_ticket_info"] = (get_setting("module_ticket") && get_setting("project_reference_in_tickets") && $ticket_access_info->access_type == "all") ? true : false;

        $view_data["project_statuses"] = $this->Project_status_model->get_details()->getResult();
        $view_data["show_customer_feedback"] = $this->has_client_feedback_access_permission();

        $view_type = $this->request->getPost('view_type');

        if ($view_type == "compact_view") {
            echo json_encode(array(
                "success" => true,
                "content" => $this->template->view("projects/details_view",  $view_data),
            ));
        } else {
            return $this->template->rander("projects/details_view", $view_data);
        }
    }

    private function can_edit_timesheet_settings($project_id) {
        $this->init_project_permission_checker($project_id);
        if ($project_id && $this->login_user->user_type === "staff" && $this->can_view_timesheet($project_id)) {
            return true;
        }
    }

    private function can_edit_slack_settings() {
        if ($this->login_user->user_type === "staff" && $this->can_create_projects()) {
            return true;
        }
    }

    function show_my_starred_projects() {
        $view_data["projects"] = $this->Projects_model->get_starred_projects($this->login_user->id)->getResult();
        return $this->template->view('projects/star/projects_list', $view_data);
    }

    /* load project overview section */

    function overview($project_id) {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        $view_data = $this->_get_project_info_data($project_id);
        $view_data["task_statuses"] = $this->Tasks_model->get_task_statistics(array("project_id" => $project_id))->task_statuses;

        $view_data['project_id'] = $project_id;
        $offset = 0;
        $view_data['offset'] = $offset;
        $view_data['activity_logs_params'] = array("log_for" => "project", "log_for_id" => $project_id, "limit" => 20, "offset" => $offset);

        $view_data["can_add_remove_project_members"] = $this->can_add_remove_project_members();
        $view_data["can_access_clients"] = $this->can_access_clients(true);

        $view_data['custom_fields_list'] = $this->Custom_fields_model->get_combined_details("projects", $project_id, $this->login_user->is_admin, $this->login_user->user_type)->getResult();

        //count total worked hours
        $options = array("project_id" => $project_id);

        //get allowed member ids
        $members = $this->_get_members_to_manage_timesheet();
        if ($members != "all") {
            //if user has permission to access all members, query param is not required
            $options["allowed_members"] = $members;
        }

        $info = $this->Timesheets_model->count_total_time($options);
        $view_data["total_project_hours"] = to_decimal_format($info->timesheet_total / 60 / 60);

        return $this->template->view('projects/overview', $view_data);
    }

    /* add-remove start mark from project */

    function add_remove_star($project_id, $type = "add") {
        if ($project_id) {
            validate_numeric_value($project_id);

            if (get_setting("disable_access_favorite_project_option_for_clients") && $this->login_user->user_type == "client") {
                app_redirect("forbidden");
            }

            $view_data["project_id"] = $project_id;

            if ($type === "add") {
                $this->Projects_model->add_remove_star($project_id, $this->login_user->id, $type = "add");
                return $this->template->view('projects/star/starred', $view_data);
            } else {
                $this->Projects_model->add_remove_star($project_id, $this->login_user->id, $type = "remove");
                return $this->template->view('projects/star/not_starred', $view_data);
            }
        }
    }

    /* load project overview section */

    function overview_for_client($project_id) {
        validate_numeric_value($project_id);
        if ($this->login_user->user_type === "client") {
            $view_data = $this->_get_project_info_data($project_id);

            $view_data['project_id'] = $project_id;

            $offset = 0;
            $view_data['offset'] = $offset;
            $view_data['show_activity'] = false;
            $view_data['show_overview'] = false;
            $view_data['activity_logs_params'] = array();

            $this->init_project_permission_checker($project_id);
            $this->init_project_settings($project_id);
            $view_data["show_timesheet_info"] = $this->can_view_timesheet($project_id);

            $options = array("project_id" => $project_id);
            $timesheet_info = $this->Timesheets_model->count_total_time($options);
            $view_data["total_project_hours"] = to_decimal_format($timesheet_info->timesheet_total / 60 / 60);

            if (get_setting("client_can_view_overview")) {
                $view_data['show_overview'] = true;
                $view_data["task_statuses"] = $this->Tasks_model->get_task_statistics(array("project_id" => $project_id))->task_statuses;

                if (get_setting("client_can_view_activity")) {
                    $view_data['show_activity'] = true;
                    $view_data['activity_logs_params'] = array("log_for" => "project", "log_for_id" => $project_id, "limit" => 20, "offset" => $offset);
                }
            }

            $view_data['custom_fields_list'] = $this->Custom_fields_model->get_combined_details("projects", $project_id, $this->login_user->is_admin, $this->login_user->user_type)->getResult();

            return $this->template->view('projects/overview_for_client', $view_data);
        }
    }

    /* load project members add/edit modal */

    function project_member_modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "project_id" => "numeric"
        ));

        $view_data['model_info'] = $this->Project_members_model->get_one($this->request->getPost('id'));
        $project_id = $this->request->getPost('project_id') ? $this->request->getPost('project_id') : $view_data['model_info']->project_id;
        $this->init_project_permission_checker($project_id);

        if (!$this->can_add_remove_project_members()) {
            app_redirect("forbidden");
        }

        $view_data['project_id'] = $project_id;

        $view_data["view_type"] = $this->request->getPost("view_type");

        $add_user_type = $this->request->getPost("add_user_type");

        $users_dropdown = array();
        if ($add_user_type == "client_contacts") {
            if (!$this->can_access_clients(true)) {
                app_redirect("forbidden");
            }

            $contacts = $this->Project_members_model->get_client_contacts_of_the_project_client($project_id)->getResult();
            foreach ($contacts as $contact) {
                $users_dropdown[$contact->id] = $contact->contact_name;
            }
        } else {
            $users = $this->Project_members_model->get_rest_team_members_for_a_project($project_id)->getResult();
            foreach ($users as $user) {
                $users_dropdown[$user->id] = $user->member_name;
            }
        }

        $view_data["users_dropdown"] = $users_dropdown;
        $view_data["add_user_type"] = $add_user_type;

        return $this->template->view('projects/project_members/modal_form', $view_data);
    }

    /* add a project members  */

    function save_project_member() {
        $project_id = $this->request->getPost('project_id');

        $this->init_project_permission_checker($project_id);

        if (!$this->can_add_remove_project_members()) {
            app_redirect("forbidden");
        }

        $this->validate_submitted_data(array(
            "user_id.*" => "required"
        ));

        $user_ids = $this->request->getPost('user_id');

        $save_ids = array();
        $already_exists = false;

        if ($user_ids) {
            foreach ($user_ids as $user_id) {
                if ($user_id) {
                    $data = array(
                        "project_id" => $project_id,
                        "user_id" => $user_id
                    );

                    $save_id = $this->Project_members_model->save_member($data);
                    if ($save_id && $save_id != "exists") {
                        $save_ids[] = $save_id;
                        log_notification("project_member_added", array("project_id" => $project_id, "to_user_id" => $user_id));
                    } else if ($save_id === "exists") {
                        $already_exists = true;
                    }
                }
            }
        }


        if (!count($save_ids) && $already_exists) {
            //this member already exists.
            echo json_encode(array("success" => true, 'id' => "exists"));
        } else if (count($save_ids)) {
            $project_member_row = array();
            foreach ($save_ids as $id) {
                $project_member_row[] = $this->_project_member_row_data($id);
            }
            echo json_encode(array("success" => true, "data" => $project_member_row, 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /* delete/undo a project members  */

    function delete_project_member() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $project_member_info = $this->Project_members_model->get_one($id);

        $this->init_project_permission_checker($project_member_info->project_id);
        if (!$this->can_add_remove_project_members()) {
            app_redirect("forbidden");
        }


        if ($this->request->getPost('undo')) {
            if ($this->Project_members_model->delete($id, true)) {
                echo json_encode(array("success" => true, "data" => $this->_project_member_row_data($id), "message" => app_lang('record_undone')));
            } else {
                echo json_encode(array("success" => false, app_lang('error_occurred')));
            }
        } else {
            if ($this->Project_members_model->delete($id)) {

                $project_member_info = $this->Project_members_model->get_one($id);

                log_notification("project_member_deleted", array("project_id" => $project_member_info->project_id, "to_user_id" => $project_member_info->user_id));
                echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
            }
        }
    }

    /* list of project members, prepared for datatable  */

    function project_member_list_data($project_id = 0, $user_type = "") {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $this->init_project_permission_checker($project_id);

        //show the message icon to client contacts list only if the user can send message to client. 
        $can_send_message_to_client = false;
        $client_message_users = get_setting("client_message_users");
        $client_message_users_array = explode(",", $client_message_users);
        if (in_array($this->login_user->id, $client_message_users_array)) {

            $can_send_message_to_client = true;
        }

        $options = array("project_id" => $project_id, "user_type" => $user_type, "show_user_wise" => true);
        $list_data = $this->Project_members_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_project_member_row($data, $can_send_message_to_client);
        }
        echo json_encode(array("data" => $result));
    }

    /* return a row of project member list */

    private function _project_member_row_data($id) {
        $options = array("id" => $id);
        $data = $this->Project_members_model->get_details($options)->getRow();
        return $this->_make_project_member_row($data);
    }

    /* prepare a row of project member list */

    private function _make_project_member_row($data, $can_send_message_to_client = false) {
        $member_image = "<span class='avatar avatar-sm'><img src='" . get_avatar($data->member_image) . "' alt='...'></span> ";

        if ($data->user_type == "staff") {
            $member = get_team_member_profile_link($data->user_id, $member_image);
            $member_name = get_team_member_profile_link($data->user_id, $data->member_name, array("class" => "dark strong"));
        } else {
            $member = get_client_contact_profile_link($data->user_id, $member_image);
            $member_name = get_client_contact_profile_link($data->user_id, $data->member_name, array("class" => "dark strong"));
        }

        $link = "";

        //check message module availability and show message button
        if (get_setting("module_message") && ($this->login_user->id != $data->user_id)) {
            $link = modal_anchor(get_uri("messages/modal_form/" . $data->user_id), "<i data-feather='mail' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('send_message')));
        }

        //check message icon permission for client contacts
        if (!$can_send_message_to_client && $data->user_type === "client") {
            $link = "";
        }


        if ($this->can_add_remove_project_members()) {
            $delete_link = js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_member'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("projects/delete_project_member"), "data-action" => "delete"));

            if (!$this->can_manage_all_projects() && ($this->login_user->id === $data->user_id)) {
                $delete_link = "";
            }
            $link .= $delete_link;
        }

        $member = '<div class="d-flex"><div class="p-2 flex-shrink-1">' . $member . '</div><div class="p-2 w-100"><div>' . $member_name . '</div><label class="text-off">' . $data->job_title . '</label></div></div>';

        return array($member, $link);
    }

    /* get all projects list */

    private function _get_all_projects_dropdown_list() {
        $projects = $this->Projects_model->get_dropdown_list(array("title"));

        $projects_dropdown = array(array("id" => "", "text" => "- " . app_lang("project") . " -"));
        foreach ($projects as $id => $title) {
            $projects_dropdown[] = array("id" => $id, "text" => $title);
        }
        return $projects_dropdown;
    }

    /* load milestones view */

    function milestones($project_id) {
        validate_numeric_value($project_id);
        $this->init_project_permission_checker($project_id);

        if (!$this->can_view_milestones()) {
            app_redirect("forbidden");
        }

        $view_data['project_id'] = $project_id;

        $view_data["can_create_milestones"] = $this->can_create_milestones();
        $view_data["can_edit_milestones"] = $this->can_edit_milestones();
        $view_data["can_delete_milestones"] = $this->can_delete_milestones();

        return $this->template->view("projects/milestones/index", $view_data);
    }

    /* load milestone add/edit modal */

    function milestone_modal_form() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $view_data['model_info'] = $this->Milestones_model->get_one($this->request->getPost('id'));
        $project_id = $this->request->getPost('project_id') ? $this->request->getPost('project_id') : $view_data['model_info']->project_id;

        $this->init_project_permission_checker($project_id);

        if ($id) {
            if (!$this->can_edit_milestones()) {
                app_redirect("forbidden");
            }
        } else {
            if (!$this->can_create_milestones()) {
                app_redirect("forbidden");
            }
        }

        $view_data['project_id'] = $project_id;

        return $this->template->view('projects/milestones/modal_form', $view_data);
    }

    /* insert/update a milestone */

    function save_milestone() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $project_id = $this->request->getPost('project_id');

        $this->init_project_permission_checker($project_id);

        if ($id) {
            if (!$this->can_edit_milestones()) {
                app_redirect("forbidden");
            }
        } else {
            if (!$this->can_create_milestones()) {
                app_redirect("forbidden");
            }
        }

        $data = array(
            "title" => $this->request->getPost('title'),
            "description" => $this->request->getPost('description'),
            "project_id" => $this->request->getPost('project_id'),
            "due_date" => $this->request->getPost('due_date')
        );
        $save_id = $this->Milestones_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_milestone_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /* delete/undo a milestone */

    function delete_milestone() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');
        $info = $this->Milestones_model->get_one($id);
        $this->init_project_permission_checker($info->project_id);

        if (!$this->can_delete_milestones()) {
            app_redirect("forbidden");
        }

        if ($this->request->getPost('undo')) {
            if ($this->Milestones_model->delete($id, true)) {
                echo json_encode(array("success" => true, "data" => $this->_milestone_row_data($id), "message" => app_lang('record_undone')));
            } else {
                echo json_encode(array("success" => false, app_lang('error_occurred')));
            }
        } else {
            if ($this->Milestones_model->delete($id)) {
                echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
            }
        }
    }

    /* list of milestones, prepared for datatable  */

    function milestones_list_data($project_id = 0) {
        validate_numeric_value($project_id);
        $this->init_project_permission_checker($project_id);

        $options = array("project_id" => $project_id);
        $list_data = $this->Milestones_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_milestone_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    /* return a row of milestone list  table */

    private function _milestone_row_data($id) {
        $options = array("id" => $id);
        $data = $this->Milestones_model->get_details($options)->getRow();
        $this->init_project_permission_checker($data->project_id);

        return $this->_make_milestone_row($data);
    }

    /* prepare a row of milestone list table */

    private function _make_milestone_row($data) {

        //calculate milestone progress
        $progress = $data->total_points ? round(($data->completed_points / $data->total_points) * 100) : 0;
        $class = "bg-primary";
        if ($progress == 100) {
            $class = "progress-bar-success";
        }

        $total_tasks = $data->total_tasks ? $data->total_tasks : 0;
        $completed_tasks = $data->completed_tasks ? $data->completed_tasks : 0;

        $progress_bar = "<div class='ml10 mr10 clearfix'><span class='float-start'>$completed_tasks/$total_tasks</span><span class='float-end'>$progress%</span></div><div class='progress mt0' title='$progress%'>
            <div  class='progress-bar $class' role='progressbar' aria-valuenow='$progress' aria-valuemin='0' aria-valuemax='100' style='width: $progress%'>
            </div>
        </div>";

        //define milesone color based on due date
        $due_date = date("L", strtotime($data->due_date));
        $label_class = "";
        if ($progress == 100) {
            $label_class = "bg-success";
        } else if ($progress !== 100 && get_my_local_time("Y-m-d") > $data->due_date) {
            $label_class = "bg-danger";
        } else if ($progress !== 100 && get_my_local_time("Y-m-d") == $data->due_date) {
            $label_class = "bg-warning";
        } else {
            $label_class = "bg-primary";
        }

        $day_or_year_name = "";
        if (date("Y", strtotime(get_current_utc_time())) === date("Y", strtotime($data->due_date))) {
            $day_or_year_name = app_lang(strtolower(date("l", strtotime($data->due_date)))); //get day name from language
        } else {
            $day_or_year_name = date("Y", strtotime($data->due_date)); //get current year
        }

        $month_name = app_lang(strtolower(date("F", strtotime($data->due_date)))); //get month name from language

        $due_date = "<div class='milestone float-start' title='" . format_to_date($data->due_date) . "'>
            <span class='badge $label_class'>" . $month_name . "</span>
            <h1>" . date("d", strtotime($data->due_date)) . "</h1>
            <span>" . $day_or_year_name . "</span>
            </div>
            ";

        $optoins = "";
        if ($this->can_edit_milestones()) {
            $optoins .= modal_anchor(get_uri("projects/milestone_modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_milestone'), "data-post-id" => $data->id));
        }

        if ($this->can_delete_milestones()) {
            $optoins .= js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_milestone'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("projects/delete_milestone"), "data-action" => "delete"));
        }


        $title = "<div><b>" . $data->title . "</b></div>";
        if ($data->description) {
            $title .= "<div>" . custom_nl2br($data->description) . "<div>";
        }

        return array(
            $data->due_date,
            $due_date,
            $title,
            $progress_bar,
            $optoins
        );
    }

    /* load comments view */

    function comments($project_id) {
        validate_numeric_value($project_id);
        $this->access_only_team_members();

        $options = array("project_id" => $project_id, "login_user_id" => $this->login_user->id);
        $view_data['comments'] = $this->Project_comments_model->get_details($options)->getResult();
        $view_data['project_id'] = $project_id;

        return $this->template->view("projects/comments/index", $view_data);
    }

    /* load comments view */

    function customer_feedback($project_id) {
        if ($this->login_user->user_type == "staff") {
            if (!$this->has_client_feedback_access_permission()) {
                app_redirect("forbidden");
            }
        }

        validate_numeric_value($project_id);
        $options = array("customer_feedback_id" => $project_id, "login_user_id" => $this->login_user->id); //customer feedback id and project id is same
        $view_data['comments'] = $this->Project_comments_model->get_details($options)->getResult();
        $view_data['customer_feedback_id'] = $project_id;
        $view_data['project_id'] = $project_id;
        return $this->template->view("projects/comments/index", $view_data);
    }

    /* save project comments */

    function save_comment() {
        $this->validate_submitted_data(array(
            "id" => "numeric"
        ));

        $id = $this->request->getPost('id');

        $target_path = get_setting("timeline_file_path");
        $files_data = move_files_from_temp_dir_to_permanent_dir($target_path, "project_comment");

        $project_id = $this->request->getPost('project_id');
        $file_id = $this->request->getPost('file_id');
        $customer_feedback_id = $this->request->getPost('customer_feedback_id');
        $comment_id = $this->request->getPost('comment_id');
        $description = $this->request->getPost('description');

        $comment_type = $this->request->getPost('comment_type');

        if ($customer_feedback_id && $this->login_user->user_type == "staff") {
            if (!$this->has_client_feedback_access_permission()) {
                app_redirect("forbidden");
            }
        }

        if ($comment_type === "project") {
            if (!$this->can_comment_on_projects()) {
                app_redirect("forbidden");
            }
        }

        $data = array(
            "created_by" => $this->login_user->id,
            "created_at" => get_current_utc_time(),
            "project_id" => $project_id,
            "file_id" => $file_id ? $file_id : 0,
            "task_id" => 0,
            "customer_feedback_id" => $customer_feedback_id ? $customer_feedback_id : 0,
            "comment_id" => $comment_id ? $comment_id : 0,
            "description" => $description
        );

        $data = clean_data($data);

        $data["files"] = $files_data; //don't clean serilized data

        $save_id = $this->Project_comments_model->save_comment($data, $id);
        if ($save_id) {
            $response_data = "";
            $options = array("id" => $save_id, "login_user_id" => $this->login_user->id);

            if ($this->request->getPost("reload_list")) {
                $view_data['comments'] = $this->Project_comments_model->get_details($options)->getResult();
                $response_data = $this->template->view("projects/comments/comment_list", $view_data);
            }
            echo json_encode(array("success" => true, "data" => $response_data, 'message' => app_lang('comment_submited')));

            $comment_info = $this->Project_comments_model->get_one($save_id);

            $notification_options = array("project_id" => $comment_info->project_id, "project_comment_id" => $save_id);

            if ($comment_info->file_id) { //file comment
                $notification_options["project_file_id"] = $comment_info->file_id;
                log_notification("project_file_commented", $notification_options);
            } else if ($comment_info->customer_feedback_id) {  //customer feedback comment
                if ($comment_id) {
                    log_notification("project_customer_feedback_replied", $notification_options);
                } else {
                    log_notification("project_customer_feedback_added", $notification_options);
                }
            } else {  //project comment
                if ($comment_id) {
                    log_notification("project_comment_replied", $notification_options);
                } else {
                    log_notification("project_comment_added", $notification_options);
                }
            }
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function delete_comment($id = 0) {

        if (!$id) {
            exit();
        }

        $comment_info = $this->Project_comments_model->get_one($id);

        //only admin and creator can delete the comment
        if (!($this->login_user->is_admin || $comment_info->created_by == $this->login_user->id)) {
            app_redirect("forbidden");
        }


        //delete the comment and files
        if ($this->Project_comments_model->delete($id) && $comment_info->files) {

            //delete the files
            $file_path = get_setting("timeline_file_path");
            $files = unserialize($comment_info->files);

            foreach ($files as $file) {
                delete_app_files($file_path, array($file));
            }
        }
    }

    /* load all replies of a comment */

    function view_comment_replies($comment_id) {
        validate_numeric_value($comment_id);
        $view_data['reply_list'] = $this->Project_comments_model->get_details(array("comment_id" => $comment_id))->getResult();
        return $this->template->view("projects/comments/reply_list", $view_data);
    }

    /* show comment reply form */

    function comment_reply_form($comment_id, $type = "project", $type_id = 0) {
        validate_numeric_value($comment_id);
        validate_numeric_value($type_id);

        $view_data['comment_id'] = $comment_id;

        if ($type === "project") {
            $view_data['project_id'] = $type_id;
        } else if ($type === "task") {
            $view_data['task_id'] = $type_id;
        } else if ($type === "file") {
            $view_data['file_id'] = $type_id;
        } else if ($type == "customer_feedback") {
            $view_data['project_id'] = $type_id;
        }
        return $this->template->view("projects/comments/reply_form", $view_data);
    }

    /* load files view */

    function files($project_id, $view_type = "", $folder_id = 0) {
        validate_numeric_value($project_id);

        $this->init_project_permission_checker($project_id);

        if (!$this->can_view_files()) {
            app_redirect("forbidden");
        }

        $view_data['can_add_files'] = $this->can_add_files();
        $options = array("project_id" => $project_id);
        $view_data['files'] = $this->Project_files_model->get_details($options)->getResult();
        $view_data['project_id'] = $project_id;

        $file_categories = $this->File_category_model->get_details()->getResult();
        $file_categories_dropdown = array(array("id" => "", "text" => "- " . app_lang("category") . " -"));

        if ($file_categories) {
            foreach ($file_categories as $file_category) {
                $file_categories_dropdown[] = array("id" => $file_category->id, "text" => $file_category->name);
            }
        }

        $view_data["file_categories_dropdown"] = json_encode($file_categories_dropdown);

        $view_data["tab"] = "";
        $view_data["folder_id"] = $folder_id;
        if ($view_type == "file_manager") {
            $view_data["tab"] = "file_manager";
        }

        $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("project_files", $this->login_user->is_admin, $this->login_user->user_type);
        $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("project_files", $this->login_user->is_admin, $this->login_user->user_type);

        return $this->template->view("projects/files/index", $view_data);
    }

    function view_file($file_id = 0) {
        validate_numeric_value($file_id);

        $file_info = $this->_get_file_info($file_id);

        if ($file_info) {

            $view_data = get_file_preview_common_data($file_info, $this->_get_file_path($file_info));

            $options = array("file_id" => $file_id, "login_user_id" => $this->login_user->id);
            $view_data['can_comment_on_files'] = $this->can_comment_on_files();
            $view_data['comments'] = $this->Project_comments_model->get_details($options)->getResult();
            $view_data['project_id'] = $file_info->project_id;
            $view_data['current_url'] = get_uri("projects/view_file/" . $file_id);
            return $this->template->view("projects/files/view", $view_data);
        } else {
            show_404();
        }
    }

    /* file upload modal */

    function file_modal_form() {

        $this->validate_submitted_data(array(
            "id" => "numeric",
            "project_id" => "numeric",
            "context_id" => "numeric",
            "folder_id" => "numeric",
        ));

        $view_data['model_info'] = $this->Project_files_model->get_one($this->request->getPost('id'));

        $project_id = $this->request->getPost('project_id') ? $this->request->getPost('project_id') : $view_data['model_info']->project_id;
        if (!$project_id  && $this->request->getPost('context_id')) {
            $project_id = $this->request->getPost('context_id');
        }

        $view_data["custom_fields"] = $this->Custom_fields_model->get_combined_details("project_files", $view_data['model_info']->id, $this->login_user->is_admin, $this->login_user->user_type)->getResult();

        $this->init_project_permission_checker($project_id);

        if (!$this->can_add_files()) {
            app_redirect("forbidden");
        }


        $view_data['project_id'] = $project_id;
        $view_data['folder_id'] =  $this->request->getPost('folder_id');

        $file_categories = $this->File_category_model->get_details()->getResult();
        $file_categories_dropdown = array("" => "-");

        if ($file_categories) {
            foreach ($file_categories as $file_category) {
                $file_categories_dropdown[$file_category->id] = $file_category->name;
            }
        }

        $view_data["file_categories_dropdown"] = $file_categories_dropdown;

        return $this->template->view('projects/files/modal_form', $view_data);
    }


    private function _check_project_file_add_edit_permission($file_id = 0, $project_id = 0) {

        if ($file_id) {
            $file_info = $this->Project_files_model->get_one($file_id);
            $project_id =  $file_info->project_id;
        }

        $this->init_project_permission_checker($project_id);

        if (!$this->can_add_files()) {
            app_redirect("forbidden");
        }
    }


    /* save project file data and move temp file to parmanent file directory */

    function save_file() {

        $this->validate_submitted_data(array(
            "project_id" => "numeric|required",
            "folder_id" => "numeric",
            "id" => "numeric",
        ));

        $id = $this->request->getPost('id');
        $project_id = $this->request->getPost('project_id');
        $category_id = $this->request->getPost('category_id');
        $folder_id = $this->request->getPost('folder_id');

        $this->_check_project_file_add_edit_permission($id, $project_id);

        $success = false;

        if ($id) {
            $data = array(
                "description" => $this->request->getPost('description'),
                "category_id" => $category_id ? $category_id : 0
            );

            $success = $this->Project_files_model->ci_save($data, $id);
            save_custom_fields("project_files", $success, $this->login_user->is_admin, $this->login_user->user_type);
        } else {
            $now = get_current_utc_time();
            $target_path = getcwd() . "/" . get_setting("project_file_path") . $project_id . "/";
            $files = $this->request->getPost("files");

            //process the fiiles which has been uploaded by dropzone
            if ($files && get_array_value($files, 0)) {
                foreach ($files as $file) {
                    $file_name = $this->request->getPost('file_name_' . $file);
                    $file_info = move_temp_file($file_name, $target_path, "");
                    if ($file_info) {
                        $data = array(
                            "project_id" => $project_id,
                            "file_name" => get_array_value($file_info, 'file_name'),
                            "file_id" => get_array_value($file_info, 'file_id'),
                            "service_type" => get_array_value($file_info, 'service_type'),
                            "description" => $this->request->getPost('description_' . $file),
                            "file_size" => $this->request->getPost('file_size_' . $file),
                            "created_at" => $now,
                            "uploaded_by" => $this->login_user->id,
                            "category_id" => $category_id ? $category_id : 0,
                            "folder_id" => $folder_id ? $folder_id : 0,
                        );

                        $data = clean_data($data);

                        $success = $this->Project_files_model->ci_save($data);
                        save_custom_fields("project_files", $success, $this->login_user->is_admin, $this->login_user->user_type);
                        log_notification("project_file_added", array("project_id" => $project_id, "project_file_id" => $success));
                    } else {
                        $success = false;
                    }
                }
            }

            //process the files which has been submitted manually
            if ($_FILES) {
                $files = $_FILES['manualFiles'];
                if ($files && count($files) > 0) {
                    $description = $this->request->getPost('description');
                    foreach ($files["tmp_name"] as $key => $file) {
                        $temp_file = $file;
                        $file_name = $files["name"][$key];
                        $file_size = $files["size"][$key];

                        $file_info = move_temp_file($file_name, $target_path, "", $temp_file);
                        if ($file_info) {
                            $data = array(
                                "project_id" => $project_id,
                                "file_name" => get_array_value($file_info, 'file_name'),
                                "file_id" => get_array_value($file_info, 'file_id'),
                                "service_type" => get_array_value($file_info, 'service_type'),
                                "description" => get_array_value($description, $key),
                                "file_size" => $file_size,
                                "created_at" => $now,
                                "uploaded_by" => $this->login_user->id,
                                "category_id" => $category_id ? $category_id : 0,
                                "folder_id" => $folder_id ? $folder_id : 0,
                            );
                            $success = $this->Project_files_model->ci_save($data);
                            save_custom_fields("project_files", $success, $this->login_user->is_admin, $this->login_user->user_type);
                            log_notification("project_file_added", array("project_id" => $project_id, "project_file_id" => $success));
                        }
                    }
                }
            }
        }

        if ($success) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    /* delete a file */

    function delete_file() {
        $id = $this->request->getPost('id');

        $this->validate_submitted_data(array(
            "id" => "numeric|required"
        ));

        if ($this->_delete_file($id)) {
            echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    /* download a file */

    function download_file($id) {
        validate_numeric_value($id);

        $file_info = $this->Project_files_model->get_one($id);

        $this->init_project_permission_checker($file_info->project_id);
        if (!$this->can_view_files()) {
            app_redirect("forbidden");
        }

        //serilize the path
        $file_data = serialize(array(array("file_name" => $file_info->project_id . "/" . $file_info->file_name, "file_id" => $file_info->file_id, "service_type" => $file_info->service_type)));

        //delete the file
        return $this->download_app_files(get_setting("project_file_path"), $file_data);
    }

    /* download multiple files as zip */

    function download_multiple_files($files_ids = "") {

        if ($files_ids) {

            validate_list_of_numbers($files_ids);
            $files_ids_array = explode('-', $files_ids);

            $files = $this->Project_files_model->get_files($files_ids_array);

            if ($files) {
                $file_path_array = array();
                $project_id = 0;

                foreach ($files->getResult() as $file_info) {

                    //we have to check the permission for each file
                    //initialize the permission check only if the project id is different

                    if ($project_id != $file_info->project_id) {
                        $this->init_project_permission_checker($file_info->project_id);
                        $project_id = $file_info->project_id;
                    }

                    if (!$this->can_view_files()) {
                        app_redirect("forbidden");
                    }

                    $file_path_array[] = array("file_name" => $file_info->project_id . "/" . $file_info->file_name, "file_id" => $file_info->file_id, "service_type" => $file_info->service_type);
                }

                $serialized_file_data = serialize($file_path_array);

                return $this->download_app_files(get_setting("project_file_path"), $serialized_file_data);
            }
        }
    }

    /* download files by zip */

    function download_comment_files($id) {

        validate_numeric_value($id);

        $info = $this->Project_comments_model->get_one($id);

        $this->init_project_permission_checker($info->project_id);
        if ($this->login_user->user_type == "client" && !$this->is_clients_project) {
            app_redirect("forbidden");
        } else if ($this->login_user->user_type == "user" && !$this->is_user_a_project_member) {
            app_redirect("forbidden");
        }

        return $this->download_app_files(get_setting("timeline_file_path"), $info->files);
    }

    /* list of files, prepared for datatable  */

    function files_list_data($project_id = 0) {
        validate_numeric_value($project_id);
        $this->init_project_permission_checker($project_id);

        if (!$this->can_view_files()) {
            app_redirect("forbidden");
        }

        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("project_files", $this->login_user->is_admin, $this->login_user->user_type);

        $options = array(
            "project_id" => $project_id,
            "category_id" => $this->request->getPost("category_id"),
            "custom_fields" => $custom_fields,
            "custom_field_filter" => $this->prepare_custom_field_filter_values("project_files", $this->login_user->is_admin, $this->login_user->user_type)
        );

        $list_data = $this->Project_files_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_file_row($data, $custom_fields);
        }
        echo json_encode(array("data" => $result));
    }

    /* prepare a row of file list table */

    private function _make_file_row($data, $custom_fields) {
        $file_ext = strtolower(pathinfo($data->file_name, PATHINFO_EXTENSION));
        $file_icon = get_file_icon($file_ext, false);

        if (in_array($file_ext, array("jpg", "jpeg", "png"))) {
            $thumbnail = get_file_thumbnail_preview($data, $this->_get_file_path($data), true);
            if ($thumbnail) {
                $file_icon = "<img src='$thumbnail' class='file-thumb'>";
            }
        }

        $image_url = get_avatar($data->uploaded_by_user_image);
        $uploaded_by = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt='...'></span> $data->uploaded_by_user_name";

        if ($data->uploaded_by_user_type == "staff") {
            $uploaded_by = get_team_member_profile_link($data->uploaded_by, $uploaded_by);
        } else {
            $uploaded_by = get_client_contact_profile_link($data->uploaded_by, $uploaded_by);
        }

        $description = "<div class='float-start text-wrap text-break-space'>" .
            js_anchor(remove_file_prefix($data->file_name), array('title' => "", "data-toggle" => "app-modal", "data-sidebar" => "1", "data-group" => "project-files", "data-url" => get_uri("projects/view_file/" . $data->id)));

        if ($data->description) {
            $description .= "<div class='text-wrap'>" . $data->description . "</div></div>";
        } else {
            $description .= "</div>";
        }

        //show checkmark to download multiple files
        $checkmark = js_anchor("<span class='checkbox-blank mr15 float-start'></span>", array('title' => "", "class" => "", "data-id" => $data->id, "data-act" => "download-multiple-file-checkbox")) . $data->id;

        $row_data = array(
            $checkmark,
            $file_icon . $description,
            $data->category_name ? $data->category_name : "-",
            convert_file_size($data->file_size),
            $uploaded_by,
            format_to_datetime($data->created_at)
        );

        foreach ($custom_fields as $field) {
            $cf_id = "cfv_" . $field->id;
            $row_data[] = $this->template->view("custom_fields/output_" . $field->field_type, array("value" => $data->$cf_id));
        }

        $options = anchor(get_uri("projects/download_file/" . $data->id), "<i data-feather='download-cloud' class='icon-16'></i>", array("title" => app_lang("download")));
        if ($this->can_add_files()) {
            $options .= modal_anchor(get_uri("projects/file_modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_files'), "data-post-id" => $data->id));
        }
        if ($this->can_delete_files($data->uploaded_by)) {
            $options .= js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_file'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("projects/delete_file"), "data-action" => "delete-confirmation"));
        }

        $row_data[] = $options;

        return $row_data;
    }

    /* load notes view */

    function notes($project_id) {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $view_data['project_id'] = $project_id;
        return $this->template->view("projects/notes/index", $view_data);
    }

    /* load history view */

    function history($offset = 0, $log_for = "", $log_for_id = "", $log_type = "", $log_type_id = "") {
        if ($this->login_user->user_type !== "staff" && ($this->login_user->user_type == "client" && get_setting("client_can_view_activity") !== "1")) {
            app_redirect("forbidden");
        }

        $view_data['offset'] = $offset;
        $view_data['activity_logs_params'] = array("log_for" => $log_for, "log_for_id" => $log_for_id, "log_type" => $log_type, "log_type_id" => $log_type_id, "limit" => 20, "offset" => $offset);
        return $this->template->view("projects/history/index", $view_data);
    }

    /* load project members view */

    function members($project_id = 0) {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        $view_data['project_id'] = $project_id;
        return $this->template->view("projects/project_members/index", $view_data);
    }

    /* load payments tab  */

    function payments($project_id) {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        if ($project_id) {
            $view_data['project_info'] = $this->Projects_model->get_details(array("id" => $project_id))->getRow();
            $view_data['project_id'] = $project_id;
            return $this->template->view("projects/payments/index", $view_data);
        }
    }

    /* load invoices tab  */

    function invoices($project_id, $client_id = 0) {
        $this->access_only_team_members_or_client_contact($client_id);
        validate_numeric_value($client_id);
        validate_numeric_value($project_id);
        if ($project_id) {
            $view_data['project_id'] = $project_id;
            $view_data['project_info'] = $this->Projects_model->get_details(array("id" => $project_id))->getRow();

            $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("invoices", $this->login_user->is_admin, $this->login_user->user_type);
            $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("invoices", $this->login_user->is_admin, $this->login_user->user_type);

            $view_data["can_edit_invoices"] = $this->can_edit_invoices();

            return $this->template->view("projects/invoices/index", $view_data);
        }
    }

    /* load expenses tab  */

    function expenses($project_id) {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        if ($project_id) {
            $view_data['project_id'] = $project_id;

            $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("expenses", $this->login_user->is_admin, $this->login_user->user_type);
            $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("expenses", $this->login_user->is_admin, $this->login_user->user_type);

            return $this->template->view("projects/expenses/index", $view_data);
        }
    }

    //save project status
    function change_status($project_id, $status_id) {
        if ($project_id && $this->can_edit_projects() && $status_id) {
            validate_numeric_value($project_id);
            validate_numeric_value($status_id);
            $status_data = array("status_id" => $status_id);
            $save_id = $this->Projects_model->ci_save($status_data, $project_id);

            //send notification
            if ($status_id == 2) {
                log_notification("project_completed", array("project_id" => $save_id));
            }
        }
    }

    /* load project settings modal */

    function settings_modal_form() {
        $this->validate_submitted_data(array(
            "project_id" => "numeric"
        ));

        $project_id = $this->request->getPost('project_id');

        $can_edit_timesheet_settings = $this->can_edit_timesheet_settings($project_id);
        $can_edit_slack_settings = $this->can_edit_slack_settings();
        $can_create_projects = $this->can_create_projects();

        if (!$project_id || !($can_edit_timesheet_settings || $can_edit_slack_settings || $can_create_projects)) {
            app_redirect("forbidden");
        }


        $this->init_project_settings($project_id);

        $view_data['project_id'] = $project_id;
        $view_data['can_edit_timesheet_settings'] = $can_edit_timesheet_settings;
        $view_data['can_edit_slack_settings'] = $can_edit_slack_settings;
        $view_data["can_create_projects"] = $this->can_create_projects();

        $task_statuses_dropdown = array();
        $task_statuses = $this->Task_status_model->get_details()->getResult();
        foreach ($task_statuses as $task_status) {
            $task_statuses_dropdown[] = array("id" => $task_status->id, "text" => $task_status->key_name ? app_lang($task_status->key_name) : $task_status->title);
        }

        $view_data["task_statuses_dropdown"] = json_encode($task_statuses_dropdown);
        $view_data["project_info"] = $this->Projects_model->get_one($project_id);

        return $this->template->view('projects/settings/modal_form', $view_data);
    }

    /* save project settings */

    function save_settings() {
        $this->validate_submitted_data(array(
            "project_id" => "numeric"
        ));

        $project_id = $this->request->getPost('project_id');

        $can_edit_timesheet_settings = $this->can_edit_timesheet_settings($project_id);
        $can_edit_slack_settings = $this->can_edit_slack_settings();
        $can_create_projects = $this->can_create_projects();

        if (!$project_id || !($can_edit_timesheet_settings || $can_edit_slack_settings || $can_create_projects)) {
            app_redirect("forbidden");
        }

        $this->validate_submitted_data(array(
            "project_id" => "required|numeric"
        ));

        $settings = array();
        if ($can_edit_timesheet_settings) {
            $settings[] = "client_can_view_timesheet";
        }

        if ($can_edit_slack_settings) {
            $settings[] = "project_enable_slack";
            $settings[] = "project_slack_webhook_url";
        }

        if ($can_create_projects) {
            $settings[] = "remove_task_statuses";
        }

        foreach ($settings as $setting) {
            $value = $this->request->getPost($setting);
            if (!$value) {
                $value = "";
            }

            $value = clean_data($value);

            $this->Project_settings_model->save_setting($project_id, $setting, $value);
        }

        //send test message
        if ($can_edit_slack_settings && $this->request->getPost("send_a_test_message")) {
            helper('notifications');
            if (send_slack_notification("test_slack_notification", $this->login_user->id, 0, $this->request->getPost("project_slack_webhook_url"))) {
                echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('slack_notification_error_message')));
            }
        } else {
            echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
        }
    }

    /* get member suggestion with start typing '@' */

    function get_member_suggestion_to_mention() {

        $this->validate_submitted_data(array(
            "project_id" => "required|numeric"
        ));

        $project_id = $this->request->getPost("project_id");

        $can_access_client = ($this->login_user->user_type == "client") ? true : $this->can_access_clients(true);

        $user_ids = array();
        if (get_array_value($this->login_user->permissions, "hide_team_members_list_from_dropdowns") == "1") {
            $user_ids[] = $this->login_user->id;
        }

        $project_members = $this->Project_members_model->get_project_members_dropdown_list($project_id, $user_ids, $can_access_client)->getResult();
        $project_members_dropdown = array();
        foreach ($project_members as $member) {
            $project_members_dropdown[] = array("name" => $member->member_name, "content" => "@[" . $member->member_name . " :" . $member->user_id . "]");
        }

        if ($project_members_dropdown) {
            echo json_encode(array("success" => TRUE, "data" => $project_members_dropdown));
        } else {
            echo json_encode(array("success" => FALSE));
        }
    }

    //reset projects dropdown on changing of client 
    function get_projects_of_selected_client_for_filter() {
        $this->access_only_team_members();
        $this->validate_submitted_data(array(
            "client_id" => "numeric"
        ));

        $client_id = $this->request->getPost("client_id");
        if ($client_id) {
            $projects = $this->Projects_model->get_all_where(array("client_id" => $client_id, "deleted" => 0), 0, 0, "title")->getResult();
            $projects_dropdown = array(array("id" => "", "text" => "- " . app_lang("project") . " -"));
            foreach ($projects as $project) {
                $projects_dropdown[] = array("id" => $project->id, "text" => $project->title);
            }
            echo json_encode($projects_dropdown);
        } else {
            //we have show all projects by de-selecting client
            echo json_encode($this->_get_all_projects_dropdown_list());
        }
    }

    function like_comment($comment_id = 0) {
        if ($comment_id) {
            validate_numeric_value($comment_id);
            $data = array(
                "project_comment_id" => $comment_id,
                "created_by" => $this->login_user->id
            );

            $existing = $this->Likes_model->get_one_where(array_merge($data, array("deleted" => 0)));
            if ($existing->id) {
                //liked already, unlike now
                $this->Likes_model->delete($existing->id);
            } else {
                //not liked, like now
                $data["created_at"] = get_current_utc_time();
                $this->Likes_model->ci_save($data);
            }

            $options = array("id" => $comment_id, "login_user_id" => $this->login_user->id);
            $comment = $this->Project_comments_model->get_details($options)->getRow();

            return $this->template->view("projects/comments/like_comment", array("comment" => $comment));
        }
    }

    /* load contracts tab  */

    function contracts($project_id) {
        validate_numeric_value($project_id);
        $this->access_only_team_members();
        if ($project_id) {
            $view_data['project_id'] = $project_id;
            $view_data['project_info'] = $this->Projects_model->get_details(array("id" => $project_id))->getRow();

            $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("contracts", $this->login_user->is_admin, $this->login_user->user_type);
            $view_data["custom_field_filters"] = $this->Custom_fields_model->get_custom_field_filters("contracts", $this->login_user->is_admin, $this->login_user->user_type);

            return $this->template->view("projects/contracts/index", $view_data);
        }
    }

    // pin/unpin comments
    function pin_comment($comment_id = 0, $task_id = 0) {
        if ($comment_id) {
            validate_numeric_value($comment_id);
            $data = array(
                "project_comment_id" => $comment_id,
                "pinned_by" => $this->login_user->id
            );

            $existing = $this->Pin_comments_model->get_one_where(array_merge($data, array("deleted" => 0)));

            $save_id = "";
            if ($existing->id) {
                //pinned already, unpin now
                $save_id = $this->Pin_comments_model->delete($existing->id);
            } else {
                //not pinned, pin now
                $data["created_at"] = get_current_utc_time();
                $save_id = $this->Pin_comments_model->ci_save($data);
            }

            if ($save_id) {
                $pinned_comments = $this->Pin_comments_model->get_details(array("id" => $save_id, "task_id" => $task_id, "pinned_by" => $this->login_user->id))->getResult();

                $save_data = $this->template->view("lib/pin_comments/comments_list", array("pinned_comments" => $pinned_comments));
                echo json_encode(array("success" => true, "data" => $save_data, "status" => "pinned"));
            } else {
                echo json_encode(array("success" => false));
            }
        }
    }

    /* load tickets tab  */

    function tickets($project_id, $client_id = 0) {
        $this->access_only_team_members_or_client_contact($client_id);
        if ($project_id) {
            validate_numeric_value($project_id);
            validate_numeric_value($client_id);
            $view_data['project_id'] = $project_id;

            $view_data["custom_field_headers"] = $this->Custom_fields_model->get_custom_field_headers_for_table("tickets", $this->login_user->is_admin, $this->login_user->user_type);

            return $this->template->view("projects/tickets/index", $view_data);
        }
    }

    function file_category($project_id = 0) {
        $this->access_only_team_members();
        validate_numeric_value($project_id);
        $this->init_project_permission_checker($project_id);
        if (!$this->can_view_files()) {
            app_redirect("forbidden");
        }

        $view_data["project_id"] = $project_id;
        $view_data['can_add_files'] = $this->can_add_files();
        return $this->template->view("projects/files/category/index", $view_data);
    }

    function file_category_list_data($project_id = 0) {
        $this->access_only_team_members();
        validate_numeric_value($project_id);
        $this->init_project_permission_checker($project_id);
        if (!$this->can_view_files()) {
            app_redirect("forbidden");
        }

        $options = array("type" => "project");
        $list_data = $this->File_category_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_file_category_row($data, $project_id);
        }

        echo json_encode(array("data" => $result));
    }

    private function _file_category_row_data($id, $project_id = 0) {
        $options = array("id" => $id);
        $data = $this->File_category_model->get_details($options)->getRow();

        return $this->_make_file_category_row($data, $project_id);
    }

    private function _make_file_category_row($data, $project_id = 0) {
        $options = "";
        if ($this->can_add_files()) {
            $options .= modal_anchor(get_uri("projects/file_category_modal_form"), "<i data-feather='edit' class='icon-16'></i>", array("class" => "edit", "title" => app_lang('edit_category'), "data-post-id" => $data->id, "data-post-project_id" => $project_id));
        }

        if ($this->can_delete_files()) {
            $options .= js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete_category'), "class" => "delete", "data-id" => $data->id, "data-action-url" => get_uri("projects/delete_file_category"), "data-action" => "delete", "data-post-project_id" => $project_id));
        }

        return array(
            $data->name,
            $options
        );
    }

    function file_category_modal_form() {
        $this->access_only_team_members();
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "project_id" => "numeric"
        ));

        $project_id = $this->request->getPost('project_id');
        $this->init_project_permission_checker($project_id);
        if (!$this->can_add_files()) {
            app_redirect("forbidden");
        }

        $view_data['model_info'] = $this->File_category_model->get_one($this->request->getPost('id'));
        $view_data['project_id'] = $project_id;
        return $this->template->view('projects/files/category/modal_form', $view_data);
    }

    function save_file_category() {
        $this->access_only_team_members();
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "project_id" => "numeric"
        ));

        $project_id = $this->request->getPost('project_id');
        $this->init_project_permission_checker($project_id);
        if (!$this->can_add_files()) {
            app_redirect("forbidden");
        }

        $id = $this->request->getPost("id");

        $data = array(
            "name" => $this->request->getPost('name'),
            "type" => "project"
        );

        $save_id = $this->File_category_model->ci_save($data, $id);

        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_file_category_row_data($save_id, $project_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
        } else {

            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function delete_file_category() {
        $this->access_only_team_members();
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "project_id" => "numeric"
        ));

        $project_id = $this->request->getPost('project_id');
        $this->init_project_permission_checker($project_id);
        if (!$this->can_delete_files()) {
            app_redirect("forbidden");
        }

        $id = $this->request->getPost('id');

        if ($this->request->getPost('undo')) {
            if ($this->File_category_model->delete($id, true)) {
                echo json_encode(array("success" => true, "data" => $this->_file_category_row_data($id, $project_id), "message" => app_lang('record_undone')));
            } else {
                echo json_encode(array("success" => false, app_lang('error_occurred')));
            }
        } else {
            if ($this->File_category_model->delete($id)) {
                echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
            } else {
                echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
            }
        }
    }

    /* delete multiple files */

    function delete_multiple_files($files_ids = "") {

        if ($files_ids) {
            validate_list_of_numbers($files_ids);

            $files_ids_array = explode('-', $files_ids);
            $files = $this->Project_files_model->get_files($files_ids_array)->getResult();
            $is_success = true;
            $is_permission_success = true;
            $project_id = get_array_value($files, 0)->project_id;
            $this->init_project_permission_checker($project_id);

            foreach ($files as $file) {

                if (!$this->can_delete_files($file->uploaded_by)) {
                    $is_permission_success = false;
                    continue; //continue to the next file
                }

                if ($this->Project_files_model->delete($file->id)) {

                    //delete the files
                    $file_path = get_setting("project_file_path");
                    delete_app_files($file_path . $file->project_id . "/", array(make_array_of_file($file)));

                    log_notification("project_file_deleted", array("project_id" => $file->project_id, "project_file_id" => $file->id));
                } else {
                    $is_success = false;
                }
            }

            if ($is_success && $is_permission_success) {
                echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
            } else {
                if (!$is_permission_success) {
                    echo json_encode(array("success" => false, 'message' => app_lang('file_delete_permission_error_message')));
                } else {
                    echo json_encode(array("success" => false, 'message' => app_lang('record_cannot_be_deleted')));
                }
            }
        }
    }

    private function has_client_feedback_access_permission() {
        if ($this->login_user->user_type != "client") {
            if ($this->login_user->is_admin || get_array_value($this->login_user->permissions, "client_feedback_access_permission") || $this->can_manage_all_projects()) {
                return true;
            }
        }
    }

    //for old notifications, redirect to tasks/view

    function task_view($task_id = 0) {
        if ($task_id) {
            app_redirect("tasks/view/" . $task_id);
        }
    }

    function team_members_summary() {
        if (!$this->can_manage_all_projects()) {
            app_redirect("forbidden");
        }

        $view_data["project_status_text_info"] = get_project_status_text_info();
        $view_data["show_time_logged_data"] = get_setting("module_project_timesheet") ? 1 : 0;

        return $this->template->rander("projects/reports/team_members_summary", $view_data);
    }

    function team_members_summary_data() {
        if (!$this->can_manage_all_projects()) {
            app_redirect("forbidden");
        }
        $options = array(
            "start_date_from" => $this->request->getPost("start_date_from"),
            "start_date_to" => $this->request->getPost("start_date_to")
        );

        $list_data = $this->Projects_model->get_team_members_summary($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_team_members_summary_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    private function _make_team_members_summary_row($data) {
        $image_url = get_avatar($data->image);
        $member = "<span class='avatar avatar-xs mr10'><img src='$image_url' alt=''></span> $data->team_member_name";

        $duration = convert_seconds_to_time_format($data->total_secconds_worked);

        $row_data = array(
            get_team_member_profile_link($data->team_member_id, $member),
            $data->open_projects,
            $data->completed_projects,
            $data->hold_projects,
            $data->open_tasks,
            $data->completed_tasks,
            $duration,
            to_decimal_format(convert_time_string_to_decimal($duration)),
        );

        return $row_data;
    }

    function clients_summary() {
        if (!$this->can_manage_all_projects()) {
            app_redirect("forbidden");
        }
        $view_data["project_status_text_info"] = get_project_status_text_info();
        $view_data["show_time_logged_data"] = get_setting("module_project_timesheet") ? 1 : 0;

        return $this->template->view("projects/reports/clints_summary", $view_data);
    }

    function clients_summary_data() {
        if (!$this->can_manage_all_projects()) {
            app_redirect("forbidden");
        }
        $options = array(
            "start_date_from" => $this->request->getPost("start_date_from"),
            "start_date_to" => $this->request->getPost("start_date_to")
        );

        $list_data = $this->Projects_model->get_clients_summary($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_clients_summary_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    private function _make_clients_summary_row($data) {

        $client_name = anchor(get_uri("clients/view/" . $data->client_id), $data->client_name);

        $duration = convert_seconds_to_time_format($data->total_secconds_worked);

        $row_data = array(
            $client_name,
            $data->open_projects ? $data->open_projects : 0,
            $data->completed_projects ? $data->completed_projects : 0,
            $data->hold_projects ? $data->hold_projects : 0,
            $data->open_tasks ? $data->open_tasks : 0,
            $data->completed_tasks ? $data->completed_tasks : 0,
            $duration,
            to_decimal_format(convert_time_string_to_decimal($duration)),
        );

        return $row_data;
    }

    private function client_can_view_tasks() {
        if ($this->login_user->user_type != "staff") {
            //check settings for client's project permission
            if (get_setting("client_can_view_tasks")) {
                //even the settings allow to create/edit task, the client can only create their own project's tasks
                return $this->is_clients_project;
            }
        }
    }

    private function can_comment_on_projects() {
        if ($this->login_user->user_type == "staff") {
            if ($this->can_manage_all_projects() || get_array_value($this->login_user->permissions, "can_comment_on_projects")) {
                return true;
            }
        }
    }

    /* import projects */

    private function _validate_excel_import_access() {
        return $this->can_create_projects();
    }

    private function _get_controller_slag() {
        return "projects";
    }

    private function _get_custom_field_context() {
        return "projects";
    }

    private function _get_headers_for_import() {
        $this->_init_required_data_before_starting_import();

        return array(
            array("name" => "title", "required" => true, "required_message" => app_lang("import_project_error_title_field_required")),
            array("name" => "project_type", "required" => true, "required_message" => app_lang("import_project_error_project_type_field_required")),
            array("name" => "client", "custom_validation" => function ($client, $row_data) {
                //if this is client project then check if the client is exist or not

                if (get_array_value($row_data, "1") == "Client Project" && !$client) {
                    return array("error" => app_lang("import_project_error_client_field_required"));
                } else if (get_array_value($row_data, "1") == "Client Project" && $client) {
                    $client_id = get_array_value($this->clients_id_by_title, $client);
                    if (!$client_id) {
                        return array("error" => app_lang("import_project_error_client_name"));
                    }
                }
            }),
            array("name" => "description"),
            array("name" => "labels"),
            array("name" => "start_date"),
            array("name" => "deadline"),
            array("name" => "status", "required" => true, "custom_validation" => function ($status, $row_data) {
                //check project status is exist or not, if not then show error
                $status_id = get_array_value($this->project_statuses_id_by_title, $status);
                if (!$status_id) {
                    return array("error" => app_lang("import_project_error_project_status"));
                }
            }),
            array("name" => "price"),
            array("name" => "project_members", "custom_validation" => function ($project_members, $row_data) {
                //if has project member then check is the user exist or not in this name, if not then show error
                if ($project_members) {
                    $project_members_array = explode(',', $project_members);
                    foreach ($project_members_array as $project_member) {
                        $project_member_id = get_array_value($this->project_members_id_by_name, trim($project_member));
                        if (!$project_member_id) {
                            return array("error" => sprintf(app_lang("import_not_exists_error_message"), $project_member));
                        }
                    }
                }
            })
        );
    }

    function download_sample_excel_file() {
        $this->can_create_projects();
        return $this->download_app_files(get_setting("system_file_path"), serialize(array(array("file_name" => "import-projects-sample.xlsx"))));
    }

    private function _init_required_data_before_starting_import() {

        $clients = $this->Clients_model->get_clients_id_and_name()->getResult();
        $clients_id_by_title = array();
        foreach ($clients as $client) {
            $clients_id_by_title[$client->name] = $client->id;
        }

        $project_statuses = $this->Project_status_model->get_details()->getResult();
        $project_statuses_id_by_title = array();
        foreach ($project_statuses as $status) {
            $project_statuses_id_by_title[$status->title] = $status->id;
        }

        $project_labels = $this->Labels_model->get_details(array("context" => "project"))->getResult();
        $project_labels_id_by_title = array();
        foreach ($project_labels as $label) {
            $project_labels_id_by_title[$label->title] = $label->id;
        }

        $project_members = $this->Users_model->get_team_members_id_and_name()->getResult();
        $project_members_id_by_name = array();
        foreach ($project_members as $user) {
            $project_members_id_by_name[$user->user_name] = $user->id;
        }

        $this->clients_id_by_title = $clients_id_by_title;
        $this->project_statuses_id_by_title = $project_statuses_id_by_title;
        $this->project_labels_id_by_title = $project_labels_id_by_title;
        $this->project_members_id_by_name = $project_members_id_by_name;
    }

    private function _save_a_row_of_excel_data($row_data) {
        $now = get_current_utc_time();

        $project_data_array = $this->_prepare_project_data($row_data);
        $project_data = get_array_value($project_data_array, "project_data");
        $project_member_data_list = get_array_value($project_data_array, "project_member_data_list");
        $custom_field_values_array = get_array_value($project_data_array, "custom_field_values_array");

        //couldn't prepare valid data
        if (!($project_data && count($project_data) > 1)) {
            return false;
        }

        //found information about project, add some additional info
        $project_data["created_by"] = $this->login_user->id;
        $project_data["created_date"] = $now;

        //save project data
        $saved_id = $this->Projects_model->ci_save($project_data);
        if (!$saved_id) {
            return false;
        }

        //save custom fields
        $this->_save_custom_fields($saved_id, $custom_field_values_array);

        //add project id to project member data
        foreach ($project_member_data_list as $project_member_data) {
            $project_member_data["project_id"] = $saved_id;

            $this->Project_members_model->save_member($project_member_data);
        }
    }

    private function _prepare_project_data($row_data) {

        $project_data = array();
        $project_member_data_list = array();
        $custom_field_values_array = array();

        foreach ($row_data as $column_index => $value) {
            if (!$value) {
                continue;
            }

            $column_name = $this->_get_column_name($column_index);
            if ($column_name == "project_type") {
                if ($value == "Client Project") {
                    $project_data["project_type"] = "client_project";
                } else {
                    $project_data["project_type"] = "internal_project";
                }
            } else if ($column_name == "client") {
                //get existing client

                $client_id = get_array_value($this->clients_id_by_title, $value);
                if ($client_id) {
                    $project_data["client_id"] = $client_id;
                }
            } else if ($column_name == "labels") {
                if ($value) {
                    $labels = "";
                    $labels_array = explode(",", $value);
                    foreach ($labels_array as $label) {
                        $label_id = get_array_value($this->project_labels_id_by_title, trim($label));

                        if ($labels) {
                            $labels .= ",";
                        }

                        if ($label_id) {
                            $labels .= $label_id;
                        } else {
                            $label_data = array("title" => trim($label), "context" => "project", "color" => "#4A89F4");
                            $saved_label_id = $this->Labels_model->ci_save($label_data);
                            $labels .= $saved_label_id;
                            $this->project_labels_id_by_title[$value] = $saved_label_id;
                        }
                    }
                    $project_data["labels"] = $labels;
                }
            } else if ($column_name == "start_date") {
                $project_data["start_date"] = $this->_check_valid_date($value);
            } else if ($column_name == "deadline") {
                $project_data["deadline"] = $this->_check_valid_date($value);
            } else if ($column_name == "status") {
                //get existing status

                $status_id = get_array_value($this->project_statuses_id_by_title, $value);
                if ($status_id) {
                    $project_data["status_id"] = $status_id;
                }
            } else if ($column_name == "project_members") {
                if ($value) {
                    $project_members_array = explode(',', $value);
                    $count = 1;
                    foreach ($project_members_array as $project_member) {
                        $project_members_data = array();
                        $project_member_id = get_array_value($this->project_members_id_by_name, trim($project_member));

                        if ($count === 1) {
                            $project_members_data["is_leader"] = 1;
                        }
                        $project_members_data["user_id"] = $project_member_id;
                        $count++;
                        array_push($project_member_data_list, $project_members_data);
                    }
                }
            } else if (strpos($column_name, 'cf') !== false) {
                $this->_prepare_custom_field_values_array($column_name, $value, $custom_field_values_array);
            } else {
                $project_data[$column_name] = $value;
            }
        }

        return array(
            "project_data" => $project_data,
            "project_member_data_list" => $project_member_data_list,
            "custom_field_values_array" => $custom_field_values_array
        );
    }

    //used by App_folders
    private function _folder_items($folder_id = "", $context_type = "", $context_id = 0) {
        $options = array(
            "folder_id" => $folder_id,
            "context_type" => $context_type,
            "project_id" => $context_id,
            "is_admin" => $this->login_user->is_admin
        );

        return $this->Project_files_model->get_details($options)->getResult();
    }

    //used by App_folders
    private function _folder_config() {
        $info = new \stdClass();
        $info->controller_slag = "projects";
        $info->add_files_modal_url = get_uri("projects/file_modal_form");

        $info->file_preview_url = get_uri("projects/view_file");
        $info->show_file_preview_sidebar = true;
        return $info;
    }

    //used by App_folders
    private function _shareable_options() {
        return array();
    }

    //used by App_folders
    private function _get_file_path($file_info) {
        return get_setting("project_file_path") . $file_info->project_id . "/";
    }

    //used by App_folders
    private function _get_file_info($file_id) {
        $file_info = $this->Project_files_model->get_details(array("id" => $file_id))->getRow();
        if ($file_info) {
            $this->init_project_permission_checker($file_info->project_id);

            if (!$this->can_view_files()) {
                app_redirect("forbidden");
            }
            $file_info->context_id = $file_info->project_id;
            $file_info->context = "project";

            return $file_info;
        }
    }

    //used by App_folders
    private function _download_file($id) {
        return $this->download_file($id);
    }
    //used by App_folders
    private function _delete_file($id) {
        $info = $this->Project_files_model->get_one($id);

        $this->init_project_permission_checker($info->project_id);

        if (!$this->can_delete_files($info->uploaded_by)) {
            app_redirect("forbidden");
        }

        if ($this->Project_files_model->delete($id)) {

            //delete the files
            $file_path = get_setting("project_file_path");
            delete_app_files($file_path . $info->project_id . "/", array(make_array_of_file($info)));

            log_notification("project_file_deleted", array("project_id" => $info->project_id, "project_file_id" => $id));
            return true;
        }
    }

    //used by App_folders
    private function _move_file_to_another_folder($file_id, $folder_id) {

        $this->_check_project_file_add_edit_permission($file_id);

        $data = array("folder_id" => $folder_id);
        $data = clean_data($data);

        $save_id = $this->Project_files_model->ci_save($data, $file_id);

        if ($save_id) {
            echo json_encode(array("success" => true, "data" => "", 'message' => app_lang('record_saved')));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    //used by App_folders
    private function _get_all_files_of_folder($folder_id, $project_id) {

        $this->init_project_permission_checker($project_id);
        if (!$this->can_view_files()) {
            app_redirect("forbidden");
        }

        return $this->Project_files_model->get_all_where(array("folder_id" => $folder_id, "project_id" => $project_id))->getResult();
    }

    //used by App_folders

    private function _can_create_folder($parent_folder_id = 0, $context_id = 0) {
        if ($this->login_user->is_admin) {
            return true;
        } else {
            $this->init_project_permission_checker($context_id);

            if (!$this->can_add_files()) {
                return false;
            }

            // client or team members both can create folder
            if ($this->login_user->user_type == "client" && $this->login_user->client_id == $context_id) {
                return true;
            } else if ($this->login_user->user_type == "staff") {
                return true;
            }
        }
    }

    //used by App_folders
    private function _can_manage_folder($parent_folder_id = 0, $context_id = 0) {
        if ($this->login_user->is_admin) {
            return true;
        } else {
            // only team members can manage

            $this->init_project_permission_checker($context_id);

            if (!$this->can_add_files()) {
                return false;
            }

            if ($this->login_user->user_type != "staff") {
                return false;
            }

            return true;
        }
    }

    //used by App_folders
    private function _can_upload_file($folder_id = 0, $context_id = 0) {
        if ($this->login_user->is_admin) {
            return true;
        } else {
            $this->init_project_permission_checker($context_id);

            if ($this->can_add_files()) {
                return true;
            }
        }
    }

    function compact_view($project_id = 0) {
        validate_numeric_value($project_id);

        if ($this->login_user->user_type === "client") {
            app_redirect("projects/view/$project_id");
        }

        return $this->all_projects(0, $project_id);
    }

    function all_timesheets() {
        app_redirect("timesheets/index");
    }
}

/* End of file projects.php */
/* Location: ./app/controllers/projects.php */
