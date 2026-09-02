<?php
$width_class = "";
if ($context === "ticket") {
    $width_class = "w-100";
}

if ($disable_timer) {
    $start_timer = js_anchor("<i data-feather='clock' class='icon-16 mr5'></i> " . app_lang('start_timer'), array('title' => app_lang('start_timer'), "class" => "btn btn-success disabled " . $width_class, "disabled" => "true", "data-action-url" => get_uri("timesheets/timer/" . $context_id . "/start"), "data-reload-on-success" => "1"));
} else {
    $start_timer = ajax_anchor(get_uri("timesheets/timer/" . $context_id . "/start"), "<i data-feather='clock' class='icon-16 mr5'></i> " . app_lang('start_timer'), array("class" => "btn btn-success " . $width_class, "id" => "start_timer", "title" => app_lang('start_timer'), "data-reload-on-success" => "1", "data-post-context" => $context, "data-post-context_id" => $context_id));
}

$stop_timer = modal_anchor(get_uri("timesheets/stop_timer_modal_form/" . $context_id), "<i data-feather='clock' class='icon-16 mr5'></i> " . app_lang('stop_timer'), array("class" => "btn btn-danger " . $width_class, "title" => app_lang('stop_timer'), "data-post-context" => $context, "data-post-context_id" => $context_id));

if ($timer_status === "open") {
    echo $stop_timer;
} else {
    echo $start_timer;
}
