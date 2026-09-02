<?php echo view('includes/intl_tel_input_js'); ?>
<?php echo form_open(get_uri("settings/save_footer_settings"), array("id" => "footer-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>

<div class=" p15">
    <input type="hidden" id="footer-menus-data" name="footer_menus" value="" />
    <input type="hidden" id="footer-social-links-data" name="footer_social_links" value="" />
    <input type="hidden" id="footer-quick-links-data" name="footer_quick_links" value="" />

    <div class="form-group">
        <div class="row">
            <div class="col-md-12">
                <i data-feather="info" class="icon-16"></i> <?php echo app_lang("footer_description_message"); ?>
            </div>
        </div>
    </div>

    <div class="form-group form-switch remove-border">
        <div class="row">
            <label for="enable_footer" class=" col-md-2"><?php echo app_lang('enable_footer'); ?></label>
            <div class="col-md-10">
                <?php
                echo form_checkbox("enable_footer", "1", get_setting("enable_footer") ? true : false, "id='enable_footer' class='form-check-input ml15'");
                ?>
            </div>
        </div>
    </div>

    <div id="footer-details-area" class="<?php echo get_setting("enable_footer") ? "" : "hide"; ?>">
        <div class="card">
            <div class="card-body">
                <div class="form-group">
                    <div class="row">
                        <label for="footer_copyright_text" class="col-md-2"><?php echo app_lang('footer_copyright_text'); ?></label>
                        <div class="col-md-10">
                            <?php
                            echo form_input(array(
                                "id" => "footer_copyright_text",
                                "name" => "footer_copyright_text",
                                "value" => get_setting("footer_copyright_text"),
                                "class" => "form-control",
                                "placeholder" => app_lang('footer_copyright_text')
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group" id="footer-menu-input-area">
                    <div class="row">
                        <label for="footer_menus" class=" col-md-2"><?php echo app_lang('footer_menus'); ?></label>
                        <div class="col-md-10">
                            <div id="footer-menus-show-area">
                                <?php echo $footer_menus; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <?php
                                    echo form_input(array(
                                        "id" => "menu_name",
                                        "name" => "menu_name",
                                        "class" => "form-control",
                                        "placeholder" => app_lang('menu_name')
                                    ));
                                    ?>
                                </div>
                                <div class="col-md-6">
                                    <?php
                                    echo form_input(array(
                                        "id" => "url",
                                        "name" => "url",
                                        "class" => "form-control",
                                        "placeholder" => "URL"
                                    ));
                                    ?>
                                </div>
                                <div id="footer-menus-options-area" class="col-md-12 mt15 hide">
                                    <button id="footer-menus-add-button" type="button" class="btn btn-primary mr10"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('add'); ?></button>
                                    <button id="footer-menus-close-button" type="button" class="btn btn-default"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('cancel'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Brand Profile Section -->
        <div class="card">
            <div class="card-header">
                <div class="form-switch">
                    <div class="row">
                        <label for="footer_brand_profile" class=" col-md-2"><?php echo app_lang('brand_profile'); ?></label>
                        <div class="col-md-10">
                            <?php
                            echo form_checkbox("footer_brand_profile", "1", get_setting("footer_brand_profile") ? true : false, "id='footer_brand_profile' class='form-check-input ml15'");
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="brand_profile_area" class="<?php echo get_setting("footer_brand_profile") ? "" : "hide"; ?>">
                <div class="card-body">
                    <div class="form-group">
                        <div class="row">
                            <label for="footer_about_section" class="col-md-2"><?php echo app_lang('about'); ?></label>
                            <div class="col-md-10">
                                <?php
                                echo form_textarea(array(
                                    "id" => "footer_about_section",
                                    "name" => "footer_about",
                                    "value" => get_setting("footer_about"),
                                    "class" => "form-control",
                                    "placeholder" => app_lang('about')
                                ));
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group" id="footer-social-links-input-area">
                        <div class="row">
                            <label for="footer_social_links" class="col-md-2"><?php echo app_lang('social_links'); ?></label>

                            <div class="col-md-10">
                                <div id="footer-social-links-show-area">
                                    <?php echo $footer_social_links; ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <?php
                                        echo form_dropdown(
                                            "social_link_type",
                                            array(
                                                "" => "- " . app_lang('social_link') . " -",
                                                "facebook" => "Facebook",
                                                "twitter" => "Twitter",
                                                "linkedin" => "LinkedIn",
                                                "whatsapp" => "WhatsApp",
                                                "youtube" => "YouTube",
                                                "pinterest" => "Pinterest",
                                                "instagram" => "Instagram",
                                                "github" => "GitHub",
                                                "digg" => "Digg",
                                                "tumblr" => "Tumblr",
                                                "vine" => "Vine",
                                                "custom" => app_lang('custom')
                                            ),
                                            "",
                                            "id='footer_social_link_type' class='select2'"
                                        );
                                        ?>
                                    </div>

                                    <div class="col-md-8">
                                        <?php
                                        echo form_input(array(
                                            "id" => "social_link",
                                            "name" => "social_link",
                                            "class" => "form-control",
                                            "placeholder" => app_lang('url')
                                        ));
                                        ?>
                                    </div>

                                    <div class="col-md-12">
                                        <div id="custom-social-link-fields" class="row hide mt15">
                                            <div class="col-md-4">
                                                <?php
                                                echo form_input(array(
                                                    "id" => "custom_social_title",
                                                    "name" => "custom_social_title",
                                                    "class" => "form-control",
                                                    "placeholder" => app_lang('title')
                                                ));
                                                ?>
                                            </div>

                                            <div class="col-md-8">
                                                <div class="float-start mr15 hide">
                                                    <img id="icon-preview" src="" alt="..." style="width: 32px" />
                                                </div>
                                                <div class="float-start">
                                                    <?php
                                                    echo form_upload(array(
                                                        "id" => "icon_file_upload",
                                                        "name" => "icon_file",
                                                        "class" => "no-outline hidden-input-file"
                                                    ));
                                                    ?>
                                                    <label for="icon_file_upload" class="btn btn-default">
                                                        <i data-feather="upload" class="icon-14"></i> <?php echo app_lang("upload_icon"); ?>
                                                    </label>
                                                </div>
                                                <input type="hidden" id="icon" name="icon" value="" />
                                            </div>
                                        </div>

                                        <div id="footer-social-links-options-area" class="col-md-12 mt15 hide">
                                            <button id="footer-social-links-add-button" type="button" class="btn btn-primary mr10"><span data-feather="check-circle" class="icon-16"></span><?php echo app_lang('add'); ?></button>
                                            <button id="footer-social-links-close-button" type="button" class="btn btn-default"><span data-feather="x" class="icon-16"></span><?php echo app_lang('cancel'); ?></button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links Section -->
        <div class="card">
            <div class="card-header">
                <div class="form-switch">
                    <div class="row">
                        <label for="footer_quick_links_enabled" class=" col-md-2"><?php echo app_lang('quick_links'); ?></label>
                        <div class="col-md-10">
                            <?php
                            echo form_checkbox("footer_quick_links_enabled", "1", get_setting("footer_quick_links_enabled") ? true : false, "id='footer_quick_links_enabled' class='form-check-input ml15'");
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div id="quick_links_area" class="<?php echo get_setting("footer_quick_links_enabled") ? "" : "hide"; ?> form-group">
                <div class="card-body">
                    <div class="row">
                        <label for="quick_links" class=" col-md-2"><?php echo app_lang('links'); ?></label>
                        <div class="col-md-10">
                            <div id="footer-quick-links-show-area">
                                <?php echo $footer_quick_links; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <?php
                                    echo form_input(array(
                                        "id" => "quick_link_name",
                                        "name" => "quick_link_name",
                                        "class" => "form-control",
                                        "placeholder" => app_lang('quick_link_name')
                                    ));
                                    ?>
                                </div>
                                <div class="col-md-6">
                                    <?php
                                    echo form_input(array(
                                        "id" => "quick_link_url",
                                        "name" => "quick_link_url",
                                        "class" => "form-control",
                                        "placeholder" => app_lang('url')
                                    ));
                                    ?>
                                </div>
                                <div id="footer-quick-links-options-area" class="col-md-12 mt15 hide">
                                    <button id="footer-quick-links-add-button" type="button" class="btn btn-primary mr10"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('add'); ?></button>
                                    <button id="footer-quick-links-close-button" type="button" class="btn btn-default"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('cancel'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Column Section -->
        <div class="card">
            <div class="card-header">
                <div class="form-switch">
                    <div class="row">
                        <label for="footer_custom_column" class=" col-md-2"><?php echo app_lang('custom_column'); ?></label>
                        <div class="col-md-10">
                            <?php
                            echo form_checkbox("footer_custom_column", "1", get_setting("footer_custom_column") ? true : false, "id='footer_custom_column' class='form-check-input ml15'");
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="custom_column_area" class="<?php echo get_setting("footer_custom_column") ? "" : "hide"; ?>">
                <div class="card-body">
                    <div class="form-group">
                        <div class="row">
                            <label for="custom_column_title" class="col-md-2"><?php echo app_lang('title'); ?></label>
                            <div class="col-md-10">
                                <?php
                                echo form_input(array(
                                    "id" => "custom_column_title",
                                    "name" => "footer_custom_column_title",
                                    "value" => get_setting("footer_custom_column_title"),
                                    "class" => "form-control",
                                    "placeholder" => app_lang('title')
                                ));
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="custom_column_content" class="col-md-2"><?php echo app_lang('content'); ?></label>
                            <div class="col-md-10">
                                <?php
                                echo form_textarea(array(
                                    "id" => "custom_column_content",
                                    "name" => "footer_custom_column_content",
                                    "value" => get_setting("footer_custom_column_content"),
                                    "class" => "form-control",
                                    "placeholder" => app_lang('content'),
                                    "data-rich-text-editor" => true
                                ));
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Info Section -->
        <div class="card">
            <div class="card-header">
                <div class="form-switch">
                    <div class="row">
                        <label for="footer_contact_info" class=" col-md-2"><?php echo app_lang('contact_info'); ?></label>
                        <div class="col-md-10">
                            <?php
                            echo form_checkbox("footer_contact_info", "1", get_setting("footer_contact_info") ? true : false, "id='footer_contact_info' class='form-check-input ml15'");
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div id="contact_info_area" class="<?php echo get_setting("footer_contact_info") ? "" : "hide"; ?>">
                <div class="card-body">
                    <div class="form-group">
                        <div class="row">
                            <label for="footer_address_1" class="col-md-2"><?php echo app_lang('address') . ' 1'; ?></label>
                            <div class="col-md-10">
                                <?php
                                echo form_textarea(array(
                                    "id" => "footer_address_1",
                                    "name" => "footer_address_1",
                                    "value" => get_setting("footer_address_1"),
                                    "class" => "form-control",
                                    "placeholder" => app_lang('address')
                                ));
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label for="footer_address_2" class="col-md-2"><?php echo app_lang('address') . ' 2'; ?></label>
                            <div class="col-md-10">
                                <?php
                                echo form_textarea(array(
                                    "id" => "footer_address_2",
                                    "name" => "footer_address_2",
                                    "value" => get_setting("footer_address_2"),
                                    "class" => "form-control",
                                    "placeholder" => app_lang('address')
                                ));
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="footer_email_1" class="col-md-2"><?php echo app_lang('email') . ' 1'; ?></label>
                            <div class="col-md-10">
                                <?php
                                echo form_input(array(
                                    "id" => "footer_email_1",
                                    "name" => "footer_email_1",
                                    "value" => get_setting("footer_email_1"),
                                    "class" => "form-control",
                                    "placeholder" => app_lang('email'),
                                    "data-rule-email" => true,
                                    "data-msg-email" => app_lang("enter_valid_email")
                                ));
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label for="footer_email_2" class="col-md-2"><?php echo app_lang('email') . ' 2'; ?></label>
                            <div class="col-md-10">
                                <?php
                                echo form_input(array(
                                    "id" => "footer_email_2",
                                    "name" => "footer_email_2",
                                    "value" => get_setting("footer_email_2"),
                                    "class" => "form-control",
                                    "placeholder" => app_lang('email'),
                                    "data-rule-email" => true,
                                    "data-msg-email" => app_lang("enter_valid_email")
                                ));
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label for="footer_phone_1" class="col-md-2"><?php echo app_lang('phone') . ' 1'; ?></label>
                            <div class="col-md-10">
                                <?php
                                echo form_input(array(
                                    "id" => "footer_phone_1",
                                    "name" => "footer_phone_1",
                                    "value" => get_setting("footer_phone_1"),
                                    "class" => "form-control"
                                ));
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label for="footer_phone_2" class="col-md-2"><?php echo app_lang('phone') . ' 2'; ?></label>
                            <div class="col-md-10">
                                <?php
                                echo form_input(array(
                                    "id" => "footer_phone_2",
                                    "name" => "footer_phone_2",
                                    "value" => get_setting("footer_phone_2"),
                                    "class" => "form-control"
                                ));
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="row">
                            <label for="footer_contact_info_link_title" class="col-md-2"><?php echo app_lang('link'); ?></label>
                            <div class="col-md-4">
                                <?php
                                echo form_input(array(
                                    "id" => "footer_contact_info_link_title",
                                    "name" => "footer_contact_info_link_title",
                                    "value" => get_setting("footer_contact_info_link_title"),
                                    "class" => "form-control",
                                    "placeholder" => app_lang('link_title')
                                ));
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                echo form_input(array(
                                    "id" => "footer_contact_info_link",
                                    "name" => "footer_contact_info_link",
                                    "value" => get_setting("footer_contact_info_link"),
                                    "class" => "form-control",
                                    "placeholder" => app_lang('link')
                                ));
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<div class="card-footer">
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>

<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        var phoneInput1 = initializeIntlTelInput("#footer_phone_1");
        var phoneInput2 = initializeIntlTelInput("#footer_phone_2");

        $("#footer-settings-form").appForm({
            isModal: false,
            beforeAjaxSubmit: function(data) {
                $.each(data, function(index, obj) {
                    if (obj.name === "footer_about") {
                        var footerAbout = $("#footer_about_section").val().trim();
                        if (footerAbout === '' || /^<p>(<br>|&nbsp;|\s)*<\/p>$/.test(footerAbout)) {
                            data[index].value = '';
                        }
                    }

                    if (obj.name === "footer_phone_1" && phoneInput1) {
                        data[index].value = phoneInput1.getNumber();
                    }

                    if (obj.name === "footer_phone_2" && phoneInput2) {
                        data[index].value = phoneInput2.getNumber();
                    }
                });
            },
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

        //save positions for first time
        setTimeout(function() {
            saveMenusPosition();
            saveSocialLinksPosition();
            saveQuickLinksPosition();
        }, 300);

        //show/hide sections
        bindToggle("#enable_footer", "#footer-details-area");
        bindToggle("#footer_brand_profile", "#brand_profile_area");
        bindToggle("#footer_quick_links_enabled", "#quick_links_area");
        bindToggle("#footer_custom_column", "#custom_column_area");
        bindToggle("#footer_contact_info", "#contact_info_area");


        // Footer Menus
        var $footerMenusShowArea = $("#footer-menus-show-area"),
            $footerMenusOptionsArea = $("#footer-menus-options-area"),
            $addBtn = $("#footer-menus-add-button"),
            $closeBtn = $("#footer-menus-close-button");

        //show save & cancel button when the input is focused
        $("#menu_name, #url").focus(function() {
            $footerMenusOptionsArea.removeClass("hide");
        });

        //add menu
        $addBtn.click(function() {
            var menuName = $("#menu_name").val(),
                url = $("#url").val();

            if (menuName && url) {
                appAjaxRequest({
                    url: "<?php echo get_uri('settings/save_footer_menu') ?>",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        menu_name: menuName,
                        url: url
                    },
                    success: function(result) {
                        if (result.success) {
                            $footerMenusShowArea.append(result.data);

                            $("#menu_name").val("").focus();
                            $("#url").val("");

                            saveMenusPosition();
                        }
                    }
                });
            }
        });

        //close options
        $closeBtn.click(function() {
            $footerMenusOptionsArea.addClass("hide");
        });

        //store the temp id for update operation
        $("body").on("click", ".footer-menu-item .footer-menu-edit-btn", function() {
            window.footerMenuItemTempId = $(this).closest(".footer-menu-item").attr("data-footer_menu_temp_id");
        });

        $("#footer-menu-input-area input").keydown(function(e) {
            if (e.keyCode === 13) {
                e.preventDefault();
                $addBtn.trigger("click");
            }
        });

        $("#footer_social_link_type").select2();

        // Footer Social Links
        var $footerSocialLinksShowArea = $("#footer-social-links-show-area"),
            $footerSocialLinksOptionsArea = $("#footer-social-links-options-area"),
            $socialAddBtn = $("#footer-social-links-add-button"),
            $socialCloseBtn = $("#footer-social-links-close-button");

        // show save & cancel buttons
        $("#footer_social_link_type, #social_link").on("change focus", function() {
            $footerSocialLinksOptionsArea.removeClass("hide");
        });

        // add social link
        $socialAddBtn.click(function() {

            var socialType = $("#footer_social_link_type").val(),
                socialLink = $("#social_link").val();

            var postData = {
                social_link_type: socialType,
                social_link: socialLink
            };

            if (socialType === "custom") {

                postData.custom_social_title = $("#custom_social_title").val();
                var iconFile = $("#icon_file_upload")[0].files[0];

                if (!postData.custom_social_title || !iconFile) {
                    return;
                }

                var reader = new FileReader();
                reader.onload = function(e) {
                    postData.custom_social_icon_file_name = iconFile.name;
                    postData.custom_social_icon_file_content = e.target.result;
                    saveFooterSocialLink(postData);
                };

                reader.readAsDataURL(iconFile);
                return;
            }

            saveFooterSocialLink(postData);
        });

        function saveFooterSocialLink(postData) {
            appAjaxRequest({
                url: "<?php echo get_uri('settings/save_footer_social_link') ?>",
                type: "POST",
                dataType: "json",
                data: postData,
                success: function(result) {
                    if (result.success) {
                        $footerSocialLinksShowArea.append(result.data);

                        $("#social_link").val("");
                        $("#custom_social_title").val("");
                        $("#icon_file_upload").val("");

                        saveSocialLinksPosition();
                    }
                }
            });
        }

        // close options
        $socialCloseBtn.click(function() {
            $footerSocialLinksOptionsArea.addClass("hide");
        });

        //store the temp id for update operation
        $("body").on("click", ".footer-quick-link-item .footer-quick-link-edit-btn", function() {
            window.footerQuickLinkTempId = $(this).closest(".footer-quick-link-item").attr("data-footer_quick_link_temp_id");
        });

        $("#footer_social_link_type").on("change", function() {
            toggleSocialLinkFields();
            $footerSocialLinksOptionsArea.removeClass("hide");
        });

        toggleSocialLinkFields();

        // Footer Quick Links
        var $footerQuickLinksOptionsArea = $("#footer-quick-links-options-area");
        var $footerQuickLinksAddBtn = $("#footer-quick-links-add-button");
        var $footerQuickLinksCloseBtn = $("#footer-quick-links-close-button");
        var $footerQuickLinksShowArea = $("#footer-quick-links-show-area");

        //show save & cancel button when the input is focused
        $("#quick_link_name, #quick_link_url").focus(function() {
            $footerQuickLinksOptionsArea.removeClass("hide");
        });

        //add menu
        $footerQuickLinksAddBtn.click(function() {
            var menuName = $("#quick_link_name").val(),
                url = $("#quick_link_url").val();

            if (menuName && url) {
                appAjaxRequest({
                    url: "<?php echo get_uri('settings/save_footer_quick_link') ?>",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        quick_link_name: menuName,
                        quick_link_url: url
                    },
                    success: function(result) {
                        if (result.success) {
                            $footerQuickLinksShowArea.append(result.data);

                            $("#quick_link_name").val("").focus();
                            $("#quick_link_url").val("");

                            saveQuickLinksPosition();
                        }
                    }
                });
            }
        });

        //close options
        $footerQuickLinksCloseBtn.click(function() {
            $footerQuickLinksOptionsArea.addClass("hide");
        });


        //store the temp id for update operation
        $("body").on("click", ".footer-social-link-item .footer-social-link-edit-btn", function() {
            window.footerSocialLinkItemTempId = $(this).closest(".footer-social-link-item").attr("data-footer_social_link_temp_id");
        });

        //delete
        bindDelete(".footer-menu-delete-btn", ".footer-menu-item", saveMenusPosition);
        bindDelete(".footer-social-link-delete-btn", ".footer-social-link-item", saveSocialLinksPosition);
        bindDelete(".footer-quick-link-delete-btn", ".footer-quick-link-item", saveQuickLinksPosition);

        //make the menus sortable
        makeSortable("#footer-menus-show-area", saveMenusPosition);
        makeSortable("#footer-quick-links-show-area", saveQuickLinksPosition);
        makeSortable("#footer-social-links-show-area", saveSocialLinksPosition);
    });

    function saveMenusPosition() {
        saveItems(
            "#footer-menus-show-area .footer-menu-item",
            "#footer-menus-data",
            function($item) {
                var $a = $item.find("a"),
                    menuName = $a.text(),
                    url = $a.attr("href");

                if (!menuName || !url) {
                    return null;
                }

                return {
                    menu_name: menuName,
                    url: url
                };
            }
        );
    }

    function saveSocialLinksPosition() {
        saveItems(
            "#footer-social-links-show-area .footer-social-link-item",
            "#footer-social-links-data",
            function($item) {
                var $a = $item.find("a"),
                    title = $a.text(),
                    url = $a.attr("href");

                if (!title || !url) {
                    return null;
                }

                return {
                    social_link_type: $a.data("type"),
                    social_link_url: url,
                    title: title,
                    icon_name: $a.data("icon_name")
                };
            }
        );
    }

    function saveQuickLinksPosition() {
        saveItems(
            "#footer-quick-links-show-area .footer-quick-link-item",
            "#footer-quick-links-data",
            function($item) {
                var $a = $item.find("a"),
                    linkName = $a.text(),
                    url = $a.attr("href");

                if (!linkName || !url) {
                    return null;
                }

                return {
                    link_name: linkName,
                    link_url: url
                };
            }
        );
    }

    function toggleSocialLinkFields() {
        var type = $("#footer_social_link_type").val();

        if (type === "custom") {
            $("#custom-social-link-fields").removeClass("hide");
        } else {
            $("#custom-social-link-fields").addClass("hide");
        }
    }

    function bindToggle(checkboxSelector, targetSelector) {
        $(checkboxSelector).on("change", function() {
            $(targetSelector).toggleClass("hide", !$(this).is(":checked"));
        });
    }

    function bindDelete(buttonSelector, itemSelector, callback) {
        $("body").on("click", buttonSelector, function() {
            $(this).closest(itemSelector).fadeOut(300, function() {
                $(this).remove();

                if (typeof callback === "function") {
                    callback();
                }
            });
        });
    }

    function saveItems(selector, hiddenField, mapper) {
        var items = [];

        $(selector).each(function() {
            var item = mapper($(this));

            if (item) {
                items.push(item);
            }
        });

        $(hiddenField).val(JSON.stringify(items));
    }

    function makeSortable(selector, callback) {
        var element = $(selector)[0];

        if (!element) {
            return;
        }

        Sortable.create(element, {
            animation: 150,
            handle: ".move-icon",
            chosenClass: "sortable-chosen",
            ghostClass: "sortable-ghost",
            onUpdate: callback
        });
    }
</script>