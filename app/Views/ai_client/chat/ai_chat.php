<?php
$chat_id = isset($chat_info->id) ? $chat_info->id : 0;

$agent_avatar = get_file_uri("assets/images/favicon.png");

if ($ai_agent_info->ai_service === 'chatgpt') {
    $agent_avatar = get_file_uri("assets/images/chatgpt-logo.png");
} elseif ($ai_agent_info->ai_service === 'gemini') {
    $agent_avatar = get_file_uri("assets/images/gemini-logo.png");
}
?>

<div class="rise-chat-header">
    <div class="chat-back chat-topbar-btn" id="js-back-to-ai-chat-tabs">
        <i data-feather="chevron-left" class="icon-22"></i>
    </div>
    <div class="chat-title">
        <div>
            <?php
            echo "<span class='avatar avatar-xs mr10'><img src='$agent_avatar' /></span>";
            echo "<span>$ai_agent_info->title</span>";
            ?>
        </div>
    </div>

    <?php echo view("messages/chat/chat_header_actions"); ?>
</div>

<div class="rise-ai-chat-body clearfix">
    <div id="js-ai-chat-messages-container" class="clearfix"></div>
    <div id="js-ai-chat-reply-indicator"></div>
</div>

<div class="rise-chat-footer">
    <div id="send-message-to-agent-form-dropzone" class="post-dropzone">
        <?php echo form_open("", array("id" => "send-message-to-agent-form", "class" => "general-form", "role" => "form")); ?>

        <?php echo view("includes/dropzone_preview"); ?>

        <input type="hidden" name="ai_agent_id" value="<?php echo $ai_agent_info->id; ?>">
        <input type="hidden" name="chat_id" value="<?php echo $chat_id; ?>">
        <input type="hidden" name="last_message_id" value="">

        <div class="chat-message-textarea">
            <?php
            echo form_textarea(array(
                "id" => "js-ai-chat-message-textarea",
                "name" => "message",
                "data-rule-required" => true,
                "autofocus" => true,
                "data-msg-required" => "",
                "placeholder" => app_lang('ask_anything')
            ));
            ?>
        </div>
        <div class="chat-button-section clearfix">
            <!-- <div class="ai-chat-file-upload-icon float-start">
                <?php
                echo view("includes/upload_button", array("upload_button_text" => ""));
                ?>
            </div> -->
            <span class="btn btn-default float-end round message-send-button"><i data-feather="send" class="icon"></i></span>
        </div>

        <?php echo form_close(); ?>
    </div>
</div>

<script type="text/javascript">
    var chatId = <?php echo isset($chat_info->id) ? $chat_info->id : 0; ?>;
    var type = "<?php echo $type; ?>";

    $(document).ready(function() {
        //if type is new, and no chat id then, append a welcome message
        if (type === "new" && !chatId) {
            var agentAvatar = "<?php echo $agent_avatar; ?>";

            $("#js-ai-chat-messages-container").append(
                '<div class="chat-other chat-row single-message mt-2 bot-message">' +
                '<div class="row">' +
                '<div class="col-md-12">' +

                '<div class="avatar-xs avatar mr10">' +
                '<img alt="..." src="' + agentAvatar + '" />' +
                '</div>' +

                '<div class="chat-msg js-chat-msg">' +
                '<div class="chat-msg-text">' +
                <?php echo json_encode(app_lang("initial_ai_chat_message")); ?> +
                '</div>' +
                '</div>' +

                '</div>' +
                '</div>' +
                '</div>'
            );
        }

        if (chatId) {
            loadAiChatMessages(1);
        }

        var aiRequestInProgress = false;

        function getAiAgentResponse() {
            if (aiRequestInProgress) {
                return;
            }

            aiRequestInProgress = true;

            var submitUrl = "<?php echo get_uri("ai_client/send_message_to_agent"); ?>";

            // Make streaming request using XMLHttpRequest
            var xhr = new XMLHttpRequest();
            xhr.open('POST', submitUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'text/event-stream');

            // Prepare form data
            var formData = new URLSearchParams();
            formData.append('ai_agent_id', $("#send-message-to-agent-form").find("input[name='ai_agent_id']").val());
            formData.append('chat_id', chatId);
            formData.append('last_message_id', ($("#js-ai-chat-messages-container .chat-msg").last().attr("data-message_id") || 0));
            formData.append('message', $("#js-ai-chat-message-textarea").val());

            // clear message input box and show loading indicator
            $("#js-ai-chat-message-textarea").val("");
            $("#send-message-to-agent-form").append('<div id="fast-loader" class="fast-line"></div>');
            $(".bot-message").remove();

            let buffer = '';
            var tempId = getRandomAlphabet(5);

            // on streaming response
            xhr.onprogress = function(e) {

                var response = xhr.responseText;
                var newData = response.substring(buffer.length); // Only process new data      
                var $responseContainer = $("#js-ai-chat-response-container-" + tempId);

                if (newData.startsWith('data: ')) {

                    // the sent message is saved in the database
                    var data = newData.substring(6);
                    var jsonData = JSON.parse(data);

                    // if this was a new chat, set chatId now
                    if (jsonData.chat_id && chatId === 0) {
                        chatId = jsonData.chat_id;
                        $("#send-message-to-agent-form").find("input[name='chat_id']").val(chatId);
                    }

                    // render the message
                    if (jsonData.data) {
                        renderAiChatMessages(jsonData.data, true);
                    }

                    if (jsonData.last_message_id) {
                        $responseContainer.attr("data-message_id", jsonData.last_message_id);
                    }
                }

                if (newData.startsWith('init_agent_response') || newData.startsWith('processing_tool_call')) {
                    aiRequestInProgress = false;

                    var processingText = "<?php echo app_lang("thinking"); ?>";
                    if (newData.startsWith('processing_tool_call')) {
                        processingText = "<?php echo app_lang("processing"); ?>";
                    }

                    var $jsAiProcessingText = $("#js-ai-processing-text-" + tempId);
                    if ($jsAiProcessingText.length) {
                        // update the processing text
                        $jsAiProcessingText.html(processingText);
                    } else {

                        // the agent response is starting
                        $("#send-message-to-agent-form #fast-loader").remove();

                        // Add thinking indicator
                        var agentAvater = "<?php echo $ai_agent_info->ai_service == 'chatgpt' ? get_file_uri("assets/images/chatgpt-logo.png") : ($ai_agent_info->ai_service == 'gemini' ? get_file_uri("assets/images/gemini-logo.png") : get_file_uri("assets/images/favicon.png")); ?>";
                        var typingIndicator = "<div class='chat-other single-message'>" +
                            "<div class='row'>" +
                            "<div class='col-md-12'>" +
                            "<div class='avatar-xs avatar mr10'>" +
                            "<img src='" + agentAvater + "' alt='Agent Avatar'>" +
                            "</div>" +
                            "<div class='chat-msg' id='js-ai-chat-response-container-" + tempId + "'>" +
                            "<span class='processing-text' id='js-ai-processing-text-" + tempId + "'>" + processingText + "</span>" +
                            "<span class='typing-indicator'><span></span><span></span><span></span></span>" +
                            "</div>" +
                            "</div>" +
                            "</div>" +
                            "</div>";

                        $("#js-ai-chat-messages-container").append(typingIndicator);

                    }
                }

                // getting the response

                buffer = response;
                var lines = newData.split('\n\n');
                if ($responseContainer.length < 1) {
                    return; // the response container is not found
                }

                for (var line of lines) {
                    if (line.startsWith('response: ') || line.startsWith('error: ')) {
                        try {

                            // Remove processing and typing indicator
                            $responseContainer.find(".typing-indicator, .processing-text").remove();

                            if (line.startsWith('response: ')) {
                                var data = JSON.parse(line.substring(10));

                                if (data.content) {

                                    // Apply nl2br equivalent to preserve line breaks
                                    var processedContent = data.content.replace(/\n/g, '<br>');

                                    $responseContainer.append(processedContent);
                                }
                            } else {
                                // Handle error case
                                var data = line.substring(7);
                                $responseContainer.append('<div class="text-danger">' + data + '</div>');
                            }
                        } catch (e) {
                            console.error('Error parsing SSE data:', e);
                        }
                    }
                }

                // Auto-scroll to bottom
                aiChatScrollToBottom();
            };

            xhr.onload = function() {
                var $responseContainer = $("#js-ai-chat-response-container-" + tempId);

                // Reset request in progress flag
                aiRequestInProgress = false;

                if (xhr.status >= 200 && xhr.status < 300) {
                    console.log('Stream complete');
                } else {
                    console.error('Request failed with status:', xhr.status);
                    $responseContainer.append('<div class="error">Error: ' + (xhr.statusText || 'Failed to get response') + '</div>');
                }
            };

            xhr.onerror = function() {
                // Reset request in progress flag
                aiRequestInProgress = false;

                console.error('Request failed');
            };

            // Send the request
            xhr.send(formData.toString());
        }

        // Enter to send
        $("#js-ai-chat-message-textarea").keypress(function(e) {
            if (e.keyCode === 13 && !e.shiftKey) {
                getAiAgentResponse();
                $(this).attr("style", "");
                return false;
            }
        });

        $("#js-rise-ai-assistant-wrapper .message-send-button").off("click.aiChat").on("click.aiChat", function() {
            getAiAgentResponse();
        });

        $("#js-back-to-ai-chat-tabs").click(function() {
            loadAiAssistantTabs();
        });

        makeDraggable("#js-rise-ai-assistant-wrapper .rise-chat-header", "#js-rise-ai-assistant-wrapper", async function(pos) {
            var currentDimensions = await IDBHelper.getValue('ai_assistant_window_dimensions') || {};

            await IDBHelper.setValue('ai_assistant_window_dimensions', {
                ...currentDimensions,
                top: pos.target.offset().top,
                left: pos.target.offset().left
            });
        });
    });

    function loadAiChatMessages(firstLoad) {
        if (!chatId) {
            return;
        }

        // checkNewMessagesAutomatically();
        appAjaxRequest({
            url: "<?php echo get_uri('ai_client/view_ai_chat'); ?>",
            type: "POST",
            data: {
                chat_id: chatId,
                last_message_id: $("#js-ai-chat-messages-container .chat-msg").last().attr("data-message_id") || 0,
                is_first_load: firstLoad
            },
            success: function(response) {
                if (response) {
                    renderAiChatMessages(response, false);
                }
            }
        });
    }

    function renderAiChatMessages(html, isMe = true) {
        appendAiChatMessage(html, isMe);
        aiChatScrollToBottom();
    }

    function appendAiChatMessage(html, isMe = true) {
        if (!html || $.trim(html) === "") return;

        var container = $("#js-ai-chat-messages-container");

        var newMessage = $(html);
        var messageId = newMessage.find(".chat-msg").attr("data-message_id");

        if (messageId) {
            var exists = container.find('.chat-msg[data-message_id="' + messageId + '"]');

            if (exists.length) {
                return;
            }
        }

        container.append(newMessage);
    }

    function aiChatScrollToBottom() {
        if ($("#js-ai-chat-message-textarea").is(":focus")) {
            $("#js-rise-ai-assistant-wrapper .rise-ai-chat-body").animate({
                scrollTop: 10000000
            }, 100);
        }
    }
</script>