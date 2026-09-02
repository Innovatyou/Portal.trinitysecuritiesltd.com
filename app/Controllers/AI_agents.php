<?php

namespace App\Controllers;

use App\Controllers\Security_Controller;
use App\Libraries\AI_assistant;
use App\Libraries\Excel_import;
use App\Libraries\Dropdown_list;

class AI_agents extends Security_Controller {

    private $AI_agents_model;
    private $AI_training_data_sources_model;
    private $AI_vector_data_status_model;
    private $AI_assistant;
    private $excel_source_id = false;
    private $AI_vector_data_model;
    use Excel_import;

    function __construct() {
        parent::__construct();

        $this->access_only_admin_or_settings_admin();

        $this->AI_agents_model = model("App\Models\AI_agents_model");
        $this->AI_training_data_sources_model = model("App\Models\AI_training_data_sources_model");
        $this->AI_vector_data_status_model = model("App\Models\AI_vector_data_status_model");
        $this->AI_assistant = new AI_assistant();
        $this->AI_vector_data_model = model("App\Models\AI_vector_data_model");
    }

    function index() {
        return $this->template->rander("ai_agents/index");
    }

    function list_data() {
        $id = $this->request->getPost("id");
        validate_numeric_value($id);
        $options = array();
        if ($id) {
            $options["id"] = $id;
        }
        $list_data = $this->AI_agents_model->get_details($options)->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }
        echo json_encode(array("data" => $result));
    }

    private function _row_data($id) {
        $options = array("id" => $id);
        $data = $this->AI_agents_model->get_details($options)->getRow();
        return $this->_make_row($data);
    }

    private function _make_row($data) {

        $status = "";
        $actions = modal_anchor(get_uri("ai_agents/train_agent"), "<span data-feather='zap' class='icon-16'></span> " . app_lang("train_agent"), array("class" => "btn btn-success", "title" => app_lang("train_agent")  . ": " . $data->title, "data-post-agent_id" => $data->id, "data-modal-lg" => true));

        if ($data->status == "draft") {
            $status = "<span class='badge badge-light clickable mt0'>" . app_lang($data->status) . "</span> ";
        } else if ($data->status == "active") {
            $status = "<span class='badge bg-success clickable mt0'>" . app_lang($data->status) . "</span>";
        } else if ($data->status == "inactive") {
            $status = "<span class='badge bg-secondary clickable mt0'>" . app_lang($data->status) . "</span>";
        }

        $edit = '<li role="presentation">' . modal_anchor(get_uri("ai_agents/modal_form"), "<span data-feather='edit' class='icon-16'></span> " . app_lang("edit"), array("class" => "dropdown-item", "title" => app_lang("edit_agent"), "data-post-id" => $data->id)) . '</li>';
        $delete = '<li role="presentation">' . js_anchor("<i data-feather='x' class='icon-16'></i> " . app_lang('delete'), array('title' => app_lang('delete'), "class" => "dropdown-item", "data-id" => $data->id, "data-action-url" => get_uri("ai_agents/delete"), "data-action" => "delete-confirmation", "data-post-id" => $data->id)) . '</li>';

        $status_option = "";
        if ($data->status === "draft" || $data->status == "inactive") {
            $status_option = '<li role="presentation">' . js_anchor("<i data-feather='check-circle' class='icon-16'></i> " . app_lang('mark_as_active'), array('title' => app_lang('mark_as_active'), "class" => "dropdown-item", "data-action-url" => get_uri("ai_agents/save_agent_status/$data->id/active"), "data-action" => "update")) . '</li>';
        } else if ($data->status == "active") {
            $status_option = '<li role="presentation">' . js_anchor("<i data-feather='x-circle' class='icon-16'></i> " . app_lang('mark_as_inactive'), array('title' => app_lang('mark_as_inactive'), "class" => "dropdown-item", "data-action-url" => get_uri("ai_agents/save_agent_status/$data->id/inactive"), "data-action" => "update")) . '</li>';
        }

        if ($data->training_status === "training_pending") {
            $actions .= "<span data-feather='cloud-off' class='icon-14 ml10 text-warning'></span>";
        } else if ($data->training_status === "training_ongoing") {
            $actions .= "<span data-feather='loader' class='icon-14 ml10 text-primary'></span>";
        } else if ($data->training_status === "failed") {
            $actions .= "<span data-feather='alert-triangle' class='icon-14 ml10 text-danger'></span>";
        }

        $options = '<span class="dropdown inline-block">
                            <button class="action-option dropdown-toggle mt0 mb0" type="button" data-bs-toggle="dropdown" aria-expanded="true" data-bs-display="static">
                                <i data-feather="more-horizontal" class="icon-16"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" role="menu">' . $edit . $status_option . $delete . '</ul>
                        </span>';

        return array(
            $data->title,
            $data->description,
            $data->base_model,
            $data->created_at,
            format_to_datetime($data->created_at),
            $status,
            $actions,
            $options
        );
    }

    private function _clean_for_training($text) {
        // Normalize line endings to LF
        $text = preg_replace("/\r\n|\r/", "\n", $text);

        // Replace multiple spaces/tabs with single space
        $text = preg_replace("/[ \t]+/", ' ', $text);

        // Limit consecutive newlines to max two
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        // Trim leading/trailing whitespace
        $text = trim($text);

        // Escape backslashes and double quotes for JSON compatibility
        $text = str_replace(
            ["\\", "\""],
            ["\\\\", "\\\""],
            $text
        );

        return $text;
    }

    /**
    function start_fine_tuning() {
        $this->validate_submitted_data([
            "id" => "required|numeric",
            "base_model" => "required"
        ]);

        $id = $this->request->getPost("id");
        $data = array(
            "base_model" => $this->request->getPost("base_model")
        );

        $this->AI_agents_model->ci_save($data, $id);
        $fine_tuning_info = $this->AI_agents_model->get_one($id);

        $this->AI_assistant->create_fine_tuning_model($fine_tuning_info);
    }

    function check_fine_tuning_status() {
        $this->validate_submitted_data([
            "id" => "required|numeric"
        ]);

        $id = $this->request->getPost("id");
        $fine_tuning_info = $this->AI_agents_model->get_one($id);
        $this->AI_assistant->check_fine_tuning_status($fine_tuning_info);

        echo json_encode(array("success" => true, "data" => $this->_row_data($id), 'id' => $id));
    }
     */

    function delete() {
        $this->validate_submitted_data([
            "id" => "required|numeric"
        ]);

        $id = $this->request->getPost("id");
        $agent_info = $this->AI_agents_model->get_one($id);

        //delete the fine tuning data
        if ($this->AI_agents_model->delete($id)) {

            // delete vector file
            // delete training files and data
            // delete vector data

            // $source_path = get_ai_files_path($agent_info->ai_service) . "vector_store_" . $id . ".jsonl";
            // delete_file_from_directory($source_path);

            echo json_encode(array("success" => true, "message" => app_lang("record_deleted")));
        }
    }

    function modal_form() {
        $id = $this->request->getPost("id");
        $view_data["model_info"] = $this->AI_agents_model->get_one($id);

        $Dropdown_list = new Dropdown_list($this);
        $view_data["ai_services_dropdown"] = $Dropdown_list->prepare_ai_services_dropdown();

        $app_actions_dropdown = array(
            array("id" => "note", "text" => app_lang('note')),
            array("id" => "todo", "text" => app_lang('todo'))
        );

        $view_data["app_actions_dropdown"] = json_encode($app_actions_dropdown);

        return $this->template->view("ai_agents/modal_form", $view_data);
    }

    function get_models_of_an_ai_service() {
        $ai_service = $this->request->getPost("ai_service");
        $models = $this->AI_assistant->get_models_dropdown($ai_service);
        echo json_encode(array("success" => true, "data" => $models));
    }

    function save() {
        $this->validate_submitted_data([
            "id" => "numeric",
            "title" => "required",
            "max_output_tokens" => "numeric",
            "temperature" => "numeric"
        ]);

        $id = $this->request->getPost("id");

        if (!$id) {
            $this->validate_submitted_data([
                "base_model" => "required",
            ]);
        }


        $title = $this->request->getPost("title");
        $base_model = $this->request->getPost("base_model");
        $parameters = $this->request->getPost("parameters");
        $ai_service = $this->request->getPost("ai_service");
        $this->_validate_image_model_parameters($ai_service, $base_model, $parameters);

        $embedding_model = "text-embedding-3-small";
        if ($ai_service === "gemini") {
            $embedding_model = "gemini-embedding-001";
        }

        $data = array(
            "title" => $title,
            "description" => $this->request->getPost("description"),
            "system_prompt" => $this->request->getPost("system_prompt"),
            "max_output_tokens" => $this->request->getPost("max_output_tokens"),
            "embedding_model" => $embedding_model,
            "temperature" => $this->request->getPost("temperature"),
            "parameters" => $parameters,
            "app_actions" => $this->request->getPost("app_actions"),
            "model_type" => $this->request->getPost("model_type"),
        );

        if (!$id) {
            $data["created_at"] = get_current_utc_time();
            $data["created_by"] = $this->login_user->id;
            $data["base_model"] = $base_model;
            $data["ai_service"] = $ai_service;
        }

        $save_id = $this->AI_agents_model->ci_save($data, $id);

        if ($save_id) {
            echo json_encode(array("success" => true, "data" => $this->_row_data($save_id), 'id' => $save_id, 'message' => app_lang("record_saved")));
        } else {
            echo json_encode(array("success" => false, "message" => app_lang("error_occurred")));
        }
    }

    private function _validate_image_model_parameters($ai_service, $base_model, $parameters) {
        if (!$parameters) {
            return false;
        }

        $all_models = $this->AI_assistant->get_models_dropdown($ai_service);

        // Find the selected model
        $selected_model = null;
        foreach ($all_models as $model) {
            if (get_array_value($model, 'id') === $base_model) {
                $selected_model = $model;
                break;
            }
        }

        // If model not found or not an image model, return original parameters
        if (!$selected_model || get_array_value($selected_model, 'type') !== 'image') {
            return false;
        }

        $parameters = json_decode($parameters, true) ?: [];

        $allowed_params = get_array_value($selected_model, 'allowed_params');

        // Validate each parameter against allowed values
        foreach ($parameters as $key => $value) {

            $allowed_values = get_array_value($allowed_params, $key);
            if (!in_array($value, $allowed_values)) {
                echo json_encode(array("success" => false, "message" => "Invalid value for '$key'. Must be one of: " . implode(', ', $allowed_values)));
                exit;
            }
        }
    }

    function import_kb_articles() {
        $this->validate_submitted_data([
            "agent_id" => "required|numeric"
        ]);

        $agent_id = $this->request->getPost("agent_id");
        $agent_info = $this->AI_agents_model->get_one($agent_id);

        $source_ids = $agent_info->source_ids ? explode(",", $agent_info->source_ids) : [];
        $kb_article_sources = $this->_get_kb_article_sources($source_ids);
        if (count($kb_article_sources)) {
            echo json_encode(array("success" => false, 'message' => app_lang('kb_articles_source_exists_error_message')));
            exit();
        }

        $Help_articles_model = model('App\Models\Help_articles_model');
        $kb_articles = $Help_articles_model->get_details(array("type" => "knowledge_base"))->getResult();

        $data = array(
            "created_at" => get_current_utc_time(),
            "created_by" => $this->login_user->id,
            "source_type" => "kb_articles",
            "title" => app_lang("knowledge_base_articles") . " - " . get_today_date()
        );

        $save_id = $this->AI_training_data_sources_model->ci_save($data);
        $this->_save_source_id_to_agent($save_id, $agent_id);

        foreach ($kb_articles as $kb_article) {

            $data = array(
                "prompt" => $this->_clean_for_training($kb_article->title),
                "response" => $this->_clean_for_training($kb_article->description),
                "context" => "kb_article",
                "context_id" => $kb_article->id,
                "source_id" => $save_id,
                "created_at" => get_current_utc_time(),
                "created_by" => $this->login_user->id
            );

            $this->AI_vector_data_model->ci_save($data);
        }

        echo json_encode(array("success" => true, "message" => app_lang("record_saved")));
    }

    private function _save_source_id_to_agent($source_id, $agent_id) {
        $agent_info = $this->AI_agents_model->get_one($agent_id);
        $source_ids = $agent_info->source_ids ? explode(",", $agent_info->source_ids) : [];
        $source_ids[] = $source_id;
        $source_ids = array_unique($source_ids);

        $data = array(
            "source_ids" => implode(",", $source_ids),
            "training_status" => "training_pending"
        );
        $this->AI_agents_model->ci_save($data, $agent_id);
    }

    function import_files_modal_form() {
        $agent_id = $this->request->getPost("agent_id");
        $view_data["agent_id"] = $agent_id;
        return $this->template->view('ai_agents/import_files_modal_form', $view_data);
    }

    function import_files() {
        $this->validate_submitted_data(array(
            "agent_id" => "numeric|required",
        ));

        $agent_id = $this->request->getPost("agent_id");
        $agent_info = $this->AI_agents_model->get_one($agent_id);

        $now = get_current_utc_time();
        $target_path = getcwd() . "/" . get_ai_files_path($agent_info->ai_service);
        $files = $this->request->getPost("files");

        //process the fiiles which has been uploaded by dropzone
        if ($files && get_array_value($files, 0)) {
            foreach ($files as $file) {
                $file_name = $this->request->getPost('file_name_' . $file);
                $file_info = move_temp_file($file_name, $target_path, "");
                if ($file_info) {
                    $data = array(
                        "title" => $this->request->getPost('description_' . $file) ? $this->request->getPost('description_' . $file) : $file_name,
                        "file_name" => get_array_value($file_info, 'file_name'),
                        "file_id" => get_array_value($file_info, 'file_id'),
                        "service_type" => get_array_value($file_info, 'service_type'),
                        "file_size" => $this->request->getPost('file_size_' . $file),
                        "created_at" => $now,
                        "created_by" => $this->login_user->id,
                        "source_type" => "file"
                    );

                    $data = clean_data($data);

                    $save_id = $this->AI_training_data_sources_model->ci_save($data);
                    $this->_save_source_id_to_agent($save_id, $agent_id);
                    $this->AI_assistant->save_training_data_from_file($save_id, $agent_info, $this->login_user->id);

                    $success = true;
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
                            "title" => get_array_value($description, $key) ? get_array_value($description, $key) : $file_name,
                            "file_name" => get_array_value($file_info, 'file_name'),
                            "file_id" => get_array_value($file_info, 'file_id'),
                            "service_type" => get_array_value($file_info, 'service_type'),
                            "file_size" => $file_size,
                            "created_at" => $now,
                            "created_by" => $this->login_user->id,
                            "source_type" => "file"
                        );
                        $save_id = $this->AI_training_data_sources_model->ci_save($data);
                        $this->_save_source_id_to_agent($save_id, $agent_id);
                        $this->AI_assistant->save_training_data_from_file($save_id, $agent_info, $this->login_user->id);
                        $success = true;
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

    function validate_import_files() {
        $file_name = $this->request->getPost("file_name");
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if (!is_valid_file_to_upload($file_name)) {
            echo json_encode(array("success" => false, 'message' => app_lang('invalid_file_type')));
            exit();
        }

        if ($file_ext == "txt") {
            echo json_encode(array("success" => true));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('invalid_file_type')));
        }
    }

    function input_text_prompt_modal_form() {
        $agent_id = $this->request->getPost("agent_id");
        $view_data["agent_id"] = $agent_id;
        return $this->template->view('ai_agents/input_text_prompt_modal_form', $view_data);
    }

    function save_text_prompt() {
        $this->validate_submitted_data([
            "agent_id" => "numeric|required",
            "prompt" => "required",
            "response" => "required"
        ]);

        $agent_id = $this->request->getPost("agent_id");
        $prompt = $this->request->getPost("prompt");
        $response = $this->request->getPost("response");

        $data = array(
            "title" => $prompt,
            "created_at" => get_current_utc_time(),
            "created_by" => $this->login_user->id,
            "source_type" => "text_snippet"
        );

        $save_id = $this->AI_training_data_sources_model->ci_save($data);
        $this->_save_source_id_to_agent($save_id, $agent_id);

        $vector_data = array(
            "source_id" => $save_id,
            "prompt" => $prompt,
            "response" => $response,
            "created_at" => get_current_utc_time(),
            "created_by" => $this->login_user->id
        );

        $this->AI_vector_data_model->ci_save($vector_data);

        echo json_encode(array("success" => true, 'message' => app_lang('record_saved')));
    }

    /* import excel */

    private function _validate_excel_import_access() {
    }

    private function _get_controller_slag() {
        return "ai_agents";
    }

    private function _get_headers_for_import() {
        return array(
            array("name" => "prompt", "required" => true, "required_message" => sprintf(app_lang("import_error_field_required"), app_lang("prompt"))),
            array("name" => "response", "required" => true, "required_message" => sprintf(app_lang("import_error_field_required"), app_lang("response"))),
        );
    }

    private function _get_custom_field_context() {
        return "";
    }

    function download_sample_excel_file() {
        return $this->download_app_files(get_setting("system_file_path"), serialize(array(array("file_name" => "import-ai-agent-training-data-sample.xlsx"))));
    }

    private function _init_required_data_before_starting_import() {
    }

    private function _init_training_data() {
        // check if the tuning data is already saved
        if (!$this->excel_source_id) {

            $this->validate_submitted_data([
                "agent_id" => "numeric|required",
            ]);

            $agent_id = $this->request->getPost("agent_id");

            $data = array(
                "title" => $this->request->getPost("description_1") ? $this->request->getPost("description_1") : $this->request->getPost("file_name"),
                "created_at" => get_current_utc_time(),
                "created_by" => $this->login_user->id,
                "source_type" => "excel_file"
            );

            $save_id = $this->AI_training_data_sources_model->ci_save($data);
            $this->_save_source_id_to_agent($save_id, $agent_id);
            $this->excel_source_id = $save_id;
        }
    }

    private function _save_a_row_of_excel_data($row_data) {
        $training_data = array();

        $this->_init_training_data();

        foreach ($row_data as $column_index => $value) {
            if (!$value) {
                continue;
            }

            $value = $this->_clean_for_training($value);

            $column_name = $this->_get_column_name($column_index);
            if ($column_name == "prompt") {
                $training_data["prompt"] = $value;
            } else if ($column_name == "response") {
                $training_data["response"] = $value;
            }
        }

        if ($training_data) {

            $vector_data = array(
                "source_id" => $this->excel_source_id,
                "prompt" => get_array_value($training_data, "prompt"),
                "response" => get_array_value($training_data, "response"),
                "created_at" => get_current_utc_time(),
                "created_by" => $this->login_user->id
            );

            $this->AI_vector_data_model->ci_save($vector_data);
        }
    }

    function train_agent() {
        $this->validate_submitted_data([
            "agent_id" => "numeric|required",
        ]);

        $agent_id = $this->request->getPost("agent_id");
        $agent_info = $this->AI_agents_model->get_one($agent_id);

        // $options = array("agent_id" => $agent_id, "statuses" => array("draft", "failed"));
        // $training_data = $this->AI_vector_data_model->get_details($options)->getResult();

        // foreach ($training_data as $data) {

        //     try {
        //         $this->AI_assistant->store_embedding_data($data);
        //     } catch (\Exception $e) {
        //         log_message('error', 'AI Training Error: ' . $e->getMessage());
        //         continue;
        //     }
        // }

        // //$this->_update_agent_status($agent_id, "completed");

        // echo json_encode(array("success" => true, "data" => $this->_row_data($agent_id), 'id' => $agent_id, 'message' => app_lang('record_saved')));

        $sources = array();
        if ($agent_info->source_ids) {
            $sources = $this->AI_training_data_sources_model->get_details(array("source_ids" => $agent_info->source_ids, "agent_id" => $agent_id))->getResult();
        }



        $view_data["sources"] = $sources;
        $view_data["agent_id"] = $agent_id;

        $view_data["model_info"] = $agent_info;

        $view_data["agent_training_data_view"] = $this->template->view('ai_agents/agent_training_data_view', $view_data);

        if ($this->request->getPost("view_type") == "training_data_view") {
            echo json_encode(array("success" => true, "training_data_view" => $view_data["agent_training_data_view"]));
            return;
        }


        $agents_dropdown = $this->AI_agents_model->get_id_and_text_dropdown(array("title"), array("id !=" => $agent_id, "ai_service" => $agent_info->ai_service, "deleted" => 0));
        $view_data["agents_dropdown"] = json_encode($agents_dropdown);


        return $this->template->view("ai_agents/train_agent", $view_data);
    }

    function train_data() {
        $this->validate_submitted_data([
            "agent_id" => "numeric|required",
            "source_id" => "numeric|required",
            "deleted" => "numeric"
        ]);

        $agent_id = $this->request->getPost("agent_id");
        $source_id = $this->request->getPost("source_id");
        $deleted = $this->request->getPost("deleted");

        if ($deleted) {
            $this->_delete_source_and_data($source_id, $agent_id);
        } else {
            $this->validate_submitted_data([
                "vector_data_id" => "numeric|required",
            ]);

            $vector_data_id = $this->request->getPost("vector_data_id");
            $vector_data_info = $this->AI_vector_data_model->get_one($vector_data_id);
            $result = $this->AI_assistant->store_embedding_data($vector_data_info, $agent_id);

            if (!$result) {
                $this->save_agent_training_status($agent_id, "failed");
                echo json_encode(array("success" => false, "message" => app_lang('training_failed_message')));
                exit;
            }
        }

        echo json_encode(array("success" => true));
    }

    private function _delete_source_and_data($source_id, $agent_id) {
        // check if this source is used by any other agent
        $delete_original_source = false;
        $agent_info = $this->AI_agents_model->get_details(array("source_ids" => $source_id))->getResult();
        if ($agent_info) {
            $delete_original_source = true;
        }

        $agent_info = $this->AI_agents_model->get_one($agent_id);

        if ($delete_original_source) {
            // delete source
            $source_info = $this->AI_training_data_sources_model->get_one($source_id);
            $this->AI_training_data_sources_model->delete_permanently($source_id);

            // delete file
            if ($source_info->source_type == "file") {
                $file_path = get_ai_files_path($agent_info->ai_service);
                delete_app_files($file_path, array(make_array_of_file($source_info)));
            }

            // delete vector data and status
            $this->_delete_vector_data_statuses($source_id, $agent_id, true);
        } else {
            // delete vector data and status
            $this->_delete_vector_data_statuses($source_id, $agent_id);
        }

        // update source_ids of the agent
        $this->_remove_a_source_from_agent($source_id, $agent_id);

        // remove from deleted ids
        $this->_remove_a_source_from_deleted_ids($source_id, $agent_id);
    }

    private function _remove_a_source_from_agent($source_id, $agent_id) {
        $agent_info = $this->AI_agents_model->get_one($agent_id);
        $source_ids = $agent_info->source_ids ? explode(",", $agent_info->source_ids) : [];
        $source_ids = array_diff($source_ids, array($source_id));
        $source_ids = implode(",", $source_ids);

        $data = array(
            "source_ids" => $source_ids
        );
        $this->AI_agents_model->ci_save($data, $agent_id);
    }

    private function _remove_a_source_from_deleted_ids($source_id, $agent_id) {
        $agent_info = $this->AI_agents_model->get_one($agent_id);
        $deleted_source_ids = $agent_info->deleted_source_ids ? explode(",", $agent_info->deleted_source_ids) : [];
        $deleted_source_ids = array_diff($deleted_source_ids, array($source_id));
        $deleted_source_ids = implode(",", $deleted_source_ids);

        $data = array(
            "deleted_source_ids" => $deleted_source_ids
        );
        $this->AI_agents_model->ci_save($data, $agent_id);
    }

    private function _delete_vector_data_statuses($source_id, $agent_id, $delete_vector_data = false) {
        $vector_data = $this->AI_vector_data_model->get_details(array("source_id" => $source_id))->getResult();
        foreach ($vector_data as $data) {

            if ($delete_vector_data) {
                $this->AI_vector_data_model->delete_permanently($data->id);
            }

            // delete status
            $vector_data_status = $this->AI_vector_data_status_model->get_details(array("vector_data_id" => $data->id, "agent_id" => $agent_id))->getRow();
            if (!$vector_data_status) {
                continue;
            }

            $this->AI_vector_data_status_model->delete_permanently($vector_data_status->id);

            // delete vector data from file
            if ($vector_data_status->status !== "completed") {
                continue;
            }

            $this->_delete_vector_data_from_file($agent_id, $data->id);
        }
    }

    private function _delete_vector_data_from_file($agent_id, $target_vector_id) {
        $agent_info = $this->AI_agents_model->get_one($agent_id);
        $file_path = get_ai_files_path($agent_info->ai_service) . "vector_store_" . $agent_id . ".jsonl";

        $temp_path = $file_path . ".tmp";

        $input = fopen($file_path, 'r');
        $output = fopen($temp_path, 'w');

        if (!$input || !$output) {
            log_message('error', 'AI Training Error: Unable to open files.');
            return;
        }

        while (($line = fgets($input)) !== false) {
            $data = json_decode($line, true);

            if (!isset($data['vector_id']) || $data['vector_id'] != $target_vector_id) {
                fwrite($output, $line);
            }
        }

        fclose($input);
        fclose($output);

        // Replace original file with the filtered temp file
        rename($temp_path, $file_path);
    }

    function import_from_existing_agent($current_agent_id) {

        validate_numeric_value($current_agent_id);
        $this->validate_submitted_data([
            "value" => "numeric|required"
        ]);

        $upcoming_agent_id = $this->request->getPost("value");
        if (!($upcoming_agent_id && $current_agent_id)) {
            echo json_encode(array("success" => true, 'has_error' => true, 'message' => app_lang('error_occurred')));
            exit();
        }

        $current_agent_info = $this->AI_agents_model->get_one($current_agent_id);
        $current_source_ids = $current_agent_info->source_ids ? explode(",", $current_agent_info->source_ids) : [];

        $upcoming_agent_info = $this->AI_agents_model->get_one($upcoming_agent_id);
        $upcoming_source_ids = $upcoming_agent_info->source_ids ? explode(",", $upcoming_agent_info->source_ids) : [];

        $all_source_ids = array_merge($current_source_ids, $upcoming_source_ids);
        $all_source_ids = array_unique($all_source_ids);

        $kb_article_sources = $this->_get_kb_article_sources($all_source_ids);
        if (count($kb_article_sources) > 1) {
            echo json_encode(array("success" => true, 'has_error' => true, 'message' => app_lang('kb_articles_source_exists_error_message_for_agent_importing')));
            exit();
        }

        $data = array(
            "source_ids" => implode(",", $all_source_ids)
        );

        $this->AI_agents_model->ci_save($data, $current_agent_id);

        // save status logs of existing agent sources
        $this->_copy_status_logs($upcoming_agent_id, $current_agent_id);

        echo json_encode(array("success" => true, 'data' => app_lang('import_from_existing_agent'), 'message' => app_lang('record_saved')));
    }

    private function _copy_status_logs($upcoming_agent_id, $current_agent_id) {
        $status_logs = $this->AI_vector_data_status_model->get_details(array("agent_id" => $upcoming_agent_id))->getResult();

        foreach ($status_logs as $status_log) {
            $data = (array) $status_log;
            unset($data["id"]);

            $data["agent_id"] = $current_agent_id;
            $data["created_at"] = get_current_utc_time();
            $data["created_by"] = $this->login_user->id;

            $this->AI_vector_data_status_model->ci_save($data);
        }
    }

    private function _get_kb_article_sources($source_ids) {
        if (!$source_ids) {
            return [];
        }

        $source_ids = implode(",", $source_ids);
        $kb_article_sources = $this->AI_training_data_sources_model->get_details(array("source_ids" => $source_ids, "source_type" => "kb_articles"))->getResult();
        return $kb_article_sources;
    }

    function delete_source() {
        $this->validate_submitted_data([
            "source_id" => "numeric|required",
            "agent_id" => "numeric|required"
        ]);

        $source_id = $this->request->getPost("source_id");
        $agent_id = $this->request->getPost("agent_id");

        $agent_info = $this->AI_agents_model->get_one($agent_id);
        $deleted_source_ids = $agent_info->deleted_source_ids ? explode(",", $agent_info->deleted_source_ids) : [];
        $deleted_source_ids[] = $source_id;
        $deleted_source_ids = array_unique($deleted_source_ids);

        $data = array(
            "deleted_source_ids" => implode(",", $deleted_source_ids)
        );
        $this->AI_agents_model->ci_save($data, $agent_info->id);

        echo json_encode(array("success" => true, 'message' => app_lang('record_deleted')));
    }

    function save_agent_training_status($agent_id = 0, $status = "not_applicable") {
        validate_numeric_value($agent_id);
        if (!$agent_id) {
            return false;
        }

        $data = array(
            "training_status" => $status
        );

        $this->AI_agents_model->ci_save($data, $agent_id);
    }

    function save_agent_status($agent_id = 0, $status = "completed", $echo_json = true) {
        validate_numeric_value($agent_id);
        if (!$agent_id) {
            return false;
        }

        $data = array(
            "status" => $status
        );

        $save_id = $this->AI_agents_model->ci_save($data, $agent_id);

        if (!$echo_json) {
            return $save_id;
        }

        if ($save_id) {
            echo json_encode(array("success" => true, "id" => $agent_id, "message" => app_lang('agent_status_updated')));
        } else {
            echo json_encode(array("success" => false, app_lang('error_occurred')));
        }
    }
}
