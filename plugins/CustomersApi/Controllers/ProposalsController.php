<?php
namespace CustomersApi\Controllers;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Custom_fields_model;
use App\Models\Proposals_model;
use App\Models\Proposal_items_model;

class ProposalsController extends RestApiController {

    public $Custom_fields_model;
    public $Proposals_model;
    public $Proposal_items_model;

    public function __construct() {
		parent::__construct();
        $this->Custom_fields_model = new Custom_fields_model();
        $this->Proposals_model = new Proposals_model();
        $this->Proposal_items_model = new Proposal_items_model();
	}

    public function proposals(): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {

            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {

                $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("proposals", 0, "client");

                $options = array(
                    "client_id" => $client->id,
                    "custom_fields" => $custom_fields,
                    "exclude_draft" => true
                );

                $data = $this->Proposals_model->get_details($options)->getResult();
                if($data){
                    return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $data]);
                }
                return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    public function showProposal($id): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {
            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {
                $proposal = $this->getProposalRow($id,$client->id);
                if ($proposal) {
                    return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $proposal]);
                }
                return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    private function getProposalRow($proposal_id,$client_id)
    {
        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("proposals", 0, "client");

        $options = array(
            'id' => $proposal_id,
            'client_id' => $client_id,
            'custom_fields' => $custom_fields,
            'exclude_draft' => true
        );
        
        $items_options = array(
            "proposal_id" => $proposal_id,
        );
        
        $proposal = $this->Proposals_model->get_details($options)->getRow();
        $proposal->items = $this->Proposal_items_model->get_details($items_options)->getResult() ?? [];

        return $proposal;
    }
}