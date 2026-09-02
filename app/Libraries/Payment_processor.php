<?php

namespace App\Libraries;

class Payment_processor {

    private $ci;
    private $request;

    public function __construct($parent_controller_instance) {
        $this->ci = $parent_controller_instance;
        $this->request = \Config\Services::request(); // we can't use it from the passed instanace because it won't work when that's extended from security controller
    }

    public function init_online_payment($login_user_id = 0) {
        $invoice_id = $this->request->getPost("invoice_id");
        $subscription_id = $this->request->getPost("subscription_id");
        $currency = $this->request->getPost("currency");
        $payment_amount = $this->request->getPost("payment_amount");
        $description = $this->request->getPost("description");
        $verification_code = $this->request->getPost("verification_code");
        $contact_user_id = $login_user_id ? $login_user_id : $this->request->getPost("contact_user_id");
        $client_id = $this->request->getPost("client_id");
        $payment_method_id = $this->request->getPost("payment_method_id");
        $balance_due = $this->request->getPost("balance_due");
        $redirect_to = "";

        if ($invoice_id) {

            $invoice_info = $this->ci->Invoices_model->get_one($invoice_id);

            //validate public invoice information
            if (!$login_user_id && !validate_invoice_verification_code($verification_code, array("invoice_id" => $invoice_id, "client_id" => $client_id, "contact_id" => $contact_user_id))) {
                return false;
            }

            $redirect_to = "invoices/preview/$invoice_id";
            if ($verification_code) {
                $redirect_to = "pay_invoice/index/$verification_code";
            }
        } else {

            $invoice_info = new \stdClass;
            $invoice_info->display_id = app_lang("process_payment");
        }

        //check if partial payment allowed or not
        if (get_setting("allow_partial_invoice_payment_from_clients")) {
            $payment_amount = unformat_currency($payment_amount);
        } else {
            $payment_amount = $balance_due;
        }

        if ($subscription_id) {
            $redirect_to = "subscriptions/preview/$subscription_id";
        }

        $payment_method_info = $this->ci->Payment_methods_model->get_one_with_settings($payment_method_id);

        //validate payment amount
        if (isset($payment_method_info->minimum_payment_amount)) {

            $minimum_payment_amount = $payment_method_info->minimum_payment_amount ? $payment_method_info->minimum_payment_amount : 0;

            if ($payment_amount < $minimum_payment_amount * 1) {

                $minimum_payment_validation_error = app_lang('minimum_payment_validation_message') . " " . to_currency($minimum_payment_amount, $currency . " ");
                $session = \Config\Services::session();
                $session->setFlashdata("error_message", $minimum_payment_validation_error);
                app_redirect($redirect_to);
            }
        }

        //we'll verify the transaction with a random string code after completing the transaction
        $payment_verification_code = make_random_string();

        $payment_verification_data = array(
            "verification_code" => $verification_code ? $verification_code : "",
            "invoice_id" => $invoice_id ? $invoice_id : 0,
            "subscription_id" => $subscription_id ? $subscription_id : 0,
            "contact_user_id" => $contact_user_id ? $contact_user_id : 0,
            "client_id" => $client_id,
            "payment_method_id" => $payment_method_id,
        );

        $online_payment_data = array(
            "subscription_id" => $subscription_id,
            "currency" => $currency,
            "payment_amount" => $payment_amount,
            "product_description" => $description,
            "contact_user_id" => $contact_user_id,
            "client_id" => $client_id,
            "product_name" => $invoice_info->display_id,
            "cancel_url" => get_uri($redirect_to),
        );

        $checkout_url = null;
        if ($payment_method_info->type == "stripe") {

            $success_url = get_uri("stripe_redirect/index/$payment_verification_code");
            if ($subscription_id) {
                $success_url = get_uri("stripe_redirect/subscription/$payment_verification_code");
            }

            $online_payment_data["success_url"] = $success_url;

            $Stripe = new Stripe();
            $checkout_url = $Stripe->get_stripe_checkout_session($online_payment_data, $payment_verification_data);
        } else if ($payment_method_info->type == "paypal_payments_standard") {
            $Paypal = new Paypal();
            $online_payment_data["success_url"] = get_uri("paypal_redirect/index/$payment_verification_code");
            $checkout_url = $Paypal->get_paypal_checkout_url($online_payment_data, $payment_verification_data);
        }

        $verification_data = array(
            "type" => "invoice_payment",
            "code" => $payment_verification_code,
            "params" => serialize($payment_verification_data)
        );

        $this->ci->Verification_model->ci_save($verification_data);

        return $checkout_url;
    }

    private function _process_order_payment($verification_code, $verification_data) {

        // create order first
        $order_data = array(
            "client_id" => get_array_value($verification_data, "client_id"),
            "order_date" => get_today_date(),
            "note" => get_array_value($verification_data, "order_note"),
            "created_by" => get_array_value($verification_data, "client_contact_id"),
            "status_id" => get_setting("order_status_after_payment") ? get_setting("order_status_after_payment") : $this->ci->Order_status_model->get_first_status(),
            "tax_id" => get_array_value($verification_data, 'tax_id') ? get_array_value($verification_data, 'tax_id') : 0,
            "tax_id2" => get_array_value($verification_data, 'tax_id2') ? get_array_value($verification_data, 'tax_id2') : 0,
            "company_id" => get_default_company_id(),
            "created_by_hash" => get_array_value($verification_data, "created_by_hash"),
            "files" => get_array_value($verification_data, "files")
        );

        $order_data = clean_data($order_data);

        $order_id = $this->ci->Orders_model->ci_save($order_data);
        if (!$order_id) {
            log_message("error", "Order creation failed.");
            exit;
        }

        // save custom fields
        $custom_field_values = get_array_value($verification_data, "custom_field_values");
        if ($custom_field_values) {

            // set post value to work later with the existing function
            $this->request->setGlobal('post', $custom_field_values);
            $_POST = array_merge($_POST, $custom_field_values);

            save_custom_fields("orders", $order_id, 0, "client"); // this functionality is available for clients only
        }

        // save items to this order
        $order_items = get_array_value($verification_data, "order_items");
        foreach ($order_items as $order_item) {
            $order_item_data = array("order_id" => $order_id);
            $this->ci->Order_items_model->delete($order_item->id, true); // undo deletion
            $this->ci->Order_items_model->ci_save($order_item_data, $order_item->id);
        }

        // create invoice
        $invoice_info = $this->ci->Invoices_model->get_one_where(array("order_id" => $order_id, "deleted" => 0));
        if ($invoice_info->id) {
            $invoice_id = $invoice_info->id;
        } else { //create invoice
            $invoice_id = create_invoice_from_order($order_id);
        }

        $this->ci->Settings_model->save_setting("user_" . get_array_value($verification_data, "client_contact_id") . "_order_payment_hash", "", "user");
        $this->_delete_verification_data($verification_code);

        return $invoice_id;
    }

    private function _process_non_invoice_payment($payment_verification_data) {
        $verification_code = get_array_value($payment_verification_data, "verification_code");
        if (!$verification_code) {
            // verification code is required to continue
            // this is the verification code where the payment should be processed
            log_message("error", "Verification code is required to process non-invoice payment.");
            exit;
        }

        // find the main verification info
        $options = array("code" => $verification_code, "type" => "invoice_payment");
        $verification_info = $this->ci->Verification_model->get_details($options)->getRow();
        if (!($verification_info && $verification_info->id)) {
            log_message("error", "Verification code is invalid to process non-invoice payment.");
            exit;
        }

        $verification_data = unserialize($verification_info->params);
        $order_items = get_array_value($verification_data, "order_items");
        if (!($order_items && count($order_items))) {
            // currently we only support order payment without invoice
            exit;
        }

        return $this->_process_order_payment($verification_code, $verification_data);
    }

    private function _delete_verification_data($verification_code = "") {
        if (!$verification_code) {
            return;
        }

        $options = array("code" => $verification_code, "type" => "invoice_payment");
        $verification_info = $this->ci->Verification_model->get_details($options)->getRow();

        if ($verification_info && $verification_info->id) {
            $this->ci->Verification_model->delete_permanently($verification_info->id);
        }
    }

    public function process_successful_payment($online_payment_data, $payment_verification_info, $show_success_message_anyway = false) {

        // assuming the payment is valid by the payment method
        $payment_verification_data = unserialize($payment_verification_info->params);

        $transaction_id = get_array_value($online_payment_data, "transaction_id");
        $invoice_id = get_array_value($payment_verification_data, "invoice_id");
        $created_by = get_array_value($payment_verification_data, "contact_user_id");
        $is_invoice_payment = true;

        if (!$invoice_id) {
            $invoice_id = $this->_process_non_invoice_payment($payment_verification_data);
            $is_invoice_payment = false;
        }

        $invoice_payment_final_data = array(
            "invoice_id" => $invoice_id,
            "payment_date" => get_current_utc_time(),
            "payment_method_id" => get_array_value($payment_verification_data, "payment_method_id"),
            "note" => "",
            "amount" => get_array_value($online_payment_data, "payment_amount"),
            "transaction_id" => $transaction_id,
            "created_at" => get_current_utc_time(),
            "created_by" => $created_by,
        );

        // check if already a payment done with this transaction
        $existing = $this->ci->Invoice_payments_model->get_one_where(array("transaction_id" => $transaction_id));
        if (!$existing->id) {
            $invoice_payment_id = $this->ci->Invoice_payments_model->ci_save($invoice_payment_final_data);

            //as receiving payment for the invoice, we'll remove the 'draft' status from the invoice 
            $this->ci->Invoices_model->update_invoice_status($invoice_id);

            log_notification("invoice_payment_confirmation", array("invoice_payment_id" => $invoice_payment_id, "invoice_id" => $invoice_id), "0");
            log_notification("invoice_online_payment_received", array("invoice_payment_id" => $invoice_payment_id, "invoice_id" => $invoice_id), $created_by);
        }

        // this param is required because on stripe the payment will be done by webhook
        // so if the payment is already done, don't show the success message
        if ($existing->id && !$show_success_message_anyway) {
            return;
        }

        $verification_code = get_array_value($payment_verification_data, "verification_code");
        if ($verification_code && $is_invoice_payment) {
            // this verification code is for public invoice preview and that shouldn't be deleted
            $redirect_to = "pay_invoice/index/$verification_code";
        } else {
            $redirect_to = "invoices/preview/$invoice_id";
        }

        // in this new system, there will always be a verification code 
        // so, we can delete it here
        $this->ci->Verification_model->delete_permanently($payment_verification_info->id);

        $session = \Config\Services::session();
        $session->setFlashdata("success_message", app_lang("payment_success_message"));
        app_redirect($redirect_to);
    }
}
