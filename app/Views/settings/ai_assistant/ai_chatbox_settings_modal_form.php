<?php echo form_open(get_uri("settings/save_ai_chatbox_settings"), array("id" => "ai-chatbox-settings-form", "class" => "general-form", "role" => "form")); ?>

<div class="modal-body clearfix">
    <div class="container-fluid">

        <br />
        <div class="form-group">
            <div class="row">
                <label for="ai_chatbox_agents" class=" col-md-3"><?php echo app_lang('ai_agents'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "ai_chatbox_agents",
                        "name" => "ai_chatbox_agents",
                        "value" => get_setting("ai_chatbox_agents"),
                        "class" => "form-control validate-hidden",
                        "placeholder" => app_lang('ai_agents'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>
        <br />

    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>

<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        $("#ai-chatbox-settings-form").appForm({
            onSuccess: function(result) {
                location.reload();
            }
        });

        $("#ai_chatbox_agents").appDropdown({
            list_data: <?php echo $agents_dropdown; ?>,
            multiple: true,
        })

    });
</script>