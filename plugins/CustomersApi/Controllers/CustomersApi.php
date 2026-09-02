<?php

namespace CustomersApi\Controllers;

use App\Controllers\Security_Controller;

class CustomersApi extends Security_Controller {

    function __construct() {
        parent::__construct();
    }

    function settings()
    {
        return $this->template->rander('CustomersApi\Views\settings');
    }

    public function saveSettings()
    {
        $this->Settings_model->save_setting("customersapi_secret_key", $this->request->getPost('jwt_secret_key'));

        echo json_encode(array("success" => true, 'message' => app_lang('settings_updated')));
    }
}