<?php
namespace CustomersApi\Controllers;

use App\Controllers\App_Controller;
use App\Models\Clients_model;
use App\Models\Email_templates_model;
use App\Models\Settings_model;
use App\Models\Users_model;
use App\Models\Verification_model;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once(PLUGINPATH.'/CustomersApi/Vendor/autoload.php');

class RestApiController extends ResourceController
{
    public $Settings_model;
    private $jwtSecret;
    private $parser;
    private $alg = 'HS256';

    function __construct()
    {
        $db = db_connect('default');
        $settingsTable = $db->getPrefix() . 'oa_settings';
        if ($db->tableExists($settingsTable)) {
            $mobileSetting = $db->table($settingsTable)
                ->select('setting_value')
                ->where('setting_key', 'mobile_app_enabled')
                ->get()
                ->getRow();
            if ($mobileSetting && (string) $mobileSetting->setting_value !== '1') {
                response()
                    ->setStatusCode(ResponseInterface::HTTP_SERVICE_UNAVAILABLE)
                    ->setJSON([
                        'success' => false,
                        'message' => 'The mobile app is currently disabled by an administrator.'
                    ])
                    ->send();
                exit;
            }
        }

        $this->Users_model = new Users_model();
        $this->Clients_model = new Clients_model();
        $this->Settings_model = new Settings_model();
        $this->Email_templates_model = new Email_templates_model();
        $this->Verification_model = new Verification_model();

        $this->parser = \Config\Services::parser();

        $this->jwtSecret = $this->Settings_model->get_setting("customersapi_secret_key");
    }

    private function generateJwtToken($email): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + 86400; // jwt valid for 1 day
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => [
                'email' => $email,
            ],
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->alg);
    }

    private function decodeJwtToken($token): bool|\stdClass
    {
        try {
            return JWT::decode($token, new Key($this->jwtSecret, $this->alg));
        } catch (\Exception $e) {
            log_message('error', $e->getMessage());
            return false;
        }
    }

    public function getUserFromToken()
    {
        $authHeader = $this->request->header('Authorization');
        if (!$authHeader) {
            return null;
        }

        $token = str_replace('Bearer ', '', $authHeader->getValue());
        $decoded = $this->decodeJwtToken($token);

        if ( $decoded && isset($decoded->data->email)) {
            return $decoded->data->email;
        }

        return null;
    }

    public function login(): ResponseInterface
    {
        $this->validate(array(
            "email" => "required",
            "password" => "required"
        ));

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $user = $this->Users_model->get_one_where(array('email' => $email, 'deleted' => 0, 'user_type' => 'client'));
        if ($user->id) {

            //there has two password encryption method for legacy (md5) compatibility
            //check if anyone of them is correct
            if ($user->password && (strlen($user->password) === 60 && password_verify($password, $user->password)) || $user->password === md5($password)) {

                if (get_setting("disable_client_login")) {

                    return $this->respond(['success' => false, 'message' => app_lang("client_login_disabled")], ResponseInterface::HTTP_UNAUTHORIZED);
                }
            } else {

                //authentication failed
                return $this->respond(['success' => false, 'message' => app_lang("authentication_failed"), 'reason' => 2], ResponseInterface::HTTP_UNAUTHORIZED);
            }
        } else {

            //authentication failed
            return $this->respond(['success' => false, 'message' => app_lang("authentication_failed")], 200);
        }

        $client = $this->Clients_model->get_one( $user->client_id );
        if ($client->id) {

            $token = $this->generateJwtToken($user->email);
            return $this->respond(['success' => true, 'message' => app_lang("data_retrieved_successfully"), 'data' => [
                'token' => $token,
                'id' => $client->id,
                'company_name' => $client->company_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'type' => $client->type,
                'address' => $client->address,
                'email' => $user->email,
                'phone' => $user->phone,
                'job_title' => $user->job_title,
                'gender' => $user->gender,
                'note' => $user->note,
                'alternative_phone' => $user->alternative_phone,
                'dob' => $user->dob,
                'avatar' => is_array($avatarFile = @unserialize((string) $user->image)) ? ($avatarFile['file_name'] ?? '') : '',
            ]]);
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    public function register()
    {
        if (get_setting("disable_client_signup")) {

            return $this->respond(array('success' => false, 'message' => app_lang('registration_disabled')));
        }

        $validate = $this->validate(array(
            "first_name" => "required",
            "last_name" => "required",
            "account_type" => "required",
            "email" => "required|valid_email",
            "password" => "required"
        ));

        if (!$validate) {

            // Return validation errors if validation fails
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $email = $this->request->getPost('email');
        if ($this->Users_model->is_email_exists($email)) {

            return $this->respond(array("success" => false, 'message' => app_lang("account_already_exists_for_your_mail")));
        }

        $first_name = $this->request->getPost('first_name');
        $last_name = $this->request->getPost('last_name');

        $company_name = $this->request->getPost("company_name") ? $this->request->getPost("company_name") : $first_name . " " . $last_name; //save user name as company name if there is no company name entered

        $client_data = array(
            "company_name" => $company_name,
            "type" => $this->request->getPost("account_type"),
            "created_by" => 1 //add default admin
        );

        $client_data = clean_data($client_data);
        //check duplicate company name, if found then show an error message
        if (get_setting("disallow_duplicate_client_company_name") == "1" && $this->Clients_model->is_duplicate_company_name($company_name)) {

            return $this->respond(array("success" => false, 'message' => app_lang("account_already_exists_for_your_company_name")));
        }

        //create a client
        $client_id = $this->Clients_model->ci_save($client_data);
        if ($client_id) {
            //client created, now create the client contact
            $user_data['first_name'] = $first_name;
            $user_data['last_name'] = $last_name;
            $user_data["user_type"] = "client";
            $user_data["email"] = $email;
            $user_data["client_id"] = $client_id;
            $user_data["is_primary_contact"] = 1;
            $user_data['client_permissions'] = "all";
            $user_id = $this->Users_model->ci_save($user_data);

            log_notification("client_signup", array("client_id" => $client_id), $user_id);

            //send welcome email
            $email_template = $this->Email_templates_model->get_final_template("new_client_greetings"); //use default template since creating new client

            $parser_data["SIGNATURE"] = $email_template->signature;
            $parser_data["CONTACT_FIRST_NAME"] = $first_name;
            $parser_data["CONTACT_LAST_NAME"] = $last_name;

            $Company_model = model('App\Models\Company_model');
            $company_info = $Company_model->get_one_where(array("is_default" => true));
            $parser_data["COMPANY_NAME"] = $company_info->name;

            $parser_data["DASHBOARD_URL"] = base_url();
            $parser_data["CONTACT_LOGIN_EMAIL"] = $email;
            $parser_data["CONTACT_LOGIN_PASSWORD"] = $this->request->getPost("password");
            $parser_data["LOGO_URL"] = get_logo_url();

            $message = $this->parser->setData($parser_data)->renderString($email_template->message);
            $subject = $this->parser->setData($parser_data)->renderString($email_template->subject);

            send_app_mail($email, $subject, $message);
            return $this->respond(array(
                'success' => true,
                'message' => app_lang('account_created'),
                'token' => $this->generateJwtToken($email)
            ));
        } else {
            echo json_encode(array("success" => false, 'message' => app_lang('error_occurred')));
            return false;
        }
    }

    public function profile(): ResponseInterface
    {
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_one_where( array('email' => $email, 'user_type' => 'client', 'deleted' => 0 ));
        if ($user->id) {

            $client = $this->Clients_model->get_one( $user->client_id );
            if ($client->id) {

                return $this->respond([
                    'success' => true,
                    'message' => app_lang("data_retrieved_successfully"),
                    'data' => [
                        'id' => $client->id,
                        'company_name' => $client->company_name,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'type' => $client->type,
                        'address' => $client->address,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'job_title' => $user->job_title,
                        'gender' => $user->gender,
                        'note' => $user->note,
                        'alternative_phone' => $user->alternative_phone,
                        'dob' => $user->dob,
                        'avatar' => is_array($avatarFile = @unserialize((string) $user->image)) ? ($avatarFile['file_name'] ?? '') : '',
                    ]
                ]);
            }
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }
    
    public function forgetPassword()
    {
        $this->validate(array('email' => 'required|valid_email'));

        $email = $this->request->getPost("email");

        $existing_user = $this->Users_model->is_email_exists($email);

        //send reset password email if found account with this email
        if ($existing_user) {
            $email_template = $this->Email_templates_model->get_final_template("reset_password", true);

            $user_language = $existing_user->language;
            $parser_data["ACCOUNT_HOLDER_NAME"] = $existing_user->first_name . " " . $existing_user->last_name;
            $parser_data["SIGNATURE"] = get_array_value($email_template, "signature_$user_language") ? get_array_value($email_template, "signature_$user_language") : get_array_value($email_template, "signature_default");
            $parser_data["LOGO_URL"] = get_logo_url();
            $parser_data["SITE_URL"] = get_uri();
            $parser_data["RECIPIENTS_EMAIL_ADDRESS"] = $existing_user->email;

            $verification_data = array(
                "type" => "reset_password",
                "code" => make_random_string(),
                "params" => serialize(array(
                    "email" => $existing_user->email,
                    "expire_time" => time() + (24 * 60 * 60)
                ))
            );

            $save_id = $this->Verification_model->ci_save($verification_data);

            $verification_info = $this->Verification_model->get_one($save_id);

            $parser_data['RESET_PASSWORD_URL'] = get_uri("signin/new_password/" . $verification_info->code);

            $message = get_array_value($email_template, "message_$user_language") ? get_array_value($email_template, "message_$user_language") : get_array_value($email_template, "message_default");
            $subject = get_array_value($email_template, "subject_$user_language") ? get_array_value($email_template, "subject_$user_language") : get_array_value($email_template, "subject_default");

            $message = $this->parser->setData($parser_data)->renderString($message);
            $subject = $this->parser->setData($parser_data)->renderString($subject);

            if (send_app_mail($email, $subject, $message)) {
                return $this->respond(array('success' => true, 'message' => app_lang("reset_info_send")));
            } else {
                return $this->respond(array('success' => false, 'message' => app_lang('error_occurred')));
            }
        } else {
            return $this->respond(array("success" => false, 'message' => app_lang("no_acount_found_with_this_email")));
        }
    }
    
    //validate submitted data
    protected function validate_submitted_data($fields = array(), $return_errors = false, $json_response = true) {
        $final_fields = array();

        foreach ($fields as $field => $validate) {
            //we've to add permit_empty rule if the field is not required
            if (strpos($validate, 'required') !== false) {
                //this is required field
            } else {
                //so, this field isn't required, add permit_empty rule
                $validate .= "|permit_empty";
            }

            $final_fields[$field] = $validate;
        }

        if (!$final_fields) {
            //no fields to validate in this context, so nothing to validate
            return true;
        }

        $validate = $this->validate($final_fields);

        if (!$validate) {
            if (ENVIRONMENT === 'production') {
                $message = app_lang('something_went_wrong');
            } else {
                $validation = \Config\Services::validation();
                $message = $validation->getErrors();
            }

            if ($return_errors) {
                return $message;
            }
            if ($json_response) {
                echo json_encode(array("success" => false, 'message' => json_encode($message)));
            } else {
                echo view("errors/html/error_general", array("heading" => "404 Bad Request", "message" => app_lang("re_captcha_error-bad-request")));
            }
            exit();
        }
    }
}
