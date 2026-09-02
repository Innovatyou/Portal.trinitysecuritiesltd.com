<div class="chat-topbar box">
    <h4 class="strong chat-topbar-title"><?php echo app_lang("ai_assistant"); ?></h4>

    <?php echo view("messages/chat/chat_header_actions"); ?>
</div>

<div class="rise-ai-chat-body clearfix full-height">
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane fade" id="ai-recent-chats-tab">
            <?php echo view("ai_client/chat/chat_list", array("chats" => $chats)); ?>
        </div>
        <div role="tabpanel" class="tab-pane fade" id="ai-agents-tab"></div>
    </div>
</div>

<div class="rise-chat-footer footer-buttons-section">
    <div class="chat-tab" data-bs-toggle="ajax-tab" role="tablist">
        <li class="box-content" id="ai-recent-chats-tab-button">
            <a role="presentation" href="#" data-bs-toggle="tab" data-bs-target="#ai-recent-chats-tab" class="btn btn-default chat-button">
                <div><i data-feather="message-square" class="icon"></i></div>
                <span class="chat-tab-text"><?php echo app_lang("recent_chats"); ?></span>
            </a>
        </li>
        <li class="box-content" id="ai-agents-tab-button">
            <a role="presentation" href="<?php echo_uri("ai_client/get_chatbox_agents"); ?>" data-bs-toggle="tab" data-bs-target="#ai-agents-tab" class="btn btn-default chat-button">
                <div><i data-feather="users" class="icon"></i></div>
                <span class="chat-tab-text"><?php echo app_lang("ai_agents"); ?></span>
            </a>
        </li>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        function updateAIAssistantTitle() {
            var activeTabText = $("#js-rise-ai-assistant-wrapper .chat-tab .active .chat-tab-text").text();
            if (!activeTabText) {
                activeTabText = $("#js-rise-ai-assistant-wrapper .chat-tab a:first .chat-tab-text").text();
            }
            $("#js-rise-ai-assistant-wrapper .chat-topbar-title").text(activeTabText);
        }

        updateAIAssistantTitle();

        $("#js-rise-ai-assistant-wrapper .chat-tab a").on("click", function() {
            updateAIAssistantTitle();
        });

        //drag and drop
        makeDraggable("#js-rise-ai-assistant-wrapper .chat-topbar", "#js-rise-ai-assistant-wrapper", async function(pos) {
            var currentDimensions = await IDBHelper.getValue('ai_assistant_window_dimensions') || {};

            await IDBHelper.setValue('ai_assistant_window_dimensions', {
                ...currentDimensions,
                top: pos.target.offset().top,
                left: pos.target.offset().left
            });
        });
    });
</script>