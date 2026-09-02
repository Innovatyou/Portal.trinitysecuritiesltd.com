<?php

namespace App\Controllers;

use App\Libraries\Paytm;
use App\Libraries\Payment_processor;

//don't extend this controller from Pre_loader 
//because this will be called by Paytm
//and login check is not required since we'll validate the data

class Paytm_redirect extends App_Controller {

    private $Payment_processor;

    function __construct() {
        parent::__construct();
        $this->Payment_processor = new Payment_processor($this);
    }

    function index($payment_verification_code = "") {
        if (!($payment_verification_code && isset($_POST["CHECKSUMHASH"]))) {
            show_404();
        }

        // verification code should be 10 characters
        if (strlen($payment_verification_code) !== 10) {
            show_404();
        }

        $paytm = new Paytm();

        //get verification data
        $options = array("code" => $payment_verification_code, "type" => "invoice_payment");
        $verification_info = $this->Verification_model->get_details($options)->getRow();
        if (!($verification_info && $verification_info->id)) {
            show_404();
        }

        $payment_data = unserialize($verification_info->params);
        $verification_code = get_array_value($payment_data, "verification_code");
        $invoice_id = get_array_value($payment_data, "invoice_id");

        $data_array = $_POST;

        //check if it's a success transaction
        if (get_array_value($data_array, "STATUS") !== "TXN_SUCCESS") {
            //failed transaction, redirect with error message
            $this->session->setFlashdata("error_message", get_array_value($data_array, "RESPMSG"));
            $this->redirect_to_invoice($invoice_id, $verification_code);
        }

        //validate the checksum hash
        $is_valid_checksum_hash = $paytm->is_valid_checksum_hash($data_array);
        if (!$is_valid_checksum_hash) {
            show_404();
        }

        //so, the payment is valid
        $online_payment_data = array(
            "payment_amount" => get_array_value($data_array, "TXNAMOUNT"),
            "transaction_id" => get_array_value($data_array, "TXNID"),
        );

        $this->Payment_processor->process_successful_payment($online_payment_data, $verification_info);
    }

    private function redirect_to_invoice($invoice_id = 0, $verification_code = "") {
        $redirect_to = "invoices/preview/$invoice_id";
        if ($verification_code) {
            $redirect_to = "pay_invoice/index/$verification_code";
        }

        app_redirect($redirect_to);
    }
}

/* End of file Paytm_redirect.php */
/* Location: ./app/controllers/Paytm_redirect.php */