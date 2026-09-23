<style type="text/css">
    .post-feed-page {
        --feed-accent: var(--sb-navy, #071b35);
        --feed-border: #e7e9ee;
        --feed-muted: #6b7280;
        padding: 30px 15px 40px;
    }

    .post-feed {
        max-width: 1100px;
        margin: 0 auto;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 2px 14px rgba(15, 23, 42, .06);
        overflow: hidden;
    }

    .post-feed .feed-section {
        padding: 30px 36px;
        border-bottom: 1px solid var(--feed-border);
    }

    .post-feed .feed-avatar {
        width: 70px;
        height: 70px;
        flex-shrink: 0;
    }

    .post-feed .post-avatar {
        width: 58px;
        height: 58px;
    }

    .post-feed .feed-avatar img,
    .post-feed .post-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        box-shadow: 0 0 0 4px #f1f2f6;
    }

    .post-feed .feed-welcome {
        font-size: 16px;
        margin-bottom: 2px;
    }

    .post-feed .feed-date {
        font-size: 26px;
        font-weight: 700;
        margin: 0;
        line-height: 1.25;
    }

    .post-feed .btn-feed-accent {
        background: var(--feed-accent);
        border-color: var(--feed-accent);
        color: #fff;
        border-radius: 12px;
        padding: 7px 15px;
    }

    .post-feed .btn-feed-accent:hover,
    .post-feed .btn-feed-accent:focus {
        opacity: .9;
        color: #fff;
    }

    .post-feed .feed-settings {
        color: #1f2937;
    }

    /* the timeline line/date bubbles aren't used in the feed layout */
    .post-feed #timeline:before,
    .post-feed #timeline .post-content .post-date {
        display: none;
    }

    .post-feed #timeline .post-content {
        width: 100%;
        padding: 0 !important;
        margin: 0;
    }

    .post-feed #timeline .post-content > .post > .card {
        margin: 0 !important;
        border: 0;
        border-bottom: 1px solid var(--feed-border);
        border-radius: 0;
        box-shadow: none;
    }

    .post-feed #timeline .post-content > .post > .card > .card-body {
        padding: 30px 36px;
    }

    .post-feed .load-more {
        margin-bottom: 20px;
    }

    .post-feed .feed-empty {
        padding: 50px 36px;
        color: var(--feed-muted);
    }

    @media (max-width: 640px) {
        .post-feed-page {
            padding: 12px 0 30px;
        }

        .post-feed .feed-section,
        .post-feed #timeline .post-content > .post > .card > .card-body {
            padding: 20px 16px;
        }

        .post-feed .feed-avatar {
            width: 52px;
            height: 52px;
        }

        .post-feed .feed-date {
            font-size: 19px;
        }
    }
</style>

<div id="timeline-content" class="post-feed-page">
    <div class="post-feed">
        <div class="feed-section d-flex align-items-center">
            <span class="feed-avatar me-3">
                <img src="<?php echo get_avatar($login_user->image); ?>" alt="..." />
            </span>
            <div class="flex-grow-1 min-w-0">
                <div class="feed-welcome"><?php echo sprintf(app_lang("welcome_user"), $login_user->first_name . " " . $login_user->last_name); ?></div>
                <h2 class="feed-date"><?php echo get_my_local_time("l jS, F Y"); ?></h2>
            </div>
            <div class="flex-shrink-0 d-flex align-items-center ms-2">
                <?php echo anchor(get_uri("dashboard"), app_lang("dashboard"), array("class" => "btn btn-feed-accent")); ?>
                <?php echo anchor(get_uri("team_members/view/" . $login_user->id . "/account"), "<i data-feather='settings' class='icon-18'></i>", array("class" => "feed-settings ms-3", "title" => app_lang("settings"))); ?>
            </div>
        </div>

        <div class="feed-section">
            <?php echo view("timeline/post_form"); ?>
        </div>

        <?php echo timeline_widget(array("limit" => 20, "offset" => 0, "is_first_load" => true)); ?>

        <div class="feed-empty text-center hide"><?php echo app_lang("no_posts_yet"); ?></div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        window.togglePostFeedEmptyMessage = function () {
            $(".post-feed .feed-empty").toggleClass("hide", $("#timeline").children().length > 0);
        };
        togglePostFeedEmptyMessage();

        //auto load more posts when scrolled to the bottom of the page
        //the page scrolls inside .main-scrollable-page, but the window scrolls on small screens
        var loadMorePostsOnScroll = function (scrollTop, visibleHeight, totalHeight) {
            if (scrollTop + visibleHeight >= totalHeight - 150) {
                var $loadMore = $(".post-feed .load-more");
                if ($loadMore.length && !$loadMore.hasClass("inline-loading")) {
                    $loadMore.trigger("click");
                }
            }
        };

        $(".main-scrollable-page").on("scroll", function () {
            loadMorePostsOnScroll(this.scrollTop, this.clientHeight, this.scrollHeight);
        });

        $(window).on("scroll", function () {
            loadMorePostsOnScroll($(window).scrollTop(), $(window).height(), $(document).height());
        });
    });
</script>
