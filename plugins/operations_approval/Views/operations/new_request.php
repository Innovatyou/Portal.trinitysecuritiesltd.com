<?php echo view('operations_approval\Views\partials\styles'); ?><div class="oa-page"><div class="oa-hero"><div class="oa-hero-copy"><div class="oa-hero-eyebrow">START A PROCESS</div><h1><?php echo app_lang('operations_new_request'); ?></h1><p>Select the appropriate workflow and provide the information required for review.</p></div><div class="oa-hero-actions"><span class="oa-section-icon"><i data-feather="edit-3" class="icon-20"></i></span></div></div>
<div class="card"><div class="card-body"><div class="form-group"><label for="operations-workflow-selector"><?php echo app_lang('operations_select_workflow'); ?></label><select id="operations-workflow-selector" class="form-control select2"><option value=""></option><?php foreach ($workflows as $workflow) { ?><option value="<?php echo (int) $workflow->id; ?>"><?php echo esc($workflow->name); ?></option><?php } ?></select></div><div id="operations-request-form"></div></div></div>
</div><script>$(document).ready(function(){
    var $selector = $('#operations-workflow-selector');
    $selector.select2();
    $selector.on('change',function(){
        var id=$(this).val();
        // Keep the chosen workflow in the URL: Android Chrome can discard
        // this tab while the Files/Drive app is open for a PDF and reload it
        // on return, which would otherwise land on an empty selector.
        try { var url = new URL(window.location.href); if (id) { url.searchParams.set('workflow', id); } else { url.searchParams.delete('workflow'); } history.replaceState(null, '', url.toString()); } catch (e) {}
        if(!id){$('#operations-request-form').empty();return;}
        $('#operations-request-form').load('<?php echo get_uri('operations/form'); ?>/'+id);
    });
    var initialWorkflow = null;
    try { initialWorkflow = new URL(window.location.href).searchParams.get('workflow'); } catch (e) {}
    if (initialWorkflow && $selector.find('option[value="' + parseInt(initialWorkflow, 10) + '"]').length) {
        $selector.val(parseInt(initialWorkflow, 10)).trigger('change');
        if (document.wasDiscarded) {
            appAlert.warning(<?php echo json_encode(app_lang('operations_page_reloaded_by_browser')); ?>, {duration: 15000});
        }
    }
});</script>
