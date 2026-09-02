<?php
$permissions = $login_user->permissions;
$ticket_assistant_ai_agents = isset($ticket_assistant_ai_agents) ? $ticket_assistant_ai_agents : null;
$quick_assistant_ai_agent = get_setting("quick_assistant_ai_agent") ; 

if (
    $login_user->user_type === "staff" && ($login_user->is_admin || get_array_value($permissions, "can_access_quick_assistant")) &&
    ($quick_assistant_ai_agent || $ticket_assistant_ai_agents)
) {

    $list_source = isset($list_source) ? $list_source : "";
    $is_decending_list = isset($is_decending_list) ? $is_decending_list : "";
    $target_input_field = isset($target_input_field) ? $target_input_field : "";
    $quick_ai_assistant_container_id = "quick-ai-assistant-container-" . make_random_string(5);
    $content_source = isset($content_source) ? $content_source : "";


    //If it's input, check if the ticket assistant is enabled. 
    //Otherwise if quick assistant is not enabled, don't show the AI menu. 

    if (!$target_input_field && !$quick_assistant_ai_agent) {
        return;
    }


?>

    <div id="<?php echo $quick_ai_assistant_container_id; ?>"
        class="quick-ai-assistant"
        data-quick-ai-assistant-list-source="<?php echo $list_source; ?>"
        data-quick-ai-assistant-is-decending-list="<?php echo $is_decending_list; ?>"
        data-quick-ai-assistant-target-input-field="<?php echo $target_input_field; ?>"
        data-quick-ai-assistant-content-source="<?php echo $content_source; ?>">

        <div class="dropdown">
            <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                <div class="ai-icon"><span>AI</span></div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">

                <?php if ($target_input_field) { ?>
                    <?php if ($quick_assistant_ai_agent) { ?>
                        <li>
                            <a href="#" class="dropdown-item quick-ai-assistant-action hide" data-action="improve">
                                <i data-feather="thumbs-up" class="icon-16 mr5"></i>
                                <?php echo app_lang('ai_improve') ?>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="dropdown-item quick-ai-assistant-action hide" data-action="improve_selection">
                                <i data-feather="thumbs-up" class="icon-16 mr5"></i>
                                <?php echo app_lang('ai_improve_selection') ?>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="dropdown-item quick-ai-assistant-action" data-action="enter_custom_prompt">
                                <i data-feather="edit-3" class="icon-16 mr5"></i>
                                <?php echo app_lang('custom_prompt') ?>
                            </a>
                        </li>

                        <?php
                    }

                    if ($ticket_assistant_ai_agents) {
                        foreach ($ticket_assistant_ai_agents as $ai_agent) {
                        ?>
                            <li>
                                <a href="#" class="dropdown-item quick-ai-assistant-action" data-action="write_with_agent" data-agent-id="<?php echo $ai_agent->id; ?>">
                                    <i data-feather="box" class="icon-16 mr5"></i>
                                    <?php echo app_lang('write_with') . " " . $ai_agent->title; ?>
                                </a>
                            </li>
                    <?php }
                    } ?>

                    <script>
                        $(document).ready(function() {
                            var inputField = "<?php echo $target_input_field; ?>";
                            var quickAiAssistantButton = "#<?php echo $quick_ai_assistant_container_id; ?>";

                            $(inputField).attr("data-ai-context-menu-id", quickAiAssistantButton);

                            $(inputField).on("input change focus", function() {
                                showHideAIContextMenu(quickAiAssistantButton, "improve", $(this).val())
                            });

                            const handleSelection = function() {
                                const input = $(inputField)[0];
                                if (input.selectionStart !== undefined) { // Input/Textarea
                                    const start = input.selectionStart;
                                    const end = input.selectionEnd;
                                    const selectedText = input.value.substring(start, end).trim();

                                    if (selectedText) {
                                        window.aiAssistantSelection = {
                                            input: input,
                                            start: start,
                                            end: end,
                                            text: selectedText
                                        };

                                    } else {
                                        window.aiAssistantSelection = null;
                                    }

                                    showHideAIContextMenu(quickAiAssistantButton, "improve_selection", selectedText)
                                }
                            };

                            $(inputField).on('selectionchange', handleSelection);
                        });
                    </script>

                <?php } else if ($quick_assistant_ai_agent) { ?>

                    <li>
                        <a href="#" class="dropdown-item quick-ai-assistant-action" data-action="summarize">
                            <i data-feather="file-text" class="icon-16 mr5"></i>
                            <?php echo app_lang('ai_summarize') ?>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="dropdown-item quick-ai-assistant-action" data-action="describe">
                            <i data-feather="align-left" class="icon-16 mr5"></i>
                            <?php echo app_lang('ai_describe') ?>
                        </a>
                    </li>
                    <li>
                        <a href="#" class="dropdown-item quick-ai-assistant-action" data-action="key_points">
                            <i data-feather="list" class="icon-16 mr5"></i>
                            <?php echo app_lang('ai_key_points') ?>
                        </a>
                    </li>

                <?php
                } ?>
            </ul>
        </div>
    </div>

<?php } ?>