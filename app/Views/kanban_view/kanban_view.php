<?php
$kanbanPermissions = $extra_view_data["kanban_permissions"] ?? [];
$canDragKanbanItems = $kanbanPermissions["can_drag_items"] ?? false;
?>

<div id="kanban-wrapper">
    <ul id="kanban-container" class="kanban-container clearfix">

        <?php foreach ($columns as $column) { ?>
            <li class="kanban-col kanban-<?php echo $column->id; ?> <?php echo (in_array($column->id, $kanban_collapsed_columns)) ? 'kanban-col-collapsed' : ''; ?>" data-column_id="<?php echo $column->id; ?>">
                <?php
                $items_count = get_array_value($column_counts, $column->id);
                if (!$items_count) {
                    $items_count = 0;
                }

                $column_items = get_array_value($items, $column->id);
                if (!$column_items) {
                    $column_items = array();
                }

                $border_direction = in_array($column->id, $kanban_collapsed_columns) ? "border-right" : "border-bottom";
                $border_color = $column->color ? $column->color : "#2e4053";
                ?>
                <div class="kanban-col-title" style="<?php echo $border_direction; ?>: 3px solid <?php echo $border_color; ?>;">
                    <span class="status-title"><?php echo $column->title; ?></span>
                    <span class="collapse-kanban-column"><i data-feather="arrow-left" class="icon-16"></i></span>
                    <span class="kanban-item-count <?php echo $column->id; ?>-item-count"><?php echo $items_count; ?> </span>
                </div>

                <div class="kanban-input general-form hide">
                    <?php
                    echo form_input(array(
                        "id" => "title",
                        "name" => "title",
                        "value" => "",
                        "class" => "form-control",
                        "placeholder" => app_lang('add_a_lead')
                    ));
                    ?>
                </div>

                <div id="kanban-item-list-<?php echo $column->id; ?>" class="kanban-item-list <?php echo (in_array($column->id, $kanban_collapsed_columns)) ? 'hide' : ''; ?>" data-status_id="<?php echo $column->id; ?>">
                    <?php
                    $extra_view_data = $extra_view_data ?? [];
                    $column_extra_data = get_array_value($extra_view_data, $column->id, []);
                    $column_view_data = array_merge(["items" => $column_items], is_array($column_extra_data) ? $column_extra_data : []);

                    echo view("{$controller_slag}/kanban/kanban_column_items", $column_view_data);
                    ?>
                </div>
            </li>
        <?php } ?>

    </ul>
</div>

<img id="move-icon" class="hide" src="<?php echo get_file_uri("assets/images/move.png"); ?>" alt="..." />

<script type="text/javascript">
    var kanbanContainerWidth = "";

    adjustViewHeightWidth = function() {

        if (!$("#kanban-container").length) {
            return false;
        }

        var totalColumns = "<?php echo $total_columns ?>";
        var columnWidth = (335 * totalColumns) + 5;
        if (isMobile()) {
            columnWidth = (230 * totalColumns) + 5;
        }

        if (columnWidth > kanbanContainerWidth) {
            $("#kanban-container").css({
                width: columnWidth + "px"
            });
        } else {
            $("#kanban-container").css({
                width: "100%"
            });
        }

        //set wrapper scroll
        if ($("#kanban-wrapper")[0].offsetWidth < $("#kanban-wrapper")[0].scrollWidth) {
            $("#kanban-wrapper").css("overflow-x", "scroll");
        } else {
            $("#kanban-wrapper").css("overflow-x", "hidden");
        }

        //set column scroll
        var columnHeight = $(window).height() - $(".kanban-item-list").offset().top - 57;
        if (isMobile()) {
            columnHeight = $(window).height() - 30;
        }

        $(".kanban-item-list").height(columnHeight);

        $(".kanban-item-list").each(function(index) {

            //set scrollbar on column... if requred
            if ($(this)[0].offsetHeight < $(this)[0].scrollHeight) {
                $(this).css("overflow-y", "scroll");
            } else {
                $(this).css("overflow-y", "hidden");
            }

        });
    };


    saveStatusAndSort = function($item, status) {
        appLoader.show();
        adjustViewHeightWidth();

        var $prev = $item.prev(),
            $next = $item.next(),
            prevSort = 0,
            nextSort = 0,
            newSort = 0,
            step = 100000,
            stepDiff = 500,
            id = $item.attr("data-id");

        if ($prev && $prev.attr("data-sort")) {
            prevSort = $prev.attr("data-sort") * 1;
        }

        if ($next && $next.attr("data-sort")) {
            nextSort = $next.attr("data-sort") * 1;
        }

        if (!prevSort && nextSort) {
            //item moved at the top
            newSort = nextSort - stepDiff;

        } else if (!nextSort && prevSort) {
            //item moved at the bottom
            newSort = prevSort + step;

        } else if (prevSort && nextSort) {
            //item moved inside two items
            newSort = (prevSort + nextSort) / 2;

        } else if (!prevSort && !nextSort) {
            //It's the first item of this column
            newSort = step * 100; //set a big value for 1st item
        }

        $item.attr("data-sort", newSort);

        appAjaxRequest({
            url: '<?php echo_uri("{$controller_slag}/save_sort_and_status") ?>',
            type: "POST",
            data: {
                id: id,
                sort: newSort,
                status_id: status
            },
            success: function() {
                appLoader.hide();

                if (isMobile()) {
                    adjustViewHeightWidth();
                }
            }
        });

    };

    setLoadmoreButton = function() {
        $(".kanban-item-count").each(function() {
            var count = parseInt($(this).text(), 10);
            var $list = $(this).closest(".kanban-col").find(".kanban-item-list");
            var itemCount = $list.children(".kanban-item").length;

            if (itemCount < count) {
                $list.addClass("js-load-more-on-scroll");
            } else {
                $list.removeClass("js-load-more-on-scroll");
            }
        });
    };

    $(document).ready(function() {
        kanbanContainerWidth = $("#kanban-container").width();

        if (isMobile() && window.scrollToKanbanContent) {
            window.scrollTo(0, 220); //scroll to the content for mobile devices
            window.scrollToKanbanContent = false;
        }

        var isChrome = !!window.chrome && !!window.chrome.webstore;

        <?php if ($canDragKanbanItems) { ?>
            $(".kanban-item-list").each(function(index) {
                var id = this.id;

                var options = {
                    animation: 150,
                    group: "kanban-item-list",
                    filter: ".disable-dragging",
                    cancel: ".disable-dragging",
                    onAdd: function(e, x) {
                        //moved to another column. update bothe sort and status
                        var status_id = $(e.item).closest(".kanban-item-list").attr("data-status_id");
                        saveStatusAndSort($(e.item), status_id);

                        var $countContainer = $("." + status_id + "-item-count");
                        $countContainer.html($countContainer.html().trim() * 1 + 1);
                        var $item = $(e.item);
                        setTimeout(function() {
                            $item.attr("data-status_id", status_id); //update status id in data.
                        });
                    },
                    onRemove: function(e, x) {
                        var status_id = $(e.item)[0].dataset.status_id;
                        var $countContainer = $("." + status_id + "-item-count");
                        $countContainer.html($countContainer.html().trim() * 1 - 1);
                    },
                    onUpdate: function(e) {
                        //updated sort
                        saveStatusAndSort($(e.item));
                    }
                };

                if (isMobile()) {
                    options.handle = '.avatar';
                }

                //apply only on chrome because this feature is not working perfectly in other browsers.
                if (isChrome) {
                    options.setData = function(dataTransfer, dragEl) {
                        var img = document.createElement("img");
                        img.src = $("#move-icon").attr("src");
                        img.style.opacity = 1;
                        dataTransfer.setDragImage(img, 5, 10);
                    };

                    options.ghostClass = "kanban-sortable-ghost";
                    options.chosenClass = "kanban-sortable-chosen";
                }

                Sortable.create($("#" + id)[0], options);
            });
        <?php } ?>

        adjustViewHeightWidth();
        setLoadmoreButton();

        $('[data-bs-toggle="tooltip"]').tooltip();

        $(".kanban-item-list").scroll(function() {
            var $instance = $(this);
            var status_id = $instance.data("status_id");
            if ($instance.hasClass("js-load-more-on-scroll")) {
                var scrollTop = $instance.scrollTop();
                var columnHeight = $instance.get(0).scrollHeight - $instance.height();

                if (scrollTop > columnHeight - 200) {
                    //load more item once the scroll reach at to bootom (200px above)

                    if (!$instance.find(".kanban-item-loading").length) {

                        $instance.append("<div class='text-center kanban-item-loading'><div class='inline-loader' ></div></div>");

                        var $lastChild = $instance.find(".kanban-item").last();

                        var filterParams = window.InstanceCollection["kanban-filters"].filterParams;

                        var postData = $.extend({}, filterParams);
                        postData.max_sort = $lastChild.data("sort") || 0;
                        postData.kanban_column_id = status_id;

                        appAjaxRequest({
                            url: window.InstanceCollection["kanban-filters"].source,
                            type: 'POST',
                            data: postData,
                            success: function(response) {
                                $instance.find(".kanban-item-loading").remove();
                                $instance.append(response);
                                setLoadmoreButton();
                            }
                        });
                    }
                }
            }
        });

        // Collapse kanban column
        $(".collapse-kanban-column").click(function(e) {
            e.stopPropagation(); // Prevent triggering parent click

            var $column = $(this).closest(".kanban-col");

            appAjaxRequest({
                url: '<?php echo_uri("{$controller_slag}/collapse_kanban_column") ?>',
                type: "POST",
                data: {
                    column_id: $column.data("column_id"),
                    collapse: 1
                },
                success: function() {
                    $column.find(".kanban-item-list").addClass("hide");
                    $column.addClass("kanban-col-collapsed");

                    var $title = $column.find(".kanban-col-title");
                    var borderBottom = $title.css("border-bottom");
                    $title.css("border-bottom", "");
                    $title.css("border-right", borderBottom);
                }
            });
        });

        // Expand kanban column
        $(document).on("click", ".kanban-col-collapsed", function(e) {
            // Prevent collapse icon inside from triggering again
            if ($(e.target).closest(".collapse-kanban-column").length) {
                return;
            }

            var $column = $(this).closest(".kanban-col");

            appAjaxRequest({
                url: '<?php echo_uri("{$controller_slag}/collapse_kanban_column") ?>',
                type: "POST",
                data: {
                    column_id: $column.data("column_id"),
                    collapse: 0
                },
                success: function() {
                    $column.find(".kanban-item-list").removeClass("hide");
                    $column.removeClass("kanban-col-collapsed");
                    $column.find(".kanban-item-list").removeClass("h-auto");
                    $column.find(".kanban-item-list").addClass("overflow-y-scroll");

                    var $title = $column.find(".kanban-col-title");
                    var borderBottom = $title.css("border-right");
                    $title.css("border-right", "");
                    $title.css("border-bottom", borderBottom);
                }
            });
        });
    });

    $(window).resize(function() {
        adjustViewHeightWidth();
    });
</script>