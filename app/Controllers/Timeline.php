<?php

namespace App\Controllers;

class Timeline extends Security_Controller {

    public function __construct() {
        parent::__construct();
        $this->access_only_team_members();
        $this->init_permission_checker("timeline_permission");
    }

    private function check_access_on_timeline_for_this_user() {
        $accessable = true;

        if ($this->login_user->user_type == "staff" && !$this->login_user->is_admin && get_array_value($this->login_user->permissions, "timeline_permission") == "no") {
            $accessable = false;
        }

        return $accessable;
    }

    private function check_timeline_user_permission() {
        if (!$this->check_access_on_timeline_for_this_user()) {
            app_redirect("forbidden");
        }
    }

    private function can_access_this_post($post_id = 0) {
        $post_info = $this->Posts_model->get_one($post_id);

        if ($this->login_user->user_type == "staff" && !$this->login_user->is_admin && get_array_value($this->login_user->permissions, "timeline_permission") == "specific" && $post_info->created_by && !in_array($post_info->created_by, $this->allowed_members)) {
            app_redirect("forbidden");
        }

        //a reply follows the visibility of its main post
        $main_post_id = $post_info->post_id ? $post_info->post_id : $post_info->id;
        if ($main_post_id && !$this->login_user->is_admin && !$this->Posts_model->is_visible_to_user($main_post_id, $this->login_user->id)) {
            app_redirect("forbidden");
        }
    }

    //teams the login user can share a post with: all teams for admins, own teams for others
    private function _get_shareable_teams() {
        $teams = $this->Team_model->get_all_where(array("deleted" => 0))->getResult();
        if ($this->login_user->is_admin) {
            return $teams;
        }

        $my_teams = array();
        foreach ($teams as $team) {
            if (in_array($this->login_user->id, explode(",", $team->members))) {
                $my_teams[] = $team;
            }
        }
        return $my_teams;
    }

    private function _get_valid_share_with($share_with) {
        if (preg_match('/^team:(\d+)$/', $share_with, $matches)) {
            foreach ($this->_get_shareable_teams() as $team) {
                if ($team->id == $matches[1]) {
                    return $share_with;
                }
            }
        }

        return "all";
    }

    /* load timeline view, it's the home page of the team members after signin */

    function index() {
        //team members without timeline access land on the operations dashboard instead
        if (!$this->check_access_on_timeline_for_this_user()) {
            app_redirect("operations");
        }

        $view_data['shareable_teams'] = $this->_get_shareable_teams();
        return $this->template->rander("timeline/index", $view_data);
    }

    /* save a post */

    function save() {
        $this->check_timeline_user_permission();
        $this->validate_submitted_data(array(
            "id" => "numeric",
            "description" => "required"
        ));

        $id = $this->request->getPost('id');

        $post_id = $this->request->getPost('post_id');
        $this->can_access_this_post($post_id);

        $target_path = get_setting("timeline_file_path");

        $files_data = move_files_from_temp_dir_to_permanent_dir($target_path, "timeline_post");

        $data = array(
            "created_by" => $this->login_user->id,
            "created_at" => get_current_utc_time(),
            "post_id" => $post_id,
            "description" => $this->request->getPost('description'),
            "share_with" => $post_id ? "" : $this->_get_valid_share_with($this->request->getPost('share_with'))
        );

        $data = clean_data($data);
        //the description column is utf8mb3, so store emojis (4-byte characters) as html entities
        $data["description"] = mb_encode_numericentity($data["description"], array(0x10000, 0x10FFFF, 0, 0x1FFFFF), "UTF-8");
        $data["files"] = $files_data; //don't clean serilized data

        $save_id = $this->Posts_model->ci_save($data, $id);
        if ($save_id) {
            $data = "";
            if ($this->request->getPost("reload_list")) {
                $options = array("id" => $save_id);
                $view_data['posts'] = $this->Posts_model->get_details($options)->result;
                $view_data['result_remaining'] = 0;
                $view_data['is_first_load'] = false;
                $view_data['single_post'] = '';
                $data = $this->template->view("timeline/post_list", $view_data);
            }
            echo json_encode(array("success" => true, "data" => $data, 'message' => app_lang('comment_submited')));

            if ($post_id == 0) {
                log_notification("created_a_new_post", array("post_id" => $save_id));
            } else {
                log_notification("timeline_post_commented", array("post_id" => $save_id));
            }
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
        }
    }

    function delete($id = 0) {

        if (!$id) {
            exit();
        }

        validate_numeric_value($id);

        $post_info = $this->Posts_model->get_one($id);

        //only admin and creator can delete the post
        if (!($this->login_user->is_admin || $post_info->created_by == $this->login_user->id)) {
            app_redirect("forbidden");
        }


        //delete the post and files
        if ($this->Posts_model->delete($id) && $post_info->files) {

            //delete the files
            $timeline_file_path = get_setting("timeline_file_path");
            $files = unserialize($post_info->files);

            delete_app_files($timeline_file_path, $files);
        }
    }

    /* load all replies of a post */

    function view_post_replies($post_id) {
        validate_numeric_value($post_id);
        $this->can_access_this_post($post_id);
        $view_data['reply_list'] = $this->Posts_model->get_details(array("post_id" => $post_id))->result;
        return $this->template->view("timeline/reply_list", $view_data);
    }

    /* show post reply form */

    function post_reply_form($post_id) {
        validate_numeric_value($post_id);
        $this->can_access_this_post($post_id);
        $view_data['post_id'] = $post_id;
        return $this->template->view("timeline/reply_form", $view_data);
    }


    function download_files($id) {
        validate_numeric_value($id);
        $this->can_access_this_post($id);
        $files = $this->Posts_model->get_one($id)->files;
        return $this->download_app_files(get_setting("timeline_file_path"), $files);
    }

    /* load more posts */

    function load_more_posts($offset = 0) {
        validate_numeric_value($offset);
        return timeline_widget(array("limit" => 20, "offset" => $offset));
    }

    /* post page for notification */

    function post($post_id) {
        validate_numeric_value($post_id);
        
        //check if it's a post's comment
        $original_post_info = $this->Posts_model->get_one($post_id);
        if ($original_post_info->post_id) {
            $post_id = $original_post_info->post_id;
        }
        
        $this->can_access_this_post($post_id);

        $post = $this->Posts_model->get_details(array("id" => $post_id));
        $view_data["posts"] = $post->result;
        $view_data['is_first_load'] = true;
        $view_data['result_remaining'] = 0;
        $view_data['single_post'] = 'single_post';
        return $this->template->rander("timeline/post_list", $view_data);
    }

}

/* End of file timeline.php */
    /* Location: ./app/controllers/timeline.php */