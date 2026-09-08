<?php echo form_open(get_uri('operations/create'), ['id' => 'operations-create-form', 'class' => 'general-form', 'role' => 'form']); ?>
<input type="hidden" name="workflow_id" value="<?php echo (int) $workflow->id; ?>">
<div class="form-group"><label for="oa-title"><?php echo app_lang('title'); ?></label><?php echo form_input(['id' => 'oa-title', 'name' => 'title', 'class' => 'form-control', 'required' => true]); ?></div>
<div class="form-group"><label for="oa-priority"><?php echo app_lang('operations_priority'); ?></label><?php echo form_dropdown('priority', ['low' => app_lang('operations_priority_low'), 'normal' => app_lang('operations_priority_medium'), 'high' => app_lang('operations_priority_high')], 'normal', "id='oa-priority' class='form-control'"); ?></div>
<?php foreach ($fields as $field) { $config = json_decode($field->config_json ?: '{}', true); ?>
<div class="form-group"><label for="field-<?php echo esc($field->field_key); ?>"><?php echo esc($field->label); ?><?php if ($field->is_required) echo ' *'; ?></label>
<?php if ($field->field_type === 'textarea') echo form_textarea(['id' => 'field-' . $field->field_key, 'name' => 'field_' . $field->field_key, 'class' => 'form-control', 'required' => (bool) $field->is_required]);
elseif ($field->field_type === 'richtext') {
    // A requester who'd otherwise have to compose a Word doc separately
    // and upload it can just type it here - reuses RISE's own built-in
    // rich text editor (same data-rich-text-editor mechanism used
    // everywhere else in the app), so it degrades to a plain textarea if
    // the site-wide rich text editor setting is off.
    echo form_textarea(['id' => 'field-' . $field->field_key, 'name' => 'field_' . $field->field_key, 'class' => 'form-control oa-richtext-field', 'required' => (bool) $field->is_required, 'data-rich-text-editor' => true, 'data-keep-rich-text-editor-after-submit' => true]);
} elseif ($field->field_type === 'spreadsheet') {
    // A lightweight, dependency-free grid for requesters who'd otherwise
    // have to build a small workbook in Excel and upload it - every cell
    // edit re-serializes the grid into the hidden field_<key> input that
    // actually gets submitted, matching every other field's plain
    // name="field_<key>" convention.
    $grid = [['', '', ''], ['', '', ''], ['', '', '']];
    // required (a hidden input can't enforce HTML5 required client-side)
    // is checked server-side already, same as every other field, in
    // Operations::create()'s is_required loop.
    ?><div class="oa-spreadsheet" data-field-key="<?php echo esc($field->field_key); ?>"><div class="table-responsive"><table class="table table-bordered table-sm oa-spreadsheet-table mb-2"><tbody><?php foreach ($grid as $row) { ?><tr><?php foreach ($row as $cell) { ?><td><input type="text" class="form-control form-control-sm oa-cell" value="<?php echo esc($cell); ?>"></td><?php } ?></tr><?php } ?></tbody></table></div><button type="button" class="btn btn-outline-secondary btn-sm oa-add-row"><i data-feather="plus" class="icon-14"></i> <?php echo app_lang('operations_add_row'); ?></button> <button type="button" class="btn btn-outline-secondary btn-sm oa-add-column"><i data-feather="plus" class="icon-14"></i> <?php echo app_lang('operations_add_column'); ?></button><input type="hidden" name="field_<?php echo esc($field->field_key); ?>" class="oa-spreadsheet-data" value="<?php echo esc(json_encode($grid)); ?>"></div><?php
} elseif (in_array($field->field_type, ['dropdown','radio'], true)) echo form_dropdown('field_' . $field->field_key, array_combine($config['options'] ?? [], $config['options'] ?? []), '', "id='field-{$field->field_key}' class='form-control'" . ($field->is_required ? ' required' : ''));
else echo form_input(['id' => 'field-' . $field->field_key, 'name' => 'field_' . $field->field_key, 'type' => in_array($field->field_type, ['date','email','number','url'], true) ? $field->field_type : 'text', 'class' => 'form-control', 'required' => (bool) $field->is_required]); ?>
<?php if (!empty($config['help'])) { ?><small class="text-muted"><?php echo esc($config['help']); ?></small><?php } ?></div><?php } ?>
<div class="form-group"><label><?php echo app_lang('attachments'); ?></label><input type="hidden" name="context" value="request"><?php echo view('includes/multi_file_uploader', ['hide_description' => true, 'max_files' => 10]); ?></div>
<button type="submit" name="save_draft" value="1" class="btn btn-default mr10"><?php echo app_lang('operations_save_as_draft'); ?></button><button type="submit" name="submit_request" value="1" class="btn btn-primary"><?php echo app_lang('operations_submit_request'); ?></button>
<?php echo form_close(); ?>
<script>$(document).ready(function(){ $('#operations-create-form').appForm({isModal:false, onSuccess: oaFormFeedback}); if(window.initOnDemandWYSIWYGEditor){$('.oa-richtext-field').each(function(){initOnDemandWYSIWYGEditor($(this));});} if(window.feather){feather.replace();} });</script>
