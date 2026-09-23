<?php if ($single_post) { ?>
    <style type="text/css">
        #timeline-content {
            max-width: 700px;
            margin: auto;
        }

        #timeline:before {
            content: none;
        }

        #timeline .post-content {
            width: 100%;
            padding: 0 !important;
        }

        #timeline>.post-content:first-child {
            padding-top: 0 !important;
        }

        #timeline .post-content .post-date:before {
            content: none;
        }
    </style>
    <div class="box">
        <div class="box-content">
            <div id="timeline-content" class="page-wrapper clearfix mb20">
            <?php } ?>
            <?php
            if ($is_first_load) {
            ?>
                <style type="text/css">
                    #timeline .post-role-badge {
                        background: var(--sb-navy, #071b35);
                        color: #fff;
                        font-weight: 500;
                        font-size: 13px;
                        border-radius: 6px;
                        padding: 4px 9px;
                    }

                    #timeline .post-menu-toggle:after {
                        display: none;
                    }

                    #timeline .post-description {
                        font-size: 16px;
                        line-height: 1.65;
                    }
                </style>
            <?php
                echo "<div id='timeline'>";
            }

            foreach ($posts as $post) {
            ?>
                <div id="post-content-container-<?php echo $post->id; ?>" class="post-content">
                    <div class="post clearfix">
                        <?php if (!$single_post) { ?>
                            <div class="post-date clearfix">
                                <span><?php echo format_to_relative_time($post->created_at, true, true); ?></span>
                            </div>
                        <?php } ?>
                        <div class="card clearfix mt15">

                            <div class="card-body">
                                <?php
                                //show the role of the creator beside the name
                                $created_by_role = $post->created_by_is_admin ? app_lang("administrator") : ($post->created_by_role_title ? $post->created_by_role_title : $post->created_by_job_title);
                                if ($post->share_with_team) {
                                    $share_with_icon = "<i data-feather='users' class='icon-14'></i>";
                                    $share_with_title = app_lang("team") . ": " . $post->share_with_team;
                                } else {
                                    $share_with_icon = "<i data-feather='globe' class='icon-14'></i>";
                                    $share_with_title = app_lang("public");
                                }
                                ?>
                                <div class="clearfix mb15">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <span class="avatar avatar-md post-avatar">
                                                <img src="<?php echo get_avatar($post->created_by_avatar); ?>" alt="..." />
                                            </span>
                                        </div>
                                        <div class="w-100">
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <?php echo get_team_member_profile_link($post->created_by, $post->created_by_user, array("class" => "dark strong font-16")); ?>
                                                <?php if ($created_by_role) { ?>
                                                    <span class="badge post-role-badge"><?php echo esc($created_by_role); ?></span>
                                                <?php } ?>
                                            </div>
                                            <small class="text-off d-inline-flex align-items-center gap-1">
                                                <span><?php echo format_to_relative_time($post->created_at); ?></span>
                                                <span title="<?php echo esc($share_with_title); ?>"><?php echo $share_with_icon; ?></span>
                                            </small>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <span class="float-end dropdown">
                                                <div class="text-off dropdown-toggle post-menu-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="true">
                                                    <i data-feather="more-vertical" class="icon"></i>
                                                </div>
                                                <ul class="dropdown-menu dropdown-menu-end" role="menu">
                                                    <li role="presentation"><?php echo anchor(get_uri("timeline/post/$post->id"), "<i data-feather='external-link' class='icon-16'></i> " . app_lang('view'), array("class" => "dropdown-item", "title" => app_lang('view'))); ?></li>
                                                    <!--  only admin and creator can delete the post -->
                                                    <?php if ($login_user->is_admin || $post->created_by == $login_user->id) { ?>
                                                        <li role="presentation"><?php echo ajax_anchor(get_uri("timeline/delete/$post->id"), "<i data-feather='x' class='icon-16'></i> " . app_lang('delete'), array("class" => "dropdown-item", "title" => app_lang('delete'), "data-fade-out-on-success" => "#post-content-container-$post->id")); ?> </li>
                                                    <?php } ?>
                                                </ul>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <p class="post-description">
                                    <?php echo $post->description ? custom_nl2br(convert_comment_link(link_it(process_images_from_content($post->description)))) : ""; ?>
                                </p>

                                <?php
                                $files = unserialize($post->files);
                                $total_files = count($files);
                                echo view("includes/timeline_preview", array("files" => $files));
                                ?>

                                <div class="mb15 clearfix">
                                    <?php
                                    echo ajax_anchor(get_uri("timeline/post_reply_form/" . $post->id), "<i data-feather='corner-up-left' class='icon-16'></i> " . app_lang('reply'), array("data-real-target" => "#reply-form-container-" . $post->id, "class" => "dark"));
                                    ?>
                                    <?php
                                    $reply_caption = "";
                                    if ($post->total_replies == 1) {
                                        $reply_caption = app_lang("reply");
                                    } else if (($post->total_replies > 1)) {
                                        $reply_caption = app_lang("replies");
                                    }

                                    if ($reply_caption) {
                                        echo ajax_anchor(get_uri("timeline/view_post_replies/" . $post->id), "<i data-feather='message-circle' class='icon-16'></i> " . app_lang("view") . " " . $post->total_replies . " " . $reply_caption, array("class" => "btn btn-default btn-sm view-replies", "id" => "show-replies-button-$post->id", "data-remove-on-success" => "#show-replies-button-$post->id", "data-real-target" => "#reply-list-" . $post->id));
                                    }
                                    //create link for reply success. trigger this link after submit any reply
                                    echo ajax_anchor(get_uri("timeline/view_post_replies/" . $post->id), "", array("class" => "hide", "id" => "reload-reply-list-button-" . $post->id, "data-real-target" => "#reply-list-" . $post->id));

                                    if ($total_files) {
                                        $download_caption = app_lang('download');
                                        if ($total_files > 1) {
                                            $download_caption = sprintf(app_lang('download_files'), $total_files);
                                        }
                                        echo anchor(get_uri("timeline/download_files/" . $post->id), $download_caption, array("class" => "float-end", "title" => $download_caption));
                                    }
                                    ?>

                                </div>
                                <div id="reply-list-<?php echo $post->id; ?>"></div>
                                <div id="reply-form-container-<?php echo $post->id; ?>"></div>

                            </div>
                        </div>
                    </div>
                </div>
            <?php
            }
            ?>
            <?php if ($single_post === "single_post") { ?>
            </div>
        </div>
    </div>
<?php
            }
            if ($result_remaining > 0) {
                $next_container_id = "load" . $next_page_offset;
?>
    <div id="<?php echo $next_container_id; ?>">
        <div class="clearfix"></div>
    </div>

    <div id="loader-<?php echo $next_container_id; ?>">
        <div class="text-center ml30">
            <?php
                echo ajax_anchor(get_uri("timeline/load_more_posts/" . $next_page_offset), app_lang("load_more"), array("class" => "btn btn-default load-more mt15 p10 spinning-btn pr0", "data-remove-on-success" => "#loader-" . $next_container_id, "title" => app_lang("load_more"), "data-inline-loader" => "1", "data-real-target" => "#" . $next_container_id));
            ?>
        </div>
    </div>
<?php
            }
            if ($is_first_load) {
                echo "</div>";
            }
?>

<?php if ($single_post == "single_post") { ?>
    <script type="text/javascript">
        $(document).ready(function() {
            $(".view-replies").trigger("click");
        });
    </script>
<?php }; ?>