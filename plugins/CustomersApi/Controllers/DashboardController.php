<?php
namespace CustomersApi\Controllers;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Projects_model;
use App\Models\Invoice_items_model;
use App\Models\Invoice_payments_model;

class DashboardController extends RestApiController {

    public function __construct() {
		parent::__construct();
        $this->Projects_model = new Projects_model();
        $this->Invoice_items_model = new Invoice_items_model();
        $this->Invoice_payments_model = new Invoice_payments_model();
	}
	
	public function overview(): ResponseInterface
    {
        $appTitle = $this->Settings_model->get_setting("app_title");
        // site_logo is a plain filename (install/database.sql's seeded
        // default) until someone uploads a real logo via Settings, at which
        // point it becomes a serialized file record - unserialize() on the
        // plain-string case throws, which crashed this endpoint outright
        // for every fresh tenant that hasn't uploaded a logo yet. Same
        // @unserialize + is_array() guard the core app itself uses
        // (get_file_from_setting() in app/Helpers/general_helper.php).
        $siteLogoSetting = $this->Settings_model->get_setting("site_logo");
        $siteLogoFile = @unserialize((string) $siteLogoSetting);
        $appLogo = is_array($siteLogoFile) ? ($siteLogoFile['file_name'] ?? $siteLogoSetting) : $siteLogoSetting;
        $language = $this->Settings_model->get_setting("language");
        $currencySymbol = $this->Settings_model->get_setting("currency_symbol");
        $defaultCurrency = $this->Settings_model->get_setting("default_currency");
        $currencyPosition = $this->Settings_model->get_setting("currency_position");
        $disableRegistration = $this->Settings_model->get_setting("disable_client_signup") ?? '0';
        $disableLogin = $this->Settings_model->get_setting("disable_client_login") ?? '0';
        $viewTasks = $this->Settings_model->get_setting("client_can_view_tasks") ?? '0';
        $createTasks = $this->Settings_model->get_setting("client_can_create_tasks") ?? '0';
        $editTasks = $this->Settings_model->get_setting("client_can_edit_tasks") ?? '0';
        $commentTasks = $this->Settings_model->get_setting("client_can_comment_on_tasks") ?? '0';
        $viewOverview = $this->Settings_model->get_setting("client_can_view_overview") ?? '0';
        
        $data = array(
            "app_title" => $appTitle,
            "app_logo" => $appLogo,
            "language" => $language,
            "currency_symbol" => $currencySymbol,
            "default_currency" => $defaultCurrency,
            "currency_position" => $currencyPosition,
            "disable_login" => $disableLogin,
            "disable_registration" => $disableRegistration,
            "client_can_view_tasks" => $viewTasks,
            "client_can_create_tasks" => $createTasks,
            "client_can_edit_tasks" => $editTasks,
            "client_can_comment_on_tasks" => $commentTasks,
            "client_can_view_overview" => $viewOverview,
        );
        
        return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $data]);
    }
	
	public function dashboard(): ResponseInterface
    {
        // Load after login success
        $email = $this->getUserFromToken();
        if (!$email) {
            return $this->respond(['success' => false, 'message' => 'Unauthorized. Token is missing or invalid.'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $user = $this->Users_model->get_one_where( array('email' => $email, 'user_type' => 'client', 'deleted' => 0 ) );

        // get_one_where() returns an empty stdClass (not null) when nothing
        // matches, which `if ($user)` treats as truthy - a staff account
        // (this app now supports staff login via operations_approval's
        // Operations_api) silently fell through this whole branch with an
        // empty $user, returning a "successful" dashboard of blank/zeroed
        // client data instead of the clean rejection login()/profile() in
        // RestApiController give for the same situation. Matching their
        // `if ($user->id)` check here instead.
        if ($user->id) {
            
            $client = $this->Clients_model->get_one( $user->client_id );
            
            $client_roles = explode(",", $user->client_permissions);
            $mobile_modules = $this->getMobileModules();
            $available_modules = array_keys(array_filter($mobile_modules));
            $client_roles = in_array('all', $client_roles, true)
                ? $available_modules
                : array_values(array_intersect($client_roles, $available_modules));
            if ($mobile_modules['operations'] && !in_array('operations', $client_roles, true)) $client_roles[] = 'operations';
            
            $options = array(
                "client_id" => $user->client_id,
            );
            
            $projects = $this->Projects_model->get_details($options)->getResult();
            
            $invoiceItems = $this->Invoice_items_model->get_details($options)->getResult();
            $totalInvoiced = 0;
            foreach ($invoiceItems as $invoiceItem){
                $totalInvoiced = $totalInvoiced + intval($invoiceItem->total);
            }
            
            $invoicePayments = $this->Invoice_payments_model->get_details($options)->getResult();
            $payments = 0;
            foreach ($invoicePayments as $invoicePayment){
                $payments = $payments + intval($invoicePayment->amount);
            }
            
            $overview = array(
                'project_count' => count($projects),
                'total_invoiced' => $totalInvoiced,
                'payments' => $payments,
                'due' => $totalInvoiced - $payments,
            );
            
            $client = array(
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
            );
            
            $data = array(
                'widgets'      => $overview,
                'client'        => $client,
                'permissions'   => $client_roles,
                'projects'      => $projects,
                'mobile_modules'=> $mobile_modules
            );
            
            return $this->respond(["success" => true, 'message' => app_lang("data_retrieved_successfully"), "data" => $data]);
        }

        return $this->respond(['success' => false, 'message' => app_lang("account_disabled")]);
    }

    private function getMobileModules(): array
    {
        $modules = ['operations','projects','contracts','proposals','estimates','invoices','payments','tickets'];
        $enabled = array_fill_keys($modules, true);
        $db = db_connect('default');
        $table = $db->getPrefix() . 'oa_settings';
        if (!$db->tableExists($table)) return $enabled;
        $rows = $db->table($table)->select('setting_key,setting_value')->whereIn('setting_key', array_map(fn($module) => 'mobile_module_' . $module, $modules))->get()->getResult();
        foreach ($rows as $row) $enabled[substr($row->setting_key, 14)] = (string) $row->setting_value === '1';
        return $enabled;
    }
}
