<?php

namespace App\Libraries;

class Paypal {

    private $paypal_live_url = "https://api-m.paypal.com/v1";
    private $paypal_sandbox_url = "https://api-m.sandbox.paypal.com/v1";
    private $paypal_url = "";
    private $paypal_config;
    private $Payment_methods_model;

    public function __construct() {
        $this->Payment_methods_model = model("App\Models\Payment_methods_model");
        $this->paypal_config = $this->Payment_methods_model->get_oneline_payment_method("paypal_payments_standard");

        if ($this->paypal_config->paypal_live == "1") {
            $this->paypal_url = $this->paypal_live_url;
        } else {
            $this->paypal_url = $this->paypal_sandbox_url;
        }
    }

    public function get_paypal_checkout_url($data = array(), &$payment_verification_data = array()) {
        $currency = get_array_value($data, "currency");
        $payment_amount = get_array_value($data, "payment_amount");
        $product_name = get_array_value($data, "product_name");
        $product_description = get_array_value($data, "product_description");
        $cancel_url = get_array_value($data, "cancel_url");
        $success_url = get_array_value($data, "success_url");

        $paypal_payment_data = array(
            "intent" => "sale",
            "payer" => array(
                "payment_method" => "paypal"
            ),
            "transactions" => array(
                array(
                    "amount" => array(
                        "total" => $payment_amount,
                        "currency" => $currency,
                    ),
                    "description" => $product_description,
                    "invoice_number" => $product_name . " - " . strtoupper(make_random_string()), //it'll give duplication error if any client wants to pay multiple times on a same invoice like partial payment
                    "payment_options" => array(
                        "allowed_payment_method" => "INSTANT_FUNDING_SOURCE"
                    ),
                ),
            ),
            "redirect_urls" => array(
                "return_url" => $success_url,
                "cancel_url" => $cancel_url
            )
        );

        $checkout_info = $this->do_request("POST", "/payments/payment", $paypal_payment_data);
        if ($checkout_info->id) {
            $checkout_url = get_array_value($checkout_info->links, "1");
            $checkout_url = $checkout_url->href;
            return $checkout_url;
        }
    }

    private function common_error_handling_for_curl($result, $err) {
        try {
            $result = json_decode($result);
        } catch (\Exception $ex) {
            echo json_encode(array("success" => false, 'message' => $ex->getMessage()));
            exit();
        }

        if ($err) {
            //got curl error
            echo json_encode(array("success" => false, 'message' => "cURL Error #:" . $err));
            exit();
        }

        if (isset($result->error_description) && $result->error_description) {
            //got error message from curl
            echo json_encode(array("success" => false, 'message' => $result->error_description));
            exit();
        }

        if (
            isset($result->error) && $result->error &&
            isset($result->error->message) && $result->error->message &&
            isset($result->error->code) && $result->error->code !== "InvalidAuthenticationToken"
        ) {
            //got error message from curl
            echo json_encode(array("success" => false, 'message' => $result->error->message));
            exit();
        }

        return $result;
    }

    private function headers($access_token) {
        return array(
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        );
    }

    //get access token everytime since we won't get refresh token
    private function get_access_token() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->paypal_url . '/oauth2/token');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->paypal_config->client_id . ':' . $this->paypal_config->client_secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
            'grant_type' => 'client_credentials'
        )));

        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        $result = $this->common_error_handling_for_curl($result, $err);

        return $result->access_token;
    }

    private function do_request($method, $path, $body = array()) {
        if (is_array($body)) {
            // Treat an empty array in the body data as if no body data was set
            if (!count($body)) {
                $body = '';
            } else {
                $body = json_encode($body);
            }
        }

        $method = strtoupper($method);
        $url = $this->paypal_url . $path;

        $access_token = $this->get_access_token();
        if (!$access_token) {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
            exit();
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers($access_token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (in_array($method, array('DELETE', 'PATCH', 'POST', 'PUT', 'GET'))) {

            // All except DELETE can have a payload in the body
            if ($method != 'DELETE' && strlen($body)) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        $result = $this->common_error_handling_for_curl($result, $err);

        return $result;
    }

    public function is_valid_ipn($payment_info) {
        $payment_id = get_array_value($payment_info, "paymentId");
        $payer_id = get_array_value($payment_info, "PayerID");

        $checkout_info = $this->do_request("POST", "/payments/payment/$payment_id/execute", array("payer_id" => $payer_id));
        if (!($checkout_info && $checkout_info->id)) {
            return false;
        }

        if ($checkout_info && $checkout_info->state === "approved") {
            //so the payment is successful
            return $checkout_info;
        }
    }
}
