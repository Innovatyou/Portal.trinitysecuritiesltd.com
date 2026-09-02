<?php

namespace CustomersApi\Config;

use CodeIgniter\Events\Events;

Events::on('pre_system', function () {
    //load helpers
    helper(array(
        'config', 'url', 'file', 'form', 'language', 'general',
        'customersapi_general', 'app_files', 'widget', 'activity_logs',
        'currency', 'reports'));
});