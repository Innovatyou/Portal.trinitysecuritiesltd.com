<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "gemini";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="page-title clearfix">
                    <h4>Gemini</h4>
                </div>

                <div class="card no-border clearfix mb0">
                    <?php echo form_open(get_uri("settings/save_gemini_settings"), array("id" => "gemini-form", "class" => "general-form dashed-row", "role" => "form")); ?>

                    <div class="card-body">
                        <div class="form-group form-switch">
                            <div class="row">
                                <label for="enable_gemini" class="col-md-2 col-xs-8 col-sm-4"><?php echo app_lang('enable_gemini'); ?></label>
                                <div class="col-md-10 col-xs-4 col-sm-8">
                                    <?php
                                    echo form_checkbox("enable_gemini", "1", get_setting("enable_gemini") ? true : false, "id='enable_gemini' class='form-check-input ml15'");
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="gemini-show-hide-area <?php echo get_setting("enable_gemini") ? "" : "hide" ?>">
                            <div class="form-group">
                                <div class="row">
                                    <label class="col-md-12">
                                        <?php echo app_lang("get_your_api_key_from_here") . " " . anchor("https://aistudio.google.com/app/apikey", "Google AI Studio", array("target" => "_blank")); ?>
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="row">
                                    <label for="gemini_api_key" class="col-md-2"><?php echo app_lang('api_key'); ?></label>
                                    <div class="col-md-10">
                                        <?php
                                        echo form_input(array(
                                            "id" => "gemini_api_key",
                                            "name" => "gemini_api_key",
                                            "value" => get_setting('gemini_api_key') ? "******" : "",
                                            "class" => "form-control",
                                            "placeholder" => app_lang('api_key'),
                                            "data-rule-required" => true,
                                            "data-msg-required" => app_lang("field_required"),
                                            "autocomplete" => "off"
                                        ));
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="row">
                                    <label for="status" class="col-md-2"><?php echo app_lang('status'); ?></label>
                                    <div class="col-md-10">
                                        <?php if (get_setting("gemini_authorized")) { ?>
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
                        <button id="gemini-save-button" type="submit" class="btn btn-primary <?php echo get_setting("enable_gemini") ? "hide" : "" ?>">
                            <span data-feather="check-circle" class="icon-16"></span> 
                            <?php echo app_lang('save'); ?>
                        </button>
                        <button id="gemini-save-and-authorize-button" type="submit" class="btn btn-primary <?php echo get_setting("enable_gemini") ? "" : "hide" ?>">
                            <span data-feather="check-circle" class="icon-16"></span> 
                            <?php echo app_lang('save_and_authorize'); ?>
                        </button>
                    </div>
                    <?php echo form_close(); ?>
                </div>

                <script type="text/javascript">
                    $(document).ready(function() {
                        var $saveAndAuthorizeBtn = $("#gemini-save-and-authorize-button"),
                            $saveBtn = $("#gemini-save-button"),
                            $geminiDetailsArea = $(".gemini-show-hide-area");

                        // Toggle settings visibility based on enable_gemini checkbox
                        $("#enable_gemini").on("change", function() {
                            if ($(this).is(":checked")) {
                                $geminiDetailsArea.removeClass("hide");
                                $saveAndAuthorizeBtn.removeClass("hide");
                                $saveBtn.addClass("hide");
                            } else {
                                $geminiDetailsArea.addClass("hide");
                                $saveAndAuthorizeBtn.addClass("hide");
                                $saveBtn.removeClass("hide");
                            }
                        });

                        // Form submission
                        $("#gemini-form").appForm({
                            isModal: false,
                            onSuccess: function(result) {
                                if (result.success) {
                                    appAlert.success(result.message, { duration: 10000 });
                                    
                                    if ($saveBtn.hasClass("hide")) {
                                        // Redirect to authorization if saving with enable_gemini checked
                                        window.location.href = "<?php echo_uri('settings/authorize_gemini'); ?>";
                                    }
                                }
                            }
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</div>
