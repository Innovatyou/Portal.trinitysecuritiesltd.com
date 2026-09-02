<?php

$links = "";

$social_link_icons = array(
    "facebook"  => "facebook",
    "twitter"   => "twitter",
    "linkedin"  => "linkedin",
    "whatsapp"  => "phone",
    "youtube"   => "youtube",
    "instagram" => "instagram",
    "github"    => "github",
);

$social_link_svg_icons = array(
    "digg"      => "digg",
    "pinterest" => "pinterest",
    "tumblr"    => "tumblr",
    "vine"      => "vine",
);

if ($social_links && is_array($social_links)) {

    foreach ($social_links as $social_link) {

        $address = $social_link->social_link_url;

        if ($address && !preg_match('/^https?:\/\//i', $address)) {
            $address = "https://" . $address;
        }

        $type = strtolower($social_link->social_link_type);

        // Custom uploaded SVG
        if ($type === "custom") {

            if (!empty($social_link->icon_name)) {

                $svg_path = get_source_url_of_file(array("file_name" => $social_link->icon_name), get_setting("system_file_path") . "social/");
                $title = isset($social_link->title) ? $social_link->title : "";

                $links .= "<a target='_blank' href='{$address}' class='social-link custom-icon' title='" . esc($title) . "'>";
                $links .= "<img src='{$svg_path}' alt='" . esc($title) . "' class='icon-16'>";
                $links .= "</a>";
            }

            continue;
        }

        // Feather icon
        if (isset($social_link_icons[$type])) {

            $links .= "<a target='_blank' href='{$address}' class='social-link'>";
            $links .= "<i data-feather='{$social_link_icons[$type]}' class='icon-16 mt0'></i>";
            $links .= "</a>";

            continue;
        }

        // SVG view icon
        if (isset($social_link_svg_icons[$type])) {

            $links .= "<a target='_blank' href='{$address}' class='social-link custom-svg'>";
            $links .= view("users/svg_social_icons/" . $social_link_svg_icons[$type]);
            $links .= "</a>";

            continue;
        }
    }
}

echo $links;
