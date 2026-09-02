<?php

namespace operations_approval\Controllers;

use App\Models\Users_model;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use operations_approval\Libraries\Access_service;
use operations_approval\Libraries\Audit_service;
use operations_approval\Libraries\Notification_service;
use operations_approval\Libraries\Operations_permissions;
use operations_approval\Libraries\Workflow_engine;

class Operations_api extends ResourceController
{
    private $db; private $p; private $secret; private $users;
    public function __construct()
    {
        $this->db=db_connect('default');$this->p=$this->db->getPrefix();$this->users=new Users_model();
        if($this->db->tableExists($this->p.'oa_settings')){$mobileSetting=$this->db->table($this->p.'oa_settings')->select('setting_value')->where('setting_key','mobile_app_enabled')->get()->getRow();if($mobileSetting&&(string)$mobileSetting->setting_value!=='1'){response()->setStatusCode(ResponseInterface::HTTP_SERVICE_UNAVAILABLE)->setJSON(['success'=>false,'message'=>'The mobile app is currently disabled by an administrator.'])->send();exit;}$operationsSetting=$this->db->table($this->p.'oa_settings')->select('setting_value')->where('setting_key','mobile_module_operations')->get()->getRow();if($operationsSetting&&(string)$operationsSetting->setting_value!=='1'){response()->setStatusCode(ResponseInterface::HTTP_SERVICE_UNAVAILABLE)->setJSON(['success'=>false,'message'=>'Operations workflows are disabled in the mobile app.'])->send();exit;}}
        $secretRow=$this->db->table($this->p.'settings')->select('setting_value')->where(['setting_name'=>'customersapi_secret_key','deleted'=>0])->get()->getRow();
        $this->secret=(string)($secretRow->setting_value??'');
        $autoload=PLUGINPATH.'CustomersApi/Vendor/autoload.php';if(is_file($autoload))require_once $autoload;
    }
    public function login():ResponseInterface
    {
        $email=trim((string)$this->request->getPost('email'));$password=(string)$this->request->getPost('password');
        $user=$this->users->get_one_where(['email'=>$email,'deleted'=>0]);
        if(!$user->id||$user->status!=='active'||!$user->password||!((strlen($user->password)===60&&password_verify($password,$user->password))||hash_equals($user->password,md5($password))))return $this->respond(['success'=>false,'message'=>app_lang('authentication_failed')],401);
        if(!$this->secret||!class_exists(JWT::class))return $this->respond(['success'=>false,'message'=>'Mobile API is not configured.'],503);
        $token=JWT::encode(['iat'=>time(),'exp'=>time()+86400,'data'=>['email'=>$user->email,'user_id'=>(int)$user->id,'user_type'=>$user->user_type]],$this->secret,'HS256');
        return $this->respond(['success'=>true,'message'=>app_lang('data_retrieved_successfully'),'data'=>['token'=>$token,'id'=>(int)$user->id,'first_name'=>$user->first_name,'last_name'=>$user->last_name,'email'=>$user->email,'job_title'=>$user->job_title,'user_type'=>$user->user_type,'avatar'=>(unserialize($user->image?:'a:0:{}')['file_name']??'')]]);
    }
    public function dashboard():ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();$uid=(int)$user->id;
        $mine=$this->db->table($this->p.'oa_requests')->where(['requester_id'=>$uid,'deleted'=>0]);
        return $this->respond(['success'=>true,'message'=>'Operations dashboard','data'=>['total'=>(clone $mine)->countAllResults(),'pending'=>(clone $mine)->whereIn('status',['submitted','pending_approval','information_requested'])->countAllResults(),'completed'=>(clone $mine)->where('status','completed')->countAllResults(),'returned'=>(clone $mine)->where('status','returned')->countAllResults(),'pending_approval'=>$this->db->table($this->p.'oa_assignments')->where(['user_id'=>$uid,'status'=>'pending'])->countAllResults(),'can_create'=>(new Operations_permissions())->allowed('operations_create_request',$user)]]);
    }
    public function workflows():ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();$rows=$this->db->table($this->p.'oa_workflows')->select('id,name,code,description,prefix,current_version_id,settings_json')->where(['status'=>'active','deleted'=>0])->orderBy('name')->get()->getResultArray();
        foreach($rows as &$row){$row['fields']=$this->availableFields($this->db->table($this->p.'oa_fields')->select('id,field_key,label,field_type,is_required,config_json')->where('version_id',$row['current_version_id'])->orderBy('position')->get()->getResultArray());}return $this->respond(['success'=>true,'message'=>'Workflows','data'=>$rows]);
    }
    public function requests():ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();$builder=$this->db->table($this->p.'oa_requests r')->select('r.id,r.request_no,r.title,r.status,r.priority,r.created_at,r.updated_at,w.name workflow_name,i.name_snapshot current_stage')->join($this->p.'oa_workflows w','w.id=r.workflow_id')->join($this->p.'oa_stage_instances i','i.id=r.current_stage_instance_id','left')->where(['r.requester_id'=>$user->id,'r.deleted'=>0])->orderBy('r.updated_at','DESC');return $this->respond(['success'=>true,'message'=>'My requests','data'=>$builder->get()->getResultArray()]);
    }
    public function pending():ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();$rows=$this->db->table($this->p.'oa_assignments a')->select('r.id,r.request_no,r.title,r.status,r.priority,r.submitted_at,w.name workflow_name,i.name_snapshot current_stage,i.due_at,i.lock_version,a.id assignment_id')->join($this->p.'oa_stage_instances i','i.id=a.stage_instance_id')->join($this->p.'oa_requests r','r.id=i.request_id')->join($this->p.'oa_workflows w','w.id=r.workflow_id')->where(['a.user_id'=>$user->id,'a.status'=>'pending'])->orderBy('i.due_at')->get()->getResultArray();return $this->respond(['success'=>true,'message'=>'Approval inbox','data'=>$rows]);
    }
    public function show($id = null):ResponseInterface
    {
        $id=(int)$id;$user=$this->auth();if(!$user)return $this->unauthorized();$request=$this->requestRow($id);if(!$request||(new Access_service())->canView((object)$request,$user)===false)return $this->respond(['success'=>false,'message'=>'Forbidden'],403);
        $request['values']=$this->db->table($this->p.'oa_request_values')->select('field_key,value_text,value_json')->where(['request_id'=>$id,'revision_no'=>$request['revision_no']])->get()->getResultArray();
        $request['timeline']=$this->db->table($this->p.'oa_stage_instances i')->select('i.id,i.name_snapshot,i.type_snapshot,i.status,i.activated_at,i.completed_at,i.due_at,d.decision,d.comment,d.actor_name_snapshot,d.created_at decision_at')->join($this->p.'oa_decisions d','d.stage_instance_id=i.id','left')->where('i.request_id',$id)->orderBy('i.position')->orderBy('d.created_at')->get()->getResultArray();
        $request['comments']=$this->db->table($this->p.'oa_comments')->select('user_name_snapshot,comment,visibility,created_at')->where('request_id',$id)->orderBy('created_at')->get()->getResultArray();
        $assignment=$request['current_stage_instance_id']?$this->db->table($this->p.'oa_assignments')->where(['stage_instance_id'=>$request['current_stage_instance_id'],'user_id'=>$user->id,'status'=>'pending'])->get()->getRowArray():null;$request['active_assignment']=$assignment;$request['can_decide']=(bool)$assignment;
        return $this->respond(['success'=>true,'message'=>'Request details','data'=>$request]);
    }
    public function create():ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();if(!(new Operations_permissions())->allowed('operations_create_request',$user))return $this->respond(['success'=>false,'message'=>'Forbidden'],403);
        $workflowId=(int)$this->request->getPost('workflow_id');$workflow=$this->db->table($this->p.'oa_workflows')->where(['id'=>$workflowId,'status'=>'active','deleted'=>0])->get()->getRow();if(!$workflow)return $this->respond(['success'=>false,'message'=>'Workflow unavailable'],422);
        $title=trim((string)$this->request->getPost('title'));if(!$title)return $this->respond(['success'=>false,'message'=>'Title is required'],422);
        $now=get_current_utc_time();$this->db->transBegin();
        try{
            $this->db->table($this->p.'oa_requests')->insert(['workflow_id'=>$workflowId,'requester_id'=>$user->id,'title'=>clean_data($title),'priority'=>clean_data($this->request->getPost('priority')?:'normal'),'status'=>'draft','created_at'=>$now,'updated_at'=>$now]);
            $id=(int)$this->db->insertID();
            $fields=$this->availableFields($this->db->table($this->p.'oa_fields')->where('version_id',$workflow->current_version_id)->get()->getResultArray());
            foreach($fields as $field){$value=$this->request->getPost('field_'.$field['field_key']);if($field['is_required']&&($value===null||$value===''))throw new \DomainException($field['label'].' is required.');if($field['field_key']==='currency'&&!in_array(strtoupper((string)$value),$this->allowedCurrencies(),true))throw new \DomainException('Select an allowed currency.');$this->db->table($this->p.'oa_request_values')->insert(['request_id'=>$id,'field_id'=>$field['id'],'field_key'=>$field['field_key'],'value_text'=>is_array($value)?null:clean_data((string)$value),'value_json'=>is_array($value)?json_encode($value):null,'revision_no'=>1,'created_at'=>$now]);}
            (new Audit_service())->record('request_created',$id,null,$user);(new Workflow_engine())->submit($id,$user);$this->db->transCommit();return $this->respond(['success'=>true,'message'=>'Request submitted','data'=>['id'=>$id]]);
        }catch(\Throwable $e){$this->db->transRollback();return $this->respond(['success'=>false,'message'=>$e->getMessage()],422);}
    }
    public function decision(int $id):ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();$decision=(string)$this->request->getPost('decision');$permission=['approve'=>'operations_approve','reject'=>'operations_reject','return'=>'operations_return'][$decision]??'';if(!$permission||!(new Operations_permissions())->allowed($permission,$user))return $this->respond(['success'=>false,'message'=>'Forbidden'],403);try{(new Workflow_engine())->decide($id,(int)$this->request->getPost('stage_instance_id'),(int)$this->request->getPost('lock_version'),$decision,trim((string)$this->request->getPost('comment')),$user);return $this->respond(['success'=>true,'message'=>'Decision recorded']);}catch(\Throwable $e){return $this->respond(['success'=>false,'message'=>$e->getMessage()],409);}
    }
    public function comment(int $id):ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();$request=$this->requestRow($id);if(!$request||!(new Access_service())->canView((object)$request,$user))return $this->respond(['success'=>false,'message'=>'Forbidden'],403);$comment=trim((string)$this->request->getPost('comment'));if(!$comment)return $this->respond(['success'=>false,'message'=>'Comment is required'],422);$this->db->table($this->p.'oa_comments')->insert(['request_id'=>$id,'stage_instance_id'=>$request['current_stage_instance_id']?:null,'user_id'=>$user->id,'user_name_snapshot'=>trim($user->first_name.' '.$user->last_name),'comment'=>clean_data($comment),'visibility'=>'workflow','created_at'=>get_current_utc_time()]);(new Audit_service())->record('comment_created',$id,$request['current_stage_instance_id']?(int)$request['current_stage_instance_id']:null,$user);return $this->respond(['success'=>true,'message'=>'Comment added']);
    }
    public function information(int $id):ResponseInterface{return $this->respond(['success'=>false,'message'=>'Use the web workflow for information requests in this build.'],422);}
    private function availableFields(array $fields):array
    {
        $setting=$this->db->table($this->p.'oa_settings')->select('setting_value')->where('setting_key','currency_enabled')->get()->getRow();
        if($setting&&(string)$setting->setting_value!=='1')return array_values(array_filter($fields,static fn($field)=>(is_array($field)?$field['field_key']:$field->field_key)!=='currency'));
        foreach($fields as &$field){if($field['field_key']==='currency'){$config=json_decode($field['config_json']?:'{}',true)?:[];$config['options']=$this->allowedCurrencies();$field['config_json']=json_encode($config);}}
        return $fields;
    }
    private function allowedCurrencies():array
    {
        $setting=$this->db->table($this->p.'oa_settings')->select('setting_value')->where('setting_key','allowed_currencies')->get()->getRow();
        $codes=array_values(array_unique(array_filter(array_map('trim',explode(',',strtoupper((string)($setting->setting_value??'NGN')))),static fn($code)=>preg_match('/^[A-Z]{3}$/',$code))));
        return $codes?:['NGN'];
    }
    private function auth():?object
    {
        $queryToken=(string)$this->request->getGet('access_token');
        if($queryToken&&!isset($_SERVER['HTTP_X_AUTHORIZATION']))$_SERVER['HTTP_X_AUTHORIZATION']=$queryToken;
        if(!$this->secret||!class_exists(JWT::class))return null;$header=$this->request->getHeaderLine('Authorization')?:$this->request->getHeaderLine('X-Authorization');if(!$header)$header=(string)($_SERVER['HTTP_AUTHORIZATION']??$_SERVER['REDIRECT_HTTP_AUTHORIZATION']??$_SERVER['HTTP_X_AUTHORIZATION']??'');if(!$header&&function_exists('apache_request_headers')){$headers=apache_request_headers();$header=(string)($headers['Authorization']??$headers['authorization']??$headers['X-Authorization']??'');}$token=preg_replace('/^Bearer\s+/i','',trim($header));if(!$token)return null;try{$jwt=JWT::decode($token,new Key($this->secret,'HS256'));$email=$jwt->data->email??'';$user=$this->users->get_one_where(['email'=>$email,'deleted'=>0]);if(!$user->id||$user->status!=='active')return null;$permissionData=$user->permissions??null;if($permissionData){$p=@unserialize($permissionData);$user->permissions=is_array($p)?$p:[];}else$user->permissions=[];return $user;}catch(\Throwable $e){return null;}
    }
    private function unauthorized():ResponseInterface{return $this->respond(['success'=>false,'message'=>'Unauthorized. Token is missing or invalid.'],401);}
    private function requestRow(int $id):?array{$row=$this->db->table($this->p.'oa_requests r')->select('r.*,w.name workflow_name,i.name_snapshot current_stage,i.lock_version stage_lock_version')->join($this->p.'oa_workflows w','w.id=r.workflow_id')->join($this->p.'oa_stage_instances i','i.id=r.current_stage_instance_id','left')->where(['r.id'=>$id,'r.deleted'=>0])->get()->getRowArray();return $row?:null;}
}
