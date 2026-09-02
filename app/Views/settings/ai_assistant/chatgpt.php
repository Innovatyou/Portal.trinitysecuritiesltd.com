<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "chatgpt";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="page-title clearfix">
                    <h4>ChatGPT</h4>
                </div>

                <div class="card no-border clearfix mb0">
                    <?php echo form_open(get_uri("settings/save_chatgpt_settings"), array("id" => "chatgpt-form", "class" => "general-form dashed-row", "role" => "form")); ?>

                    <div class="card-body">
                        <div class="form-group form-switch">
                            <div class="row">
                                <label for="enable_chatgpt" class="col-md-2 col-xs-8 col-sm-4"><?php echo app_lang('enable_chatgpt'); ?></label>
                                <div class="col-md-10 col-xs-4 col-sm-8">
                                    <?php
                                    echo form_checkbox("enable_chatgpt", "1", get_setting("enable_chatgpt") ? true : false, "id='enable_chatgpt' class='form-check-input ml15'");
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="chatgpt-show-hide-area <?php echo get_setting("enable_chatgpt") ? "" : "hide" ?>">

                            <div class="form-group">
                                <div class="row">
                                    <label class=" col-md-12">
                                        <?php echo app_lang("get_your_api_key_from_here") . " " . anchor("https://platform.openai.com/api-keys", "OpenAI API", array("target" => "_blank")); ?>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="row">
                                    <label for="chatgpt_api_key" class=" col-md-2"><?php echo app_lang('api_key'); ?></label>
                                    <div class=" col-md-10">
                                        <?php
                                        echo form_input(array(
                                            "id" => "chatgpt_api_key",
                                            "name" => "chatgpt_api_key",
                                            "value" => get_setting('chatgpt_api_key') ? "******" : "",
                                            "class" => "form-control",
                                            "placeholder" => app_lang('api_key'),
                                            "data-rule-required" => true,
                                            "data-msg-required" => app_lang("field_required"),
                                        ));
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="row">
                                    <label for="status" class=" col-md-2"><?php echo app_lang('status'); ?></label>
                                    <div class=" col-md-10">
                                        <?php if (get_setting("chatgpt_authorized")) { ?>
                                            <span class="ml5 badge bg-success"><?php echo app_lang("authorized"); ?></span>
                                        <?php } else { ?>
                                            <span class="ml5 badge" style="background:#F9A52D;"><?php echo app_lang("unauthorized"); ?></span>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                    <div class="card-footer">
                        <button id="chatgpt-save-button" type="submit" class="btn btn-primary <?php echo get_setting("enable_chatgpt") ? "hide" : "" ?>"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
                        <button id="chatgpt-save-and-authorize-button" type="submit" class="btn btn-primary <?php echo get_setting("enable_chatgpt") ? "" : "hide" ?>"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save_and_authorize'); ?></button>
                    </div>
                    <?php echo form_close(); ?>
                </div>

                <script type="text/javascript">
                    $(document).ready(function() {
                        var $saveAndAuthorizeBtn = $("#chatgpt-save-and-authorize-button"),
                            $saveBtn = $("#chatgpt-save-button"),
                            $chatgptDetailsArea = $(".chatgpt-show-hide-area");

                        $("#chatgpt-form").appForm({
                            isModal: false,
                            onSuccess: function(result) {
                                if (result.success) {
                                    appAlert.success(result.message, {
                                        duration: 10000
                                    });

                                    if ($saveBtn.hasClass("hide")) {
                                        window.location.href = "<?php echo_uri('settings/authorize_chatgpt'); ?>";
                                    }
                                } else {
                                    appAlert.error(result.message);
                                }
                            }
                        });

                        //show/hide chatgpt details area
                        $("#enable_chatgpt").click(function() {
                            if ($(this).is(":checked")) {
                                $saveAndAuthorizeBtn.removeClass("hide");
                                $chatgptDetailsArea.removeClass("hide");
                                $saveBtn.addClass("hide");
                            } else {
                                $saveAndAuthorizeBtn.addClass("hide");
                                $chatgptDetailsArea.addClass("hide");
                                $saveBtn.removeClass("hide");
                            }
                        });

                    });
                </script>

            </div>
        </div>
    </div>
</div>