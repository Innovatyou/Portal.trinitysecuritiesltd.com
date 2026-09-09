<?php
// Keep user content as text: PDF rendering must not load embedded URLs or files.
$text = static fn($value) => nl2br(esc((string) ($value ?? '')));
$label = static fn($value) => ucwords(str_replace('_', ' ', (string) $value));
?>
<h2><?php echo esc(app_lang('operations_request_report')); ?></h2>
<h3><?php echo $text($request->request_no ?: '#' . $request->id); ?>: <?php echo $text($request->title); ?></h3>
<table cellpadding="5" border="1">
<?php foreach ([
    app_lang('operations_workflow') => $request->workflow_name,
    app_lang('requested_by') => $request->requester_name,
    app_lang('status') => $label($request->status),
    app_lang('priority') => $label($request->priority),
    'Revision' => $request->revision_no,
    'Created (UTC)' => $request->created_at,
    'Submitted (UTC)' => $request->submitted_at,
    'Completed (UTC)' => $request->completed_at,
] as $name => $value) { ?>
<tr><td width="30%"><b><?php echo esc($name); ?></b></td><td width="70%"><?php echo $text($value); ?></td></tr>
<?php } ?>
</table>
<h4><?php echo esc(app_lang('operations_request_details')); ?></h4>
<?php foreach ($values as $value) {
    $content = $value->value_text ?? $value->value_json ?? '';
?>
<p><b><?php echo esc($label($value->field_key)); ?></b></p>
<?php if (($value->field_type ?? '') === 'spreadsheet') {
    $grid = json_decode($content, true);
    if (is_array($grid) && $grid) { ?>
<table border="1" cellpadding="4">
<?php foreach ($grid as $row) { ?><tr><?php foreach ((array) $row as $cell) { ?><td><?php echo $text(is_scalar($cell) || $cell === null ? $cell : json_encode($cell)); ?></td><?php } ?></tr><?php } ?>
</table>
<?php } else { ?><p><?php echo $text($content); ?></p><?php }
} elseif (($value->field_type ?? '') === 'richtext') {
    $content = preg_replace('/<\s*(br\b[^>]*|\/(?:p|div|li|tr|h[1-6])\s*)>/i', "\n", $content);
?><p><?php echo $text(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?></p>
<?php } else { ?><p><?php echo $text($content); ?></p><?php } ?>
<?php } ?>
<?php foreach ($customFields as $field) { ?><p><b><?php echo $text($field->label); ?></b><br><?php echo $text($field->value); ?></p><?php } ?>
<h4><?php echo esc(app_lang('operations_approval_timeline')); ?></h4>
<?php foreach ($timeline as $item) { ?>
<p><b><?php echo $text($item->name_snapshot); ?></b> — <?php echo $text($label($item->status)); ?>
<?php if ($item->actor_name_snapshot) { ?><br><?php echo $text($item->actor_name_snapshot); ?> — <?php echo $text($label($item->decision)); ?> — <?php echo $text($item->decision_at); ?> UTC<br><?php echo $text($item->comment); ?><?php } ?></p>
<?php } ?>
<h4><?php echo esc(app_lang('comments')); ?></h4>
<?php foreach ($comments as $comment) { ?><p><b><?php echo $text($comment->user_name_snapshot); ?></b> — <?php echo $text($comment->created_at); ?> UTC<br><?php echo $text($comment->comment); ?></p><?php } ?>
<?php if ($conversations) { ?><h4><?php echo esc(app_lang('operations_information_request')); ?></h4>
<?php foreach ($conversations as $conversation) { ?><p><b><?php echo $text($conversation->question); ?></b><br><?php echo $text($conversation->response); ?><br><?php echo $text($label($conversation->status)); ?> — <?php echo $text($conversation->opened_at); ?> UTC</p><?php } ?>
<?php } ?>
<h4><?php echo esc(app_lang('attachments')); ?></h4>
<?php foreach ($attachments as $file) { ?><p><?php echo $text($file->original_name); ?> (<?php echo round($file->size_bytes / 1024, 1); ?> KB)</p><?php } ?>
