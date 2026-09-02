<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "ai_assistant_general";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="page-title clearfix">
                    <h4><?php echo app_lang('general_settings'); ?></h4>
                </div>

                <div class="card no-border clearfix mb0">
                    <?php echo form_open(get_uri("settings/save_ai_assistant_general_settings"), array("id" => "ai-assistant-general-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>

                    <div class="card-body mt10">

                        <?php if (get_setting("enable_chatgpt") || get_setting("enable_gemini")) { ?>

                            <div class="row">
                                <div class="col-md-6 col-sm-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="clearfix form-switch">
                                                <label for="quick_assistant" class="block float-start"><?php echo app_lang("quick_assistant") ?></label>
                                                <div class="float-end ml10 <?php echo get_setting("quick_assistant_ai_agent") ? "" : "hide" ?>">
                                                    <?php echo modal_anchor(get_uri("settings/quick_assistant_settings_modal_form"), "<i data-feather='settings' class='icon-16'></i>", array("title" => app_lang('quick_assistant_settings'))); ?>
                                                </div>
                                                <?php echo form_checkbox("quick_assistant", "1", get_setting("quick_assistant_ai_agent") ? true : false, "id='quick_assistant' class='form-check-input float-end ai-assistant-settings-checkbox'") ?>

                                            </div>
                                            <div class="text-off mt5">
                                                <?php echo app_lang("quick_assistant_instructions") ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="clearfix form-switch">
                                                <label for="ticket_assistant" class="block float-start"><?php echo app_lang("quick_assistant") . " (" . app_lang("tickets") . ")" ?></label>
                                                <div class="float-end ml10 <?php echo get_setting("ticket_assistant_ai_agents") ? "" : "hide" ?>">
                                                    <?php echo modal_anchor(get_uri("settings/ticket_assistant_settings_modal_form"), "<i data-feather='settings' class='icon-16'></i>", array("title" => app_lang('ticket_assistant_settings'))); ?>
                                                </div>
                                                <?php echo form_checkbox("ticket_assistant", "1", get_setting("ticket_assistant_ai_agents") ? true : false, "id='ticket_assistant' class='form-check-input float-end ai-assistant-settings-checkbox'") ?>
                                            </div>
                                            <div class="text-off mt5">
                                                <?php echo app_lang("ticket_assistant_instructions") ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 col-sm-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="clearfix form-switch">
                                                <label for="ai_chatbox" class="block float-start"><?php echo app_lang("ai_chatbox") ?></label>
                                                <div class="float-end ml10 <?php echo get_setting("ai_chatbox_agents") ? "" : "hide" ?>">
                                                    <?php echo modal_anchor(get_uri("settings/ai_chatbox_settings_modal_form"), "<i data-feather='settings' class='icon-16'></i>", array("title" => app_lang('ai_chatbox_settings'))); ?>
                                                </div>
                                                <?php echo form_checkbox("ai_chatbox", "1", get_setting("ai_chatbox_agents") ? true : false, "id='ai_chatbox' class='form-check-input float-end ai-assistant-settings-checkbox'") ?>
                                            </div>
                                            <div class="text-off mt5">
                                                <?php echo app_lang("ai_chatbox_instructions") ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php } else { ?>
                            <i data-feather='alert-triangle' class="icon-16 text-danger"></i>
                            <?php echo app_lang("no_ai_integration_error_message"); ?>
                        <?php } ?>

                    </div>
                    <div class="card-footer hide" id="ai-assistant-general-settings-form-footer">
                        <button type="submit" class="btn btn-primary"><span data-feather='check-circle' class="icon-16"></span> <?php echo app_lang('save'); ?></button>
                    </div>

                    <?php echo form_close(); ?>
                </div>


                <script type="text/javascript">
                    $(document).ready(function() {

                        $(".ai-assistant-settings-checkbox").on("change", function() {
                            var checked = $(this).is(":checked");

                            if (checked) {
                                $(this).closest(".card-body").find("[data-act='ajax-modal']").trigger("click");
                            } else {
                                $("#ai-assistant-general-settings-form-footer").removeClass("hide");
                            }
                        });

                        $("#ai-assistant-general-settings-form").appForm({
                            isModal: false,
                            onSuccess: function(result) {
                                if (result.success) {
                                    appAlert.success(result.message, {
                                        duration: 10000
                                    });
                                } else {
                                    appAlert.error(result.message);
                                }
                            }
                        });

                    });
                </script>

            </div>
        </div>
    </div>
</div>