<div class="card"><div class="card-header"><h4><?php echo app_lang('operations_sign_document'); ?></h4></div><div class="card-body">
<?php echo form_open(get_uri('operations/sign_attachment'), ['id' => 'operations-sign-form', 'class' => 'general-form']); ?>
<label for="oa-sign-attachment"><?php echo app_lang('attachments'); ?></label>
<select id="oa-sign-attachment" name="attachment_id" class="form-control mb-3">
<?php foreach ($pdfAttachments as $file) { ?><option value="<?php echo (int) $file->id; ?>" data-url="<?php echo esc(get_uri('operations/download/' . $file->id)); ?>"><?php echo esc($file->original_name); ?></option><?php } ?>
</select>
<p>Review the document, then draw your signature directly where it should appear. Changing the page or document clears your signature.</p>
<div class="d-flex align-items-center gap-2 mb-3">
<button type="button" id="oa-sign-prev" class="btn btn-default" disabled>Previous</button>
<span id="oa-sign-page-label" aria-live="polite"></span>
<button type="button" id="oa-sign-next" class="btn btn-default" disabled>Next</button>
<button type="button" id="oa-sign-clear" class="btn btn-default">Clear signature</button>
</div>
<p id="oa-sign-status" role="status">Loading document…</p>
<div style="max-width:900px; margin:auto; position:relative;" id="oa-sign-preview" hidden>
<canvas id="oa-sign-pdf" style="display:block; width:100%;"></canvas>
<canvas id="oa-sign-ink" aria-label="Draw your signature on the document" style="position:absolute; inset:0; width:100%; height:100%; touch-action:none; cursor:crosshair;"></canvas>
</div>
<input type="hidden" name="page" id="oa-sign-page">
<input type="hidden" name="signature" id="oa-sign-data" class="validate-hidden" data-rule-required="true" data-msg-required="<?php echo esc(app_lang('operations_signature_required')); ?>">
<?php foreach (['x', 'y', 'w', 'h'] as $coordinate) { ?><input type="hidden" name="<?php echo $coordinate; ?>" id="oa-sign-<?php echo $coordinate; ?>"><?php } ?>
<button type="submit" id="oa-sign-save" class="btn btn-primary mt-3" disabled><?php echo app_lang('operations_sign_document'); ?></button>
<?php echo form_close(); ?>
</div></div>
<script src="<?php echo base_url('assets/js/pdf-js/pdf.min.js'); ?>"></script>
<script src="<?php echo base_url('plugins/operations_approval/assets/sign_document.js'); ?>"></script>
<script>$(function () { initOperationsDocumentSigning(<?php echo json_encode(base_url('assets/js/pdf-js/pdf.worker.min.js')); ?>); });</script>
