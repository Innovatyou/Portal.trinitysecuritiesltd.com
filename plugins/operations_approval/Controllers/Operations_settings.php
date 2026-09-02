<?php

namespace operations_approval\Controllers;

use App\Controllers\Security_Controller;
use operations_approval\Libraries\Operations_permissions;

class Operations_settings extends Security_Controller
{
    private $db; private $p;
    public function __construct() { parent::__construct(); $this->access_only_team_members(); if (!(new Operations_permissions())->allowed('operations_manage_settings', $this->login_user)) app_redirect('forbidden'); $this->db=db_connect('default'); $this->p=$this->db->getPrefix(); }
    public function index()
    {
        $rows=$this->db->table($this->p.'oa_settings')->get()->getResult(); $settings=[]; foreach($rows as $row)$settings[$row->setting_key]=$row->setting_value;
        $signup_setting=$this->db->table($this->p.'settings')->select('setting_value')->where(['setting_name'=>'disable_client_signup','deleted'=>0])->get()->getRow();
        $settings['customer_signup_enabled']=!$signup_setting||$signup_setting->setting_value!=='1'?'1':'0';
        $departments=$this->db->table($this->p.'oa_departments')->where('deleted',0)->orderBy('name')->get()->getResult();
        $users=$this->db->table($this->p.'users')->select("id,CONCAT(first_name,' ',last_name) name")->where(['user_type'=>'staff','status'=>'active','deleted'=>0])->orderBy('first_name')->get()->getResult();
        $delegations=$this->db->table($this->p.'oa_delegations d')->select('d.*,u.first_name,u.last_name,du.first_name delegate_first_name,du.last_name delegate_last_name')->join($this->p.'users u','u.id=d.user_id')->join($this->p.'users du','du.id=d.delegate_id')->where('d.revoked_at',null)->orderBy('d.created_at','DESC')->get()->getResult();
        return $this->template->rander('operations_approval\Views\settings\index',compact('settings','departments','users','delegations'));
    }
    public function save()
    {
        $disable_signup=$this->request->getPost('customer_signup_enabled')?'0':'1';
        $core_settings=$this->db->table($this->p.'settings');
        if($core_settings->where(['setting_name'=>'disable_client_signup','deleted'=>0])->countAllResults())$core_settings->where(['setting_name'=>'disable_client_signup','deleted'=>0])->update(['setting_value'=>$disable_signup]);
        else $core_settings->insert(['setting_name'=>'disable_client_signup','setting_value'=>$disable_signup]);
        foreach(['allowed_extensions','max_file_size_mb','default_page_size','mobile_app_enabled','mobile_module_operations','mobile_module_projects','mobile_module_contracts','mobile_module_proposals','mobile_module_estimates','mobile_module_invoices','mobile_module_payments','mobile_module_tickets','currency_enabled','allowed_currencies','email_enabled','in_app_enabled','reminder_hours','escalation_hours','retention_days'] as $key){$value=clean_data((string)$this->request->getPost($key));if($key==='allowed_currencies'){$value=strtoupper(preg_replace('/[^A-Z,]/','',str_replace(' ','',$value)))?:'NGN';}$exists=$this->db->table($this->p.'oa_settings')->where('setting_key',$key)->countAllResults();if($exists)$this->db->table($this->p.'oa_settings')->where('setting_key',$key)->update(['setting_value'=>$value,'updated_by'=>$this->login_user->id,'updated_at'=>get_current_utc_time()]);else $this->db->table($this->p.'oa_settings')->insert(['setting_key'=>$key,'setting_value'=>$value,'updated_by'=>$this->login_user->id,'updated_at'=>get_current_utc_time()]);}
        echo json_encode(['success'=>true,'message'=>app_lang('record_saved')]);
    }
    public function save_department()
    {
        $this->validate_submitted_data(['name'=>'required','code'=>'required','head_user_id'=>'permit_empty|numeric']);
        $this->db->table($this->p.'oa_departments')->insert(['name'=>clean_data($this->request->getPost('name')),'code'=>strtoupper(clean_data($this->request->getPost('code'))),'head_user_id'=>(int)$this->request->getPost('head_user_id')?:null,'created_by'=>$this->login_user->id,'created_at'=>get_current_utc_time()]);
        echo json_encode(['success'=>true,'message'=>app_lang('record_saved'),'redirect_to'=>get_uri('operations_settings')]);
    }
    public function save_delegation()
    {
        $this->validate_submitted_data(['user_id'=>'required|numeric','delegate_id'=>'required|numeric','starts_at'=>'required','ends_at'=>'required','reason'=>'required']);
        if((int)$this->request->getPost('user_id')===(int)$this->request->getPost('delegate_id')){echo json_encode(['success'=>false,'message'=>app_lang('operations_delegate_must_differ')]);return;}
        $this->db->table($this->p.'oa_delegations')->insert(['user_id'=>(int)$this->request->getPost('user_id'),'delegate_id'=>(int)$this->request->getPost('delegate_id'),'starts_at'=>$this->request->getPost('starts_at'),'ends_at'=>$this->request->getPost('ends_at'),'reason'=>clean_data($this->request->getPost('reason')),'created_by'=>$this->login_user->id,'created_at'=>get_current_utc_time()]);
        echo json_encode(['success'=>true,'message'=>app_lang('record_saved'),'redirect_to'=>get_uri('operations_settings')]);
    }
}
