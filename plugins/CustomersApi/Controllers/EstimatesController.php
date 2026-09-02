<?php
namespace CustomersApi\Controllers;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Custom_fields_model;
use App\Models\Estimates_model;
use App\Models\Estimate_items_model;

class EstimatesController extends RestApiController {

    public $Custom_fields_model;
    public $Estimates_model;
    public $Estimate_items_model;

    public function __construct() {
		parent::__construct();
        $this->Custom_fields_model = new Custom_fields_model();
        $this->Estimates_model = new Estimates_model();
        $this->Estimate_items_model = new Estimate_items_model();
	}

    public function estimates(): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {

            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {

                $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("estimates", 0, "client");

                $options = array(
                    "client_id" => $client->id,
                    "custom_fields" => $custom_fields,
                );

                $data = $this->Estimates_model->get_details($options)->getResult();
                if($data){
                    return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $data]);
                }
                return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    public function showEstimate($id): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {
            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {
                $estimate = $this->getEstimateRow($id,$client->id);
                if ($estimate) {
                    return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $estimate]);
                }
                return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    private function getEstimateRow($estimate_id,$client_id)
    {
        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("estimates", 0, "client");

        $options = array(
            'id' => $estimate_id,
            'client_id' => $client_id,
            'custom_fields' => $custom_fields,
            'exclude_draft' => true
        );
        
        $items_options = array(
            "estimate_id" => $estimate_id,
        );
        
        $estimate = $this->Estimates_model->get_details($options)->getRow();
        $estimate->items = $this->Estimate_items_model->get_details($items_options)->getResult() ?? [];

        return $estimate;
    }
}