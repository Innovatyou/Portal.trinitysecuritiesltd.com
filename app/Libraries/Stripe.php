<?php

namespace App\Libraries;

class Stripe {

    private $stripe_config;
    private $Users_model;
    private $Clients_model;

    public function __construct() {
        $Payment_methods_model = model("App\Models\Payment_methods_model");

        $this->stripe_config = $Payment_methods_model->get_oneline_payment_method("stripe");
        $this->Users_model = model("App\Models\Users_model");
        $this->Clients_model = model("App\Models\Clients_model");

        require_once(APPPATH . "ThirdParty/Stripe/vendor/autoload.php");

        \Stripe\Stripe::setApiKey($this->stripe_config->secret_key);
        \Stripe\Stripe::setApiVersion("2022-11-15");
    }

    public function get_stripe_checkout_session($data = array(), &$payment_verification_data = array()) {
        $subscription_id = get_array_value($data, "subscription_id");
        $payment_amount = get_array_value($data, "payment_amount");
        $contact_user_id = get_array_value($data, "contact_user_id");
        $client_id = get_array_value($data, "client_id");
        $product_name = get_array_value($data, "product_name");
        $product_description = get_array_value($data, "product_description");
        $cancel_url = get_array_value($data, "cancel_url");
        $success_url = get_array_value($data, "success_url");
        $currency = get_array_value($data, "currency");

        $payment_method_types = array("card");

        if ($currency == "EUR" && isset($this->stripe_config->enable_stripe_ideal_payment) && $this->stripe_config->enable_stripe_ideal_payment) {
            $payment_method_types = array("card", "ideal");
        }

        if ($subscription_id) {
            //create/get existing stripe client first
            $stripe_customer_id = $this->get_customer_id($client_id, $contact_user_id);

            //create session to add card
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => $payment_method_types,
                'mode' => 'setup',
                'customer' => $stripe_customer_id,
                'success_url' => $success_url,
                'cancel_url' => $cancel_url,
            ]);
        } else { //single time payment
            $session = \Stripe\Checkout\Session::create(array(
                'mode' => 'payment',
                'payment_method_types' => $payment_method_types,
                'line_items' => array(
                    array(
                        'quantity' => 1,
                        'price_data' => array(
                            'unit_amount' => $payment_amount * 100, //stripe will devide it with 100
                            'currency' => $currency,
                            'product_data' => array(
                                'name' => $product_name,
                                'description' => $product_description,
                                'images' => array(
                                    get_file_uri("assets/images/stripe-payment-logo.png")
                                ),
                            )
                        ),
                    )
                ),
                'payment_intent_data' => array(
                    'description' => $product_name . ", " . $product_description,
                    'metadata' => $payment_verification_data,
                    //'setup_future_usage' => 'off_session', //save this paymentIntent's payment method for future use
                ),
                'success_url' => $success_url,
                'cancel_url' => $cancel_url,
            ));
        }

        if ($session->id) {
            //so, the session creation is success
            //save ipn data to db
            if ($subscription_id) {
                $payment_verification_data["setup_intent"] = $session->setup_intent;
            } else {
                /***
                  so, the session creation is success
                  save ipn data to db
                  store the session id now
                  because in the latest version, we won't get payment_intent here
                  but it'll be available after the payment
                  so get the payment_intent after the payment with the session_id
                 */
                $payment_verification_data["session_id"] = $session->id;
            }

            return $session->url;
        }
    }

    private function get_customer_id($client_id, $contact_user_id) {
        $client_info = $this->Clients_model->get_one($client_id);

        if ($client_info->stripe_customer_id) {
            return $client_info->stripe_customer_id;
        } else {
            //create stripe client
            $user_info = $this->Users_model->get_one($contact_user_id);
            $customer = \Stripe\Customer::create(array(
                "name" => $client_info->company_name,
                "phone" => $client_info->phone,
                "email" => $user_info->email,
                "address" => array(
                    "line1" => $client_info->address,
                    "city" => $client_info->city,
                    "state" => $client_info->state,
                    "postal_code" => $client_info->zip,
                    "country" => $client_info->country,
                ),
            ));

            //save the stripe customer id to clients table
            $client_data = array("stripe_customer_id" => $customer->id);
            $this->Clients_model->ci_save($client_data, $client_id);

            return $customer->id;
        }
    }

    public function get_publishable_key() {
        return $this->stripe_config->publishable_key;
    }

    public function retrieve_session($session_id) {
        $session = \Stripe\Checkout\Session::retrieve($session_id);
        return $session;
    }

    public function retrieve_payment_intent($payment_intent_id) {
        $payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
        return $payment_intent;
    }

    public function is_valid_ipn($session_id) {
        //get the payment_intent with the session_id
        $session = $this->retrieve_session($session_id);
        $payment_intent = $this->retrieve_payment_intent($session->payment_intent);
        if ($payment_intent && $payment_intent->status == "succeeded") {
            //so the payment is successful
            return $payment_intent;
        }
    }

    public function get_products_list() {
        return \Stripe\Product::all(array("active" => true, "limit" => 100));
    }

    public function retrieve_customer($customer_id) {
        return \Stripe\Customer::retrieve($customer_id);
    }

    public function update_customer($customer_id, $options = array()) {
        return \Stripe\Customer::update($customer_id, $options);
    }

    public function retrieve_setup_intent($setup_intent) {
        return \Stripe\SetupIntent::retrieve($setup_intent);
    }

    public function retrieve_product($product_id) {
        return \Stripe\Product::retrieve($product_id);
    }

    public function create_subscription($options = array()) {
        return \Stripe\Subscription::create($options);
    }

    public function retrieve_payment_method($payment_method_id) {
        return \Stripe\PaymentMethod::retrieve($payment_method_id);
    }

    public function retrieve_all_taxes() {
        return \Stripe\TaxRate::all(array("inclusive" => false));
    }

    public function retrieve_all_prices_of_the_product($product_id) {
        return \Stripe\Price::all(array("product" => $product_id, "type" => "recurring"));
    }

    public function retrieve_price($price_id) {
        return \Stripe\Price::retrieve($price_id);
    }

    public function create_webhook($webhook_listener_link) {
        return \Stripe\WebhookEndpoint::create(array(
            'url' => get_uri("webhooks_listener/stripe_subscription") . "/" . $webhook_listener_link,
            'enabled_events' => array('invoice.payment_succeeded', 'invoice.payment_failed'),
        ));
    }

    public function update_webhook($webhook_id, $webhook_listener_link) {
        return \Stripe\WebhookEndpoint::update($webhook_id, array(
            'url' => get_uri("webhooks_listener/stripe_subscription") . "/" . $webhook_listener_link,
        ));
    }

    public function retrieve_subscription($subscription_id) {
        return \Stripe\Subscription::retrieve($subscription_id);
    }

    public function cancel_subscription($subscription_id) {
        $subscription = $this->retrieve_subscription($subscription_id);
        $subscription->cancel();
    }
}
