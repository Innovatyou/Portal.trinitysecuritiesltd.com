<?php

namespace App\Models;

class AI_vector_data_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'ai_vector_data';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $ai_vector_data_table = $this->db->prefixTable('ai_vector_data');
        $ai_training_data_sources_table = $this->db->prefixTable('ai_training_data_sources');

        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $ai_vector_data_table.id=$id";
        }

        $agent_id = $this->_get_clean_value($options, "agent_id");
        if ($agent_id) {
            $where .= " AND $ai_vector_data_table.source_id IN (
                SELECT $ai_training_data_sources_table.id FROM $ai_training_data_sources_table WHERE $ai_training_data_sources_table.agent_id=$agent_id) ";
        }

        $statuses = $this->_get_clean_value($options, "statuses");
        if ($statuses) {
            $statuses = implode(",", $statuses);
            $where .= " AND FIND_IN_SET($ai_vector_data_table.status, '$statuses')";
        }

        $contexts = $this->_get_clean_value($options, "contexts");
        if ($contexts) {
            $contexts = implode(",", $contexts);
            $where .= " AND FIND_IN_SET($ai_vector_data_table.context, '$contexts')";
        }

        $source_id = $this->_get_clean_value($options, "source_id");
        if ($source_id) {
            $where .= " AND $ai_vector_data_table.source_id=$source_id";
        }

        $sql = "SELECT $ai_vector_data_table.*
        FROM $ai_vector_data_table
        WHERE $ai_vector_data_table.deleted=0 $where
        ORDER BY $ai_vector_data_table.id DESC";
        return $this->db->query($sql);
    }
}
