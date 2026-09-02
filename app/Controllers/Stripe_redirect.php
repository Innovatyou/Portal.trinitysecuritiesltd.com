<?php

namespace App\Controllers;

use App\Libraries\Stripe;
use App\Libraries\Payment_processor;

//don't extend this controller from Pre_loader 
//because this will be called by Stripe 
//and login check is not required since we'll validate the data

class Stripe_redirect extends App_Controller {

    private $stripe;
    private $Payment_processor;

    function __construct() {
        parent::__construct();
        $this->stripe = new Stripe();
        $this->Payment_processor = new Payment_processor($this);
    }

    function index($payment_verification_code = "") {

        $verification_info = $this->_get_verification_info($payment_verification_code);
        $payment_verification_data = unserialize($verification_info->params);

        $payment = $this->stripe->is_valid_ipn(get_array_value($payment_verification_data, "session_id"));
        if (!$payment) {
            show_404();
        }

        //so, the payment is valid
        $online_payment_data = array(
            "payment_amount" => $payment->amount / 100,
            "transaction_id" => $payment->id,
        );

        $this->Payment_processor->process_successful_payment($online_payment_data, $verification_info, true);
    }

    private function _get_verification_info($payment_verification_code) {
        if (!$payment_verification_code) {
            show_404();
        }

        $options = array("code" => $payment_verification_code, "type" => "invoice_payment");
        $verification_info = $this->Verification_model->get_details($options)->getRow();
        if (!$verification_info) {
            show_404();
        }

        return $verification_info;
    }

    function subscription($payment_verification_code = "") {

        $verification_info = $this->_get_verification_info($payment_verification_code);
        $payment_verification_data = unserialize($verification_info->params);

        $subscription_id = get_array_value($payment_verification_data, "subscription_id");
        if (!$subscription_id) {
            show_404();
        }

        $customer_id = $this->Clients_model->get_stripe_customer_id($subscription_id);
        $subscription_info = $this->Subscriptions_model->get_details(array("id" => $subscription_id))->getRow();

        $stripe = new Stripe();
        $stripe_payment_method_id = $stripe->retrieve_setup_intent(get_array_value($payment_verification_data, "setup_intent"))->payment_method;
        $stripe_product_info = $stripe->retrieve_product($subscription_info->stripe_product_id);
        $subscription_item_info = $this->Subscription_items_model->get_one_where(array("subscription_id" => $subscription_info->id, "deleted" => 0));

        $tax_rates = array();
        if ($subscription_info->stripe_tax_id) {
            array_push($tax_rates, $subscription_info->stripe_tax_id);
        }
        if ($subscription_info->stripe_tax_id2) {
            array_push($tax_rates, $subscription_info->stripe_tax_id2);
        }


        $subscription_data = array();

        //create subscription with this payment method
        $stripe_subscription_data = array(
            "customer" => $customer_id,
            "items" => array(
                array(
                    "price" => $subscription_info->stripe_product_price_id,
                    "quantity" => $subscription_item_info->quantity,
                    "tax_rates" => $tax_rates
                )
            ),
            "default_payment_method" => $stripe_payment_method_id,
            "metadata" => array(
                "subscription_id" => $subscription_id,
                "contact_user_id" => get_array_value($payment_verification_data, "contact_user_id"),
                "payment_method_id" => get_array_value($payment_verification_data, "payment_method_id"),
            ),
            "proration_behavior" => "none"
        );

        $billing_cycle_anchor = $subscription_info->bill_date;
        $today = get_my_local_time("Y-m-d H:i:s");
        if ($billing_cycle_anchor > $today) {
            $stripe_subscription_data["billing_cycle_anchor"] = strtotime($billing_cycle_anchor);
        } else {
            $subscription_data["bill_date"] = $today;
        }



        //prepare the last billed date 
        if ($subscription_info->no_of_cycles) {
            $last_billed_date = $subscription_info->bill_date;
            for ($i = 0; $i < $subscription_info->no_of_cycles; $i++) {
                $last_billed_date = add_period_to_date($last_billed_date, $subscription_info->repeat_every, $subscription_info->repeat_type);
            }

            //add one more day to work on stripe
            $last_billed_date = add_period_to_date($last_billed_date, 1, "days");

            $stripe_subscription_data["cancel_at"] = strtotime($last_billed_date);
        }

        try {
            $stripe_subscription_info = $stripe->create_subscription($stripe_subscription_data);

            //save subscription id on the subscription
            //it'll also take the first payment now
            //grab that with the same webhook
            $subscription_data["stripe_subscription_id"] = $stripe_subscription_info->id;
            $subscription_data["status"] = "active";
            $this->Subscriptions_model->ci_save($subscription_data, $subscription_id);

            //save the last 4 digits of card to clients table        
            $client_data = array("stripe_card_ending_digit" => $stripe->retrieve_payment_method($stripe_payment_method_id)->card->last4);
            $this->Clients_model->ci_save($client_data, get_array_value($payment_verification_data, "client_id"));

            //delete the verification data
            $this->Verification_model->delete_permanently($verification_info->id);

            log_notification("subscription_started", array("subscription_id" => $subscription_id));

            $this->session->setFlashdata("success_message", app_lang("subscription_success_message"));
            app_redirect("subscriptions/preview/$subscription_id");
        } catch (\Exception $ex) {
            echo json_encode(array("success" => false, "message" => $ex->getMessage()));
        }
    }
}

/* End of file Stripe_redirect.php */
/* Location: ./app/controllers/Stripe_redirect.php */