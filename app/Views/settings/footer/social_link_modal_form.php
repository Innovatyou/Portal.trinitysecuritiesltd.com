<?php echo form_open(get_uri("settings/save_footer_social_link"), array("id" => "footer-social-link-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="type" value="data" />
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
                    strtolower($model_info->social_link_type),
                    "id='footer_social_link_type_edit' class='select2'"
                );
                ?>
            </div>

            <div class="col-md-8">
                <?php
                echo form_input(array(
                    "id" => "social_link",
                    "name" => "social_link",
                    "class" => "form-control",
                    "placeholder" => app_lang('url'),
                    "value" => $model_info->social_link_url
                ));
                ?>
            </div>

            <div class="col-md-12">
                <div id="custom-social-link-fields" class="row hide mt15">
                    <div class="col-md-4">
                        <?php
                        echo form_input(array(
                            "id" => "custom_social_title_edit",
                            "name" => "custom_social_title",
                            "class" => "form-control",
                            "value" => $model_info->title,
                            "placeholder" => app_lang('title')
                        ));
                        ?>
                    </div>

                    <div class="col-md-8">
                        <div class="float-start mr15 <?php echo $model_info->icon_name ? "" : "hide"; ?>">
                            <img id="icon-preview-edit" src="<?php echo $model_info->icon_name ? get_source_url_of_file(array("file_name" => $model_info->icon_name), get_setting("system_file_path") . "social/") : ""; ?>" alt="..." style="width: 32px" />
                        </div>
                        <div class="float-start">
                            <?php
                            echo form_upload(array(
                                "id" => "icon_file_upload_edit",
                                "name" => "icon_file",
                                "class" => "no-outline hidden-input-file"
                            ));
                            ?>
                            <label for="icon_file_upload_edit" class="btn btn-default">
                                <i data-feather="upload" class="icon-14"></i> <?php echo app_lang("upload_icon"); ?>
                            </label>
                        </div>
                        <input type="hidden" id="custom_social_svg_edit" name="custom_social_svg" value="<?php echo $model_info->icon_name; ?>" />
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

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function() {
        var $footerSocialLinkForm = $("#footer-social-link-form");

        function updateFooterSocialLink(result) {
            var $item = $("#footer-social-links-show-area").find("[data-footer_social_link_temp_id='" + window.footerSocialLinkItemTempId + "']");
            $item.replaceWith(result.data);

            saveSocialLinksPosition();
            window.footerSocialLinkItemTempId = "";
            $("#ajaxModal").modal("hide");
        }

        function saveFooterSocialLink(postData) {
            appAjaxRequest({
                url: "<?php echo get_uri('settings/save_footer_social_link') ?>",
                type: "POST",
                dataType: "json",
                data: postData,
                success: function(result) {
                    if (result.success) {
                        updateFooterSocialLink(result);
                    }
                }
            });
        }

        $footerSocialLinkForm.submit(function(e) {
            if ($("#footer_social_link_type_edit").val() !== "custom") {
                return true;
            }

            e.preventDefault();
            e.stopImmediatePropagation();

            var iconFile = $footerSocialLinkForm.find("[name='icon_file']")[0].files[0],
                postData = {
                    type: "data",
                    social_link_type: $("#footer_social_link_type_edit").val(),
                    social_link: $footerSocialLinkForm.find("[name='social_link']").val(),
                    custom_social_title: $footerSocialLinkForm.find("[name='custom_social_title']").val(),
                    custom_social_svg: $footerSocialLinkForm.find("[name='custom_social_svg']").val()
                };

            if (!postData.custom_social_title || (!postData.custom_social_svg && !iconFile)) {
                return false;
            }

            if (iconFile) {
                var reader = new FileReader();
                reader.onload = function(event) {
                    postData.custom_social_icon_file_name = iconFile.name;
                    postData.custom_social_icon_file_content = event.target.result;
                    saveFooterSocialLink(postData);
                };

                reader.readAsDataURL(iconFile);
                return false;
            }

            saveFooterSocialLink(postData);
            return false;
        });

        $footerSocialLinkForm.appForm({
            closeModalOnSuccess: false,
            onSuccess: updateFooterSocialLink
        });

        $("#footer_social_link_type_edit").select2();

        $("#footer_social_link_type_edit").on("change", function() {
            $("#footer-social-link-form #custom-social-link-fields")
                .toggleClass("hide", $(this).val() !== "custom");
        });

        $("#footer_social_link_type_edit").trigger("change");
    });
</script>