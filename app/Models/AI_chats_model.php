<?php

namespace App\Models;

class AI_chats_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'ai_chats';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $ai_chats_table = $this->db->prefixTable('ai_chats');
        $users_table = $this->db->prefixTable('users');

        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $ai_chats_table.id=$id";
        }

        $chat_id = $this->_get_clean_value($options, "chat_id");
        if ($chat_id) {
            $where .= " AND $ai_chats_table.id=$chat_id";
        }

        $user_id = $this->_get_clean_value($options, "user_id");
        if ($user_id) {
            $where .= " AND $ai_chats_table.created_by=$user_id";
        }

        $ai_agent_id = $this->_get_clean_value($options, "ai_agent_id");
        if ($ai_agent_id) {
            $where .= " AND $ai_chats_table.ai_agent_id=$ai_agent_id";
        }

        $last_message_id = $this->_get_clean_value($options, "last_message_id");
        if ($last_message_id) {
            $where .= " AND $ai_chats_table.id>$last_message_id";
        }

        $limit = $this->_get_clean_value($options, "limit");
        $limit = $limit ? $limit : "30";
        $offset = $this->_get_clean_value($options, "offset");
        $offset = $offset ? $offset : "0";

        $sql = "SELECT * FROM (SELECT $ai_chats_table.*, CONCAT($users_table.first_name, ' ', $users_table.last_name) AS user_name
            FROM $ai_chats_table
            LEFT JOIN $users_table ON $users_table.id = $ai_chats_table.created_by
            WHERE $ai_chats_table.deleted = 0 $where
            ORDER BY $ai_chats_table.id DESC
            LIMIT $offset, $limit
        ) AS new_chat
        ORDER BY id ASC";

        $query = $this->db->query($sql);

        $data = new \stdClass();
        $data->result = $query->getResult();
        $data->row = $query->getRow();
        $data->found_rows = 0;

        if ($chat_id) {
            $data->found_rows = $this->db->query("SELECT COUNT(id) AS found_rows FROM $ai_chats_table WHERE $ai_chats_table.id = $chat_id")->getRow()->found_rows;
        }

        return $data;
    }

    function get_ai_chat_list($options = array()) {
        $ai_chats_table = $this->db->prefixTable('ai_chats');
        $ai_agents_table = $this->db->prefixTable('ai_agents');

        $login_user_id = $this->_get_clean_value($options, "login_user_id");

        $where = "";
        $accessible_ai_agents = $this->_get_clean_value($options, "accessible_ai_agents");
        if ($accessible_ai_agents) {
            $where .= " AND FIND_IN_SET($ai_chats_table.ai_agent_id, '$accessible_ai_agents')";
        }

        $sql = "SELECT $ai_chats_table.*, $ai_agents_table.title as ai_agent_name, $ai_agents_table.ai_service as ai_agent_service
        FROM $ai_chats_table
        LEFT JOIN $ai_agents_table ON $ai_agents_table.id = $ai_chats_table.ai_agent_id
        WHERE $ai_chats_table.deleted=0 AND $ai_chats_table.created_by=$login_user_id $where
        GROUP BY $ai_chats_table.id
        ORDER BY $ai_chats_table.created_at DESC";

        return $this->db->query($sql);
    }
}
