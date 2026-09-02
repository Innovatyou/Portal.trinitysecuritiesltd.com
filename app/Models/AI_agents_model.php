<?php

namespace App\Models;

class AI_agents_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'ai_agents';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $ai_agents_table = $this->db->prefixTable('ai_agents');

        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $ai_agents_table.id=$id";
        }

        $agent_ids = $this->_get_clean_value($options, "agent_ids");
        if ($agent_ids) {
            $where .= " AND FIND_IN_SET($ai_agents_table.id, '$agent_ids')";
        }

        $sql = "SELECT $ai_agents_table.*
        FROM $ai_agents_table
        WHERE $ai_agents_table.deleted=0 $where";
        return $this->db->query($sql);
    }

    function get_agents_for_chatbox($options = array()) {
        $ai_agents_table = $this->db->prefixTable('ai_agents');

        $where = "";
        $agent_ids = $this->_get_clean_value($options, "agent_ids");
        if (!is_null($agent_ids)) {
            $where .= " AND FIND_IN_SET($ai_agents_table.id, '$agent_ids')";
        }

        $accessible_ai_agents = $this->_get_clean_value($options, "accessible_ai_agents");
        if ($accessible_ai_agents) {
            $where .= " AND FIND_IN_SET($ai_agents_table.id, '$accessible_ai_agents')";
        }

        $sql = "SELECT $ai_agents_table.*
        FROM $ai_agents_table
        WHERE $ai_agents_table.deleted=0 AND $ai_agents_table.status='active' $where";
        return $this->db->query($sql);
    }
}
