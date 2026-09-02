<?php echo view('operations_approval\Views\partials\styles'); ?>
<?php
$statusClass = static function ($status) {
    if (in_array($status, ['completed', 'approved'], true)) return 'success';
    if ($status === 'rejected') return 'danger';
    if (in_array($status, ['returned', 'cancelled'], true)) return 'warning';
    return 'info';
};
$kpiIcons = ['total' => 'layers', 'pending' => 'clock', 'approved' => 'check-circle', 'rejected' => 'x-circle', 'returned' => 'corner-up-left', 'my_pending' => 'user-check'];
?>
<div class="oa-page">
<section class="oa-hero"><div class="oa-hero-copy"><div class="oa-hero-eyebrow">WORKFLOW CONTROL CENTRE</div><h1><?php echo app_lang('operations_dashboard'); ?></h1><p>Track requests, approvals and decisions from one secure workspace.</p></div><div class="oa-hero-actions"><?php echo anchor(get_uri('operations/new_request'), '<i data-feather="plus-circle" class="icon-16"></i> ' . app_lang('operations_new_request'), ['class' => 'btn btn-primary']); ?></div></section>
<div class="oa-kpi-grid"><?php foreach ($kpis as $key => $value) { ?><article class="oa-kpi"><div class="oa-kpi-icon"><i data-feather="<?php echo esc($kpiIcons[$key] ?? 'clipboard'); ?>" class="icon-20"></i></div><div class="oa-kpi-value"><?php echo (int) $value; ?></div><div class="oa-kpi-label"><?php echo app_lang('operations_kpi_' . $key); ?></div></article><?php } ?></div>
<section class="card"><div class="card-header oa-section-title"><div class="d-flex align-items-center gap-2"><span class="oa-section-icon"><i data-feather="activity" class="icon-18"></i></span><h4><?php echo app_lang('operations_recent_requests'); ?></h4></div><?php echo anchor(get_uri('operations/my_requests'), 'View all <i data-feather="arrow-right" class="icon-14"></i>', ['class' => 'btn btn-default btn-sm']); ?></div><div class="table-responsive"><table class="table"><thead><tr><th><?php echo app_lang('operations_request_number'); ?></th><th><?php echo app_lang('title'); ?></th><th><?php echo app_lang('operations_workflow'); ?></th><th><?php echo app_lang('status'); ?></th><th><?php echo app_lang('created_date'); ?></th></tr></thead><tbody>
<?php foreach ($recent as $row) { ?><tr><td><strong><?php echo anchor(get_uri('operations/view/' . $row->id), esc($row->request_no ?: app_lang('draft'))); ?></strong></td><td><?php echo esc($row->title); ?></td><td><?php echo esc($row->workflow_name); ?></td><td><span class="oa-status oa-status-<?php echo $statusClass($row->status); ?>"><?php echo esc(ucwords(str_replace('_', ' ', $row->status))); ?></span></td><td><?php echo format_to_datetime($row->created_at); ?></td></tr><?php } ?>
<?php if (!$recent) { ?><tr><td colspan="5" class="oa-empty"><i data-feather="inbox" class="icon-32"></i>No workflow requests yet. Create the first request to get started.</td></tr><?php } ?>
</tbody></table></div></section></div>
