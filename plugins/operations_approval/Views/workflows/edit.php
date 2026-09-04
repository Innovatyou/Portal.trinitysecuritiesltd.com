<?php echo view('operations_approval\Views\partials\styles'); ?><div class="oa-page"><?php $workflowSettings=json_decode($workflow->settings_json??'{}',true)?:[]; ?>
<div class="page-title clearfix"><h1><?php echo $workflow ? app_lang('edit') : app_lang('add'); ?> <?php echo app_lang('operations_workflow'); ?></h1></div>
<div class="card"><div class="card-body"><?php echo form_open(get_uri('operations_workflows/save'), ['id' => 'operations-workflow-form', 'class' => 'general-form']); ?><input type="hidden" name="id" value="<?php echo (int) ($workflow->id ?? 0); ?>"><div class="row"><div class="col-md-6 form-group"><label><?php echo app_lang('name'); ?></label><input name="name" class="form-control" required value="<?php echo esc($workflow->name ?? ''); ?>"></div><div class="col-md-3 form-group"><label><?php echo app_lang('operations_code'); ?></label><input name="code" class="form-control" required value="<?php echo esc($workflow->code ?? ''); ?>"></div><div class="col-md-3 form-group"><label><?php echo app_lang('operations_prefix'); ?></label><input name="prefix" class="form-control" required value="<?php echo esc($workflow->prefix ?? 'REQ'); ?>"></div></div><div class="form-group"><label><?php echo app_lang('description'); ?></label><textarea name="description" class="form-control"><?php echo esc($workflow->description ?? ''); ?></textarea></div><div class="row mb-3"><?php foreach(['allow_attachments','require_attachments','allow_requester_comments','allow_approver_attachments','allow_return','allow_cancellation','allow_resubmission','allow_delegation'] as $option){ ?><div class="col-md-3"><label><input type="checkbox" name="<?php echo $option; ?>" value="1" <?php echo !empty($workflowSettings[$option])?'checked':''; ?>> <?php echo app_lang('operations_'.$option); ?></label></div><?php } ?></div><?php
// Built from this account's actual users/roles/groups so a clicked
// template is a genuinely valid, ready-to-publish definition (matching
// validateDefinition()'s rules directly) rather than a generic example
// the admin has to hand-edit IDs into before it'll save at all.
$firstUserIds = array_values(array_map(fn($u) => (int) $u->id, array_slice($users, 0, 2)));
$firstRoleId = isset($roles[0]) ? (int) $roles[0]->id : null;
$firstGroupId = isset($groups[0]) ? (int) $groups[0]->id : null;

$moneyFields = [
    ['key' => 'amount', 'label' => 'Amount', 'type' => 'currency', 'required' => true],
    ['key' => 'reason', 'label' => 'Reason', 'type' => 'textarea', 'required' => true],
];

$workflowTemplates = [
    'simple_manager' => [
        'label' => 'Simple - your manager approves',
        'definition' => [
            'fields' => [['key' => 'reason', 'label' => 'Reason', 'type' => 'textarea', 'required' => true]],
            'stages' => [['name' => 'Manager Approval', 'approver_type' => 'requester_manager', 'approver' => new \stdClass(), 'rule' => 'any', 'required_count' => 1]],
        ],
    ],
    'department_head' => [
        'label' => 'Your department head approves',
        'definition' => [
            'fields' => [['key' => 'reason', 'label' => 'Reason', 'type' => 'textarea', 'required' => true]],
            'stages' => [['name' => 'Department Head Approval', 'approver_type' => 'requester_department_head', 'approver' => new \stdClass(), 'rule' => 'any', 'required_count' => 1]],
        ],
    ],
];
if ($firstUserIds) {
    $workflowTemplates['specific_people'] = [
        'label' => count($firstUserIds) >= 2 ? 'Two specific people must both approve' : 'One specific person approves',
        'definition' => [
            'fields' => $moneyFields,
            'stages' => [['name' => 'Approval', 'approver_type' => 'users', 'approver' => ['user_ids' => $firstUserIds], 'rule' => count($firstUserIds) >= 2 ? 'minimum' : 'any', 'required_count' => count($firstUserIds)]],
        ],
    ];
}
if ($firstRoleId) {
    $workflowTemplates['by_role'] = [
        'label' => 'Anyone with a specific role approves',
        'definition' => [
            'fields' => $moneyFields,
            'stages' => [['name' => 'Approval', 'approver_type' => 'role', 'approver' => ['role_id' => $firstRoleId], 'rule' => 'any', 'required_count' => 1]],
        ],
    ];
}
if ($firstGroupId) {
    $workflowTemplates['two_stage'] = [
        'label' => 'Two stages - manager, then an approver group',
        'definition' => [
            'fields' => $moneyFields,
            'stages' => [
                ['name' => 'Manager Approval', 'approver_type' => 'requester_manager', 'approver' => new \stdClass(), 'rule' => 'any', 'required_count' => 1],
                ['name' => 'Group Approval', 'approver_type' => 'group', 'approver' => ['group_id' => $firstGroupId], 'rule' => 'any', 'required_count' => 1],
            ],
        ],
    ];
}
?>
<div class="form-group">
    <label><?php echo app_lang('operations_workflow_templates'); ?></label><br>
    <?php foreach ($workflowTemplates as $tpl) { ?>
        <button type="button" class="btn btn-outline-secondary btn-sm oa-template-btn mr-2 mb-2" data-template="<?php echo esc(json_encode($tpl['definition'], JSON_UNESCAPED_SLASHES), 'attr'); ?>"><?php echo esc($tpl['label']); ?></button>
    <?php } ?>
    <div><small class="text-muted">Loads a starting point into the approval levels below, which you can then adjust - or build levels from scratch with "+ Add approval level".</small></div>
</div>
<div class="form-group">
    <label>Approval levels</label>
    <div id="oa-levels-container"></div>
    <button type="button" id="oa-add-level" class="btn btn-outline-primary btn-sm"><i class="fa fa-plus"></i> Add approval level</button>
    <div><small class="text-muted">Each level is a stage a request must pass before moving to the next. Add one level per person/role that needs to sign off, in order.</small></div>
</div>

<div id="oa-level-template" style="display:none;">
    <div class="card oa-level-row mb-2">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Level name</label>
                    <input type="text" class="form-control form-control-sm oa-level-name" placeholder="e.g. Manager Approval">
                </div>
                <div class="col-md-4 form-group">
                    <label>Who approves this level</label>
                    <select class="form-control form-control-sm oa-level-approver-type">
                        <option value="users">Specific people</option>
                        <option value="role">Anyone with a role</option>
                        <option value="group">An approver group</option>
                        <option value="requester_manager">The requester's manager</option>
                        <option value="department_head">A specific department's head</option>
                        <option value="requester_department_head">The requester's own department head</option>
                        <option value="dynamic_field">Whoever the requester picks in a field</option>
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Approval rule</label>
                    <select class="form-control form-control-sm oa-level-rule">
                        <option value="any">Any one approver decides</option>
                        <option value="minimum">Require a minimum number of approvals</option>
                    </select>
                </div>
                <div class="col-md-1 form-group d-flex align-items-end justify-content-end">
                    <button type="button" class="btn btn-outline-danger btn-sm oa-level-remove" title="Remove this level">&times;</button>
                </div>
            </div>
            <div class="row oa-level-count-row" style="display:none;">
                <div class="col-md-4 form-group">
                    <label>Number of approvals required</label>
                    <input type="number" min="1" value="1" class="form-control form-control-sm oa-level-count">
                </div>
            </div>
            <div class="oa-level-users-panel form-group">
                <label>Select who can approve this level</label><br>
                <?php foreach ($users as $u) { ?>
                    <label class="mr-3"><input type="checkbox" class="oa-level-user-checkbox" value="<?php echo (int) $u->id; ?>"> <?php echo esc($u->name); ?></label>
                <?php } ?>
                <?php if (!$users) { ?><div class="text-muted">No staff members found.</div><?php } ?>
            </div>
            <div class="oa-level-role-panel form-group" style="display:none;">
                <label>Role</label>
                <select class="form-control form-control-sm oa-level-role">
                    <?php foreach ($roles as $r) { ?><option value="<?php echo (int) $r->id; ?>"><?php echo esc($r->name); ?></option><?php } ?>
                </select>
            </div>
            <div class="oa-level-group-panel form-group" style="display:none;">
                <label>Approver group</label>
                <select class="form-control form-control-sm oa-level-group">
                    <?php foreach ($groups as $g) { ?><option value="<?php echo (int) $g->id; ?>"><?php echo esc($g->name); ?></option><?php } ?>
                </select>
            </div>
            <div class="oa-level-department-panel form-group" style="display:none;">
                <label>Department</label>
                <select class="form-control form-control-sm oa-level-department">
                    <?php foreach ($departments as $d) { ?><option value="<?php echo (int) $d->id; ?>"><?php echo esc($d->name); ?></option><?php } ?>
                </select>
            </div>
            <div class="oa-level-field-panel form-group" style="display:none;">
                <label>Field key that holds the approver's user ID</label>
                <input type="text" class="form-control form-control-sm oa-level-field-key" placeholder="e.g. manager_id">
                <small class="text-muted">Must match a field key defined in the JSON box's "fields" array.</small>
            </div>
        </div>
    </div>
</div>

<div class="form-group"><label for="oa-definition"><?php echo app_lang('operations_definition_json'); ?></label><textarea id="oa-definition" name="definition_json" class="form-control font-monospace" rows="24" required><?php echo esc($definition); ?></textarea><small class="text-muted"><?php echo app_lang('operations_definition_help'); ?> Fields aren't editable above yet - add/edit the "fields" array here directly; the levels above only manage "stages".</small></div><button class="btn btn-primary"><?php echo app_lang('save'); ?></button><?php echo form_close(); ?></div></div>
</div>
<script>
var oaExistingStages = <?php $decodedDefinition = json_decode($definition, true) ?: ['stages' => []]; echo json_encode($decodedDefinition['stages'] ?? [], JSON_UNESCAPED_SLASHES); ?>;

$(document).ready(function () {
    $('#operations-workflow-form').appForm({isModal: false, onSuccess: oaFormFeedback});

    function currentFieldsFromTextarea() {
        try {
            var current = JSON.parse($('#oa-definition').val() || '{}');
            return current.fields || [];
        } catch (e) {
            return [];
        }
    }

    function rebuildDefinitionJson() {
        var stages = [];
        $('#oa-levels-container .oa-level-row').each(function () {
            var $row = $(this);
            var approverType = $row.find('.oa-level-approver-type').val();
            var approver = {};
            if (approverType === 'users') {
                var ids = [];
                $row.find('.oa-level-user-checkbox:checked').each(function () {
                    ids.push(parseInt($(this).val(), 10));
                });
                approver = {user_ids: ids};
            } else if (approverType === 'role') {
                approver = {role_id: parseInt($row.find('.oa-level-role').val(), 10) || 0};
            } else if (approverType === 'group') {
                approver = {group_id: parseInt($row.find('.oa-level-group').val(), 10) || 0};
            } else if (approverType === 'department_head') {
                approver = {department_id: parseInt($row.find('.oa-level-department').val(), 10) || 0};
            } else if (approverType === 'dynamic_field') {
                approver = {field_key: $row.find('.oa-level-field-key').val() || ''};
            }
            var rule = $row.find('.oa-level-rule').val();
            var requiredCount = parseInt($row.find('.oa-level-count').val(), 10) || 1;
            var stage = {
                name: $row.find('.oa-level-name').val() || 'Approval',
                approver_type: approverType,
                approver: approver,
                rule: rule,
                required_count: rule === 'minimum' ? requiredCount : 1
            };
            // Advanced config (condition/settings/sla_minutes) an existing
            // stage might already have but this builder doesn't expose UI
            // for - preserved as-is via .data() so rebuilding from the
            // levels above never silently drops it.
            var advanced = $row.data('oaAdvanced');
            if (advanced) $.extend(stage, advanced);
            stages.push(stage);
        });
        var def = {fields: currentFieldsFromTextarea(), stages: stages};
        $('#oa-definition').val(JSON.stringify(def, null, 4));
    }

    function updateApproverPanels($row) {
        var type = $row.find('.oa-level-approver-type').val();
        $row.find('.oa-level-users-panel, .oa-level-role-panel, .oa-level-group-panel, .oa-level-department-panel, .oa-level-field-panel').hide();
        if (type === 'users') $row.find('.oa-level-users-panel').show();
        else if (type === 'role') $row.find('.oa-level-role-panel').show();
        else if (type === 'group') $row.find('.oa-level-group-panel').show();
        else if (type === 'department_head') $row.find('.oa-level-department-panel').show();
        else if (type === 'dynamic_field') $row.find('.oa-level-field-panel').show();
    }

    function updateRuleVisibility($row) {
        $row.find('.oa-level-count-row').toggle($row.find('.oa-level-rule').val() === 'minimum');
    }

    function bindLevelRow($row) {
        $row.find('.oa-level-approver-type').on('change', function () {
            updateApproverPanels($row);
            rebuildDefinitionJson();
        });
        $row.find('.oa-level-rule').on('change', function () {
            updateRuleVisibility($row);
            rebuildDefinitionJson();
        });
        $row.find('.oa-level-name, .oa-level-count, .oa-level-role, .oa-level-group, .oa-level-department, .oa-level-field-key').on('input change', rebuildDefinitionJson);
        $row.find('.oa-level-user-checkbox').on('change', rebuildDefinitionJson);
        $row.find('.oa-level-remove').on('click', function () {
            $row.remove();
            rebuildDefinitionJson();
        });
        updateApproverPanels($row);
        updateRuleVisibility($row);
    }

    function addLevelRow(prefill) {
        var $row = $('#oa-level-template .oa-level-row').clone();
        $('#oa-levels-container').append($row);
        if (prefill) {
            $row.find('.oa-level-name').val(prefill.name || '');
            $row.find('.oa-level-approver-type').val(prefill.approver_type || 'users');
            $row.find('.oa-level-rule').val(prefill.rule || 'any');
            $row.find('.oa-level-count').val(prefill.required_count || 1);
            var approver = prefill.approver || {};
            if (approver.user_ids) {
                approver.user_ids.forEach(function (id) {
                    $row.find('.oa-level-user-checkbox[value="' + id + '"]').prop('checked', true);
                });
            }
            if (approver.role_id) $row.find('.oa-level-role').val(approver.role_id);
            if (approver.group_id) $row.find('.oa-level-group').val(approver.group_id);
            if (approver.department_id) $row.find('.oa-level-department').val(approver.department_id);
            if (approver.field_key) $row.find('.oa-level-field-key').val(approver.field_key);
            var advanced = {};
            if (prefill.condition) advanced.condition = prefill.condition;
            if (prefill.settings) advanced.settings = prefill.settings;
            if (prefill.sla_minutes) advanced.sla_minutes = prefill.sla_minutes;
            if (Object.keys(advanced).length) $row.data('oaAdvanced', advanced);
        }
        bindLevelRow($row);
        return $row;
    }

    $('#oa-add-level').on('click', function () {
        addLevelRow(null);
        rebuildDefinitionJson();
    });

    $('.oa-template-btn').on('click', function () {
        var def = JSON.parse($(this).attr('data-template'));
        $('#oa-definition').val(JSON.stringify(def, null, 4));
        $('#oa-levels-container').empty();
        (def.stages || []).forEach(function (stage) {
            addLevelRow(stage);
        });
        rebuildDefinitionJson();
    });

    // Load this workflow's existing stages into the builder, if editing
    // one - left as-is otherwise (a brand-new workflow starts with zero
    // levels, same as the empty {"fields":[],"stages":[]} it always did).
    oaExistingStages.forEach(function (stage) {
        addLevelRow(stage);
    });
});
</script>
