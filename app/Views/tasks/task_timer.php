<?php
$context = $model_info->context ?? "";
$context_id = 0;

if ($context === "project") {
    $context_id = $model_info->project_id ?? 0;
} else if ($context === "client") {
    $context_id = $model_info->client_id ?? 0;
} else if ($context === "lead") {
    $context_id = $model_info->lead_id ?? 0;
} else if ($context === "invoice") {
    $context_id = $model_info->invoice_id ?? 0;
} else if ($context === "estimate") {
    $context_id = $model_info->estimate_id ?? 0;
} else if ($context === "order") {
    $context_id = $model_info->order_id ?? 0;
} else if ($context === "contract") {
    $context_id = $model_info->contract_id ?? 0;
} else if ($context === "proposal") {
    $context_id = $model_info->proposal_id ?? 0;
} else if ($context === "subscription") {
    $context_id = $model_info->subscription_id ?? 0;
} else if ($context === "ticket") {
    $context_id = $model_info->ticket_id ?? 0;
} else if ($context === "expense") {
    $context_id = $model_info->expense_id ?? 0;
}

$is_general_context = ($context === "general");
$has_context = ($context && $context_id);

$disabled_message = $is_general_context ? app_lang('timer_not_available_for_personal_tasks') : "";

if($context === "project") {
    $timer_context = "project";
} else {
    $timer_context = "general_task";
}

$common_attrs = array(
    "data-post-task_id" => $model_info->id,
    "data-post-context" => $timer_context,
    "data-post-context_id" => $context_id
);

if ($disable_timer || !$has_context || $is_general_context) {
    $start_timer = "<span class='d-inline-block' tabindex='0' data-bs-toggle='tooltip' title='$disabled_message'>"
        . ajax_anchor("", "<i data-feather='clock' class='icon-16 mr5'></i> " . app_lang('start_timer'), array_merge($common_attrs, array("class" => "btn btn-success disabled", "disabled" => "true")))
        . "</span>";
} else {
    $start_timer = ajax_anchor(get_uri("timesheets/timer/" . $context_id . "/start"), "<i data-feather='clock' class='icon-16 mr5'></i> " . app_lang('start_timer'), array_merge($common_attrs, array("class" => "btn btn-success", "title" => app_lang('start_timer'), "data-real-target" => "#start-timer-btn-$model_info->id", "data-post-task_timer" => true)));
}

$stop_timer = modal_anchor(get_uri("timesheets/stop_timer_modal_form/" . $context_id), "<i data-feather='clock' class='icon-16 mr5'></i> " . app_lang('stop_timer'), array_merge($common_attrs, array("class" => "btn btn-danger", "title" => app_lang('stop_timer'))));

if ($timer_status === "open") {
    echo $stop_timer;
} else {
    echo "<span id='start-timer-btn-$model_info->id'>$start_timer</span>";
}
?>

<script type="text/javascript">
    $(document).ready(function() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>