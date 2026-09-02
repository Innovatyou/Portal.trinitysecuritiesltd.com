<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "customersapi";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">

                <div class="card-header">
                    <h4>Settings</h4>
                </div>

                <?php echo form_open(get_uri("customersapi/save_settings"), array("id" => "customersapi-settings-form", "class" => "general-form dashed-row", "role" => "form")); ?>

                <div class="card-body post-dropzone">
                    <div class="form-group">
                        <div class="row">
                            <label for="jwt_secret_key" class=" col-md-3">JWT Secret Key: </label>
                            <div class=" col-md-9">
                                <?php
                                echo form_input(array(
                                    "id" => "jwt_secret_key",
                                    "name" => "jwt_secret_key",
                                    "value" => get_setting("customersapi_secret_key"),
                                    "class" => "form-control",
                                    "placeholder" => "Secret Key",
                                ));
                                ?>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
                </div>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    "use strict";

    $(document).ready(function () {
        $("#customersapi-settings-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                appAlert.success(result.message, {duration: 10000});
            }
        });
    });
</script>