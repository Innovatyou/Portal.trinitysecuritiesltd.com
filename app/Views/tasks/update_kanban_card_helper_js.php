<script type="text/javascript">
    function refreshKanbanCard(taskId, type = "") {
        if (type === "delete") {
            updateKanbanCard({
                task_id: taskId,
                type: "delete"
            });
            return;
        }

        var kanbanInstance = window.InstanceCollection['kanban-filters'];

        if (!kanbanInstance || !kanbanInstance.source) {
            return;
        }

        var url = kanbanInstance.source;
        var params = $.extend({}, kanbanInstance.filterParams || {}, {
            task_id: taskId
        });

        appAjaxRequest({
            url: url,
            type: "POST",
            dataType: "json",
            data: params,
            success: function(res) {
                if (!res.success || !res.kanban_card) {
                    // Task no longer matches current filters, remove the card from kanban
                    updateKanbanCard({
                        task_id: taskId,
                        type: "delete"
                    });
                }

                updateKanbanCard({
                    task_id: taskId,
                    kanban_card: res.kanban_card
                });
            }
        });
    }

    function updateKanbanCard(response) {
        if (!response.task_id) {
            return;
        }

        var taskId = response.task_id;
        var $oldCard = $(".kanban-item[data-id='" + taskId + "']");
        var oldStatusId = $oldCard.length ? $oldCard.data("status_id") : null;

        // Handle delete
        if (response.type === "delete") {
            if ($oldCard.length && oldStatusId) {
                $oldCard.remove();
                updateKanbanCount(oldStatusId, "decrement");
            }
            return;
        }

        if (!response.kanban_card) {
            return;
        }

        var $newCard = $(response.kanban_card);
        var newStatusId = $newCard.data("status_id");
        var newSort = parseInt($newCard.data("sort"), 10);

        // Handle new task
        var isNewTask = !$oldCard.length;
        if (isNewTask) {
            var $column = $(".kanban-col[data-column_id='" + newStatusId + "'] .kanban-item-list");
            if (!$column.length) {
                return;
            }

            var inserted = false;

            $column.children(".kanban-item").each(function() {
                var existingSort = parseInt($(this).data("sort"), 10);

                if (newSort < existingSort) {
                    $(this).before($newCard);
                    inserted = true;
                    return false;
                }
            });

            if (!inserted) {
                $column.append($newCard);
            }

            updateKanbanCount(newStatusId, "increment");
            feather.replace();
            return;
        }

        // Handle update (same status)
        if ($oldCard.length && oldStatusId === newStatusId) {
            $oldCard.replaceWith($newCard);
            feather.replace();
            return;
        }

        // Handle move (status changed)
        if (!$oldCard.length) {
            return;
        }

        var $newColumn = $(".kanban-col[data-column_id='" + newStatusId + "'] .kanban-item-list");
        if (!$newColumn.length) {
            return;
        }

        var inserted = false;
        $oldCard.remove();

        $newColumn.children(".kanban-item").each(function() {
            var existingSort = parseInt($(this).data("sort"), 10);

            if (newSort < existingSort) {
                $(this).before($newCard);
                inserted = true;
                return false;
            }
        });

        if (!inserted) {
            $newColumn.append($newCard);
        }

        updateKanbanCount(oldStatusId, "decrement");
        updateKanbanCount(newStatusId, "increment");

        feather.replace();
    }

    function updateKanbanCount(statusId, action) {
        var $countEl = $(".kanban-item-count." + statusId + "-item-count");
        if (!$countEl.length) {
            return;
        }

        var count = parseInt($.trim($countEl.text()), 10) || 0;

        if (action === "increment") {
            count++;
        } else if (action === "decrement") {
            count = Math.max(0, count - 1);
        }

        $countEl.text(count);
    }
</script>