<?php if (get_setting("enable_footer")) { ?>

    <?php
    $footer_about = trim(get_setting("footer_about"));
    $footer_address_1 = trim(get_setting("footer_address_1"));
    $footer_address_2 = trim(get_setting("footer_address_2"));
    $footer_email_1 = trim(get_setting("footer_email_1"));
    $footer_email_2 = trim(get_setting("footer_email_2"));
    $footer_phone_1 = trim(get_setting("footer_phone_1"));
    $footer_phone_2 = trim(get_setting("footer_phone_2"));
    $footer_menus = unserialize(get_setting("footer_menus"));
    $footer_copyright = trim(get_setting("footer_copyright_text"));
    $footer_contact_info_link = trim(get_setting("footer_contact_info_link"));
    $footer_custom_column_title = trim(get_setting("footer_custom_column_title"));
    $footer_custom_column_content = trim(get_setting("footer_custom_column_content"));

    $footer_brand_profile = get_setting("footer_brand_profile");
    $has_buttons = $footer_menus && is_array($footer_menus) && count($footer_menus) > 0;
    $has_copyright = !empty($footer_copyright);
    $social_links = unserialize(get_setting("footer_social_links"));
    $quick_links = unserialize(get_setting("footer_quick_links"));
    $has_quick_links = get_setting("footer_quick_links_enabled") && $quick_links && is_array($quick_links) && count($quick_links) > 0;
    $has_custom_column = get_setting("footer_custom_column") && ($footer_custom_column_title || $footer_custom_column_content);
    $has_contact = get_setting("footer_contact_info") && ($footer_address_1 || $footer_address_2 || $footer_email_1 || $footer_email_2 || $footer_phone_1 || $footer_phone_2 || $footer_contact_info_link);
    $footer_grid_items_count = 0;

    if ($footer_brand_profile) {
        $footer_grid_items_count++;
    }

    if ($has_quick_links) {
        $footer_grid_items_count++;
    }

    if ($has_custom_column) {
        $footer_grid_items_count++;
    }

    if ($has_contact) {
        $footer_grid_items_count++;
    }

    $has_footer_grid = $footer_grid_items_count > 0;
    $has_footer_bottom = $has_copyright || $has_buttons;
    ?>

    <div class="footer">
        <div class="container">

            <?php if ($has_footer_grid) { ?>
                <div class="footer-grid footer-grid-<?php echo $footer_grid_items_count; ?> <?php echo $footer_brand_profile ? "footer-grid-with-brand" : "footer-grid-without-brand"; ?> footer-category">
                    <?php if ($footer_brand_profile) { ?>
                        <div class="footer-brand-profile-section">
                            <div class="max-w600">
                                <img class="max-height-width-logo mb10" src="<?php echo get_logo_url(); ?>" />
                                <p class="mb20"><?php echo custom_nl2br($footer_about); ?></p>
                                <div class="footer-social-links-section">
                                    <?php echo view("includes/social_links_widget", ["social_links" => $social_links]); ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($has_quick_links) { ?>
                        <div>
                            <div class="footer-category-label">
                                <p><?php echo app_lang("quick_links"); ?></p>
                            </div>
                            <div class="footer-links">
                                <?php foreach ($quick_links as $quick_link) { ?>
                                    <?php echo anchor($quick_link->link_url, $quick_link->link_name, 'class="d-block mb-1 footer-link"'); ?>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($has_custom_column) { ?>
                        <div>
                            <?php if ($footer_custom_column_title) { ?>
                                <div class="footer-category-label">
                                    <p><?php echo $footer_custom_column_title; ?></p>
                                </div>
                            <?php } ?>

                            <?php echo $footer_custom_column_content; ?>
                        </div>
                    <?php } ?>

                    <?php if ($has_contact) { ?>
                        <div class="footer-contact-info-section">
                            <div class="footer-category-label">
                                <p><?php echo app_lang("contact_us"); ?></p>
                            </div>

                            <?php if ($footer_address_1) { ?>
                                <div class="d-flex align-items-center mb10">
                                    <i data-feather="map-pin" class="icon-16 text-off me-2"></i>
                                    <span><?php echo custom_nl2br($footer_address_1); ?></span>
                                </div>
                            <?php } ?>

                            <?php if ($footer_address_2) { ?>
                                <div class="d-flex align-items-center mb10">
                                    <i data-feather="map-pin" class="icon-16 text-off me-2"></i>
                                    <span><?php echo custom_nl2br($footer_address_2); ?></span>
                                </div>
                            <?php } ?>

                            <?php if ($footer_email_1) { ?>
                                <div class="mb10">
                                    <i data-feather="mail" class="icon-16 text-off me-2"></i>
                                    <a href="mailto:<?php echo esc($footer_email_1); ?>">
                                        <?php echo esc($footer_email_1); ?>
                                    </a>
                                </div>
                            <?php } ?>

                            <?php if ($footer_email_2) { ?>
                                <div class="mb10">
                                    <i data-feather="mail" class="icon-16 text-off me-2"></i>
                                    <a href="mailto:<?php echo esc($footer_email_2); ?>">
                                        <?php echo esc($footer_email_2); ?>
                                    </a>
                                </div>
                            <?php } ?>

                            <?php if ($footer_phone_1) { ?>
                                <div class="mb10">
                                    <i data-feather="phone" class="icon-16 text-off me-2"></i>
                                    <a href="tel:<?php echo esc($footer_phone_1); ?>">
                                        <?php echo esc($footer_phone_1); ?>
                                    </a>
                                </div>
                            <?php } ?>

                            <?php if ($footer_phone_2) { ?>
                                <div class="mb10">
                                    <i data-feather="phone" class="icon-16 text-off me-2"></i>
                                    <a href="tel:<?php echo esc($footer_phone_2); ?>">
                                        <?php echo esc($footer_phone_2); ?>
                                    </a>
                                </div>
                            <?php } ?>

                            <?php if ($footer_contact_info_link) { ?>
                                <?php
                                $footer_contact_info_link_title = get_setting('footer_contact_info_link_title');
                                $link_text = $footer_contact_info_link_title ?: $footer_contact_info_link;
                                ?>
                                <div class="mb10">
                                    <i data-feather="link" class="icon-16 text-off me-2"></i>
                                    <a href="<?php echo esc($footer_contact_info_link); ?>" target="_blank" class="p0"><?php echo esc($link_text); ?></a>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>


            <?php if ($has_footer_bottom) { ?>
                <div class="d-flex justify-content-between py25 copyright-section <?php echo $has_footer_grid ? "b-t" : ""; ?>">
                    <?php if ($has_copyright) { ?>
                        <div>
                            <?php echo $footer_copyright; ?>
                        </div>
                    <?php } ?>

                    <?php if ($has_buttons) { ?>
                        <div>
                            <?php foreach ($footer_menus as $footer) { ?>
                                <?php echo anchor($footer->url, $footer->menu_name, 'class="me-3"'); ?>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
<?php } ?>