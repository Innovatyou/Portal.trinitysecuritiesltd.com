<?php
foreach ($replies as $index => $reply_info) {
    $prev = $replies[$index - 1] ?? null;
    $next = $replies[$index + 1] ?? null;

    echo view("ai_client/chat/single_message", array("reply_info" => $reply_info, "prev_info" => $prev, "next_info" => $next));
}
?>