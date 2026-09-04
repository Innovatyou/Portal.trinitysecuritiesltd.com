<?php

/**
 * Plugin Name: Operations & Approval Workflow
 * Description: Configurable company operations, request management, multi-level approvals, conditional routing, attachments, comments, SLA tracking and complete audit history for RISE CRM.
 * Version: 1.0.0
 * Requires at least: 3.9.5
 * Author: RISE Operations
 */

use operations_approval\Libraries\Operations_installer;
use operations_approval\Libraries\Operations_permissions;

defined('PLUGINPATH') or exit('No direct script access allowed');

defined('OPERATIONS_APPROVAL_VERSION') or define('OPERATIONS_APPROVAL_VERSION', '1.0.0');
defined('OPERATIONS_APPROVAL_SLUG') or define('OPERATIONS_APPROVAL_SLUG', 'operations_approval');

// Installation runs before the plugin namespace is added to RISE's active-plugin autoloader.
require_once __DIR__ . '/Libraries/Operations_installer.php';

register_installation_hook(OPERATIONS_APPROVAL_SLUG, function () {
    (new Operations_installer())->install();
});

register_update_hook(OPERATIONS_APPROVAL_SLUG, function () {
    (new Operations_installer())->upgrade();
});

register_uninstallation_hook(OPERATIONS_APPROVAL_SLUG, function () {
    // Deliberately retain workflow and audit history. See UNINSTALL.md.
    (new Operations_installer())->markUninstalled();
});

register_activation_hook(OPERATIONS_APPROVAL_SLUG, function () {
    (new Operations_installer())->setActive(true);
});

register_deactivation_hook(OPERATIONS_APPROVAL_SLUG, function () {
    (new Operations_installer())->setActive(false);
});

app_hooks()->add_filter('app_filter_staff_left_menu', function ($menu) {
    return (new Operations_permissions())->addMenu($menu);
});

//Expose WorkFlow administration inside the native RISE Settings navigation.
app_hooks()->add_filter('app_filter_admin_settings_menu', function ($settings_menu) {
    $settings_menu['app_settings'][] = [
        'name' => 'workflow_settings',
        'url' => 'operations_settings'
    ];
    return $settings_menu;
});

app_hooks()->add_action('app_hook_role_permissions_extension', function () {
    echo view('operations_approval\Views\roles\permissions');
});

app_hooks()->add_filter('app_filter_role_permissions_save_data', function ($permissions) {
    return (new Operations_permissions())->collectRolePermissions($permissions);
});

app_hooks()->add_action('app_hook_after_cron_run', function () {
    (new \operations_approval\Libraries\Sla_service())->process();
});

app_hooks()->add_filter('app_filter_notification_config', function ($events) {
    $operationEvents = ['request_submitted', 'approval_assigned', 'request_approved', 'request_rejected', 'request_returned', 'information_requested', 'request_resubmitted', 'request_completed', 'approval_reminder', 'sla_breached', 'approval_delegated'];
    foreach ($operationEvents as $event) {
        $events['operations_' . $event] = [
            'notify_to' => ['operations_recipients'],
            'info' => function ($options) {
                $requestId = (int) get_array_value($options, 'plugin_request_id');
                return anchor(get_uri('operations/view/' . $requestId), app_lang('operations_view_request'));
            }
        ];
    }
    return $events;
});

app_hooks()->add_filter('app_filter_create_notification_where_query', function ($queries, $context) {
    $options = get_array_value($context, 'options');
    $recipients = preg_replace('/[^0-9,]/', '', (string) get_array_value($options, 'plugin_recipients'));
    if ($recipients) {
        $usersTable = db_connect('default')->getPrefix() . 'users';
        $queries[] = " OR FIND_IN_SET({$usersTable}.id, '{$recipients}') ";
    }
    return $queries;
}, 10, 2);

$routes = service('routes');
$routes->get('operations', '\operations_approval\Controllers\Operations::index');
$routes->get('operations/my_requests', '\operations_approval\Controllers\Operations::my_requests');
$routes->get('operations/requests', '\operations_approval\Controllers\Operations::requests');
$routes->get('operations/pending', '\operations_approval\Controllers\Operations::pending');
$routes->get('operations/new_request', '\operations_approval\Controllers\Operations::new_request');
$routes->get('operations/form/(:num)', '\operations_approval\Controllers\Operations::form/$1');
$routes->get('operations/view/(:num)', '\operations_approval\Controllers\Operations::view/$1');
$routes->get('operations/reports', '\operations_approval\Controllers\Operations::reports');
$routes->post('operations/create', '\operations_approval\Controllers\Operations::create', ['filter' => 'csrf']);
$routes->post('operations/submit/(:num)', '\operations_approval\Controllers\Operations::submit/$1', ['filter' => 'csrf']);
$routes->post('operations/decide/(:num)', '\operations_approval\Controllers\Operations::decide/$1', ['filter' => 'csrf']);
$routes->post('operations/comment/(:num)', '\operations_approval\Controllers\Operations::comment/$1', ['filter' => 'csrf']);
$routes->post('operations/request_information/(:num)', '\operations_approval\Controllers\Operations::request_information/$1', ['filter' => 'csrf']);
$routes->post('operations/respond_information/(:num)', '\operations_approval\Controllers\Operations::respond_information/$1', ['filter' => 'csrf']);
$routes->post('operations/resubmit/(:num)', '\operations_approval\Controllers\Operations::resubmit/$1', ['filter' => 'csrf']);
$routes->post('operations/cancel/(:num)', '\operations_approval\Controllers\Operations::cancel/$1', ['filter' => 'csrf']);
$routes->post('operations/delegate/(:num)', '\operations_approval\Controllers\Operations::delegate/$1', ['filter' => 'csrf']);
$routes->post('operations/upload/(:num)', '\operations_approval\Controllers\Operations::upload/$1', ['filter' => 'csrf']);
$routes->get('operations/download/(:num)', '\operations_approval\Controllers\Operations::download/$1');
$routes->get('operations/export', '\operations_approval\Controllers\Operations::export');
$routes->get('operations/receipt/(:num)', '\operations_approval\Controllers\Operations::receipt/$1');
$routes->post('operations/receipt/(:num)/email', '\operations_approval\Controllers\Operations::send_receipt_email/$1', ['filter' => 'csrf']);
$routes->get('operations/receipt/(:num)/share/(:segment)', '\operations_approval\Controllers\Operations::share_receipt/$1/$2');
$routes->get('operations_workflows', '\operations_approval\Controllers\Operations_workflows::index');
$routes->get('operations_workflows/edit', '\operations_approval\Controllers\Operations_workflows::edit');
$routes->get('operations_workflows/edit/(:num)', '\operations_approval\Controllers\Operations_workflows::edit/$1');
$routes->post('operations_workflows/save', '\operations_approval\Controllers\Operations_workflows::save', ['filter' => 'csrf']);
$routes->post('operations_workflows/publish/(:num)', '\operations_approval\Controllers\Operations_workflows::publish/$1', ['filter' => 'csrf']);
$routes->post('operations_workflows/toggle-status/(:num)', '\operations_approval\Controllers\Operations_workflows::toggle_status/$1', ['filter' => 'csrf']);
$routes->get('operations_settings', '\operations_approval\Controllers\Operations_settings::index');
$routes->post('operations_settings/save', '\operations_approval\Controllers\Operations_settings::save', ['filter' => 'csrf']);
$routes->post('operations_settings/save_department', '\operations_approval\Controllers\Operations_settings::save_department', ['filter' => 'csrf']);
$routes->post('operations_settings/save_delegation', '\operations_approval\Controllers\Operations_settings::save_delegation', ['filter' => 'csrf']);
$routes->post('customersapi/operations-login', '\operations_approval\Controllers\Operations_api::login');
$routes->get('customersapi/operations', '\operations_approval\Controllers\Operations_api::dashboard');
$routes->post('customersapi/profile/avatar', '\operations_approval\Controllers\Operations_api::avatar');
$routes->get('customersapi/operations/workflows', '\operations_approval\Controllers\Operations_api::workflows');
$routes->get('customersapi/operations/requests', '\operations_approval\Controllers\Operations_api::requests');
$routes->get('customersapi/operations/pending', '\operations_approval\Controllers\Operations_api::pending');
$routes->get('customersapi/operations/requests/(:num)', '\operations_approval\Controllers\Operations_api::show/$1');
$routes->post('customersapi/operations/requests', '\operations_approval\Controllers\Operations_api::create');
$routes->post('customersapi/operations/requests/(:num)/decision', '\operations_approval\Controllers\Operations_api::decision/$1');
$routes->post('customersapi/operations/requests/(:num)/comment', '\operations_approval\Controllers\Operations_api::comment/$1');
$routes->post('customersapi/operations/requests/(:num)/information', '\operations_approval\Controllers\Operations_api::information/$1');
$routes->post('customersapi/operations/requests/(:num)/resubmit', '\operations_approval\Controllers\Operations_api::resubmit/$1');
$routes->post('customersapi/operations/requests/(:num)/cancel', '\operations_approval\Controllers\Operations_api::cancel/$1');
$routes->post('customersapi/operations/requests/(:num)/attachments', '\operations_approval\Controllers\Operations_api::upload/$1');
$routes->post('customersapi/operations/attachments/(:num)/sign', '\operations_approval\Controllers\Operations_api::signAttachment/$1');
$routes->get('customersapi/operations/attachments/(:num)/download', '\operations_approval\Controllers\Operations_api::download/$1');
