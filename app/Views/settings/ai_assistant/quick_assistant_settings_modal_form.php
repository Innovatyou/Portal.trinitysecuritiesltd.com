<?php echo form_open(get_uri("settings/save_quick_assistant_settings"), array("id" => "quick-assistant-settings-form", "class" => "general-form", "role" => "form")); ?>

<div class="modal-body clearfix">
    <div class="container-fluid">

        <br />
        <div class="form-group">
            <div class="row">
                <label for="quick_assistant_ai_agent" class=" col-md-3"><?php echo app_lang('ai_agent'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "quick_assistant_ai_agent",
                        "name" => "quick_assistant_ai_agent",
                        "value" => get_setting("quick_assistant_ai_agent"),
                        "class" => "form-control validate-hidden",
                        "placeholder" => app_lang('ai_agent'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>

            <div class="mt20 mb20 b-b">
                <p class="strong"><?php echo app_lang('system_prompt'); ?></p>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="quick_assistant_ai_agent" class=" col-md-3"><?php echo app_lang('ai_summarize'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_textarea(array(
                            "id" => "ai_system_prompt_for_summarize",
                            "name" => "ai_system_prompt_for_summarize",
                            "value" => get_setting("ai_system_prompt_for_summarize"),
                            "class" => "form-control",
                        ));
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <div class="row">
                    <label for="quick_assistant_ai_agent" class=" col-md-3"><?php echo app_lang('ai_improve'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_textarea(array(
                            "id" => "ai_system_prompt_for_improve",
                            "name" => "ai_system_prompt_for_improve",
                            "value" => get_setting("ai_system_prompt_for_improve"),
                            "class" => "form-control",
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="quick_assistant_ai_agent" class=" col-md-3"><?php echo app_lang('ai_describe'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_textarea(array(
                            "id" => "ai_system_prompt_for_describe",
                            "name" => "ai_system_prompt_for_describe",
                            "value" => get_setting("ai_system_prompt_for_describe"),
                            "class" => "form-control",
                        ));
                        ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="row">
                    <label for="quick_assistant_ai_agent" class=" col-md-3"><?php echo app_lang('ai_key_points'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_textarea(array(
                            "id" => "ai_system_prompt_for_key_points",
                            "name" => "ai_system_prompt_for_key_points",
                            "value" => get_setting("ai_system_prompt_for_key_points"),
                            "class" => "form-control",
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
            $("#quick-assistant-settings-form").appForm({
                onSuccess: function(result) {
                    location.reload();
                }
            });

            $("#quick_assistant_ai_agent").appDropdown({
                list_data: <?php echo $agents_dropdown; ?>
            })
        });
    </script>