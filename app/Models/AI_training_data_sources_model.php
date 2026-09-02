<?php

namespace App\Models;

class AI_training_data_sources_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'ai_training_data_sources';
        parent::__construct($this->table);
    }

    function get_details($options = array()) {
        $ai_training_data_sources_table = $this->db->prefixTable('ai_training_data_sources');
        $ai_vector_data_table = $this->db->prefixTable('ai_vector_data');
        $ai_vector_data_status_table = $this->db->prefixTable('ai_vector_data_status');

        $where = "";
        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $ai_training_data_sources_table.id=$id";
        }

        $source_ids = $this->_get_clean_value($options, "source_ids");
        if ($source_ids) {
            $where .= " AND FIND_IN_SET($ai_training_data_sources_table.id, '$source_ids')";
        }

        $statuses = $this->_get_clean_value($options, "statuses");
        if ($statuses) {
            $statuses = implode(",", $statuses);
            $where .= " AND FIND_IN_SET($ai_training_data_sources_table.status, '$statuses')";
        }

        $vector_data_status_where = "";
        $agent_id = $this->_get_clean_value($options, "agent_id");
        if ($agent_id) {
            $vector_data_status_where .= " AND $ai_vector_data_status_table.agent_id=$agent_id";
        }

        $source_type = $this->_get_clean_value($options, "source_type");
        if ($source_type) {
            $where .= " AND $ai_training_data_sources_table.source_type='$source_type'";
        }

        $sql = "SELECT $ai_training_data_sources_table.*,
            (SELECT GROUP_CONCAT($ai_vector_data_table.id SEPARATOR ',') FROM $ai_vector_data_table WHERE $ai_vector_data_table.source_id=$ai_training_data_sources_table.id) AS all_data_ids,
            (SELECT GROUP_CONCAT($ai_vector_data_table.id SEPARATOR ',') FROM $ai_vector_data_table WHERE $ai_vector_data_table.source_id=$ai_training_data_sources_table.id 
                AND $ai_vector_data_table.id IN (SELECT $ai_vector_data_status_table.vector_data_id FROM $ai_vector_data_status_table WHERE $ai_vector_data_status_table.deleted=0 AND $ai_vector_data_status_table.status='completed' $vector_data_status_where )
            ) AS completed_data_ids
        FROM $ai_training_data_sources_table
        WHERE $ai_training_data_sources_table.deleted=0 $where
        ORDER BY $ai_training_data_sources_table.id DESC";

        return $this->db->query($sql);
    }
}
