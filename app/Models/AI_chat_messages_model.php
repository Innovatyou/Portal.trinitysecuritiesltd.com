<?php

namespace App\Models;

class AI_chat_messages_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'ai_chat_messages';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $ai_chat_messages_table = $this->db->prefixTable('ai_chat_messages');
        $ai_agent_table = $this->db->prefixTable('ai_agents');
        $users_table = $this->db->prefixTable('users');

        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $ai_chat_messages_table.id=$id";
        }

        $chat_id = $this->_get_clean_value($options, "chat_id");
        if ($chat_id) {
            $where .= " AND $ai_chat_messages_table.chat_id=$chat_id";
        }

        $user_id = $this->_get_clean_value($options, "user_id");
        if ($user_id) {
            $where .= " AND $ai_chat_messages_table.user_id=$user_id";
        }

        $ai_agent_id = $this->_get_clean_value($options, "ai_agent_id");
        if ($ai_agent_id) {
            $where .= " AND $ai_chat_messages_table.ai_agent_id=$ai_agent_id";
        }

        $last_message_id = $this->_get_clean_value($options, "last_message_id");
        if ($last_message_id) {
            $where .= " AND $ai_chat_messages_table.id>$last_message_id";
        }

        $top_message_id = $this->_get_clean_value($options, "top_message_id");
        if ($top_message_id) {
            $where .= " AND $ai_chat_messages_table.id<$top_message_id";
        }

        $limit = $this->_get_clean_value($options, "limit");
        $limit = $limit ? $limit : "30";
        $offset = $this->_get_clean_value($options, "offset");
        $offset = $offset ? $offset : "0";

        $sql = "SELECT * FROM (SELECT $ai_chat_messages_table.*, CONCAT($users_table.first_name, ' ', $users_table.last_name) AS user_name, $ai_agent_table.ai_service AS ai_agent_service
            FROM $ai_chat_messages_table
            LEFT JOIN $users_table ON $users_table.id = $ai_chat_messages_table.user_id
            LEFT JOIN $ai_agent_table ON $ai_agent_table.id = $ai_chat_messages_table.ai_agent_id
            WHERE $ai_chat_messages_table.deleted = 0 $where
            ORDER BY $ai_chat_messages_table.id DESC
            LIMIT $offset, $limit
        ) AS new_chat
        ORDER BY id ASC";

        $query = $this->db->query($sql);

        $data = new \stdClass();
        $data->result = $query->getResult();
        $data->row = $query->getRow();
        $data->found_rows = 0;

        if ($chat_id) {
            $data->found_rows = $this->db->query("SELECT COUNT(id) AS found_rows FROM $ai_chat_messages_table WHERE $ai_chat_messages_table.chat_id = $chat_id")->getRow()->found_rows;
        }

        return $data;
    }
}
