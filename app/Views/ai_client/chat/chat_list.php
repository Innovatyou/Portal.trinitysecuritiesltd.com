<?php
if ($chats) {
    foreach ($chats as $chat) {
        if ($chat->ai_agent_service == 'chatgpt') {
            $agent_avatar = get_file_uri("assets/images/chatgpt-logo.png");
        } else if ($chat->ai_agent_service == 'gemini') {
            $agent_avatar = get_file_uri("assets/images/gemini-logo.png");
        } else {
            $agent_avatar = get_file_uri("assets/images/favicon.png");
        }
?>
        <div class='js-ai-chat-row message-row' data-id='<?php echo $chat->id; ?>' data-index='<?php echo $chat->id; ?>'>
            <div class="d-flex">
                <div class='flex-shrink-0 mt5'>
                    <span class='avatar avatar-xs'>
                        <img src='<?php echo $agent_avatar; ?>' />
                    </span>
                </div>
                <div class='w-100 pl15'>
                    <div class='mb5'>
                        <strong><?php echo $chat->ai_agent_name; ?></strong>
                        <span class='text-off float-end time'><?php echo format_to_relative_time($chat->created_at); ?></span>
                    </div>
                    <?php echo $chat->title; ?>
                </div>
            </div>
        </div>

<?php
    }
}
?>

<script>
    $(document).ready(function() {
        //trigger the ai agents list tab if there is no chats
        <?php if (!$chats) { ?>
            if ($("#ai-agents-tab-button").length) {
                $("#ai-agents-tab-button a").trigger("click");
                $("#ai-recent-chats-tab-button").addClass("hide");
            } else {
                $("#ai-recent-chats-tab-button a").trigger("click");
            }
        <?php } ?>
    });
</script>