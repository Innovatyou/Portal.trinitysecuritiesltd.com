<?php if ($show_ticket_timer) { ?>
    <div class="card">
        <div class="card-body">
            <div class="col-md-12 mb15">
                <?php
                if ($show_ticket_timer) {
                    echo view("timesheets/timer_button", array("context" => "ticket", "context_id" => $ticket_info->id, "timer_status" => $timer_status, "disable_timer" => $disable_timer));
                }
                ?>
            </div>
            <div class="col-md-12">
                <strong><?php echo app_lang("total_time_logged") . ": "; ?></strong>
                <?php
                echo ajax_anchor(get_uri("timesheets/ticket_timesheet/" . $ticket_info->id), $total_ticket_hours, array("data-real-target" => "#ticket-timesheet", "class" => "strong"));
                ?>
            </div>
            <div class="col-md-12">
                <div id="ticket-timesheet"></div>
            </div>
        </div>
    </div>
<?php } ?>