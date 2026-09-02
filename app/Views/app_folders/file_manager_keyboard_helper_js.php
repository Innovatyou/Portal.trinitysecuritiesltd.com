<script type="text/javascript">
    $(document).ready(function() {
        // File Manager Keyboard Handling
        var ITEM_SELECTOR = '.files-and-folders-list > li.folder-item';
        var CONTENT_SELECTOR = '.folder-item-content';
        var SELECTED_CLASS = 'selected-folder-item';

        // Helpers
        function getItems() {
            return $(ITEM_SELECTOR);
        }

        function getSelectedItems() {
            return getItems().find('.' + SELECTED_CLASS).closest('li');
        }

        // Last selected item is the active one
        function getActiveItem() {
            var $selected = getSelectedItems();
            return $selected.length ? $selected.last() : $();
        }

        function getActiveItemDetails($li) {
            var type = $li.data('type');

            if (type === 'folder') {
                return {
                    type: 'folder',
                    name: $.trim($li.find('.folder-name').first().text()),
                    info: $.trim($li.find('.folder-info').first().text())
                };
            }

            return {
                type: 'file',
                name: $.trim($li.find('.file-name').first().text()),
                info: $.trim($li.find('.file-info').first().text())
            };
        }

        function setActive($li) {
            getItems().find(CONTENT_SELECTOR).removeClass(SELECTED_CLASS);
            $li.find(CONTENT_SELECTOR).addClass(SELECTED_CLASS);
            scrollIntoView($li);
        }

        function scrollIntoView($li) {
            if ($li.length) {
                $li[0].scrollIntoView({
                    block: 'nearest'
                });
            }
        }

        // Navigation
        function moveUp() {
            moveBy(-1);
        }

        function moveDown() {
            moveBy(1);
        }

        function moveBy(step) {
            var $items = getItems();
            var $active = getActiveItem();
            var $next;

            if (!$active.length) {
                $next = $items.first();
                setActive($next);
                showActiveItemDetails($next);
                return;
            }

            var index = $items.index($active);
            var nextIndex = index + step;

            if (nextIndex < 0 || nextIndex >= $items.length) {
                return;
            }

            $next = $items.eq(nextIndex);
            setActive($next);
            showActiveItemDetails($next);
        }

        function showActiveItemDetails($li) {
            if (!$li || !$li.length) return;

            var data = $li.data();

            window.isDoubleClick = false;
            $("#file-details-box").removeClass("hide");

            getItemDetails(data.type, data.id);

            if ($(window).width() < 576) {
                $("#file-manager-items-box").addClass("hide");
                $("#file-details-box").addClass("d-block").addClass("w-100");
            }
        }

        // Open active item (double click behavior)
        function openActiveItem() {
            var $active = getActiveItem();
            if (!$active.length) return;

            var data = $active.data();

            // simulate double click behavior
            window.isDoubleClick = true;

            if (data.type === "folder") {
                openFolderWindow(data.folder_id);
            } else if (data.type === "file") {
                var $button = $active.find("a");
                var buttonData = $button.data();

                if (buttonData && buttonData.preview_function && typeof window[buttonData.preview_function] === "function") {
                    window[buttonData.preview_function]($button);
                }
            }
        }

        // Go back to parent folder
        function goBack() {
            var $breadcrumbs = $('.breadcrumb-folder-item');

            $breadcrumbs.trigger('click');
        }

        function deleteActiveItem() {
            var $active = getActiveItem();

            if (!$active.length) {
                return;
            }

            updateDeleteConfirmationModal($active);

            var id = $active.data('id');
            var type = $active.data('type');

            if (!id || !type) {
                return;
            }

            var actionUrl = type === 'folder' ? '<?php echo get_uri($controller_slag . "/delete_folder"); ?>' : '<?php echo get_uri($controller_slag . "/delete_folder_file"); ?>';

            // Create a temporary delete-confirmation link
            var $tempLink = $('<a>', {
                href: '#',
                'data-id': id,
                'data-action': 'delete-confirmation',
                'data-action-url': actionUrl,
                'data-reload-on-success': 1
            });

            // Append, trigger click, then remove
            $('body').append($tempLink);
            $tempLink.trigger('click');
            $tempLink.remove();
        }

        function updateDeleteConfirmationModal($li) {
            if (!$li || !$li.length) {
                return;
            }

            var details = getActiveItemDetails($li);
            var html = '';

            if (details.type === 'folder') {
                html =
                    "<div class='mt15'>" +
                    "<div class='d-flex'>" +
                    "<div class='flex-shrink-0 me-3 icon-wrapper'>" +
                    "<i data-feather='folder' class='icon-40 bold-folder-icon'></i>" +
                    "</div>" +
                    "<div class='w-100'>" +
                    "<div>" + details.name + "</div>" +
                    "<small class='text-off'>" + details.info + "</small>" +
                    "</div>" +
                    "</div>" +
                    "</div>";

                $("#confirmationModalContent .container-fluid").html(
                    '<?php echo app_lang("folder_delete_confirmation_message"); ?>' + html
                );
            } else {
                html =
                    "<div class='mt15'>" +
                    "<div class='d-flex'>" +
                    "<div class='flex-shrink-0 me-3 icon-wrapper'>" +
                    "<i data-feather='file' class='icon-40 bold-file-icon'></i>" +
                    "</div>" +
                    "<div class='w-100'>" +
                    "<div>" + details.name + "</div>" +
                    "<small class='text-off'>" + details.info + "</small>" +
                    "</div>" +
                    "</div>" +
                    "</div>";

                $("#confirmationModalContent .container-fluid").html(
                    '<?php echo app_lang("file_delete_confirmation_message"); ?>' + html
                );
            }

            feather.replace();
        }

        // Keyboard Events
        $(document).on('keydown', function(e) {

            // If delete confirmation modal is open
            if ($('#confirmationModal').hasClass('show')) {

                // Enter - confirm delete
                if (e.keyCode === 13) {
                    e.preventDefault();
                    $('#confirmDeleteButton').trigger('click');
                    return;
                }

                return;
            }

            // Do NOT handle shortcuts when:
            // - any modal is open
            // - typing in input / textarea
            // - file manager is not visible
            if ($('.modal.show').length || $(e.target).is('input, textarea') || !$('.files-and-folders-list').is(':visible')) {
                return;
            }

            // prevent backspace navigation
            if (e.keyCode === 8) {
                e.preventDefault();
                goBack();
                return;
            }

            var keyMap = {
                38: moveUp,
                40: moveDown,
                13: openActiveItem,
                46: deleteActiveItem
            };

            if (keyMap[e.keyCode]) {
                e.preventDefault();
                keyMap[e.keyCode]();
            }
        });

        // Mouse click sync
        $(document).on('click', ITEM_SELECTOR, function() {
            setActive($(this));
        });
    });
</script>