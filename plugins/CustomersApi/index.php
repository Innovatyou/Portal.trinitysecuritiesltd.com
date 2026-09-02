<?php
defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: Customers API
  Description: Customers-area endpoints REST API for RISE CRM.
  Version: 1.0
  Author: Branditta
  Author URL: https://codecanyon.net/user/branditta
*/

//add admin setting menu item
app_hooks()->add_filter('app_filter_admin_settings_menu', function ($settings_menu) {
    $settings_menu["plugins"][] = array("name" => "customersapi", "url" => "customersapi/settings");
    return $settings_menu;
});

//add setting link to the plugin setting
app_hooks()->add_filter('app_filter_action_links_of_CustomersApi', function () {
    $action_links_array = array(
        anchor(get_uri("customersapi/settings"), "Settings"),
    );

    return $action_links_array;
});

register_installation_hook("CustomersApi", function ($item_purchase_code) {
    
    if (!(isset($item_purchase_code) && $item_purchase_code)) {
        echo json_encode(array("success" => false, "message" => "Please enter a valid purchase code."));
        exit();
    }
    
    $bearer   = 'bearer RPF7ATdnrMyI8jyj3rdq4qriresAPL0G';
    $header   = [];
    $header[] = 'Content-length: 0';
    $header[] = 'Content-type: application/json; charset=utf-8';
    $header[] = 'Authorization: '.$bearer;

    $envato_url = 'https://api.envato.com/v3/market/author/sale/';
    $verify  = curl_init($envato_url.'?code='.$item_purchase_code);

    curl_setopt($verify, CURLOPT_HTTPHEADER, $header);
    curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($verify, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($verify, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($verify, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

    $verify_data = curl_exec($verify);
    curl_close($verify);

    if ('' != $verify_data) {
        $data = json_decode($verify_data);
        if (!empty($data->error)) {
            echo json_encode(array('success'=>false, 'message'=>$data->description));
            exit();
        }
        $item = 55477071;
        if ($item != $data->item->id) {
            echo json_encode(array('success'=>false, 'message'=>'Purchase key is not valid'));
            exit();
        }
    }
});

register_uninstallation_hook("CustomersApi", function () {
    $dbprefix = get_db_prefix();
    $db = db_connect('default');

    $sql_query = "DELETE FROM `" . $dbprefix . "settings` WHERE `" . $dbprefix . "settings`.`setting_name`='customersapi_secret_key';";
    $db->query($sql_query);

});