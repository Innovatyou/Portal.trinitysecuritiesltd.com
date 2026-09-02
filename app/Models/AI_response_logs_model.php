<?php

namespace App\Models;

class AI_response_logs_model extends Crud_model {

    protected $table = null;

    function __construct() {
        $this->table = 'ai_response_logs';
        parent::__construct($this->table);
    }

}
