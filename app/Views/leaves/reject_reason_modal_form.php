<?php echo form_open(get_uri("leaves/update_status"), array("id" => "reject-reason-form", "class" => "general-form", "role" => "form")); ?>
<input type="hidden" name="id" value="<?php echo $leave_id; ?>" />
<input type="hidden" name="status" value="rejected" />

<div class="modal-body clearfix">
    <div class="container-fluid">
        <div class="form-group">
            <label for="rejection_reason" class=" col-md-12"><?php echo app_lang('reason'); ?></label>
            <div class=" col-md-12">
                <?php
                echo form_textarea(array(
                    "id" => "rejection_reason",
                    "name" => "rejection_reason",
                    "class" => "form-control",
                    "placeholder" => app_lang('reason')
                ));
                ?>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        $("#reject-reason-form").appForm({
            onSuccess: function(result) {
                location.reload();
            }
        });

        setTimeout(function() {
            $("#rejection_reason").focus();
        }, 200);
    });
</script>