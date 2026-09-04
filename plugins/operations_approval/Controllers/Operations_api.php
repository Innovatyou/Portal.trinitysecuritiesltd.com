<?php

namespace operations_approval\Controllers;

use App\Models\Users_model;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use operations_approval\Libraries\Access_service;
use operations_approval\Libraries\Attachment_service;
use operations_approval\Libraries\Audit_service;
use operations_approval\Libraries\Notification_service;
use operations_approval\Libraries\Operations_permissions;
use operations_approval\Libraries\Pdf_signer;
use operations_approval\Libraries\Workflow_engine;

class Operations_api extends ResourceController
{
    private $db; private $p; private $secret; private $users;
    public function __construct()
    {
        $this->db=db_connect('default');$this->p=$this->db->getPrefix();$this->users=new Users_model();
        // This controller extends ResourceController, not App_Controller, so
        // none of the app_settings_array population App_Controller normally
        // does happens here - meaning the global get_setting() helper (used
        // by Attachment_service for temp_file_path/timeline_file_path,
        // among others) silently returned '' for every core setting.
        // Reproduced directly: attachment upload rejected a plain .txt file
        // as "not allowed" because get_setting('accepted_file_formats')
        // came back empty, not because the extension was actually blocked.
        $coreSettings=(new \App\Models\Settings_model())->get_all_required_settings(0)->getResult();
        foreach($coreSettings as $coreSetting)config('Rise')->app_settings_array[$coreSetting->setting_name]=$coreSetting->setting_value;
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
    public function avatar():ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();
        $file=$this->request->getFile('avatar');
        if(!$file||!$file->isValid())return $this->respond(['success'=>false,'message'=>'No image was received'],422);
        if(!str_starts_with((string)$file->getMimeType(),'image/'))return $this->respond(['success'=>false,'message'=>'Please upload an image file'],422);

        // move_temp_file/delete_app_files live in app_files_helper.php, one
        // of the helpers App_Controller normally autoloads for every web
        // request - this controller extends ResourceController instead
        // (see the settings-population workaround in __construct() for the
        // same underlying gap), so load it explicitly rather than depend on
        // something else in the request happening to have pulled it in.
        helper(['app_files','file']);

        $userRow=$this->users->get_one((int)$user->id);
        $stored=move_temp_file('avatar.png',get_setting('profile_image_path'),'',$file->getTempName(),'','',false,$file->getSize());
        if(!$stored)return $this->respond(['success'=>false,'message'=>'Could not save the image'],500);

        if($userRow->image){
            delete_app_files(get_setting('profile_image_path'),[@unserialize($userRow->image)]);
        }

        $this->users->ci_save(['image'=>serialize($stored)],(int)$user->id);

        return $this->respond(['success'=>true,'message'=>'Profile picture updated','data'=>['avatar'=>$stored['file_name']??'']]);
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
        $request['conversations']=$this->db->table($this->p.'oa_conversations')->where('request_id',$id)->orderBy('opened_at')->get()->getResultArray();
        $request['attachments']=$this->db->table($this->p.'oa_attachments')->select('id,original_name,mime_type,size_bytes,context,created_at')->where(['request_id'=>$id,'deleted_at'=>null])->orderBy('created_at')->get()->getResultArray();
        $assignment=$request['current_stage_instance_id']?$this->db->table($this->p.'oa_assignments')->where(['stage_instance_id'=>$request['current_stage_instance_id'],'user_id'=>$user->id,'status'=>'pending'])->get()->getRowArray():null;$request['active_assignment']=$assignment;$request['can_decide']=(bool)$assignment;
        $request['can_resubmit']=(int)$request['requester_id']===(int)$user->id&&$request['status']==='returned';
        if($request['can_resubmit']){
            $resubmitFields=$this->availableFields($this->db->table($this->p.'oa_fields')->select('id,field_key,label,field_type,is_required,config_json')->where('version_id',$request['version_id'])->orderBy('position')->get()->getResultArray());
            foreach($resubmitFields as &$resubmitField){$fieldConfig=json_decode($resubmitField['config_json']?:'{}',true)?:[];$resubmitField['editable_on_return']=!array_key_exists('editable_on_return',$fieldConfig)||!empty($fieldConfig['editable_on_return']);}
            $request['fields']=$resubmitFields;
        }
        $request['can_cancel']=(int)$request['requester_id']===(int)$user->id&&in_array($request['status'],['draft','submitted','pending_approval','returned','information_requested'],true);
        $openConversation=$request['status']==='information_requested'?$this->db->table($this->p.'oa_conversations')->where(['request_id'=>$id,'assigned_to'=>$user->id,'status'=>'open'])->get()->getRowArray():null;
        $request['can_respond_information']=(bool)$openConversation;
        $request['open_conversation_id']=$openConversation['id']??null;
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
    public function information(int $id):ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();$request=$this->requestRow($id);if(!$request)return $this->respond(['success'=>false,'message'=>'Not found'],404);
        $action=(string)$this->request->getPost('action');
        if($action==='respond')return $this->respondInformation($id,$request,$user);
        return $this->requestInformation($id,$request,$user);
    }
    private function requestInformation(int $id,array $request,object $user):ResponseInterface
    {
        $question=trim((string)$this->request->getPost('question'));if(!$question)return $this->respond(['success'=>false,'message'=>'Question is required'],422);
        $assignment=$request['current_stage_instance_id']?$this->db->table($this->p.'oa_assignments')->where(['stage_instance_id'=>$request['current_stage_instance_id'],'user_id'=>$user->id,'status'=>'pending'])->get()->getRow():null;
        if(!$assignment)return $this->respond(['success'=>false,'message'=>'Forbidden'],403);
        $now=get_current_utc_time();$this->db->transStart();
        $this->db->table($this->p.'oa_conversations')->insert(['request_id'=>$id,'stage_instance_id'=>$request['current_stage_instance_id'],'opened_by'=>$user->id,'assigned_to'=>$request['requester_id'],'status'=>'open','question'=>clean_data($question),'opened_at'=>$now]);
        $conversationId=(int)$this->db->insertID();
        $this->db->table($this->p.'oa_requests')->where('id',$id)->update(['status'=>'information_requested','updated_at'=>$now]);
        (new Audit_service())->record('information_requested',$id,(int)$request['current_stage_instance_id'],$user,[],['conversation_id'=>$conversationId,'question'=>$question]);
        $this->db->transComplete();
        (new Notification_service())->send('information_requested',$id,[(int)$request['requester_id']],$user,['comment'=>$question,'dedupe'=>$conversationId]);
        if(!$this->db->transStatus())return $this->respond(['success'=>false,'message'=>'Could not save the request'],500);
        return $this->respond(['success'=>true,'message'=>'Information requested']);
    }
    private function respondInformation(int $id,array $request,object $user):ResponseInterface
    {
        if((int)$request['requester_id']!==(int)$user->id||$request['status']!=='information_requested')return $this->respond(['success'=>false,'message'=>'Forbidden'],403);
        $conversationId=(int)$this->request->getPost('conversation_id');$response=trim((string)$this->request->getPost('response'));
        if(!$conversationId||!$response)return $this->respond(['success'=>false,'message'=>'conversation_id and response are required'],422);
        $conversation=$this->db->table($this->p.'oa_conversations')->where(['id'=>$conversationId,'request_id'=>$id,'assigned_to'=>$user->id,'status'=>'open'])->get()->getRow();
        if(!$conversation)return $this->respond(['success'=>false,'message'=>'Forbidden'],403);
        $now=get_current_utc_time();$this->db->transStart();
        $this->db->table($this->p.'oa_conversations')->where('id',$conversation->id)->update(['response'=>clean_data($response),'status'=>'answered','responded_at'=>$now]);
        $this->db->table($this->p.'oa_requests')->where('id',$id)->update(['status'=>'pending_approval','updated_at'=>$now]);
        (new Audit_service())->record('information_supplied',$id,(int)$conversation->stage_instance_id,$user,[],['conversation_id'=>$conversation->id,'response'=>$response]);
        $this->db->transComplete();
        (new Notification_service())->send('approval_assigned',$id,[(int)$conversation->opened_by],$user,['comment'=>$response,'dedupe'=>'info-'.$conversation->id]);
        if(!$this->db->transStatus())return $this->respond(['success'=>false,'message'=>'Could not save the response'],500);
        return $this->respond(['success'=>true,'message'=>'Response sent']);
    }
    /**
     * Stamps a signature image onto one page of an already-uploaded PDF
     * attachment, producing a new "-signed" PDF registered as its own
     * attachment (context 'signature') - the original attachment is never
     * modified, so the unsigned version stays in the timeline alongside it.
     *
     * x/y/width/height are fractions of the page (0-1), not points/mm - the
     * app works purely off however it rendered the page for the approver to
     * position the signature, and this maps that straight onto the page's
     * actual size regardless of the page's real dimensions/orientation.
     */
    public function signAttachment(int $attachmentId):ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();

        $attachment=(new Attachment_service())->get($attachmentId);
        if(!$attachment)return $this->respond(['success'=>false,'message'=>'Not found'],404);

        $request=$this->requestRow((int)$attachment->request_id);
        if(!$request||(new Access_service())->canView((object)$request,$user)===false)return $this->respond(['success'=>false,'message'=>'Forbidden'],403);

        if(strtolower(pathinfo($attachment->original_name,PATHINFO_EXTENSION))!=='pdf')return $this->respond(['success'=>false,'message'=>'This attachment is not a PDF'],422);
        $sourcePath=rtrim($attachment->storage_path,'/\\').DIRECTORY_SEPARATOR.$attachment->storage_name;
        if(!is_file($sourcePath))return $this->respond(['success'=>false,'message'=>'Not found'],404);

        $signatureFile=$this->request->getFile('signature');
        if(!$signatureFile||!$signatureFile->isValid())return $this->respond(['success'=>false,'message'=>'No signature image was received'],422);
        if(!str_starts_with((string)$signatureFile->getMimeType(),'image/'))return $this->respond(['success'=>false,'message'=>'Signature must be an image'],422);

        $page=max(1,(int)$this->request->getPost('page'));
        $x=(float)$this->request->getPost('x');$y=(float)$this->request->getPost('y');
        $w=(float)$this->request->getPost('width');$h=(float)$this->request->getPost('height');
        if($w<=0||$h<=0||$x<0||$y<0)return $this->respond(['success'=>false,'message'=>'Invalid signature position'],422);

        try{
            helper('app_files');
            $tempDir=rtrim(get_setting('temp_file_path'),'/\\');
            $signedFileName=Pdf_signer::stamp($sourcePath,$signatureFile->getTempName(),$page,$x,$y,$w,$h,$tempDir,pathinfo($attachment->original_name,PATHINFO_FILENAME));
        }catch(\DomainException $e){
            return $this->respond(['success'=>false,'message'=>$e->getMessage()],422);
        }catch(\Throwable $e){
            log_message('error','Operations signAttachment failed: {message}',['message'=>$e->getMessage()]);
            return $this->respond(['success'=>false,'message'=>'Could not sign this document - it may be encrypted or in an unsupported format.'],500);
        }

        try{
            $signedAttachmentId=(new Attachment_service())->store((int)$attachment->request_id,(int)$user->id,$signedFileName,$request['current_stage_instance_id']?(int)$request['current_stage_instance_id']:null,'signature');
        }catch(\Throwable $e){
            return $this->respond(['success'=>false,'message'=>$e->getMessage()],422);
        }

        (new Audit_service())->record('attachment_signed',(int)$attachment->request_id,$request['current_stage_instance_id']?(int)$request['current_stage_instance_id']:null,$user,[],['original_attachment_id'=>$attachmentId,'signed_attachment_id'=>$signedAttachmentId]);

        return $this->respond(['success'=>true,'message'=>'Document signed','data'=>['attachment_id'=>$signedAttachmentId]]);
    }
    public function upload(int $id):ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();$request=$this->requestRow($id);if(!$request||!(new Access_service())->canView((object)$request,$user))return $this->respond(['success'=>false,'message'=>'Forbidden'],403);
        $file=$this->request->getFile('file');
        if(!$file||!$file->isValid())return $this->respond(['success'=>false,'message'=>'No file was received'],422);
        $originalName=$file->getClientName();
        // Extension/size/executable-MIME checks all happen inside
        // Attachment_service::store() below (against operations_approval's
        // own oa_settings.allowed_extensions) - matching what the web
        // controller does, rather than duplicating a second, differently-
        // sourced allowlist here.
        $tempDir=rtrim(get_setting('temp_file_path'),'/\\');
        if(!is_dir($tempDir)&&!mkdir($tempDir,0755,true))return $this->respond(['success'=>false,'message'=>'Could not prepare upload storage'],500);
        if(!$file->move($tempDir,$originalName))return $this->respond(['success'=>false,'message'=>'Could not save the uploaded file'],500);
        try{
            $attachmentId=(new Attachment_service())->store($id,(int)$user->id,$originalName,$request['current_stage_instance_id']?(int)$request['current_stage_instance_id']:null,'request');
            (new Audit_service())->record('attachment_uploaded',$id,$request['current_stage_instance_id']?(int)$request['current_stage_instance_id']:null,$user,[],['attachment_id'=>$attachmentId]);
            return $this->respond(['success'=>true,'message'=>'Attachment uploaded','data'=>['id'=>$attachmentId]]);
        }catch(\Throwable $e){return $this->respond(['success'=>false,'message'=>$e->getMessage()],422);}
    }
    public function download(int $attachmentId):ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();
        $attachment=(new Attachment_service())->get($attachmentId);
        if(!$attachment)return $this->respond(['success'=>false,'message'=>'Not found'],404);
        $request=$this->requestRow((int)$attachment->request_id);
        if(!$request||!(new Access_service())->canView((object)$request,$user))return $this->respond(['success'=>false,'message'=>'Forbidden'],403);
        $path=rtrim($attachment->storage_path,'/\\').DIRECTORY_SEPARATOR.$attachment->storage_name;
        if(!is_file($path)||!hash_equals($attachment->sha256,hash_file('sha256',$path)))return $this->respond(['success'=>false,'message'=>'Not found'],404);
        return $this->response->download($path,null)->setFileName($attachment->original_name);
    }
    public function cancel(int $id):ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();$request=$this->requestRow($id);if(!$request)return $this->respond(['success'=>false,'message'=>'Not found'],404);
        if((int)$request['requester_id']!==(int)$user->id||!in_array($request['status'],['draft','submitted','pending_approval','returned','information_requested'],true))return $this->respond(['success'=>false,'message'=>'Forbidden'],403);
        $reason=trim((string)$this->request->getPost('reason'));if(!$reason)return $this->respond(['success'=>false,'message'=>'reason is required'],422);
        $workflow=$this->db->table($this->p.'oa_workflows')->where('id',$request['workflow_id'])->get()->getRow();
        $settings=json_decode($workflow->settings_json?:'{}',true)?:[];
        if(empty($settings['allow_cancellation'])&&$request['status']!=='draft')return $this->respond(['success'=>false,'message'=>'Cancellation is not allowed for this workflow'],422);
        $now=get_current_utc_time();$this->db->transStart();
        $this->db->table($this->p.'oa_requests')->where('id',$id)->update(['status'=>'cancelled','current_stage_instance_id'=>null,'cancelled_at'=>$now,'updated_at'=>$now]);
        $this->db->table($this->p.'oa_stage_instances')->where('request_id',$id)->whereIn('status',['pending','active','overdue'])->update(['status'=>'cancelled','completed_at'=>$now]);
        $stageIds=array_column($this->db->table($this->p.'oa_stage_instances')->select('id')->where('request_id',$id)->get()->getResultArray(),'id');
        if($stageIds)$this->db->table($this->p.'oa_assignments')->whereIn('stage_instance_id',$stageIds)->where('status','pending')->update(['status'=>'cancelled']);
        (new Audit_service())->record('request_cancelled',$id,null,$user,[],['reason'=>$reason]);
        $this->db->transComplete();
        if(!$this->db->transStatus())return $this->respond(['success'=>false,'message'=>'Could not cancel the request'],500);
        return $this->respond(['success'=>true,'message'=>'Request cancelled']);
    }
    public function resubmit(int $id):ResponseInterface
    {
        $user=$this->auth();if(!$user)return $this->unauthorized();$request=$this->requestRow($id);if(!$request)return $this->respond(['success'=>false,'message'=>'Not found'],404);
        if(!(new Access_service())->canEdit((object)$request,$user)||$request['status']!=='returned')return $this->respond(['success'=>false,'message'=>'Forbidden'],403);
        $reason=trim((string)$this->request->getPost('resubmission_comment'));if(!$reason)return $this->respond(['success'=>false,'message'=>'resubmission_comment is required'],422);
        $fields=$this->db->table($this->p.'oa_fields')->where('version_id',$request['version_id'])->get()->getResult();
        $oldRows=$this->db->table($this->p.'oa_request_values')->where(['request_id'=>$id,'revision_no'=>$request['revision_no']])->get()->getResult();
        $old=[];foreach($oldRows as $row)$old[$row->field_key]=$row->value_json?json_decode($row->value_json,true):$row->value_text;
        $newRevision=((int)$request['revision_no'])+1;$changes=[];
        $this->db->transBegin();
        try{
            foreach($fields as $field){
                $config=json_decode($field->config_json?:'{}',true)?:[];
                $editable=!array_key_exists('editable_on_return',$config)||!empty($config['editable_on_return']);
                $value=$editable&&$this->request->getPost('field_'.$field->field_key)!==null?$this->request->getPost('field_'.$field->field_key):($old[$field->field_key]??null);
                if($field->is_required&&($value===null||$value===''))throw new \DomainException($field->label.' is required.');
                if(($old[$field->field_key]??null)!=$value)$changes[$field->field_key]=['from'=>$old[$field->field_key]??null,'to'=>$value];
                $this->db->table($this->p.'oa_request_values')->insert(['request_id'=>$id,'field_id'=>$field->id,'field_key'=>$field->field_key,'value_text'=>is_array($value)?null:clean_data((string)$value),'value_json'=>is_array($value)?json_encode($value):null,'revision_no'=>$newRevision,'created_at'=>get_current_utc_time()]);
            }
            $this->db->table($this->p.'oa_request_revisions')->insert(['request_id'=>$id,'revision_no'=>$newRevision,'changed_by'=>$user->id,'reason'=>clean_data($reason),'changes_json'=>json_encode($changes),'created_at'=>get_current_utc_time()]);
            $this->db->table($this->p.'oa_requests')->where('id',$id)->update(['revision_no'=>$newRevision,'updated_at'=>get_current_utc_time()]);
            (new Audit_service())->record('request_fields_revised',$id,null,$user,$old,$changes,['reason'=>$reason,'revision_no'=>$newRevision]);
            (new Workflow_engine())->resubmit($id,$user);
            $this->db->transCommit();
            return $this->respond(['success'=>true,'message'=>'Request resubmitted']);
        }catch(\Throwable $e){$this->db->transRollback();return $this->respond(['success'=>false,'message'=>$e->getMessage()],422);}
    }
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
        if(!$this->secret||!class_exists(JWT::class))return null;$header=$this->request->getHeaderLine('Authorization')?:$this->request->getHeaderLine('X-Authorization');if(!$header)$header=(string)($_SERVER['HTTP_AUTHORIZATION']??$_SERVER['REDIRECT_HTTP_AUTHORIZATION']??$_SERVER['HTTP_X_AUTHORIZATION']??'');if(!$header&&function_exists('apache_request_headers')){$headers=apache_request_headers();$header=(string)($headers['Authorization']??$headers['authorization']??$headers['X-Authorization']??'');}$token=preg_replace('/^Bearer\s+/i','',trim($header));if(!$token)return null;try{
    $jwt=JWT::decode($token,new Key($this->secret,'HS256'));
    $email=$jwt->data->email??'';
    $basicUser=$this->users->get_one_where(['email'=>$email,'deleted'=>0]);
    if(!$basicUser->id||$basicUser->status!=='active')return null;
    // get_one_where() is a plain row from `users` - it has no permissions
    // column of its own (only client_permissions, for client-type users'
    // per-client access). A staff member's permissions live on their
    // *role*, joined via role_id. get_access_info() is the same lookup
    // Security_Controller uses for the web app, so mobile and web resolve
    // permissions identically instead of this route silently having its
    // own, broken, always-empty version. Confirmed directly: without this,
    // a non-admin staff user assigned a role with operations_create_request
    // still got can_create:false from every mobile endpoint - permissions
    // were never actually loaded, so nothing but is_admin users could do
    // anything via the app.
    $user=$this->users->get_access_info($basicUser->id);
    $permissionData=$user->permissions??null;
    if($permissionData){$p=@unserialize($permissionData);$user->permissions=is_array($p)?$p:[];}else$user->permissions=[];
    return $user;
}catch(\Throwable $e){return null;}
    }
    private function unauthorized():ResponseInterface{return $this->respond(['success'=>false,'message'=>'Unauthorized. Token is missing or invalid.'],401);}
    private function requestRow(int $id):?array{$row=$this->db->table($this->p.'oa_requests r')->select('r.*,w.name workflow_name,i.name_snapshot current_stage,i.lock_version stage_lock_version')->join($this->p.'oa_workflows w','w.id=r.workflow_id')->join($this->p.'oa_stage_instances i','i.id=r.current_stage_instance_id','left')->where(['r.id'=>$id,'r.deleted'=>0])->get()->getRowArray();return $row?:null;}
}
