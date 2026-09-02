<?php
namespace CustomersApi\Controllers;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Custom_fields_model;
use App\Models\Tickets_model;
use App\Models\Ticket_comments_model;
use App\Models\Ticket_types_model;

class TicketsController extends RestApiController {

    public $Custom_fields_model;
    public $Tickets_model;
    public $Ticket_comments_model;
    public $Ticket_types_model;

    public function __construct() {
		parent::__construct();
        $this->Custom_fields_model = new Custom_fields_model();
        $this->Tickets_model = new Tickets_model();
        $this->Ticket_comments_model = new Ticket_comments_model();
        $this->Ticket_types_model = new Ticket_types_model();
	}

    public function tickets(): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {

            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {

                $custom_fields = $this->Custom_fields_model->get_available_fields_for_table("tickets", 0, "client");

                $options = array(
                    "client_id" => $client->id,
                    "custom_fields" => $custom_fields,
                );

                $data = $this->Tickets_model->get_details($options)->getResult();
                if($data){
                    return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $data]);
                }
                return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    public function showTicket($id): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {

            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {

                return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $this->getTicketRow($id)]);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    public function storeTickets(): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $validation_array = array(
            "title" => "required",
            "description" => "required",
            "ticket_type_id" => "required"
        );

        $this->validate_submitted_data($validation_array);

        $user = $this->Users_model->get_one_where(array('email' => $email, 'deleted' => 0, 'user_type' => 'client'));
        if ($user) {

            $client = $this->Clients_model->get_one( $user->client_id );
            if ($client) {

                $ticket_type_id = $this->request->getPost('ticket_type_id');
                $requested_by = $user->id;
                $assigned_to = 0;

                //if this logged in user is a team member and there has a requested_by client contact, change the created_by field also
                $created_by = $user->id;

                $now = get_current_utc_time();

                $labels = $this->request->getPost('labels');
                validate_list_of_numbers($labels);

                $ticket_data = array(
                    "title" => $this->request->getPost('title'),
                    "client_id" => $client->id,
                    "project_id" => $this->request->getPost('project_id') ? $this->request->getPost('project_id') : 0,
                    "ticket_type_id" => $ticket_type_id,
                    "created_by" => $created_by,
                    "created_at" => $now,
                    "last_activity_at" => $now,
                    "labels" => $labels,
                    "assigned_to" => $assigned_to ? $assigned_to : 0,
                    "requested_by" => $requested_by ? $requested_by : 0
                );

                $ticket_data["creator_name"] = "";
                $ticket_data["creator_email"] = "";

                $ticket_data = clean_data($ticket_data);

                $ticket_id = $this->Tickets_model->ci_save($ticket_data);

                $target_path = get_setting("timeline_file_path");
                $files_data = move_files_from_temp_dir_to_permanent_dir($target_path, "ticket");

                if ($ticket_id) {

                    save_custom_fields("tickets", $ticket_id, 0, "client");

                    //ticket added. now add a comment in this ticket
                    $description = decode_ajax_post_data($this->request->getPost('description'));

                    $comment_data = array(
                        "description" => $description,
                        "ticket_id" => $ticket_id,
                        "created_by" => $user->id,
                        "created_at" => $now
                    );

                    $comment_data = clean_data($comment_data);

                    $comment_data["files"] = $files_data; //don't clean serilized data

                    $ticket_comment_id = $this->Ticket_comments_model->ci_save($comment_data);

                    if ($ticket_comment_id) {
                        log_notification("ticket_created", array("ticket_id" => $ticket_id, "ticket_comment_id" => $ticket_comment_id));
                    }

                    //don't add auto reply if it's created by team members
                    add_auto_reply_to_ticket($ticket_id);

                    return $this->respond(array("success" => true, "data" => $this->getTicketRow($ticket_id), 'id' => $ticket_id, 'message' => app_lang('record_saved')));
                } else {
                    return $this->respond(array("success" => false, 'message' => app_lang('error_occurred')));
                }
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    public function markTicketAsClosed($id): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {

            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {

                $data = array(
                    "status" => "closed"
                );

                $this->Tickets_model->ci_save($data, $id);

                return $this->respond(["success" => true, "message" => app_lang("ticket_closed")]);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    public function markTicketAsOpened($id): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_details( array('email' => $email ))->getRow();
        if ($user) {

            $client = $this->Clients_model->get_details(array('id' => $user->client_id))->getRow();
            if ($client) {

                $data = array(
                    "status" => "open"
                );

                $this->Tickets_model->ci_save($data, $id);

                return $this->respond(["success" => true, "message" => app_lang("ticket_reopened")]);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    public function storeTicketComment($id): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $this->validate_submitted_data(array(
            "description" => "required",
        ));

        $user = $this->Users_model->get_one_where(array('email' => $email, 'deleted' => 0, 'user_type' => 'client'));
        if ($user) {

            $client = $this->Clients_model->get_one( $user->client_id );
            if ($client) {

                $description = decode_ajax_post_data($this->request->getPost('description'));
                $now = get_current_utc_time();

                $target_path = get_setting("timeline_file_path");
                $files_data = move_files_from_temp_dir_to_permanent_dir($target_path, "ticket");
                $is_note = $this->request->getPost('is_note');

                $comment_data = array(
                    "description" => $description,
                    "ticket_id" => $id,
                    "created_by" => $user->id,
                    "created_at" => $now,
                    "files" => $files_data,
                    "is_note" => $is_note ? $is_note : 0
                );


                $comment_data = clean_data($comment_data);
                $comment_data["files"] = $files_data; //don't clean serialized data

                $comment_id = $this->Ticket_comments_model->ci_save($comment_data);
                if ($comment_id) {
                    //update ticket status;
                    $ticket_data = array(
                        "status" => "client_replied",
                        "last_activity_at" => $now
                    );
                    $ticket_data = clean_data($ticket_data);

                    $this->Tickets_model->ci_save($ticket_data, $id);

                    $comments_options = array("id" => $comment_id);
                    $comment = $this->Ticket_comments_model->get_details($comments_options)->getRow();

                    if (!$is_note) {
                        log_notification("ticket_commented", array("ticket_id" => $id, "ticket_comment_id" => $comment_id));
                    }

                    return $this->respond(array("success" => true, "data" => $comment, 'message' => app_lang('comment_submited')));
                } else {
                    return $this->respond(array("success" => false, 'message' => app_lang('error_occurred')));
                }
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    private function getTicketRow($ticket_id)
    {

        $sort_as_decending = get_setting("show_recent_ticket_comments_at_the_top");

        $comments_options = array(
            "ticket_id" => $ticket_id,
            "sort_as_decending" => $sort_as_decending
        );
        $ticketComments = array();
        
        $ticket = $this->Tickets_model->get_details(array('id' => $ticket_id))->getRow();
        $comments = $this->Ticket_comments_model->get_details($comments_options)->getResult();
        foreach ($comments as $comment){
            $comment->files = is_array($commentFiles = @unserialize((string) $comment->files)) ? $commentFiles : [];
            $ticketComments[] = $comment;
        }
        $ticket->comments = $ticketComments; 

        return $ticket;
    }

    public function getTicketTypes(): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $list_data = $this->Ticket_types_model->get_details()->getResult();
        if($list_data){
            return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $list_data]);
        }
        return $this->respond(["success" => false, 'message' => app_lang("no_data_found")], ResponseInterface::HTTP_NOT_FOUND);
    }
}