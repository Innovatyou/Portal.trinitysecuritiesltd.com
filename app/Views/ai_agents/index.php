<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "ai_agents";
            echo view("settings/tabs", $tab_view);

            $has_ai_provider = get_setting("enable_chatgpt") || get_setting("enable_gemini");
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="page-title clearfix">
                    <h4> <?php echo app_lang('ai_agents'); ?></h4>

                    <?php if ($has_ai_provider) { ?>
                        <div class="title-button-group">
                            <?php echo modal_anchor(get_uri("ai_agents/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_ai_agent'), array("class" => "btn btn-default", "title" => app_lang('add_ai_agent'))); ?>
                        </div>
                    <?php } ?>
                </div>

                <?php if ($has_ai_provider) { ?>
                    <div class="table-responsive">
                        <table id="ai-agents-table" class="display" cellspacing="0" width="100%">
                        </table>
                    </div>
                <?php } else { ?>
                    <div class="card-body">
                        <i data-feather='alert-triangle' class="icon-16 text-danger"></i>
                        <?php echo app_lang("chatgpt_not_authorized_message"); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php if ($has_ai_provider) { ?>
    <script type="text/javascript">
        $(document).ready(function() {

            function check_training_status(id) {
                $.ajax({
                    url: "<?php echo_uri("ai_agents/check_training_status") ?>",
                    type: "POST",
                    dataType: "json",
                    data: {
                        id: id
                    },
                    success: function(result) {
                        if (result.success) {
                            $("#ai-agents-table").appTable({
                                newData: result.data,
                                dataId: result.id
                            });
                        }
                    }
                });
            }

            $("#ai-agents-table").appTable({
                source: '<?php echo_uri("ai_agents/list_data") ?>',
                columns: [{
                        title: '<?php echo app_lang("title"); ?>'
                    },
                    {
                        title: '<?php echo app_lang("description"); ?>'
                    },
                    {
                        title: '<?php echo app_lang("base_model"); ?>'
                    },
                    {
                        visible: false,
                        searchable: false
                    },
                    {
                        title: '<?php echo app_lang("created_date") ?>',
                        "iDataSort": 3
                    },
                    {
                        title: '<?php echo app_lang("status") ?>'
                    },
                    {
                        title: '<?php echo app_lang("actions") ?>',
                        "class": "w175"
                    },
                    {
                        title: '<i data-feather="menu" class="icon-16"></i>',
                        "class": "text-center w50"
                    }
                ],
                reloadHooks: [{
                    type: "app_table_row_update",
                    tableId: "ai-agents-table"
                }],
                onInitComplete: function() {
                    $("#ai-agents-table").find(".processing-training").each(function() {
                        var $this = $(this);
                        var id = $this.data("id");
                        check_training_status(id);
                    });

                    $("#ai-agents-table [data-bs-toggle='tooltip']").tooltip();
                }
            });
        });
    </script>
<?php } ?>