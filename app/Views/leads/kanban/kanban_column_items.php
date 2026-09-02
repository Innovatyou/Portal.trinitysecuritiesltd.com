<?php
foreach ($items as $lead) {

    $source = "";
    if ($lead->lead_source_title) {
        $source = "<div class='float-start'><i data-feather='anchor' class='icon-14 text-off mr5'></i> " . $lead->lead_source_title . "</div>";
    }

    $owner = "";
    if ($lead->owner_id) {
        $owner = "<div class='float-end'><span class='avatar float-none' data-bs-toggle='tooltip' title='" . $lead->owner_name . "'><img src='" . get_avatar($lead->owner_avatar) . "' class='me-0'></span></div>";
    }

    $lead_labels = "";
    $lead_labels_data = make_labels_view_data($lead->labels_list);
    if ($lead_labels_data) {
        $lead_labels .= "<div class='meta mr5'>$lead_labels_data</div>";
    }

    $leads_total_counts = "<div class='mt10 float-end'>";

    if (!$lead_labels) {
        $leads_total_counts = "<div class='float-start'>";
    }

    //total contacts
    if ($lead->total_contacts_count) {
        $leads_total_counts .= "<span class='mr5' title='" . app_lang("contacts") . "'><i data-feather='users' class='icon-14 text-off'></i> " . $lead->total_contacts_count . "</span> ";
    }

    //total events
    if ($lead->total_events_count) {
        $leads_total_counts .= "<span class='mr5' title='" . app_lang("events") . "'><i data-feather='calendar' class='icon-14 text-off'></i> " . $lead->total_events_count . "</span> ";
    }

    //total notes
    if ($lead->total_notes_count) {
        $leads_total_counts .= "<span class='mr5' title='" . app_lang("notes") . "'><i data-feather='book' class='icon-14 text-off'></i> " . $lead->total_notes_count . "</span> ";
    }

    //total estimates
    if ($lead->total_estimates_count) {
        $leads_total_counts .= "<span class='mr5' title='" . app_lang("estimates") . "'><i data-feather='file' class='icon-14 text-off'></i> " . $lead->total_estimates_count . "</span> ";
    }

    //total estimate requests
    if ($lead->total_estimate_requests_count) {
        $leads_total_counts .= "<span class='mr5' title='" . app_lang("estimate_requests") . "'><i data-feather='file' class='icon-14 text-off'></i> " . $lead->total_estimate_requests_count . "</span> ";
    }

    //total files
    if ($lead->total_files_count) {
        $leads_total_counts .= "<span class='mr5' title='" . app_lang("files") . "'><i data-feather='file-text' class='icon-14 text-off'></i> " . $lead->total_files_count . "</span> ";
    }

    $leads_total_counts .= "</div>";

    $open_in_new_tab = anchor(get_uri("leads/view/" . $lead->id), "<i data-feather='external-link' class='icon-14'></i>", array("target" => "_blank", "class" => "float-end", "title" => app_lang("details")));

    $make_client = modal_anchor(get_uri("leads/make_client_modal_form/") . $lead->id, "<i data-feather='briefcase' class='icon-14'></i>", array("title" => app_lang('make_client'), "class" => "float-end mr10"));

    //custom fields to show in kanban
    $kanban_custom_fields_data = "";
    $kanban_custom_fields = get_custom_variables_data("leads", $lead->id, $login_user->is_admin);
    if (!empty($kanban_custom_fields)) {
        foreach ($kanban_custom_fields as $kanban_custom_field) {
            $type = get_array_value($kanban_custom_field, "custom_field_type");

            if (!$type) {
                continue;
            }

            $kanban_custom_fields_data .= "<br /><small>" . get_array_value($kanban_custom_field, "custom_field_title") . ": " . view("custom_fields/output_" . $type, array("value" => get_array_value($kanban_custom_field, "value"))) . "</small>";
        }
    }

    echo "<span class='lead-kanban-item kanban-item' data-id='$lead->id' data-sort='$lead->new_sort' data-post-id='$lead->id' data-status_id='$lead->lead_status_id'>
                    <div class='selection-pe-none'><span class='avatar'><img src='" . get_avatar($lead->primary_contact_avatar) . "'></span>" . anchor(get_uri("leads/view/" . $lead->id), $lead->company_name) . $open_in_new_tab . $make_client . "</div><div class='clearfix'></div>" .
        "<div class='mt15'>" . $source . $owner . "</div>" . $kanban_custom_fields_data . "<div class='clearfix'></div>" .
        $leads_total_counts . $lead_labels . "</span>";
}
