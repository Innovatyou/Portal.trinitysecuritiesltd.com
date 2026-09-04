<?php echo form_open(get_uri('operations/create'), ['id' => 'operations-create-form', 'class' => 'general-form', 'role' => 'form']); ?>
<input type="hidden" name="workflow_id" value="<?php echo (int) $workflow->id; ?>">
<div class="form-group"><label for="oa-title"><?php echo app_lang('title'); ?></label><?php echo form_input(['id' => 'oa-title', 'name' => 'title', 'class' => 'form-control', 'required' => true]); ?></div>
<div class="form-group"><label for="oa-priority"><?php echo app_lang('operations_priority'); ?></label><?php echo form_dropdown('priority', ['low' => app_lang('operations_priority_low'), 'normal' => app_lang('operations_priority_medium'), 'high' => app_lang('operations_priority_high')], 'normal', "id='oa-priority' class='form-control'"); ?></div>
<?php foreach ($fields as $field) { $config = json_decode($field->config_json ?: '{}', true); ?>
<div class="form-group"><label for="field-<?php echo esc($field->field_key); ?>"><?php echo esc($field->label); ?><?php if ($field->is_required) echo ' *'; ?></label>
<?php if ($field->field_type === 'textarea') echo form_textarea(['id' => 'field-' . $field->field_key, 'name' => 'field_' . $field->field_key, 'class' => 'form-control', 'required' => (bool) $field->is_required]);
elseif (in_array($field->field_type, ['dropdown','radio'], true)) echo form_dropdown('field_' . $field->field_key, array_combine($config['options'] ?? [], $config['options'] ?? []), '', "id='field-{$field->field_key}' class='form-control'" . ($field->is_required ? ' required' : ''));
else echo form_input(['id' => 'field-' . $field->field_key, 'name' => 'field_' . $field->field_key, 'type' => in_array($field->field_type, ['date','email','number','url'], true) ? $field->field_type : 'text', 'class' => 'form-control', 'required' => (bool) $field->is_required]); ?>
<?php if (!empty($config['help'])) { ?><small class="text-muted"><?php echo esc($config['help']); ?></small><?php } ?></div><?php } ?>
<div class="form-group"><label><?php echo app_lang('attachments'); ?></label><input type="hidden" name="context" value="request"><?php echo view('includes/multi_file_uploader', ['hide_description' => true, 'max_files' => 10]); ?></div>
<button type="submit" name="save_draft" value="1" class="btn btn-default mr10"><?php echo app_lang('operations_save_as_draft'); ?></button><button type="submit" name="submit_request" value="1" class="btn btn-primary"><?php echo app_lang('operations_submit_request'); ?></button>
<?php echo form_close(); ?>
<script>$(document).ready(function(){ $('#operations-create-form').appForm({isModal:false, onSuccess: oaFormFeedback}); });</script>
