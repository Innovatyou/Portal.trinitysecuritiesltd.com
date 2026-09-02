<div class="modal-body clearfix">
    <div class="container-fluid">

        <div class="row">
            <div class="col-md-8 b-r" id="train-agent-sources-container">
                <?php echo $agent_training_data_view; ?>
            </div>

            <div class="col-md-4">
                <ul class="list-group">

                    <?php if (get_setting("module_knowledge_base") == "1") { ?>
                        <li class="list-group-item b-b">
                            <i data-feather="help-circle" class="icon-16"></i> <span class="ml5"><?php echo ajax_anchor(get_uri("ai_agents/import_kb_articles"), app_lang("import_knowledge_base_articles"), array("title" => app_lang("import_knowledge_base_articles"), "data-request-group" => "delete_training_data_row", "data-post-agent_id" => $agent_id, "data-show-response" => "1")); ?></span>
                        </li>
                    <?php } ?>

                    <li class="list-group-item b-b">
                        <i data-feather="file-minus" class="icon-16"></i> <span class="ml5"><?php echo modal_anchor(get_uri("ai_agents/import_modal_form"), app_lang("import_from_excel_file"), array("title" => app_lang("import_from_excel_file"), "data-post-agent_id" => $agent_id, "data-post-show_description" => true, "data-reopen-parent-modal" => "1")); ?></span>
                    </li>
                    <li class="list-group-item b-b">
                        <i data-feather="file-text" class="icon-16"></i> <span class="ml5"><?php echo modal_anchor(get_uri("ai_agents/import_files_modal_form"), app_lang("import_from_txt_file"), array("title" => app_lang("import_from_txt_file"), "data-post-agent_id" => $agent_id, "data-reopen-parent-modal" => "1")); ?></span>
                    </li>
                    <li class="list-group-item b-b">
                        <i data-feather="edit-3" class="icon-16"></i> <span class="ml5"><?php echo modal_anchor(get_uri("ai_agents/input_text_prompt_modal_form"), app_lang("input_text_prompt"), array("title" => app_lang("input_text_prompt"), "data-post-agent_id" => $agent_id, "data-reopen-parent-modal" => "1")); ?></span>
                    </li>
                    <li class="list-group-item b-b">
                        <i data-feather="download" class="icon-16"></i> <span class="ml5">
                            <?php
                            echo js_anchor(
                                app_lang("import_from_existing_agent"),
                                array(
                                    'title' => app_lang("import_from_existing_agent"),
                                    "data-act" => "import-from-existing-agent-modifier",
                                    "data-modifier-group" => "train_agent_sources",
                                    "data-field" => "import_from_existing_agent_id",
                                    "data-action-url" => get_uri("ai_agents/import_from_existing_agent/$agent_id")
                                )
                            );
                            ?>
                        </span>
                    </li>
                </ul>
            </div>

        </div>
    </div>

</div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>


    <button id="start_training" type="button" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('start_training'); ?></button>

</div>

<script type="text/javascript">
    $(document).ready(function() {
        var toTrainDataArray = window.untrainedDataArray;
        var deletedSourceIds = window.deletedSourceIds

        if (toTrainDataArray.length === 0 && deletedSourceIds.length === 0) {
            $("#start_training").prop("disabled", true).addClass("hide");
        } else {
            $("#start_training").prop("disabled", false).removeClass("hide");
        }

        $("#start_training").click(function() {
            $(this).prop("disabled", true);
            var agentId = $("#agent_id").val();
            var dataIndex = 0;

            function showSuccessIcon(sourceId, dataIndex) {
                // check if there is any further data to train under this source
                var hasMoreDataWithThisSource = toTrainDataArray.slice(dataIndex + 1).findIndex(function(data) {
                    return data.source_id == sourceId;
                }) !== -1;

                if (!hasMoreDataWithThisSource) {
                    // this source traing is completed
                    $("#source_" + sourceId + " .loading-icon").addClass("hide");
                    $("#source_" + sourceId + " .data-count-container").removeClass("text-warning");
                    $("#source_" + sourceId + " .data-count-icon-container").html("<i data-feather='check-circle' class='icon-14 text-success'></i>");

                    feather.replace();
                }
            }

            function trainData(data) {
                if (dataIndex >= toTrainDataArray.length) {
                    // finished training, save status
                    $.ajax({
                        url: "<?php echo get_uri('ai_agents/save_agent_training_status'); ?>" + "/" + agentId + "/completed"
                    });

                    location.reload();
                    return false;
                }

                $("#source_" + data.source_id + " .loading-icon").removeClass("hide");

                $.ajax({
                    url: "<?php echo get_uri('ai_agents/train_data'); ?>",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        agent_id: agentId,
                        source_id: data.source_id,
                        vector_data_id: data.vector_data_id,
                        deleted: data.deleted
                    },
                    success: function(response) {
                        if (response.success) {

                            if (data.deleted) {
                                // fade out the entire source
                                $("#source_" + data.source_id).closest(".row").fadeOut(500);
                            } else {
                                // show success icon if all data is trained
                                showSuccessIcon(data.source_id, dataIndex);

                                // update count
                                var completedDataCount = parseInt($("#source_" + data.source_id + " .completed-data-count").text());
                                completedDataCount++;
                                $("#source_" + data.source_id + " .completed-data-count").text(completedDataCount);
                            }

                            // train next data
                            dataIndex++;
                            trainData(toTrainDataArray[dataIndex]);
                        } else {
                            appAlert.error(response.message, {
                                container: "#train-agent-sources-container",
                                animate: false
                            });
                        }
                    }
                });
            }

            // starting training, save status
            $.ajax({
                url: "<?php echo get_uri('ai_agents/save_agent_training_status'); ?>" + "/" + agentId + "/training_ongoing"
            });

            trainData(toTrainDataArray[dataIndex]);
        });

        $('body').on('click', '[data-act=import-from-existing-agent-modifier]', function(e) {
            var $instance = $(this);

            $(this).appModifier({
                dropdownData: {
                    import_from_existing_agent_id: <?php echo $agents_dropdown; ?>
                },
                onSuccess: function(response, newValue) {
                    if (response.success) {
                        setTimeout(function() {
                            $instance.html(response.data);
                        }, 50);
                    }
                }
            });

            return false;
        });


        appContentBuilder.init("<?php echo get_uri('ai_agents/train_agent'); ?>", {
            id: "ai-training-data-view-builder",
            data: {
                agent_id: "<?php echo $agent_id; ?>",
                view_type: "training_data_view"
            },
            reloadHooks: [{
                    type: "app_form",
                    id: "import-text-form"
                },
                {
                    type: "ajax_request",
                    group: "delete_training_data_row"
                },
                {
                    type: "app_modifier",
                    group: "train_agent_sources"
                }
            ],
            reload: function(bind, result) {
                bind("#train-agent-sources-container", result.training_data_view);
                $("#start_training").prop("disabled", false).removeClass("hide");
            }
        });
    });
</script>