<?php echo form_open(get_uri("ai_agents/save"), array("id" => "agent-form", "class" => "general-form", "role" => "form")); ?>

<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
        <div class="form-group">
            <div class="row">
                <label for="title" class=" col-md-3"><?php echo app_lang('title'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "title",
                        "name" => "title",
                        "value" => $model_info->title,
                        "class" => "form-control",
                        "placeholder" => app_lang('title'),
                        "autofocus" => true,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <label for="description" class=" col-md-3"><?php echo app_lang('description'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_textarea(array(
                        "id" => "description",
                        "name" => "description",
                        "value" => process_images_from_content($model_info->description, false),
                        "class" => "form-control",
                        "placeholder" => app_lang('description') . "..."
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="ai_service" class=" col-md-3"><?php echo app_lang('ai_service'); ?></label>
                <div class="col-md-9">
                    <?php
                    $ai_service_options = array(
                        "id" => "ai_service",
                        "name" => "ai_service",
                        "value" => $model_info->ai_service,
                        "class" => "form-control mini validate-hidden",
                        "placeholder" => app_lang('ai_service'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    );

                    if ($model_info->id) {
                        $ai_service_options["disabled"] = "disabled";
                    }

                    echo form_input($ai_service_options);
                    ?>
                </div>
            </div>
        </div>

        <div id="ai-service-details-area" class="<?php echo !$model_info->ai_service ? "hide" : ""; ?>">
            <div class="form-group hide">
                <div class="row">
                    <label for="model_type" class=" col-md-3"><?php echo app_lang('model_type'); ?></label>
                    <div class="col-md-9">
                        <?php

                        echo form_input(array(
                            "id" => "model_type",
                            "name" => "model_type",
                            "value" => $model_info->model_type ? $model_info->model_type : "text",
                            "class" => "form-control mini",
                            "placeholder" => app_lang('model_type'),
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="row">
                    <label for="base_model" class=" col-md-3"><?php echo app_lang('base_model'); ?></label>
                    <div class="col-md-9">
                        <?php
                        $base_model_options = array(
                            "id" => "base_model",
                            "name" => "base_model",
                            "value" => $model_info->base_model,
                            "class" => "form-control validate-hidden",
                            "placeholder" => app_lang('base_model'),
                            "data-rule-required" => true,
                            "data-msg-required" => app_lang("field_required"),
                        );

                        if ($model_info->id) {
                            $base_model_options["disabled"] = "disabled";
                        }

                        echo form_input($base_model_options);
                        ?>
                    </div>
                </div>
            </div>

            <!-- Parameters (Shown only for image models) -->
            <div class="form-group <?php echo $model_info->parameters ? "" : "hide"; ?>" id="parameters-area">
                <div class="row">
                    <label for="parameters" class="col-md-3"><?php echo app_lang('parameters'); ?></label>
                    <div class="col-md-9">
                        <?php
                        echo form_textarea(array(
                            "id" => "parameters",
                            "name" => "parameters",
                            "value" => $model_info->parameters,
                            "class" => "form-control",
                            "placeholder" => '{"size": "1024x1024", "n": 1}',
                        ));
                        ?>
                    </div>
                </div>
            </div>

            <div id="text-models-settings-area" class="<?php echo !$model_info->model_type || $model_info->model_type == "text" ? "" : "hide"; ?>">

                <div class="form-group">
                    <div class="row">
                        <label for="system_prompt" class=" col-md-3"><?php echo app_lang('system_prompt'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_textarea(array(
                                "id" => "system_prompt",
                                "name" => "system_prompt",
                                "class" => "form-control",
                                "placeholder" => app_lang('system_prompt'),
                                "value" => $model_info->system_prompt ? $model_info->system_prompt : "You are a helpful assistant.",
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group " id="app-actions-area">
                    <div class="row">
                        <label for="app_actions" class="col-md-3"><?php echo app_lang('app_actions'); ?></label>
                        <div class="col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "app_actions",
                                "name" => "app_actions",
                                "value" => $model_info->app_actions,
                                "class" => "form-control",
                                "placeholder" => app_lang('app_actions')
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="max_output_tokens" class=" col-md-3"><?php echo app_lang('max_output_tokens'); ?></label>
                        <div class=" col-md-9">
                            <?php
                            echo form_input(array(
                                "id" => "max_output_tokens",
                                "name" => "max_output_tokens",
                                "type" => "number",
                                "value" => $model_info->max_output_tokens ? $model_info->max_output_tokens : "",
                                "class" => "form-control mini",
                                "placeholder" => app_lang('max_output_tokens_placeholder'),
                            ));
                            ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <label for="temperature" class=" col-md-3"><?php echo app_lang('temperature'); ?>
                            <span class="help" data-container="body" data-bs-toggle="tooltip" title="<?php echo app_lang('temperature_help_message'); ?>"><i data-feather="help-circle" class="icon-16"></i></span>
                        </label>
                        <div class=" col-md-9">
                            <?php
                            $temperature = array(
                                "0" => "0",
                                "0.1" => "0.1",
                                "0.2" => "0.2",
                                "0.3" => "0.3",
                                "0.4" => "0.4",
                                "0.5" => "0.5",
                                "0.6" => "0.6",
                                "0.7" => "0.7",
                                "0.8" => "0.8",
                                "0.9" => "0.9",
                                "1" => "1",
                            );
                            echo form_dropdown(
                                "temperature",
                                $temperature,
                                $model_info->temperature ? $model_info->temperature : "0.7",
                                "class='select2 mini' id='temperature'"
                            );
                            ?>
                        </div>
                    </div>
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
        $("#agent-form").appForm({
            onSuccess: function(result) {
                $("#ai-agents-table").appTable({
                    newData: result.data,
                    dataId: result.id
                });
            }
        });

        setTimeout(function() {
            $("#title").focus();
        }, 200);

        $("#agent-form .select2").select2();
        $("#agent-form [data-bs-toggle='tooltip']").tooltip();

        $("#app_actions").appDropdown({
            multiple: true,
            list_data: <?php echo $app_actions_dropdown; ?>
        });


        function showParametersArea(allModels) {

            var modelId = $("#base_model").val();

            var existingBaseModel = "<?php echo $model_info->base_model; ?>";
            if (existingBaseModel === modelId) {
                // in edit mode, if it's the same model then don't reset the parameters because there is already some parameters saved
                return;
            }

            var selectedModel = allModels.find(function(model) {
                return model.id === modelId;
            });

            if (selectedModel && selectedModel.default_params) {
                $("#parameters").val(JSON.stringify(selectedModel.default_params, null, 2));
            }
        }

        function setBaseModelDropdown(allModels) {

            var modelType = $("#model_type").val() || "text";

            if (!$("#base_model").length) {
                console.log("base_model element not found");
                return;
            }


            if (modelType === "text") {
                $("#parameters-area").addClass("hide");
            } else if (modelType === "image") {
                $("#text-models-settings-area").addClass("hide");
            }


            var modelsList = allModels
                .filter(function(model) {
                    return model.type === modelType;
                })
                .map(function(model) {
                    return {
                        id: model.id,
                        text: model.name,
                        defaultParams: model.default_params || {}
                    };
                });


            $("#base_model").appDropdown({
                list_data: modelsList,
            }).on("change", function() {

                if (modelType === "image") {
                    $("#parameters-area").removeClass("hide");
                    showParametersArea(allModels);
                }
            });
            $("#default-params-area").hide();
        }


        function setModelTypeDropdownOfSelectedAiService() {
            var aiService = $("#ai_service").val();
            if (!aiService) {
                console.log("aiService is not selected");
                return;
            }

            $.ajax({
                url: "<?php echo site_url('ai_agents/get_models_of_an_ai_service'); ?>",
                type: "POST",
                dataType: "json",
                data: {
                    ai_service: aiService
                },
                success: function(response) {
                    if (response.success) {
                        allModels = response.data;

                        $("#ai-service-details-area").removeClass("hide");

                        var modelTypeLangs = {
                            text: "<?php echo app_lang("text"); ?>",
                            image: "<?php echo app_lang("image"); ?>",
                            tts: "<?php echo app_lang("tts"); ?>",
                            video: "<?php echo app_lang("video"); ?>",
                        };

                        // Get unique model types from allModels
                        const modelTypes = [...new Set(allModels.map(model => model.type))];

                        // Prepare select2 data format
                        const modelTypesDropdown = modelTypes.map(type => ({
                            id: type,
                            text: modelTypeLangs[type] || type
                        }));


                        $("#model_type").appDropdown({
                            list_data: modelTypesDropdown,
                        }).on("change", function() {
                            setBaseModelDropdown(allModels);
                        });

                        setBaseModelDropdown(allModels);
                    }
                }
            });
        }

        // Initialize model type dropdown
        $("#ai_service").appDropdown({
            list_data: <?php echo $ai_services_dropdown; ?>
        }).on("change", function() {
            setModelTypeDropdownOfSelectedAiService();
        });


        setModelTypeDropdownOfSelectedAiService();


    });
</script>