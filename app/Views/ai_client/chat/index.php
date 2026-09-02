<script type="text/javascript">
    $(document).ready(function() {
        if (!$("#js-rise-ai-assistant-wrapper").length) {
            var $aiAssistantWrapper = '<div id="js-rise-ai-assistant-wrapper" class="rise-chat-wrapper hide"></div>';
            $('body').append($aiAssistantWrapper);
        }

        openAiChatBox = function() {
            $("#js-rise-ai-assistant-wrapper").removeClass("hide"); //show chat box
        };

        var isAiAssistantOpen = getCookie("ai_assistant_open"),
            activeAiChatId = getCookie("active_ai_chat_id");

        if (isAiAssistantOpen) {
            if (activeAiChatId && activeAiChatId !== "0") {
                openAiChatBox();
                getAiAgentChat("", activeAiChatId);
            } else {
                loadAiAssistantTabs();
            }
        }

        //make resizable
        makeResizable('#js-rise-ai-assistant-wrapper', {
            minWidth: 310,
            maxWidth: 800,
            minHeight: 400,
            maxHeight: $(window).height() - 100,
            handle: ['left', 'right', 'top', 'bottom'],
            onResize: function(wrapper) {
                adjustAiChatBodyHeight();

                IDBHelper.setValue('ai_assistant_window_dimensions', {
                    width: wrapper.width(),
                    height: wrapper.height(),
                    top: wrapper.offset().top,
                    left: wrapper.offset().left
                });
            }
        });

        //close chat box
        $(document).on('click', '#js-rise-ai-assistant-wrapper #chat-close-icon', function() {
            $("#js-rise-ai-assistant-wrapper").addClass("hide"); //hide chat box
            setCookie("ai_assistant_open", "");
            setCookie("active_ai_chat_id", "");
            feather.replace();
        });

        //reset ai assistant dimensions
        $(document).on('click', '#js-rise-ai-assistant-wrapper .reset-chat-dimension', function() {
            IDBHelper.setValue('ai_assistant_window_dimensions', null);
            $('#js-rise-ai-assistant-wrapper').removeAttr('style');
            $('#js-rise-ai-assistant-wrapper').removeClass('full-screen');
            $('#js-rise-ai-assistant-wrapper .chat-full-screen').removeClass('hide');
            $('#js-rise-ai-assistant-wrapper .chat-exit-full-screen').addClass('hide');

            adjustAiChatBodyHeight();
        });

        //ai assistant full screen
        $(document).on('click', '#js-rise-ai-assistant-wrapper .chat-full-screen', function() {
            enterAiAssistantFullScreen();
        });

        //ai assistant exit full screen
        $(document).on('click', '#js-rise-ai-assistant-wrapper .chat-exit-full-screen', function() {
            exitAiAssistantFullScreen();
        });

        $('body #js-rise-ai-assistant-wrapper').on('click', '.js-ai-chat-row', function() {
            getAiAgentChat("", $(this).attr("data-id"));
        });

        $('body #js-rise-ai-assistant-wrapper').on('click', '.js-chat-row-of-ai-agent', function() {
            getAiAgentChat("new", 0, $(this).attr("data-id"));
        });

        // Function to restore ai chat dimensions
        async function restoreAiAssistantDimensions() {
            var dimensions = await IDBHelper.getValue('ai_assistant_window_dimensions');

            if (dimensions) {
                var $chatWrapper = $('#js-rise-ai-assistant-wrapper');

                var headerHeight = $('.navbar').outerHeight() || 66;
                var sidebarWidth = $('.sidebar').outerWidth() || 70;

                var winWidth = $(window).width();
                var winHeight = $(window).height() - headerHeight;

                var chatBoxWidth = dimensions.width || 430;
                var chatBoxHeight = dimensions.height || "auto";
                var left = dimensions.left;
                var top = dimensions.top;

                // Clamp width/height to viewport
                if (chatBoxWidth > winWidth) chatBoxWidth = winWidth;
                if (chatBoxHeight > winHeight) chatBoxHeight = winHeight;

                // Clamp left/top so the box is visible
                if (left + chatBoxWidth > winWidth) left = winWidth - chatBoxWidth;
                if (top + chatBoxHeight > winHeight) top = winHeight - chatBoxHeight;

                if (left < sidebarWidth) left = sidebarWidth;
                if (top < headerHeight) top = headerHeight;

                $chatWrapper.css({
                    width: chatBoxWidth + 'px',
                    height: chatBoxHeight + 'px',
                    top: top + 'px',
                    left: left + 'px'
                });

                adjustAiChatBodyHeight();
            }
        }

        // Restore dimensions on page load
        restoreAiAssistantDimensions();
    });

    function enterAiAssistantFullScreen() {
        var $wrapper = $("#js-rise-ai-assistant-wrapper");
        $wrapper.addClass("full-screen");
        $wrapper.find(".chat-full-screen").addClass("hide");
        $wrapper.find(".chat-exit-full-screen").removeClass("hide");
        adjustAiChatBodyHeight();
    }

    function exitAiAssistantFullScreen() {
        var $wrapper = $("#js-rise-ai-assistant-wrapper");
        $wrapper.removeClass("full-screen");
        $wrapper.find(".chat-exit-full-screen").addClass("hide");
        $wrapper.find(".chat-full-screen").removeClass("hide");
        adjustAiChatBodyHeight();
    }

    function loadAiAssistantTabs(trigger_from_agent_chat) {
        openAiChatBox();
        setCookie("active_ai_chat_id", "");

        appLoader.show({
            container: "#js-rise-ai-assistant-wrapper",
            css: "bottom: 40%; right: 40%;"
        });

        appAjaxRequest({
            url: "<?php echo get_uri("ai_client/ai_chat_list"); ?>",
            success: function(response) {
                $("#js-rise-ai-assistant-wrapper").html(response);

                if (!trigger_from_agent_chat) {
                    $("#ai-recent-chats-tab-button a").trigger("click");
                } else if (trigger_from_agent_chat === "ai_agents") {
                    $("#ai-agents-tab-button a").trigger("click");
                }

                appLoader.hide();
            }
        });

        setTimeout(function() {
            adjustAiChatBodyHeight();

            //append resizable handles
            resizableHandles("#js-rise-ai-assistant-wrapper");
        }, 350);
    }

    function getAiAgentChat(type = "", ai_chat_id = 0, ai_agent_id = 0) {
        appLoader.show({
            container: "#js-rise-ai-assistant-wrapper",
            css: "bottom: 40%; right: 35%;"
        });

        appAjaxRequest({
            url: "<?php echo get_uri('ai_client/ai_chat'); ?>",
            type: "POST",
            data: {
                type: type,
                ai_chat_id: ai_chat_id,
                ai_agent_id: ai_agent_id
            },
            success: function(response) {

                if (response == "access_denied") {
                    appAlert.error("<?php echo app_lang('access_denied'); ?>");
                    appLoader.hide();
                    return;
                }

                $("#js-rise-ai-assistant-wrapper").html(response);
                appLoader.hide();
                setCookie("active_ai_chat_id", ai_chat_id);
                $("#js-ai-chat-message-textarea").focus();

                //append resizable handles
                setTimeout(function() {
                    adjustAiChatBodyHeight();

                    resizableHandles("#js-rise-ai-assistant-wrapper");
                }, 200);
            }
        });
    }

    // Adjust inner chat body dynamically
    function adjustAiChatBodyHeight() {
        var aiChatBoxHeight = $('#js-rise-ai-assistant-wrapper');
        var aiHeaderHeight = aiChatBoxHeight.find('.rise-chat-header, .chat-topbar').outerHeight() || 60;
        var aiFooterHeight = aiChatBoxHeight.find('.rise-chat-footer').outerHeight() || 77;

        aiChatBoxHeight.find('.rise-ai-chat-body').height(aiChatBoxHeight.height() - (aiHeaderHeight + aiFooterHeight));
    }
</script>