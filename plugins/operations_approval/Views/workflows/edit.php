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
        <button type="button" class="btn btn-outline-secondary btn-sm oa-template-btn mr-2 mb-2" data-template="<?php echo esc(json_encode($tpl['definition'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'attr'); ?>"><?php echo esc($tpl['label']); ?></button>
    <?php } ?>
    <div><small class="text-muted">Loads a working starting point into the box below - review who it approves through before saving, then add/edit fields and stages as needed.</small></div>
</div>
<div class="form-group"><label for="oa-definition"><?php echo app_lang('operations_definition_json'); ?></label><textarea id="oa-definition" name="definition_json" class="form-control font-monospace" rows="24" required><?php echo esc($definition); ?></textarea><small class="text-muted"><?php echo app_lang('operations_definition_help'); ?></small></div><button class="btn btn-primary"><?php echo app_lang('save'); ?></button><?php echo form_close(); ?></div></div>
</div><script>$(document).ready(function(){
    $('#operations-workflow-form').appForm({isModal:false});
    $('.oa-template-btn').on('click', function () {
        $('#oa-definition').val($(this).attr('data-template'));
    });
});</script>
