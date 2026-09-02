<?php if ($agents) { ?>
    <div id="js-chat-team-members-list">
        <?php
        foreach ($agents as $agent) {

            if ($agent->ai_service == 'chatgpt') {
                $agent_avatar = get_file_uri("assets/images/chatgpt-logo.png");
            } else if ($agent->ai_service == 'gemini') {
                $agent_avatar = get_file_uri("assets/images/gemini-logo.png");
            } else {
                $agent_avatar = get_file_uri("assets/images/favicon.png");
            }
        ?>
            <div class="message-row js-chat-row-of-ai-agent" data-id="<?php echo $agent->id; ?>" data-index="1" data-reply="">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <span class="avatar avatar-xs">
                            <img alt="..." src="<?php echo $agent_avatar; ?>">
                        </span>
                    </div>
                    <div class="w-100 ps-3">
                        <div class="mb5">
                            <strong> <?php echo $agent->title; ?></strong>
                        </div>
                        <div class="text-off d-block"><?php echo $agent->description; ?></div>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
<?php } else { ?>
    <div class="chat-no-messages text-off text-center">
        <i data-feather="frown" height="4rem" width="4rem"></i><br />
        <?php echo app_lang("no_ai_agents_found"); ?>
    </div>
<?php } ?>