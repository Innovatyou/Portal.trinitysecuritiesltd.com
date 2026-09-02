<?php
namespace CustomersApi\Controllers;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Custom_fields_model;
use App\Models\Projects_model;
use App\Models\Project_comments_model;
use App\Models\Invoices_model;
use App\Models\Tasks_model;

class ProjectsController extends RestApiController {

    public function __construct() {
		parent::__construct();
        $this->Custom_fields_model = new Custom_fields_model();
        $this->Projects_model = new Projects_model();
        $this->Project_comments_model = new Project_comments_model();
        $this->Invoices_model = new Invoices_model();
        $this->Tasks_model = new Tasks_model();
	}

    public function projects(): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {

            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {

                $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("projects", 0, "client");

                $options = array(
                    "client_id" => $client->id,
                    "custom_fields" => $custom_fields,
                );

                $data = $this->Projects_model->get_details($options)->getResult();
                if($data){
                    return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $data]);
                }
                return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    public function showProject($id): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {
            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {
                $project = $this->getProjectRow($id,$client->id);
                if ($project) {
                    return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $project]);
                }
                return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    private function getProjectRow($project_id,$client_id)
    {
        $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("projects", 0, "client");

        $options = array(
            'id' => $project_id,
            'client_id' => $client_id,
            'custom_fields' => $custom_fields,
            'exclude_draft' => true
        );
        $project = $this->Projects_model->get_details($options)->getRow();
        
        $comments_options = array(
            "customer_feedback_id" => $project_id
        );
        $projectComments = array();
        $comments = $this->Project_comments_model->get_details($comments_options)->getResult();
        foreach ($comments as $comment){
            $comment->files = is_array($commentFiles = @unserialize((string) $comment->files)) ? $commentFiles : [];
            $comment->created_by_avatar = is_array($avatarFile = @unserialize((string) $comment->created_by_avatar)) ? ($avatarFile['file_name'] ?? '') : '';
            $projectComments[] = $comment;
        }
        $project->comments = $projectComments; 

        return $project;
    }

    public function getProjectTasks($id): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {

            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {

                $options = array(
                    "project_id" => $id,
                );

                $data = $this->Tasks_model->get_details($options)->getResult();
                if($data){
                    return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $data]);
                }
                return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    public function getProjectInvoices($id): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {

            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {

                $options = array(
                    "project_id" => $id,
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
}