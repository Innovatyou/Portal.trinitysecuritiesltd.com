<?php echo form_open(get_uri("ai_agents/save_text_prompt"), array("id" => "import-text-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="agent_id" value="<?php echo $agent_id; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="prompt" class=" col-md-12"><?php echo app_lang('prompt'); ?></label>
                <div class=" col-md-12">
                    <?php
                    echo form_input(array(
                        "id" => "prompt",
                        "name" => "prompt",
                        "class" => "form-control",
                        "placeholder" => app_lang('prompt'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="response" class=" col-md-12"><?php echo app_lang('response'); ?></label>
                <div class=" col-md-12">
                    <?php
                    echo form_textarea(array(
                        "id" => "response",
                        "name" => "response",
                        "class" => "form-control",
                        "placeholder" => app_lang('response'),
                        "value" => "",
                        "data-height" => 300
                    ));
                    ?>
                </div>
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
        $("#import-text-form").appForm({
            onSuccess: function(result) {
                if (result.success) {} else {
                    appAlert.error(result.message);
                }
            }
        });
    });
</script>