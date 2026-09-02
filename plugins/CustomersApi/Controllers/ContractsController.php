<?php
namespace CustomersApi\Controllers;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Custom_fields_model;
use App\Models\Contracts_model;

class ContractsController extends RestApiController {

    public $Custom_fields_model;
    public $Contracts_model;

    public function __construct() {
		parent::__construct();
        $this->Custom_fields_model = new Custom_fields_model();
        $this->Contracts_model = new Contracts_model();
	}

    public function contracts(): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {

            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {

                $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("contracts", 0, "client");

                $options = array(
                    "client_id" => $client->id,
                    "custom_fields" => $custom_fields,
                );

                $data = $this->Contracts_model->get_details($options)->getResult();
                if($data){
                    return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $data]);
                }
                return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    public function showContract($id): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {
            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {
                $contract = $this->getContractRow($id,$client->id);
                if ($contract) {
                    return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $contract]);
                }
                return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    private function getContractRow($contract_id,$client_id)
    {
        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("contracts", 0, "client");

        $options = array(
            'id' => $contract_id,
            'client_id' => $client_id,
            'custom_fields' => $custom_fields,
            'exclude_draft' => true
        );
        
        $contract = $this->Contracts_model->get_details($options)->getRow();

        return $contract;
    }
}