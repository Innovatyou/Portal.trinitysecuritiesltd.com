<?php

namespace operations_approval\Controllers;

use App\Controllers\Security_Controller;
use operations_approval\Libraries\Audit_service;
use operations_approval\Libraries\Access_service;
use operations_approval\Libraries\Attachment_service;
use operations_approval\Libraries\Delegation_service;
use operations_approval\Libraries\Notification_service;
use operations_approval\Libraries\Report_service;
use operations_approval\Libraries\Operations_permissions;
use operations_approval\Libraries\Pdf_signer;
use operations_approval\Libraries\Workflow_engine;

class Operations extends Security_Controller
{
    private $db;
    private $p;
    private $permissions;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        $this->db = db_connect('default');
        $this->p = $this->db->getPrefix();
        $this->permissions = new Operations_permissions();
    }

    public function index()
    {
        $userId = (int) $this->login_user->id;
        $base = $this->db->table($this->p . 'oa_requests')->where('deleted', 0);
        $data['kpis'] = [
            'total' => $this->visibleRequests(clone $base)->countAllResults(),
            'pending' => $this->visibleRequests(clone $base)->whereIn('status', ['submitted', 'pending_approval', 'information_requested'])->countAllResults(),
            'approved' => $this->visibleRequests(clone $base)->whereIn('status', ['approved', 'completed'])->countAllResults(),
            'rejected' => $this->visibleRequests(clone $base)->where('status', 'rejected')->countAllResults(),
            'returned' => $this->visibleRequests(clone $base)->where('status', 'returned')->countAllResults(),
            'my_pending' => $this->db->table($this->p . 'oa_assignments')->where(['user_id' => $userId, 'status' => 'pending'])->countAllResults()
        ];
        $data['recent'] = $this->visibleRequests($this->db->table($this->p . 'oa_requests r')->select('r.*, w.name workflow_name')->join($this->p . 'oa_workflows w', 'w.id=r.workflow_id')->where('r.deleted', 0))->orderBy('r.created_at', 'DESC')->get(10)->getResult();
        return $this->template->rander('operations_approval\Views\operations\dashboard', $data);
    }

    public function my_requests()
    {
        $rows = $this->db->table($this->p . 'oa_requests r')->select('r.*, w.name workflow_name')->join($this->p . 'oa_workflows w', 'w.id=r.workflow_id')->where(['r.requester_id' => $this->login_user->id, 'r.deleted' => 0])->orderBy('r.created_at', 'DESC')->get()->getResult();
        return $this->template->rander('operations_approval\Views\operations\request_list', ['rows' => $rows, 'title' => app_lang('operations_my_requests')]);
    }

    public function requests()
    {
        $this->requirePermission('operations_view_all_requests');
        $rows = $this->db->table($this->p . 'oa_requests r')->select('r.*, w.name workflow_name')->join($this->p . 'oa_workflows w', 'w.id=r.workflow_id')->where('r.deleted', 0)->orderBy('r.created_at', 'DESC')->get()->getResult();
        return $this->template->rander('operations_approval\Views\operations\request_list', ['rows' => $rows, 'title' => app_lang('operations_requests')]);
    }

    public function pending()
    {
        $rows = $this->db->table($this->p . 'oa_assignments a')->select('r.*, w.name workflow_name, i.name_snapshot stage_name')->join($this->p . 'oa_stage_instances i', 'i.id=a.stage_instance_id')->join($this->p . 'oa_requests r', 'r.id=i.request_id')->join($this->p . 'oa_workflows w', 'w.id=r.workflow_id')->where(['a.user_id' => $this->login_user->id, 'a.status' => 'pending'])->orderBy('a.assigned_at')->get()->getResult();
        return $this->template->rander('operations_approval\Views\operations\request_list', ['rows' => $rows, 'title' => app_lang('operations_pending_my_approval')]);
    }

    public function new_request()
    {
        $this->requirePermission('operations_create_request');
        $data['workflows'] = $this->db->table($this->p . 'oa_workflows')->where(['status' => 'active', 'deleted' => 0])->orderBy('name')->get()->getResult();
        return $this->template->rander('operations_approval\Views\operations\new_request', $data);
    }

    public function create()
    {
        $this->requirePermission('operations_create_request');
        $this->validate_submitted_data(['workflow_id' => 'required|numeric', 'title' => 'required|max_length[255]']);
        $workflow = $this->db->table($this->p . 'oa_workflows')->where(['id' => $this->request->getPost('workflow_id'), 'status' => 'active', 'deleted' => 0])->get()->getRow();
        if (!$workflow || !$workflow->current_version_id) return $this->jsonError(app_lang('operations_workflow_not_available'));
        $now = get_current_utc_time();
        $fields = $this->availableFields($this->db->table($this->p . 'oa_fields')->where('version_id', $workflow->current_version_id)->get()->getResult());
        foreach ($fields as $field) {
            $value = $this->request->getPost('field_' . $field->field_key);
            if ($field->is_required && ($value === null || $value === '')) return $this->jsonError(sprintf(app_lang('operations_field_required'), $field->label));
            if ($field->field_key === 'currency' && !in_array(strtoupper((string) $value), $this->allowedCurrencies(), true)) return $this->jsonError(app_lang('operations_currency_not_allowed'));
        }
        $this->db->transBegin();
        try {
            $this->db->table($this->p . 'oa_requests')->insert([
                'workflow_id' => $workflow->id, 'requester_id' => $this->login_user->id, 'title' => clean_data($this->request->getPost('title')),
                'priority' => clean_data($this->request->getPost('priority') ?: 'normal'), 'status' => 'draft', 'created_at' => $now, 'updated_at' => $now
            ]);
            $id = (int) $this->db->insertID();
            foreach ($fields as $field) {
                $value = $this->request->getPost('field_' . $field->field_key);
            $this->db->table($this->p . 'oa_request_values')->insert(['request_id' => $id, 'field_id' => $field->id, 'field_key' => $field->field_key, 'value_text' => is_array($value) ? null : clean_data((string) $value), 'value_json' => is_array($value) ? json_encode($value) : null, 'revision_no' => 1, 'created_at' => $now]);
            }
            (new Audit_service())->record('request_created', $id, null, $this->login_user);
            if ($this->request->getPost('submit_request')) (new Workflow_engine())->submit($id, $this->login_user);
            $this->db->transCommit();
            echo json_encode(['success' => true, 'message' => app_lang('record_saved'), 'redirect_to' => get_uri('operations/view/' . $id)]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Operations request creation failed: {message}', ['message' => $e->getMessage()]);
            $this->jsonError($e instanceof \DomainException ? $e->getMessage() : app_lang('error_occurred'));
        }
    }

    public function form(int $workflowId)
    {
        $this->requirePermission('operations_create_request');
        $workflow = $this->db->table($this->p . 'oa_workflows')->where(['id' => $workflowId, 'status' => 'active', 'deleted' => 0])->get()->getRow();
        if (!$workflow) show_404();
        $fields = $this->availableFields($this->db->table($this->p . 'oa_fields')->where('version_id', $workflow->current_version_id)->orderBy('position')->get()->getResult());
        return $this->template->view('operations_approval\Views\operations\request_form', ['workflow' => $workflow, 'fields' => $fields]);
    }

    private function availableFields(array $fields): array
    {
        $setting = $this->db->table($this->p . 'oa_settings')->select('setting_value')->where('setting_key', 'currency_enabled')->get()->getRow();
        $currencyEnabled = !$setting || (string) $setting->setting_value === '1';
        if (!$currencyEnabled) return array_values(array_filter($fields, static fn($field) => $field->field_key !== 'currency'));
        foreach ($fields as $field) {
            if ($field->field_key === 'currency') {
                $config = json_decode($field->config_json ?: '{}', true) ?: [];
                $config['options'] = $this->allowedCurrencies();
                $field->config_json = json_encode($config);
            }
        }
        return $fields;
    }

    private function allowedCurrencies(): array
    {
        $setting = $this->db->table($this->p . 'oa_settings')->select('setting_value')->where('setting_key', 'allowed_currencies')->get()->getRow();
        $codes = array_values(array_unique(array_filter(array_map('trim', explode(',', strtoupper((string) ($setting->setting_value ?? 'NGN')))), static fn($code) => preg_match('/^[A-Z]{3}$/', $code))));
        return $codes ?: ['NGN'];
    }

    public function view(int $id)
    {
        $request = $this->getRequest($id);
        if (!$this->canView($request)) app_redirect('forbidden');
        $data['request'] = $request;
        $data['values'] = $this->db->table($this->p . 'oa_request_values')->where(['request_id' => $id, 'revision_no' => $request->revision_no])->get()->getResult();
        $data['timeline'] = $this->db->table($this->p . 'oa_stage_instances i')->select('i.*, d.decision, d.comment, d.actor_name_snapshot, d.created_at decision_at')->join($this->p . 'oa_decisions d', 'd.stage_instance_id=i.id', 'left')->where('i.request_id', $id)->orderBy('i.position')->orderBy('d.created_at')->get()->getResult();
        $data['comments'] = $this->db->table($this->p . 'oa_comments')->where('request_id', $id)->orderBy('created_at')->get()->getResult();
        $data['attachments'] = $this->db->table($this->p . 'oa_attachments')->where(['request_id' => $id, 'deleted_at' => null])->orderBy('created_at')->get()->getResult();
        $data['conversations'] = $this->db->table($this->p . 'oa_conversations')->where('request_id', $id)->orderBy('opened_at')->get()->getResult();
        $data['revisions'] = $this->db->table($this->p . 'oa_request_revisions')->where('request_id', $id)->orderBy('revision_no', 'DESC')->get()->getResult();
        $data['can_decide'] = $request->current_stage_instance_id && $this->db->table($this->p . 'oa_assignments')->where(['stage_instance_id' => $request->current_stage_instance_id, 'user_id' => $this->login_user->id, 'status' => 'pending'])->countAllResults() > 0;
        $data['active_assignment'] = $data['can_decide'] ? $this->db->table($this->p . 'oa_assignments')->where(['stage_instance_id' => $request->current_stage_instance_id, 'user_id' => $this->login_user->id, 'status' => 'pending'])->get()->getRow() : null;
        $data['active_stage'] = $request->current_stage_instance_id ? $this->db->table($this->p . 'oa_stage_instances')->where('id', $request->current_stage_instance_id)->get()->getRow() : null;
        $data['staff'] = $this->db->table($this->p . 'users')->select("id,CONCAT(first_name,' ',last_name) name")->where(['user_type'=>'staff','status'=>'active','deleted'=>0])->where('id !=',$this->login_user->id)->orderBy('first_name')->get()->getResult();
        return $this->template->rander('operations_approval\Views\operations\view', $data);
    }

    public function request_information(int $id)
    {
        $request = $this->getRequest($id);
        $this->validate_submitted_data(['question' => 'required']);
        $assignment = $this->activeAssignment($request);
        if (!$assignment) app_redirect('forbidden');
        $question = trim((string) $this->request->getPost('question'));
        $now = get_current_utc_time();
        $this->db->transStart();
        $this->db->table($this->p . 'oa_conversations')->insert(['request_id' => $id, 'stage_instance_id' => $request->current_stage_instance_id, 'opened_by' => $this->login_user->id, 'assigned_to' => $request->requester_id, 'status' => 'open', 'question' => clean_data($question), 'opened_at' => $now]);
        $conversationId = (int) $this->db->insertID();
        $this->db->table($this->p . 'oa_requests')->where('id', $id)->update(['status' => 'information_requested', 'updated_at' => $now]);
        (new Audit_service())->record('information_requested', $id, (int) $request->current_stage_instance_id, $this->login_user, [], ['conversation_id' => $conversationId, 'question' => $question]);
        $this->db->transComplete();
        (new Notification_service())->send('information_requested', $id, [(int) $request->requester_id], $this->login_user, ['comment' => $question, 'dedupe' => $conversationId]);
        echo json_encode(['success' => true, 'message' => app_lang('operations_information_requested'), 'redirect_to' => get_uri('operations/view/' . $id)]);
    }

    public function respond_information(int $id)
    {
        $request = $this->getRequest($id);
        if ((int) $request->requester_id !== (int) $this->login_user->id || $request->status !== 'information_requested') app_redirect('forbidden');
        $this->validate_submitted_data(['conversation_id' => 'required|numeric', 'response' => 'required']);
        $conversation = $this->db->table($this->p . 'oa_conversations')->where(['id' => $this->request->getPost('conversation_id'), 'request_id' => $id, 'assigned_to' => $this->login_user->id, 'status' => 'open'])->get()->getRow();
        if (!$conversation) app_redirect('forbidden');
        $response = trim((string) $this->request->getPost('response'));
        $now = get_current_utc_time();
        $this->db->transStart();
        $this->db->table($this->p . 'oa_conversations')->where('id', $conversation->id)->update(['response' => clean_data($response), 'status' => 'answered', 'responded_at' => $now]);
        $this->db->table($this->p . 'oa_requests')->where('id', $id)->update(['status' => 'pending_approval', 'updated_at' => $now]);
        (new Audit_service())->record('information_supplied', $id, (int) $conversation->stage_instance_id, $this->login_user, [], ['conversation_id' => $conversation->id, 'response' => $response]);
        $this->db->transComplete();
        (new Notification_service())->send('approval_assigned', $id, [(int) $conversation->opened_by], $this->login_user, ['comment' => $response, 'dedupe' => 'info-' . $conversation->id]);
        echo json_encode(['success' => true, 'message' => app_lang('operations_information_supplied'), 'redirect_to' => get_uri('operations/view/' . $id)]);
    }

    public function resubmit(int $id)
    {
        $request = $this->getRequest($id);
        if (!(new Access_service())->canEdit($request, $this->login_user) || $request->status !== 'returned') app_redirect('forbidden');
        $this->validate_submitted_data(['resubmission_comment' => 'required']);
        $fields = $this->db->table($this->p . 'oa_fields')->where('version_id', $request->version_id)->get()->getResult();
        $oldRows = $this->db->table($this->p . 'oa_request_values')->where(['request_id' => $id, 'revision_no' => $request->revision_no])->get()->getResult();
        $old = [];
        foreach ($oldRows as $row) $old[$row->field_key] = $row->value_json ? json_decode($row->value_json, true) : $row->value_text;
        $newRevision = ((int) $request->revision_no) + 1;
        $changes = [];
        $this->db->transBegin();
        try {
            foreach ($fields as $field) {
                $config = json_decode($field->config_json ?: '{}', true) ?: [];
                $editable = !array_key_exists('editable_on_return', $config) || !empty($config['editable_on_return']);
                $value = $editable && $this->request->getPost('field_' . $field->field_key) !== null ? $this->request->getPost('field_' . $field->field_key) : ($old[$field->field_key] ?? null);
                if ($field->is_required && ($value === null || $value === '')) throw new \DomainException(sprintf(app_lang('operations_field_required'), $field->label));
                if (($old[$field->field_key] ?? null) != $value) $changes[$field->field_key] = ['from' => $old[$field->field_key] ?? null, 'to' => $value];
                $this->db->table($this->p . 'oa_request_values')->insert(['request_id' => $id, 'field_id' => $field->id, 'field_key' => $field->field_key, 'value_text' => is_array($value) ? null : clean_data((string) $value), 'value_json' => is_array($value) ? json_encode($value) : null, 'revision_no' => $newRevision, 'created_at' => get_current_utc_time()]);
            }
            $reason = trim((string) $this->request->getPost('resubmission_comment'));
            $this->db->table($this->p . 'oa_request_revisions')->insert(['request_id' => $id, 'revision_no' => $newRevision, 'changed_by' => $this->login_user->id, 'reason' => clean_data($reason), 'changes_json' => json_encode($changes), 'created_at' => get_current_utc_time()]);
            $this->db->table($this->p . 'oa_requests')->where('id', $id)->update(['revision_no' => $newRevision, 'updated_at' => get_current_utc_time()]);
            (new Audit_service())->record('request_fields_revised', $id, null, $this->login_user, $old, $changes, ['reason' => $reason, 'revision_no' => $newRevision]);
            (new Workflow_engine())->resubmit($id, $this->login_user);
            $this->db->transCommit();
            echo json_encode(['success' => true, 'message' => app_lang('operations_request_resubmitted'), 'redirect_to' => get_uri('operations/view/' . $id)]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            $this->jsonError($e->getMessage());
        }
    }

    public function cancel(int $id)
    {
        $request = $this->getRequest($id);
        if ((int) $request->requester_id !== (int) $this->login_user->id || !in_array($request->status, ['draft', 'submitted', 'pending_approval', 'returned', 'information_requested'], true)) app_redirect('forbidden');
        $this->validate_submitted_data(['reason' => 'required']);
        $workflow = $this->db->table($this->p . 'oa_workflows')->where('id', $request->workflow_id)->get()->getRow();
        $settings = json_decode($workflow->settings_json ?: '{}', true) ?: [];
        if (empty($settings['allow_cancellation']) && $request->status !== 'draft') throw new \DomainException(app_lang('operations_cancellation_not_allowed'));
        $now = get_current_utc_time();
        $this->db->transStart();
        $this->db->table($this->p . 'oa_requests')->where('id', $id)->update(['status' => 'cancelled', 'current_stage_instance_id' => null, 'cancelled_at' => $now, 'updated_at' => $now]);
        $this->db->table($this->p . 'oa_stage_instances')->where('request_id', $id)->whereIn('status', ['pending', 'active', 'overdue'])->update(['status' => 'cancelled', 'completed_at' => $now]);
        $stageIds = array_column($this->db->table($this->p . 'oa_stage_instances')->select('id')->where('request_id', $id)->get()->getResultArray(), 'id');
        if ($stageIds) $this->db->table($this->p . 'oa_assignments')->whereIn('stage_instance_id', $stageIds)->where('status', 'pending')->update(['status' => 'cancelled']);
        (new Audit_service())->record('request_cancelled', $id, null, $this->login_user, [], ['reason' => trim((string) $this->request->getPost('reason'))]);
        $this->db->transComplete();
        echo json_encode(['success' => true, 'message' => app_lang('operations_request_cancelled'), 'redirect_to' => get_uri('operations/view/' . $id)]);
    }

    public function delegate(int $id)
    {
        $this->validate_submitted_data(['assignment_id' => 'required|numeric', 'delegate_id' => 'required|numeric', 'reason' => 'required']);
        if (!$this->permissions->allowed('operations_manage_delegation', $this->login_user) && !$this->permissions->allowed('operations_approve', $this->login_user)) app_redirect('forbidden');
        try {
            (new Delegation_service())->delegateAssignment((int) $this->request->getPost('assignment_id'), (int) $this->request->getPost('delegate_id'), $this->login_user, trim((string) $this->request->getPost('reason')));
            echo json_encode(['success' => true, 'message' => app_lang('operations_approval_delegated'), 'redirect_to' => get_uri('operations/view/' . $id)]);
        } catch (\Throwable $e) { $this->jsonError($e->getMessage()); }
    }

    public function upload(int $id)
    {
        $request = $this->getRequest($id);
        if (!$this->canView($request)) app_redirect('forbidden');
        $fileName = $this->request->getPost('file_name') ?: $this->request->getPost('file_name_1');
        if (!$fileName) return $this->jsonError(app_lang('operations_file_required'));
        try {
            $attachmentId = (new Attachment_service())->store($id, (int) $this->login_user->id, basename((string) $fileName), $request->current_stage_instance_id ? (int) $request->current_stage_instance_id : null, clean_data($this->request->getPost('context') ?: 'request'));
            (new Audit_service())->record('attachment_uploaded', $id, $request->current_stage_instance_id ? (int) $request->current_stage_instance_id : null, $this->login_user, [], ['attachment_id' => $attachmentId]);
            echo json_encode(['success' => true, 'message' => app_lang('operations_attachment_uploaded'), 'redirect_to' => get_uri('operations/view/' . $id)]);
        } catch (\Throwable $e) { $this->jsonError($e->getMessage()); }
    }

    public function signAttachment()
    {
        $this->validate_submitted_data(['attachment_id' => 'required|numeric']);
        $attachmentId = (int) $this->request->getPost('attachment_id');
        $attachment = (new Attachment_service())->get($attachmentId);
        if (!$attachment) return $this->jsonError(app_lang('not_found'));
        $request = $this->getRequest((int) $attachment->request_id);
        if (!$this->canView($request)) return $this->jsonError(app_lang('access_denied'));
        if (strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION)) !== 'pdf') return $this->jsonError(app_lang('operations_not_a_pdf'));
        $sourcePath = rtrim($attachment->storage_path, '/\\') . DIRECTORY_SEPARATOR . $attachment->storage_name;
        if (!is_file($sourcePath)) return $this->jsonError(app_lang('not_found'));

        $signature = (string) $this->request->getPost('signature');
        $signatureData = $signature ? base64_decode(get_array_value(explode(',', $signature), 1) ?: '') : '';
        if (!$signatureData) return $this->jsonError(app_lang('operations_signature_required'));

        // Fixed bottom-right placement (a plain page-number choice rather than
        // mobile's drag-to-position UI - this is the simpler web equivalent).
        $page = (int) $this->request->getPost('page');
        $x = 0.66; $y = 0.85; $w = 0.30; $h = 0.10;

        try {
            $tempDir = rtrim(get_setting('temp_file_path'), '/\\');
            $signedFileName = Pdf_signer::stamp($sourcePath, '@' . $signatureData, $page, $x, $y, $w, $h, $tempDir, pathinfo($attachment->original_name, PATHINFO_FILENAME));
        } catch (\DomainException $e) {
            return $this->jsonError($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Operations web signAttachment failed: {message}', ['message' => $e->getMessage()]);
            return $this->jsonError('Could not sign this document - it may be encrypted or in an unsupported format.');
        }

        try {
            $signedAttachmentId = (new Attachment_service())->store((int) $attachment->request_id, (int) $this->login_user->id, $signedFileName, $request->current_stage_instance_id ? (int) $request->current_stage_instance_id : null, 'signature');
        } catch (\Throwable $e) {
            return $this->jsonError($e->getMessage());
        }

        (new Audit_service())->record('attachment_signed', (int) $attachment->request_id, $request->current_stage_instance_id ? (int) $request->current_stage_instance_id : null, $this->login_user, [], ['original_attachment_id' => $attachmentId, 'signed_attachment_id' => $signedAttachmentId]);

        echo json_encode(['success' => true, 'message' => app_lang('operations_document_signed'), 'redirect_to' => get_uri('operations/view/' . $attachment->request_id)]);
    }

    public function download(int $attachmentId)
    {
        $attachment = (new Attachment_service())->get($attachmentId);
        if (!$attachment) show_404();
        $request = $this->getRequest((int) $attachment->request_id);
        if (!$this->canView($request)) app_redirect('forbidden');
        $path = rtrim($attachment->storage_path, '/\\') . DIRECTORY_SEPARATOR . $attachment->storage_name;
        if (!is_file($path) || !hash_equals($attachment->sha256, hash_file('sha256', $path))) show_404();
        return $this->response->download($path, null)->setFileName($attachment->original_name);
    }

    public function export()
    {
        $this->requirePermission('operations_export');
        $rows = $this->db->table($this->p . 'oa_requests r')->select('r.request_no,w.name workflow,r.title,r.priority,r.status,r.submitted_at,r.completed_at')->join($this->p . 'oa_workflows w', 'w.id=r.workflow_id')->where('r.deleted', 0)->orderBy('r.created_at', 'DESC')->get()->getResultArray();
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, ['Request Number', 'Workflow', 'Title', 'Priority', 'Status', 'Submitted', 'Completed']);
        foreach ($rows as $row) fputcsv($stream, $row);
        rewind($stream); $csv = stream_get_contents($stream); fclose($stream);
        return $this->response->download('operations-requests-' . date('Ymd-His') . '.csv', $csv);
    }

    public function decide(int $id)
    {
        $this->validate_submitted_data(['stage_instance_id' => 'required|numeric', 'lock_version' => 'required|numeric', 'decision' => 'required']);
        $decision = (string) $this->request->getPost('decision');
        $requiredPermission = ['approve' => 'operations_approve', 'reject' => 'operations_reject', 'return' => 'operations_return'][$decision] ?? '';
        if (!$requiredPermission || !$this->permissions->allowed($requiredPermission, $this->login_user)) app_redirect('forbidden');
        try {
            (new Workflow_engine())->decide($id, (int) $this->request->getPost('stage_instance_id'), (int) $this->request->getPost('lock_version'), $decision, trim((string) $this->request->getPost('comment')), $this->login_user);
            echo json_encode(['success' => true, 'message' => app_lang('operations_decision_recorded'), 'redirect_to' => get_uri('operations/view/' . $id)]);
        } catch (\Throwable $e) {
            log_message('warning', 'Operations decision rejected: {message}', ['message' => $e->getMessage()]);
            $this->jsonError($e->getMessage());
        }
    }

    public function submit(int $id)
    {
        $request = $this->getRequest($id);
        if ((int) $request->requester_id !== (int) $this->login_user->id || $request->status !== 'draft') app_redirect('forbidden');
        $workflow = $this->db->table($this->p . 'oa_workflows')->where('id', $request->workflow_id)->get()->getRow();
        $settings = json_decode($workflow->settings_json ?: '{}', true) ?: [];
        if (!empty($settings['require_attachments']) && !$this->db->table($this->p . 'oa_attachments')->where(['request_id' => $id, 'deleted_at' => null])->countAllResults()) return $this->jsonError(app_lang('operations_attachment_required'));
        try {
            (new Workflow_engine())->submit($id, $this->login_user);
            echo json_encode(['success' => true, 'message' => app_lang('operations_submit_request'), 'redirect_to' => get_uri('operations/view/' . $id)]);
        } catch (\Throwable $e) { $this->jsonError($e->getMessage()); }
    }

    public function comment(int $id)
    {
        $request = $this->getRequest($id);
        if (!$this->canView($request) || !$this->permissions->allowed('operations_comment', $this->login_user)) app_redirect('forbidden');
        $this->validate_submitted_data(['comment' => 'required']);
        $this->db->table($this->p . 'oa_comments')->insert(['request_id' => $id, 'stage_instance_id' => $request->current_stage_instance_id ?: null, 'user_id' => $this->login_user->id, 'user_name_snapshot' => trim($this->login_user->first_name . ' ' . $this->login_user->last_name), 'comment' => clean_data($this->request->getPost('comment')), 'visibility' => 'workflow', 'created_at' => get_current_utc_time()]);
        (new Audit_service())->record('comment_created', $id, $request->current_stage_instance_id ? (int) $request->current_stage_instance_id : null, $this->login_user);
        echo json_encode(['success' => true, 'message' => app_lang('comment_added'), 'redirect_to' => get_uri('operations/view/' . $id)]);
    }

    public function reports()
    {
        $this->requirePermission('operations_view_reports');
        $filters=['date_from'=>$this->request->getGet('date_from'),'date_to'=>$this->request->getGet('date_to'),'workflow_id'=>$this->request->getGet('workflow_id'),'status'=>$this->request->getGet('status'),'department_id'=>$this->request->getGet('department_id')];
        $summary=(new Report_service())->summary($filters);
        $workflows=$this->db->table($this->p.'oa_workflows')->where('deleted',0)->orderBy('name')->get()->getResult();
        $departments=$this->db->table($this->p.'oa_departments')->where('deleted',0)->orderBy('name')->get()->getResult();
        return $this->template->rander('operations_approval\Views\operations\reports',compact('summary','filters','workflows','departments'));
    }

    public function receipt(int $id)
    {
        $data = $this->receiptData($id);
        return $this->template->rander('operations_approval\Views\operations\receipt', $data);
    }

    public function send_receipt_email(int $id)
    {
        $data = $this->receiptData($id);
        if (!filter_var($data['receipt']['client_email'], FILTER_VALIDATE_EMAIL)) return $this->jsonError(app_lang('operations_receipt_email_missing'));
        $subject = 'Payment receipt ' . $data['request']->request_no;
        $message = view('operations_approval\Views\operations\receipt_email', $data);
        if (!send_app_mail($data['receipt']['client_email'], $subject, $message)) return $this->jsonError(app_lang('operations_receipt_email_failed'));
        (new Audit_service())->record('receipt_emailed', $id, null, $this->login_user);
        echo json_encode(['success' => true, 'message' => app_lang('operations_receipt_email_sent'), 'redirect_to' => get_uri('operations/receipt/' . $id)]);
    }

    public function share_receipt(int $id, string $channel)
    {
        if (!in_array($channel, ['whatsapp', 'sms'], true)) show_404();
        $data = $this->receiptData($id);
        $phone = preg_replace('/\D+/', '', $data['receipt']['client_phone']);
        if (!$phone) app_redirect('operations/receipt/' . $id);
        $message = $this->receiptMessage($data['request'], $data['receipt']);
        (new Audit_service())->record('receipt_' . $channel . '_opened', $id, null, $this->login_user);
        if ($channel === 'whatsapp') {
            if (str_starts_with($phone, '0')) $phone = '234' . substr($phone, 1);
            return redirect()->to('https://wa.me/' . $phone . '?text=' . rawurlencode($message));
        }
        return redirect()->to('sms:' . $phone . '?body=' . rawurlencode($message));
    }

    private function receiptData(int $id): array
    {
        $request = $this->getRequest($id);
        if (!$this->canView($request) || $request->workflow_code !== 'CLIENT_PAYMENT_RECEIPT') app_redirect('forbidden');
        if (!in_array($request->status, ['completed', 'approved'], true)) app_redirect('operations/view/' . $id);
        $rows = $this->db->table($this->p . 'oa_request_values')->select('field_key,value_text,value_json')->where(['request_id' => $id, 'revision_no' => $request->revision_no])->get()->getResult();
        $receipt = [];
        foreach ($rows as $row) $receipt[$row->field_key] = $row->value_text ?: $row->value_json;
        $receipt += ['client_name'=>'','client_email'=>'','client_phone'=>'','amount'=>'0','currency'=>'NGN','payment_date'=>'','payment_method'=>'','payment_reference'=>'','invoice_number'=>'','payment_for'=>''];
        return ['request' => $request, 'receipt' => $receipt, 'login_user' => $this->login_user];
    }

    private function receiptMessage(object $request, array $receipt): string
    {
        return "Payment Receipt {$request->request_no}\nClient: {$receipt['client_name']}\nAmount: {$receipt['currency']} " . number_format((float)$receipt['amount'], 2) . "\nDate: {$receipt['payment_date']}\nReference: {$receipt['payment_reference']}\nPayment for: {$receipt['payment_for']}\nThank you.";
    }

    private function getRequest(int $id): object
    {
        $row = $this->db->table($this->p . 'oa_requests r')->select('r.*, w.name workflow_name, w.code workflow_code')->join($this->p . 'oa_workflows w', 'w.id=r.workflow_id')->where(['r.id' => $id, 'r.deleted' => 0])->get()->getRow();
        if (!$row) show_404();
        return $row;
    }

    private function canView(object $request): bool
    {
        return (new Access_service())->canView($request, $this->login_user);
    }

    private function activeAssignment(object $request): ?object
    {
        if (!$request->current_stage_instance_id) return null;
        return $this->db->table($this->p . 'oa_assignments')->where(['stage_instance_id' => $request->current_stage_instance_id, 'user_id' => $this->login_user->id, 'status' => 'pending'])->get()->getRow();
    }

    private function visibleRequests($builder)
    {
        if (!$this->permissions->allowed('operations_view_all_requests', $this->login_user)) $builder->where('requester_id', $this->login_user->id);
        return $builder;
    }

    private function requirePermission(string $permission): void
    {
        if (!$this->permissions->allowed($permission, $this->login_user)) app_redirect('forbidden');
    }

    private function jsonError(string $message)
    {
        echo json_encode(['success' => false, 'message' => $message]);
        return false;
    }
}
