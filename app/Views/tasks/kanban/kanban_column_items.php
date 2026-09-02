<?php

$show_in_kanban = get_setting("show_in_kanban");
$show_in_kanban_items = explode(',', $show_in_kanban);
foreach ($items as $task) {
    echo view("tasks/kanban/task_card", array("task" => $task, "show_in_kanban_items" => $show_in_kanban_items));
}
