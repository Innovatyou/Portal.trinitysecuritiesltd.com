<?php

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

$database = $argv[1] ?? 'admintsl_securitiesportaldb';
$prefix = $argv[2] ?? 'tsl_';
$mysqli = new mysqli('127.0.0.1', 'root', '', $database);
if ($mysqli->connect_errno) {
    throw new RuntimeException($mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

function staffId(mysqli $db, string $prefix, string $match, int $fallback): int
{
    $sql = "SELECT id FROM `{$prefix}users` WHERE user_type='staff' AND status='active' AND deleted=0 AND (job_title LIKE ? OR CONCAT(first_name,' ',last_name) LIKE ?) ORDER BY is_admin, id LIMIT 1";
    $stmt = $db->prepare($sql);
    $like = '%' . $match . '%';
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    return (int) ($stmt->get_result()->fetch_assoc()['id'] ?? $fallback);
}

function field(string $key, string $label, string $type = 'text', bool $required = true, array $options = []): array
{
    $field = ['key' => $key, 'label' => $label, 'type' => $type, 'required' => $required];
    if ($options) $field['options'] = $options;
    return $field;
}

function stage(string $name, string $type, array $users, int $slaHours, ?array $condition = null): array
{
    $stage = [
        'name' => $name,
        'type' => $type,
        'approver_type' => 'users',
        'approver' => ['user_ids' => array_values(array_unique($users))],
        'rule' => 'any',
        'required_count' => 1,
        'sla_minutes' => $slaHours * 60,
        'settings' => [
            'allow_self_approval' => false,
            'allow_reject' => true,
            'allow_return' => true,
            'comment_required_for_reject' => true,
            'return_strategy' => 'same_stage'
        ]
    ];
    if ($condition) $stage['condition'] = $condition;
    return $stage;
}

$admin = staffId($mysqli, $prefix, 'Admin', 1);
$managingDirector = staffId($mysqli, $prefix, 'Managing Director', $admin);
$compliance = staffId($mysqli, $prefix, 'Compliance', $admin);
$risk = staffId($mysqli, $prefix, 'Risk', $admin);
$it = staffId($mysqli, $prefix, 'IT', $admin);
$customerService = staffId($mysqli, $prefix, 'Customer Service', $admin);

$amountOver = static fn (int $amount): array => ['mode' => 'AND', 'rules' => [['field' => 'amount', 'operator' => 'greater_than', 'value' => (string) $amount]]];
$critical = ['mode' => 'AND', 'rules' => [['field' => 'severity', 'operator' => 'equals', 'value' => 'Critical']]];
$highRisk = ['mode' => 'AND', 'rules' => [['field' => 'risk_level', 'operator' => 'equals', 'value' => 'High']]];

$workflows = [
    ['Payment Request', 'PAYMENT', 'PAY', 'Request and approve supplier, customer, statutory, and internal payments.', [
        field('amount', 'Amount', 'number'), field('currency', 'Currency', 'dropdown', true, ['NGN','USD','GBP','EUR']), field('beneficiary', 'Beneficiary'),
        field('bank_name', 'Bank Name'), field('account_number', 'Account Number'), field('purpose', 'Payment Purpose', 'textarea'), field('due_date', 'Payment Due Date', 'date')
    ], [stage('Compliance Verification', 'verification', [$compliance,$admin], 24), stage('Finance and Risk Review', 'review', [$risk,$admin], 24), stage('Managing Director Approval', 'final_approval', [$managingDirector,$admin], 24, $amountOver(5000000))]],

    ['Purchase Request', 'PURCHASE', 'PUR', 'Purchase goods, equipment, subscriptions, and services.', [
        field('item_description', 'Goods or Services Required', 'textarea'), field('quantity', 'Quantity', 'number'), field('amount', 'Estimated Amount', 'number'),
        field('currency', 'Currency', 'dropdown', true, ['NGN','USD','GBP','EUR']), field('preferred_vendor', 'Preferred Vendor', 'text', false), field('business_justification', 'Business Justification', 'textarea'), field('required_date', 'Required Date', 'date')
    ], [stage('Operational Review', 'review', [$customerService,$it,$admin], 24), stage('Compliance Review', 'verification', [$compliance,$admin], 24), stage('Managing Director Approval', 'final_approval', [$managingDirector,$admin], 24, $amountOver(2000000))]],

    ['Expense Reimbursement', 'EXPENSE', 'EXP', 'Submit employee expenses for verification and reimbursement approval.', [
        field('amount', 'Total Amount', 'number'), field('currency', 'Currency', 'dropdown', true, ['NGN','USD','GBP','EUR']), field('expense_category', 'Expense Category', 'dropdown', true, ['Transport','Accommodation','Meals','Communication','Office','Client Service','Other']),
        field('expense_date', 'Expense Date', 'date'), field('description', 'Expense Description', 'textarea'), field('payment_details', 'Reimbursement Account Details', 'textarea')
    ], [stage('Expense Verification', 'verification', [$customerService,$admin], 24), stage('Compliance Approval', 'approval', [$compliance,$admin], 24), stage('Executive Approval', 'final_approval', [$managingDirector,$admin], 24, $amountOver(1000000))]],

    ['IT Service Request', 'IT_SERVICE', 'ITR', 'Request technical support, systems access, equipment, or software changes.', [
        field('request_category', 'Request Category', 'dropdown', true, ['Technical Support','System Access','Hardware','Software','Network','Security','Change Request']),
        field('severity', 'Severity', 'dropdown', true, ['Low','Medium','High','Critical']), field('affected_system', 'Affected System or Asset', 'text', false), field('details', 'Request Details', 'textarea'), field('required_date', 'Required Date', 'date', false)
    ], [stage('IT Technical Review', 'review', [$it,$admin], 12), stage('Security and Compliance Review', 'verification', [$compliance,$admin], 12, $critical), stage('Management Approval', 'approval', [$managingDirector,$admin], 12, $critical)]],

    ['Leave Request', 'LEAVE_REQUEST', 'LEV', 'Request annual, medical, compassionate, or other staff leave.', [
        field('leave_type', 'Leave Type', 'dropdown', true, ['Annual','Medical','Compassionate','Study','Maternity','Paternity','Unpaid','Other']), field('start_date', 'Start Date', 'date'), field('end_date', 'End Date', 'date'),
        field('reason', 'Reason', 'textarea'), field('handover_to', 'Handover To'), field('handover_notes', 'Handover Notes', 'textarea', false)
    ], [stage('Administrative Review', 'review', [$customerService,$admin], 24), stage('Management Approval', 'final_approval', [$managingDirector,$admin], 24)]],

    ['Travel Request', 'TRAVEL', 'TRV', 'Request approval for business travel and estimated expenditure.', [
        field('destination', 'Destination'), field('departure_date', 'Departure Date', 'date'), field('return_date', 'Return Date', 'date'), field('purpose', 'Business Purpose', 'textarea'),
        field('amount', 'Estimated Cost', 'number'), field('currency', 'Currency', 'dropdown', true, ['NGN','USD','GBP','EUR']), field('travel_requirements', 'Travel Requirements', 'textarea', false)
    ], [stage('Travel and Budget Review', 'review', [$risk,$customerService,$admin], 24), stage('Compliance Review', 'verification', [$compliance,$admin], 24), stage('Management Approval', 'final_approval', [$managingDirector,$admin], 24)]],

    ['Contract Approval', 'CONTRACT', 'CON', 'Review and approve customer, vendor, partnership, and service contracts.', [
        field('counterparty', 'Counterparty'), field('contract_type', 'Contract Type', 'dropdown', true, ['Customer','Vendor','Service','Employment','Partnership','NDA','Other']), field('amount', 'Contract Value', 'number'),
        field('currency', 'Currency', 'dropdown', true, ['NGN','USD','GBP','EUR']), field('effective_date', 'Effective Date', 'date'), field('expiry_date', 'Expiry Date', 'date', false), field('summary', 'Contract Summary and Key Obligations', 'textarea')
    ], [stage('Compliance and Legal Review', 'verification', [$compliance,$admin], 48), stage('Risk Review', 'review', [$risk,$admin], 24), stage('Managing Director Approval', 'final_approval', [$managingDirector,$admin], 24)]],

    ['Account Opening Approval', 'ACCOUNT_OPENING', 'ACO', 'Review KYC, risk, and approval requirements for new account opening.', [
        field('client_name', 'Client Name'), field('account_type', 'Account Type', 'dropdown', true, ['Individual','Corporate','Institutional','Joint']), field('risk_level', 'Risk Level', 'dropdown', true, ['Low','Medium','High']),
        field('source_of_funds', 'Source of Funds', 'textarea'), field('expected_activity', 'Expected Account Activity', 'textarea'), field('pep_status', 'Politically Exposed Person', 'dropdown', true, ['No','Yes'])
    ], [stage('Customer Service Review', 'review', [$customerService,$admin], 24), stage('Compliance/KYC Approval', 'verification', [$compliance,$admin], 48), stage('Risk Approval', 'approval', [$risk,$admin], 24, $highRisk), stage('Managing Director Approval', 'final_approval', [$managingDirector,$admin], 24, $highRisk)]],

    ['Vendor Onboarding', 'VENDOR_ONBOARDING', 'VEN', 'Perform due diligence and approve a new supplier or service provider.', [
        field('vendor_name', 'Vendor Name'), field('service_category', 'Service Category'), field('contact_name', 'Contact Name'), field('contact_email', 'Contact Email', 'email'), field('tax_id', 'Tax Identification Number', 'text', false),
        field('estimated_annual_value', 'Estimated Annual Value', 'number'), field('due_diligence_notes', 'Due Diligence Notes', 'textarea')
    ], [stage('Operational Due Diligence', 'review', [$customerService,$it,$admin], 24), stage('Compliance Approval', 'verification', [$compliance,$admin], 48), stage('Risk Approval', 'approval', [$risk,$admin], 24), stage('Management Approval', 'final_approval', [$managingDirector,$admin], 24, ['mode'=>'AND','rules'=>[['field'=>'estimated_annual_value','operator'=>'greater_than','value'=>'5000000']]])]],

    ['Client Payment Receipt', 'CLIENT_PAYMENT_RECEIPT', 'RCP', 'Record a client payment, approve it, and issue a receipt by email, WhatsApp, or SMS.', [
        field('client_name', 'Client Name'), field('client_email', 'Client Email', 'email'), field('client_phone', 'Client Phone Number'),
        field('amount', 'Amount Paid', 'number'), field('currency', 'Currency', 'dropdown', true, ['NGN']), field('payment_date', 'Payment Date', 'date'),
        field('payment_method', 'Payment Method', 'dropdown', true, ['Bank Transfer','Card','Cheque','Cash','Direct Debit','Other']),
        field('payment_reference', 'Payment Reference'), field('invoice_number', 'Invoice Number', 'text', false), field('payment_for', 'Payment For', 'textarea')
    ], [stage('Payment Verification and Receipt Approval', 'approval', [$customerService,$admin], 24)]],

    ['Budget Request', 'BUDGET', 'BUD', 'Request new budget allocation, an increase, or transfer between budget lines.', [
        field('budget_type', 'Budget Request Type', 'dropdown', true, ['New Allocation','Increase','Transfer','Emergency']), field('amount', 'Requested Amount', 'number'), field('currency', 'Currency', 'dropdown', true, ['NGN','USD','GBP','EUR']),
        field('budget_period', 'Budget Period'), field('cost_centre', 'Cost Centre'), field('justification', 'Business Justification', 'textarea'), field('expected_benefit', 'Expected Benefit', 'textarea')
    ], [stage('Budget and Risk Review', 'review', [$risk,$admin], 48), stage('Compliance Verification', 'verification', [$compliance,$admin], 24), stage('Managing Director Approval', 'final_approval', [$managingDirector,$admin], 24)]]
];

$mysqli->begin_transaction();
try {
    $now = gmdate('Y-m-d H:i:s');
    foreach ($workflows as [$name,$code,$numberPrefix,$description,$fields,$stages]) {
        $definition = ['fields' => $fields, 'stages' => $stages];
        $json = json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $settings = json_encode(['allow_attachments'=>true,'require_attachments'=>false,'allow_requester_comments'=>true,'allow_approver_attachments'=>true,'allow_return'=>true,'allow_cancellation'=>true,'allow_resubmission'=>true,'allow_delegation'=>true,'completion_behavior'=>'completed']);
        $stmt = $mysqli->prepare("INSERT INTO `{$prefix}oa_workflows` (name,code,description,prefix,settings_json,status,created_by,created_at,updated_at,deleted) VALUES (?,?,?,?,?,'active',?,?,?,0) ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),prefix=VALUES(prefix),settings_json=VALUES(settings_json),status='active',updated_at=VALUES(updated_at),deleted=0");
        $stmt->bind_param('sssssiss', $name,$code,$description,$numberPrefix,$settings,$admin,$now,$now);
        $stmt->execute();
        $workflowId = (int) ($mysqli->insert_id ?: $mysqli->query("SELECT id FROM `{$prefix}oa_workflows` WHERE code='".$mysqli->real_escape_string($code)."'")->fetch_assoc()['id']);
        $existing = $mysqli->query("SELECT id,definition_hash FROM `{$prefix}oa_workflow_versions` WHERE workflow_id={$workflowId} AND status='published' ORDER BY version_no DESC LIMIT 1")->fetch_assoc();
        $hash = hash('sha256', $json);
        if ($existing && hash_equals($existing['definition_hash'], $hash)) {
            $versionId = (int) $existing['id'];
        } else {
            $versionNo = 1 + (int) ($mysqli->query("SELECT COALESCE(MAX(version_no),0) n FROM `{$prefix}oa_workflow_versions` WHERE workflow_id={$workflowId}")->fetch_assoc()['n']);
            $stmt = $mysqli->prepare("INSERT INTO `{$prefix}oa_workflow_versions` (workflow_id,version_no,definition_json,definition_hash,status,published_by,published_at,created_by,created_at) VALUES (?,?,?,?,'published',?,?,?,?)");
            $stmt->bind_param('iissisis', $workflowId,$versionNo,$json,$hash,$admin,$now,$admin,$now);
            $stmt->execute();
            $versionId = (int) $mysqli->insert_id;
            foreach ($fields as $position => $field) {
                $config = json_encode($field, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $required = !empty($field['required']) ? 1 : 0;
                $pos = $position + 1;
                $stmt = $mysqli->prepare("INSERT INTO `{$prefix}oa_fields` (version_id,field_key,label,field_type,position,config_json,is_required) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param('isssisi', $versionId,$field['key'],$field['label'],$field['type'],$pos,$config,$required);
                $stmt->execute();
            }
            foreach ($stages as $position => $stage) {
                $pos = $position + 1;
                $approver = json_encode($stage['approver']);
                $condition = isset($stage['condition']) ? json_encode($stage['condition']) : null;
                $stageSettings = json_encode($stage['settings']);
                $stmt = $mysqli->prepare("INSERT INTO `{$prefix}oa_stages` (version_id,name,stage_type,position,approver_type,approver_config_json,approval_rule,required_count,condition_json,settings_json,sla_minutes) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param('ississsissi', $versionId,$stage['name'],$stage['type'],$pos,$stage['approver_type'],$approver,$stage['rule'],$stage['required_count'],$condition,$stageSettings,$stage['sla_minutes']);
                $stmt->execute();
            }
        }
        $mysqli->query("UPDATE `{$prefix}oa_workflows` SET current_version_id={$versionId},status='active' WHERE id={$workflowId}");
    }

    $permissionKeys = ['operations_create_request','operations_view_own_requests','operations_view_department_requests','operations_approve','operations_reject','operations_return','operations_comment','operations_manage_delegation'];
    $roles = $mysqli->query("SELECT id,permissions FROM `{$prefix}roles` WHERE deleted=0");
    while ($role = $roles->fetch_assoc()) {
        $permissions = @unserialize($role['permissions']) ?: [];
        foreach ($permissionKeys as $key) $permissions[$key] = '1';
        $isManagement = (int) $role['id'] === 4;
        foreach (['operations_view_all_requests','operations_view_reports','operations_export'] as $key) $permissions[$key] = $isManagement ? '1' : '0';
        $serialized = serialize($permissions);
        $stmt = $mysqli->prepare("UPDATE `{$prefix}roles` SET permissions=? WHERE id=?");
        $stmt->bind_param('si', $serialized, $role['id']);
        $stmt->execute();
    }
    $mysqli->commit();
    echo 'Configured ' . count($workflows) . " published workflows.\n";
} catch (Throwable $e) {
    $mysqli->rollback();
    throw $e;
}
