<?php

namespace App\Controllers;

use App\Libraries\Paypal;
use App\Libraries\Payment_processor;

//don't extend this controller from Pre_loader 
//because this will be called by Paypal 
//and login check is not required since we'll validate the data

class Paypal_redirect extends App_Controller {

    private $Payment_processor;

    function __construct() {
        parent::__construct();
        $this->Payment_processor = new Payment_processor($this);
    }

    function index($payment_verification_code = "") {
        if (!($payment_verification_code && count($_GET))) {
            show_404();
        }

        $options = array("code" => $payment_verification_code, "type" => "invoice_payment");
        $verification_info = $this->Verification_model->get_details($options)->getRow();
        if (!$verification_info) {
            show_404();
        }

        $Paypal = new Paypal();
        $payment = $Paypal->is_valid_ipn($_GET);
        if (!$payment) {
            show_404();
        }

        //so, the payment is valid
        $online_payment_data = array(
            "payment_amount" => get_array_value($payment->transactions, 0)->amount->total,
            "transaction_id" => get_array_value($_GET, "paymentId")
        );

        $this->Payment_processor->process_successful_payment($online_payment_data, $verification_info);
    }
}

/* End of file Paypal_redirect.php */
/* Location: ./app/controllers/Paypal_redirect.php */