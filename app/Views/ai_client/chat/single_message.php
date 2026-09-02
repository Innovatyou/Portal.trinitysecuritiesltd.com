<?php
$prev = $prev_info ?? null;
$next = $next_info ?? null;

$currSender = $reply_info->user_id ?? ($reply_info['user_id'] ?? null); // Get the sender ID of the current message
$prevSender = $prev->user_id ?? ($prev['user_id'] ?? null); // Get the sender ID of the previous message
$nextSender = $next->user_id ?? ($next['user_id'] ?? null); // Get the sender ID of the next message

// Determine if this is the first message in a group
// (True if there's no previous message or previous sender is different)
$isFirst = !$prev || $prevSender !== $currSender;

// Determine if this is the last message in a group
// (True if there's no next message or next sender is different)
$isLast  = !$next || $nextSender !== $currSender;

// - If both first and last => it's a standalone message
// - If only first         => it's the start of a group
// - If only last          => it's the end of a group
// - Else                  => it's in the middle of a group
if ($isFirst && $isLast) {
    $message_position_class = "single-message";
} elseif ($isFirst) {
    $message_position_class = "first-message";
} elseif ($isLast) {
    $message_position_class = "last-message";
} else {
    $message_position_class = "middle-message";
}

$message_class = "m-row-" . $reply_info->id;
if ($reply_info->user_id === $login_user->id) {
?>
    <div class="chat-me chat-row <?php echo $message_class . ' ' . $message_position_class; ?>">
        <div class="row">
            <div class="col-md-12">
                <div class="chat-msg js-chat-msg" data-message_id="<?php echo $reply_info->id; ?>">
                    <?php
                    echo custom_nl2br(link_it(process_images_from_content($reply_info->message)));
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php } else {
?>

    <div class="chat-other chat-row <?php echo $message_class . ' ' . $message_position_class; ?>">
        <div class="row">
            <div class="col-md-12">
                <div class="avatar-xs avatar mr10">
                    <?php
                    if ($reply_info->ai_agent_service == 'chatgpt') {
                        $agent_avatar = get_file_uri("assets/images/chatgpt-logo.png");
                    } else if ($reply_info->ai_agent_service == 'gemini') {
                        $agent_avatar = get_file_uri("assets/images/gemini-logo.png");
                    } else {
                        $agent_avatar = get_file_uri("assets/images/favicon.png");
                    }

                    echo "<img alt='...' src='" . $agent_avatar . "' />";
                    ?>
                </div>
                <div class="chat-msg js-chat-msg" data-message_id="<?php echo $reply_info->id ?>">
                    <?php
                    echo custom_nl2br(link_it(process_images_from_content($reply_info->message)));
                    ?>
                </div>
            </div>
        </div>
    </div>

<?php } ?>

<script class="temp-script33">
    //don't show duplicate messages
    $("<?php echo '.' . $message_class; ?>:first").nextAll("<?php echo '.' . $message_class; ?>").remove();
</script>