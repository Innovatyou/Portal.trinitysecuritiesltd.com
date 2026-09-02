<?php echo form_open(get_uri("ai_agents/import_files"), array("id" => "import-files-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="agent_id" value="<?php echo $agent_id; ?>" />

        <?php echo view("includes/multi_file_uploader", array(
            "validation_url" => get_uri("ai_agents/validate_import_files")
        )); ?>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default cancel-upload" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" disabled="disabled" class="btn btn-primary start-upload" id="file-save-button"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {

        $("#import-files-form").appForm({
            onSuccess: function(result) {

            }
        });

    });
</script>