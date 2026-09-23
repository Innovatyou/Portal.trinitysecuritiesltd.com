<style type="text/css">
    #post-form-container #post_description {
        border: 2px solid var(--feed-accent, #071b35);
        border-radius: 12px;
        padding: 18px 24px;
        font-size: 18px;
        min-height: 84px;
        resize: vertical;
        box-shadow: none;
    }

    #post-form-container .post-tool-btn {
        height: 54px;
        min-width: 54px;
        border: 1px solid #d9dce3;
        border-radius: 10px;
        background: #fff;
        color: #1f2937;
        font-size: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 0 16px;
    }

    #post-form-container .post-tool-btn:hover,
    #post-form-container .post-tool-btn:focus {
        background: #f5f6f8;
    }

    #post-form-container .post-tool-btn.dropdown-toggle:after {
        display: none;
    }

    #post-form-container .post-share-with-btn {
        min-width: 185px;
        justify-content: space-between;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .12);
    }

    #post-form-container .emoji-picker {
        width: 290px;
        padding: 8px;
    }

    #post-form-container .emoji-picker .emoji-option {
        width: 34px;
        height: 34px;
        border: 0;
        background: transparent;
        border-radius: 6px;
        font-size: 20px;
        line-height: 1;
    }

    #post-form-container .emoji-picker .emoji-option:hover {
        background: #f1f2f6;
    }

    #post-form-container .post-submit-btn {
        height: 54px;
        border-radius: 10px;
        padding: 0 22px;
    }

    @media (max-width: 640px) {
        #post-form-container #post_description {
            font-size: 16px;
            padding: 12px 14px;
        }

        #post-form-container .post-tool-btn,
        #post-form-container .post-submit-btn {
            height: 44px;
            min-width: 44px;
            font-size: 16px;
        }

        #post-form-container .post-share-with-btn {
            min-width: 0;
        }
    }
</style>

<div id="post-form-container">
    <?php echo form_open(get_uri("timeline/save"), array("id" => "post-form", "class" => "general-form", "role" => "form")); ?>
    <div id="post-dropzone" class="post-dropzone">
        <input type="hidden" name="post_id" value="<?php echo isset($post_id) ? $post_id : 0; ?>">
        <input type="hidden" name="reload_list" value="1">
        <input type="hidden" name="share_with" id="post-share-with" value="all">

        <div class="form-group mb15">
            <?php
            echo form_textarea(array(
                "id" => "post_description",
                "name" => "description",
                "class" => "form-control",
                "placeholder" => app_lang('what_do_you_want_to_share'),
                "data-rule-required" => true,
                "data-msg-required" => app_lang("field_required"),
                "rows" => 2
            ));
            ?>
        </div>

        <?php echo view("includes/dropzone_preview"); ?>

        <div class="d-none">
            <?php echo view("includes/upload_button", array("hide_recording" => true)); ?>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
            <div class="dropdown">
                <button type="button" class="post-tool-btn post-share-with-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="d-inline-flex align-items-center gap-2">
                        <i data-feather="globe" class="icon-18 post-share-with-icon"></i>
                        <span class="post-share-with-text"><?php echo app_lang("public"); ?></span>
                    </span>
                    <i data-feather="chevron-down" class="icon-16"></i>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <li><a href="#" class="dropdown-item post-share-with-option" data-value="all" data-icon="globe"><?php echo app_lang("public"); ?></a></li>
                    <?php if (isset($shareable_teams) && count($shareable_teams)) { ?>
                        <li><h6 class="dropdown-header"><?php echo app_lang("team"); ?></h6></li>
                        <?php foreach ($shareable_teams as $team) { ?>
                            <li><a href="#" class="dropdown-item post-share-with-option" data-value="team:<?php echo $team->id; ?>" data-icon="users"><?php echo esc($team->title); ?></a></li>
                        <?php } ?>
                    <?php } ?>
                </ul>
            </div>

            <div class="dropdown">
                <button type="button" class="post-tool-btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="<?php echo app_lang("emoji"); ?>">
                    <i data-feather="smile" class="icon-18"></i>
                </button>
                <div class="dropdown-menu emoji-picker">
                    <?php
                    $emojis = array("😀", "😃", "😄", "😁", "😆", "😅", "😂", "🙂", "😉", "😊", "😍", "🥳", "😎", "🤔", "😮", "😢", "😭", "😡", "👍", "👎", "👏", "🙌", "🙏", "💪", "👋", "🤝", "❤️", "🔥", "🎉", "🎂", "✅", "⭐", "📌", "📢", "📅", "💡", "🚀", "💼", "☕", "🏆");
                    foreach ($emojis as $emoji) {
                        echo "<button type='button' class='emoji-option'>$emoji</button>";
                    }
                    ?>
                </div>
            </div>

            <button type="button" class="post-tool-btn post-add-file-btn" title="<?php echo app_lang("upload_file"); ?>">
                <i data-feather="image" class="icon-18"></i>
            </button>

            <button type="submit" class="submit-button btn btn-feed-accent post-submit-btn ms-auto"><i data-feather="send" class="icon-16"></i> <?php echo app_lang("post"); ?></button>
        </div>
    </div>
    <?php echo form_close(); ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {

        var $description = $("#post_description");

        $("#post-form").appForm({
            isModal: false,
            onSuccess: function (result) {
                if ($("body").hasClass("dropzone-disabled")) {
                    location.reload();
                } else {
                    $description.val("");
                    $("#timeline").prepend(result.data);
                    window.formDropzone["post-dropzone"].removeAllFiles();
                    if (window.togglePostFeedEmptyMessage) {
                        togglePostFeedEmptyMessage();
                    }
                }
            }
        });

        $(".post-share-with-option").on("click", function (e) {
            e.preventDefault();
            var $option = $(this);
            $("#post-share-with").val($option.attr("data-value"));
            $(".post-share-with-text").text($option.text());
            $(".post-share-with-icon").replaceWith('<i data-feather="' + $option.attr("data-icon") + '" class="icon-18 post-share-with-icon"></i>');
            feather.replace();
        });

        //insert the selected emoji at the cursor position
        $(".emoji-option").on("click", function () {
            var textarea = $description[0],
                    emoji = $(this).text(),
                    start = textarea.selectionStart,
                    end = textarea.selectionEnd,
                    value = textarea.value;

            textarea.value = value.substring(0, start) + emoji + value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
            textarea.focus();
        });

        //the image button opens the (hidden) dropzone upload button
        $(".post-add-file-btn").on("click", function () {
            $("#post-dropzone .upload-file-button")[0].click();
        });

    });
</script>
