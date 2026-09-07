<?php

namespace operations_approval\Controllers;

use App\Controllers\Security_Controller;
use operations_approval\Libraries\Operations_permissions;

class Operations_workflows extends Security_Controller
{
    private $db;
    private $p;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        if (!(new Operations_permissions())->allowed('operations_manage_workflows', $this->login_user)) app_redirect('forbidden');
        $this->db = db_connect('default');
        $this->p = $this->db->getPrefix();
    }

    public function index()
    {
        $rows = $this->db->table($this->p . 'oa_workflows')->where('deleted', 0)->orderBy('name')->get()->getResult();
        return $this->template->rander('operations_approval\Views\workflows\index', ['rows' => $rows]);
    }

    public function edit(int $id = 0)
    {
        $workflow = $id ? $this->db->table($this->p . 'oa_workflows')->where(['id' => $id, 'deleted' => 0])->get()->getRow() : null;
        $definition = ['fields' => [], 'stages' => []];
        if ($workflow) {
            $version = $this->db->table($this->p . 'oa_workflow_versions')->where('workflow_id', $id)->orderBy('version_no', 'DESC')->get(1)->getRow();
            if ($version) $definition = json_decode($version->definition_json, true) ?: $definition;
        }
        $users = $this->db->table($this->p . 'users')->select("id, CONCAT(first_name, ' ', last_name) name")->where(['user_type' => 'staff', 'status' => 'active', 'deleted' => 0])->orderBy('first_name')->get()->getResult();
        $roles = $this->db->table($this->p . 'roles')->select('id,title name')->where('deleted', 0)->orderBy('title')->get()->getResult();
        $groups = $this->db->table($this->p . 'oa_approver_groups')->select('id,name')->where(['deleted' => 0, 'status' => 'active'])->orderBy('name')->get()->getResult();
        $departments = $this->db->table($this->p . 'oa_departments')->select('id,name')->where(['deleted' => 0, 'status' => 'active'])->orderBy('name')->get()->getResult();
        return $this->template->rander('operations_approval\Views\workflows\edit', ['workflow' => $workflow, 'definition' => json_encode($definition, JSON_UNESCAPED_SLASHES), 'users' => $users, 'roles' => $roles, 'groups' => $groups, 'departments' => $departments]);
    }

    public function save()
    {
        $this->validate_submitted_data(['id' => 'permit_empty|numeric', 'name' => 'required|max_length[150]', 'code' => 'required|max_length[50]', 'prefix' => 'required|max_length[20]', 'definition_json' => 'required']);
        $id = (int) $this->request->getPost('id');
        $code = strtoupper(clean_data($this->request->getPost('code')));
        // code is globally unique at the DB level, including soft-deleted
        // rows (delete() frees its code by renaming it). DBDebug is off in
        // production, so letting a duplicate reach the INSERT/UPDATE would
        // fail silently (returns false, no exception) and this would
        // still report "success" while never having written the row -
        // check up front instead.
        if ($this->db->table($this->p . 'oa_workflows')->where('code', $code)->where('id !=', $id)->countAllResults()) {
            return $this->jsonError(app_lang('operations_duplicate_workflow_code'));
        }
        $definition = json_decode((string) $this->request->getPost('definition_json'), true);
        $errors = $this->validateDefinition($definition);
        if ($errors) return $this->jsonError(implode(' ', $errors));
        $now = get_current_utc_time();
        $workflowSettings = [];
        foreach (['allow_attachments','require_attachments','allow_requester_comments','allow_approver_attachments','allow_return','allow_cancellation','allow_resubmission','allow_delegation'] as $option) $workflowSettings[$option] = $this->request->getPost($option) ? true : false;
        $workflowSettings['completion_behavior'] = clean_data($this->request->getPost('completion_behavior') ?: 'completed');
        $workflowData = ['name' => clean_data($this->request->getPost('name')), 'code' => $code, 'prefix' => strtoupper(clean_data($this->request->getPost('prefix'))), 'description' => clean_data($this->request->getPost('description')), 'settings_json' => json_encode($workflowSettings), 'updated_at' => $now];
        $this->db->transBegin();
        try {
            if ($id) {
                if (!$this->db->table($this->p . 'oa_workflows')->where(['id' => $id, 'deleted' => 0])->update($workflowData)) throw new \RuntimeException('Failed to update the workflow record.');
            } else {
                $workflowData += ['status' => 'draft', 'created_by' => $this->login_user->id, 'created_at' => $now];
                if (!$this->db->table($this->p . 'oa_workflows')->insert($workflowData)) throw new \RuntimeException('Failed to create the workflow record.');
                $id = (int) $this->db->insertID();
            }
            $last = $this->db->table($this->p . 'oa_workflow_versions')->selectMax('version_no', 'max_version')->where('workflow_id', $id)->get()->getRow();
            $versionNo = ((int) ($last->max_version ?? 0)) + 1;
            $json = json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!$this->db->table($this->p . 'oa_workflow_versions')->insert(['workflow_id' => $id, 'version_no' => $versionNo, 'definition_json' => $json, 'definition_hash' => hash('sha256', $json), 'status' => 'draft', 'created_by' => $this->login_user->id, 'created_at' => $now])) throw new \RuntimeException('Failed to save the workflow version.');
            $this->db->transCommit();
            echo json_encode(['success' => true, 'message' => app_lang('record_saved'), 'redirect_to' => get_uri('operations_workflows/edit/' . $id)]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Operations workflow save failed: {message}', ['message' => $e->getMessage()]);
            $this->jsonError(app_lang('error_occurred'));
        }
    }

    public function publish(int $id)
    {
        $version = $this->db->table($this->p . 'oa_workflow_versions')->where(['workflow_id' => $id, 'status' => 'draft'])->orderBy('version_no', 'DESC')->get(1)->getRow();
        if (!$version) return $this->jsonError(app_lang('operations_no_draft_version'));
        $definition = json_decode($version->definition_json, true);
        $errors = $this->validateDefinition($definition);
        if ($errors) return $this->jsonError(implode(' ', $errors));
        $this->db->transBegin();
        try {
            foreach ($definition['fields'] as $position => $field) {
                $this->db->table($this->p . 'oa_fields')->insert(['version_id' => $version->id, 'field_key' => $field['key'], 'label' => $field['label'], 'field_type' => $field['type'], 'position' => $position + 1, 'config_json' => json_encode($field), 'is_required' => !empty($field['required']) ? 1 : 0]);
            }
            foreach ($definition['stages'] as $position => $stage) {
                $this->db->table($this->p . 'oa_stages')->insert(['version_id' => $version->id, 'name' => $stage['name'], 'stage_type' => $stage['type'] ?? 'approval', 'position' => $position + 1, 'approver_type' => $stage['approver_type'], 'approver_config_json' => json_encode($stage['approver'] ?? []), 'approval_rule' => $stage['rule'] ?? 'any', 'required_count' => (int) ($stage['required_count'] ?? 1), 'condition_json' => !empty($stage['condition']) ? json_encode($stage['condition']) : null, 'settings_json' => json_encode($stage['settings'] ?? []), 'sla_minutes' => !empty($stage['sla_minutes']) ? (int) $stage['sla_minutes'] : null]);
            }
            $now = get_current_utc_time();
            $this->db->table($this->p . 'oa_workflow_versions')->where('id', $version->id)->update(['status' => 'published', 'published_by' => $this->login_user->id, 'published_at' => $now]);
            $this->db->table($this->p . 'oa_workflows')->where('id', $id)->update(['current_version_id' => $version->id, 'status' => 'active', 'updated_at' => $now]);
            $this->db->transCommit();
            echo json_encode(['success' => true, 'message' => app_lang('operations_workflow_published'), 'redirect_to' => get_uri('operations_workflows')]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Operations workflow publish failed: {message}', ['message' => $e->getMessage()]);
            $this->jsonError(app_lang('error_occurred'));
        }
    }

    public function toggle_status(int $id)
    {
        $workflow = $this->db->table($this->p . 'oa_workflows')->where(['id' => $id, 'deleted' => 0])->get()->getRow();
        if (!$workflow) return $this->jsonError(app_lang('record_not_found'));
        if (empty($workflow->current_version_id)) return $this->jsonError(app_lang('operations_publish_before_enabling'));
        $status = $workflow->status === 'active' ? 'inactive' : 'active';
        $this->db->table($this->p . 'oa_workflows')->where('id', $id)->update(['status' => $status, 'updated_at' => get_current_utc_time()]);
        echo json_encode([
            'success' => true,
            'message' => $status === 'active' ? app_lang('operations_workflow_enabled') : app_lang('operations_workflow_disabled'),
            'redirect_to' => get_uri('operations_workflows')
        ]);
    }

    // A soft-deleted workflow simply drops off this list and out of the
    // "new request" picker (both already filter deleted=0) - it never
    // removes rows or breaks joins for requests already created against
    // it (Workflow_engine and every request query look the workflow up
    // by id with no deleted filter). The one thing it WOULD break is an
    // admin's ability to reach operations_workflows/edit to fix the
    // stage setup behind a stuck request, so block deletion while any
    // request against this workflow is still in flight.
    public function delete(int $id)
    {
        $workflow = $this->db->table($this->p . 'oa_workflows')->where(['id' => $id, 'deleted' => 0])->get()->getRow();
        if (!$workflow) return $this->jsonError(app_lang('record_not_found'));
        $blocking = $this->db->table($this->p . 'oa_requests')->select('id,request_no,status')->where(['workflow_id' => $id, 'deleted' => 0])->whereIn('status', ['draft', 'submitted', 'pending_approval', 'returned', 'information_requested', 'configuration_error'])->orderBy('created_at')->get()->getResult();
        if ($blocking) {
            $labels = array_map(fn ($r) => $r->request_no ?: ('#' . $r->id), array_slice($blocking, 0, 5));
            if (count($blocking) > 5) $labels[] = '+' . (count($blocking) - 5) . ' more';
            return $this->jsonError(sprintf(app_lang('operations_workflow_has_active_requests'), implode(', ', $labels)));
        }
        // code stays unique at the DB level even for deleted=1 rows (the
        // index has no deleted component), so free it up here rather than
        // leaving a deleted workflow permanently blocking reuse of its
        // code. code is VARCHAR(50); truncate before appending the suffix
        // so this never overflows the column.
        $suffix = '_del' . $id;
        $freedCode = substr($workflow->code, 0, 50 - strlen($suffix)) . $suffix;
        if (!$this->db->table($this->p . 'oa_workflows')->where('id', $id)->update(['deleted' => 1, 'code' => $freedCode, 'updated_at' => get_current_utc_time()])) {
            return $this->jsonError(app_lang('error_occurred'));
        }
        echo json_encode(['success' => true, 'message' => app_lang('operations_workflow_deleted'), 'redirect_to' => get_uri('operations_workflows')]);
    }

    private function validateDefinition($definition): array
    {
        if (!is_array($definition)) return [app_lang('operations_invalid_definition_json')];
        $errors = [];
        $keys = [];
        foreach ($definition['fields'] ?? [] as $field) {
            if (empty($field['key']) || !preg_match('/^[a-z][a-z0-9_]*$/', $field['key'])) $errors[] = app_lang('operations_invalid_field_key');
            if (in_array($field['key'] ?? '', $keys, true)) $errors[] = app_lang('operations_duplicate_field_key');
            $keys[] = $field['key'] ?? '';
            if (empty($field['label']) || empty($field['type'])) $errors[] = app_lang('operations_incomplete_field');
        }
        if (empty($definition['stages'])) $errors[] = app_lang('operations_stage_required');
        foreach ($definition['stages'] ?? [] as $stage) {
            if (empty($stage['name']) || !in_array($stage['approver_type'] ?? '', ['users', 'role', 'dynamic_field', 'group', 'requester_manager', 'department_head', 'requester_department_head'], true)) $errors[] = app_lang('operations_invalid_stage');
            $count = count($stage['approver']['user_ids'] ?? []);
            if (($stage['rule'] ?? 'any') === 'minimum' && (int) ($stage['required_count'] ?? 0) > $count && ($stage['approver_type'] ?? '') === 'users') $errors[] = app_lang('operations_impossible_approval_count');
        }
        return array_values(array_unique($errors));
    }

    private function jsonError(string $message)
    {
        echo json_encode(['success' => false, 'message' => $message]);
        return false;
    }
}
