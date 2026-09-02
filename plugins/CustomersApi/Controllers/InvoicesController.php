<?php
namespace CustomersApi\Controllers;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Custom_fields_model;
use App\Models\Invoices_model;
use App\Models\Invoice_payments_model;
use App\Models\Invoice_items_model;

class InvoicesController extends RestApiController {

    public $Custom_fields_model;
    public $Invoices_model;
    public $Invoice_payments_model;
    public $Invoice_items_model;

    public function __construct() {
		parent::__construct();
        $this->Custom_fields_model = new Custom_fields_model();
        $this->Invoices_model = new Invoices_model();
        $this->Invoice_payments_model = new Invoice_payments_model();
        $this->Invoice_items_model = new Invoice_items_model();
	}

    public function invoices(): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {

            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {

                $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("invoices", 0, "client");

                $options = array(
                    "client_id" => $client->id,
                    "custom_fields" => $custom_fields,
                    "exclude_draft" => true
                );

                $data = $this->Invoices_model->get_details($options)->getResult();
                if($data){
                    return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $data]);
                }
                return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    public function showInvoice($id): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {
            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {
                $invoice = $this->getInvoiceRow($id,$client->id);
                if ($invoice) {
                    return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $invoice]);
                }
                return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    private function getInvoiceRow($invoice_id,$client_id)
    {
        
        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("invoices", 0, "client");

        $options = array(
            "id" => $invoice_id,
            "client_id" => $client_id,
            "custom_fields" => $custom_fields,
        );
        
        $items_options = array(
            "invoice_id" => $invoice_id,
        );
        
        $invoice = $this->Invoices_model->get_details($options)->getRow();
        $invoice->items = $this->Invoice_items_model->get_details($items_options)->getResult() ?? [];
        $invoice->payments = $this->Invoice_payments_model->get_details($items_options)->getResult() ?? [];

        return $invoice;
    }
}