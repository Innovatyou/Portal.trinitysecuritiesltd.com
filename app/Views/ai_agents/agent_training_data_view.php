<div class="p15">
    <input type="hidden" id="agent_id" value="<?php echo $agent_id; ?>">

    <?php if (empty($sources)) { ?>
        <div class="text-center mt20 text-off"><?php echo app_lang("no_training_data_messgae"); ?></div>
    <?php } ?>

    <?php

    $untrained_data_array = array();
    $deleted_source_ids = $model_info->deleted_source_ids ? explode(",", $model_info->deleted_source_ids) : [];

    foreach ($sources as $source) {

        if ($source->source_type == "file") {
            $icon = "file-text";
        } else if ($source->source_type == "text_snippet") {
            $icon = "edit-3";
        } else if ($source->source_type == "kb_articles") {
            $icon = "help-circle";
        } else  if ($source->source_type == "excel_file") {
            $icon = "file-minus";
        }

        $completed_data_count = 0;

        $all_data_ids = $source->all_data_ids ? explode(",", $source->all_data_ids) : [];
        $all_data_count = count($all_data_ids);
        $completed_data_ids = array();

        if ($source->completed_data_ids) {
            $completed_data_ids = explode(",", $source->completed_data_ids);
            $completed_data_count = count($completed_data_ids);
        }

        if (in_array($source->id, $deleted_source_ids)) {
            $untrained_data_array[] = array("source_id" => $source->id, "deleted" => 1);
        } else {
            // store untrained data ids for training
            foreach ($all_data_ids as $data_id) {
                if (!in_array($data_id, $completed_data_ids)) {
                    $untrained_data_array[] = array("source_id" => $source->id, "vector_data_id" => $data_id);
                }
            }
        }

        $training_data_icon = "<i data-feather='cloud-off' class='icon-14'></i>";
        $training_pending_class = "text-warning";
        $icon_bg = "bg-warning";
        if ($completed_data_count == $all_data_count) {
            $training_data_icon = "<i data-feather='check-circle' class='icon-14 text-success'></i>";
            $training_pending_class = "";
            $icon_bg = "bg-success";
        }


        $deleted_badge = "";
        //$delete = js_anchor("<i data-feather='x' class='icon-16'></i>", array('title' => app_lang('delete'), "class" => "float-end action-option delete", "data-request-group" => "delete_training_data_row", "data-id" => $source->id));
        $delete = ajax_anchor(get_uri("ai_agents/delete_source/$source->id"), "<i data-feather='x' class='icon-16'></i>", array("class" => "float-end action-option delete", "data-request-group" => "delete_training_data_row", "data-post-source_id" => $source->id, "data-post-agent_id" => $agent_id));

        if (in_array($source->id, $deleted_source_ids)) {
            $deleted_badge = "<span class='badge bg-danger mt0 ml5'>" . app_lang("pending_deletion") . "</span>";
            $delete = "";
            $icon_bg = "bg-danger";
        }

    ?>
        <div class="row mb20">
            <div class="col-md-1 p10 text-center border-radius-5 <?php echo $icon_bg; ?>">
                <i data-feather='<?php echo $icon; ?>' class='icon-18'></i>
            </div>
            <div class="col-md-11">
                <div id="source_<?php echo $source->id; ?>">
                    <div class="mt-0 mb-1">
                        <span>
                            <?php
                            echo $source->title ? $source->title : remove_file_prefix($source->file_name);
                            echo $delete;
                            ?>
                        </span>

                    </div>
                    <div>
                        <?php
                        echo "<span class='$training_pending_class data-count-container'><span class='data-count-icon-container'>$training_data_icon</span> <span class='completed-data-count'>" . $completed_data_count . "</span> " . app_lang("of") . " " . $all_data_count . " " . strtolower(app_lang("data_trained")) . "</span>";
                        echo $deleted_badge;
                        ?>

                        <?php if ($completed_data_count !== $all_data_count) { ?>
                            <span class="spinning-btn loading-icon spinning hide"></span>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<script type="text/javascript">
    window.untrainedDataArray = <?php echo json_encode($untrained_data_array); ?>;
    window.deletedSourceIds = <?php echo json_encode($deleted_source_ids); ?>;
</script>