<?php

namespace App\Models;

class AI_vector_data_status_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'ai_vector_data_status';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $ai_vector_data_status_table = $this->db->prefixTable('ai_vector_data_status');

        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $ai_vector_data_status_table.id=$id";
        }

        $agent_id = $this->_get_clean_value($options, "agent_id");
        if ($agent_id) {
            $where .= " AND $ai_vector_data_status_table.agent_id=$agent_id";
        }

        $vector_data_id = $this->_get_clean_value($options, "vector_data_id");
        if ($vector_data_id) {
            $where .= " AND $ai_vector_data_status_table.vector_data_id=$vector_data_id";
        }

        $statuses = $this->_get_clean_value($options, "statuses");
        if ($statuses) {
            $statuses = implode(",", $statuses);
            $where .= " AND FIND_IN_SET($ai_vector_data_status_table.status, '$statuses')";
        }

        $sql = "SELECT $ai_vector_data_status_table.*
        FROM $ai_vector_data_status_table
        WHERE $ai_vector_data_status_table.deleted=0 $where
        ORDER BY $ai_vector_data_status_table.id DESC";
        return $this->db->query($sql);
    }
}
