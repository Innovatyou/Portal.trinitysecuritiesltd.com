<?php

namespace App\Libraries;

trait Kanban_view {
    abstract private function _kanban_config();
    abstract private function _get_kanban_data($options);
    abstract private function _get_kanban_statuses();
    abstract private function _get_kanban_counts($extra_data = []);

    private $controller_slag;
    private $column_status_field = "status";
    private $kanban_default_limit = 15;

    private function init() {
        $configs = array(
            "controller_slag",
            "column_status_field"
        );

        $this->_set_configs($configs);

        if (!$this->controller_slag) {
            die("controller_slag config is missing");
        }
    }

    private function _set_configs($configs) {
        $kanban_config = $this->_kanban_config();
        foreach ($configs as $config_name) {
            if (isset($kanban_config->$config_name)) {
                $this->$config_name = $kanban_config->$config_name;
            }
        }
    }

    private function _render_kanban_view($options = []) {
        $this->init();

        $status_id = $this->request->getPost('kanban_column_id');

        $result = $this->_load_kanban_data($status_id, $options);

        if ($status_id) {
            // Load single column items (load more)
            $view_data["items"] = $result["items"];

            $extra_view_data = $this->_kanban_extra_view_data($result["items"], 0, $status_id, $options);
            $extra_view_data = (array) $extra_view_data;
            $column_extra_data = get_array_value($extra_view_data, $status_id, []);
            $view_data = array_merge($view_data, (array) $column_extra_data);

            return $this->template->view("{$this->controller_slag}/kanban/kanban_column_items", $view_data);
        } else {
            // Load full board
            $statuses = $this->_get_kanban_statuses();
            $view_data["total_columns"] = $statuses->resultID->num_rows;
            $view_data["columns"] = $statuses->getResult();
            $view_data["items"] = $result["items"];
            $view_data["column_counts"] = $this->_get_kanban_counts($options);

            $view_data["controller_slag"] = $this->controller_slag;

            // Get user's kanban_collapsed_columns setting
            $view_data["kanban_collapsed_columns"] = $this->_get_kanban_collapsed_columns();

            $extra_view_data = $this->_kanban_extra_view_data($result["items"], 0, 0, $options);
            $view_data['extra_view_data'] = $extra_view_data;
            $view_data = array_merge($view_data, $extra_view_data);

            return $this->template->view("kanban_view/kanban_view", $view_data);
        }
    }

    private function _load_kanban_data($status_id = 0, $options = []) {

        $this->init();

        $status_id = get_array_value($options, "kanban_column_id");
        $offset = get_array_value($options, "max_sort");
        $limit = $this->kanban_default_limit;

        if ($status_id) {
            // Load single column (load more)
            $options[$this->column_status_field] = $status_id;
            $options["get_after_max_sort"] = $offset;
            $options["limit"] = 100;

            $items = $this->_get_kanban_data($options);
        } else {
            // Full board
            $statuses = $this->_get_kanban_statuses();

            $items = [];
            foreach ($statuses->getResult() as $status) {
                $id = $status->id;

                $options[$this->column_status_field] = $id;
                $options["limit"] = $limit;

                $items[$id] = $this->_get_kanban_data($options);
            }
        }

        return array("items" => $items);
    }

    private function _collapse_kanban_column() {
        $this->init();

        $setting_name = "user_" . $this->login_user->id . "_ui_selection";
        $column_id = clean_data($this->request->getPost("column_id"));
        $collapse = $this->request->getPost("collapse") == "1"; // true if collapsing

        $existing_value = $this->Settings_model->get_setting($setting_name);
        $settings_array = [];

        if (!is_null($existing_value)) {
            $settings_array = unserialize($existing_value);
            if (!is_array($settings_array)) {
                $settings_array = [];
            }
        }

        $collapse_key = "{$this->controller_slag}_kanban_collapsed_columns";

        $collapsed = isset($settings_array[$collapse_key]) && is_array($settings_array[$collapse_key]) ? $settings_array[$collapse_key] : [];

        if ($collapse) {
            // Add column_id if not already in list
            if (!in_array($column_id, $collapsed)) {
                $collapsed[] = $column_id;
            }
        } else {
            // Remove column_id if present
            $collapsed = array_filter($collapsed, function ($id) use ($column_id) {
                return $id != $column_id;
            });
        }

        $settings_array[$collapse_key] = array_values($collapsed); // reindex
        $this->Settings_model->save_setting($setting_name, serialize($settings_array));

        echo json_encode(["success" => true, "message" => app_lang("settings_updated")]);
    }

    private function _get_kanban_collapsed_columns() {
        $this->init();

        $user_ui_selection = get_setting("user_" . $this->login_user->id . "_ui_selection");

        $ui_selection = [];
        if (!is_null($user_ui_selection)) {
            $unserialized = @unserialize($user_ui_selection);
            if (is_array($unserialized)) {
                $ui_selection = $unserialized;
            }
        }

        $collapse_key = "{$this->controller_slag}_kanban_collapsed_columns";
        return is_array($ui_selection[$collapse_key] ?? null) ? $ui_selection[$collapse_key] : [];
    }

    protected function _kanban_extra_view_data($items, $item_id = 0, $status_id = 0, $options = array()) {
        return array();
    }
}
