<?php

/**
 * get the defined config value by a key
 * @param string $key
 * @return config value
 */

function get_timezone_offset($date = "now") {
    $timeZone = new DateTimeZone("UTC");
    $dateTime = new DateTime($date, $timeZone);
    return $timeZone->getOffset($dateTime);
}

function get_current_utc_time($format = "Y-m-d H:i:s") {
    $d = DateTime::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:s"));
    $d->setTimeZone(new DateTimeZone("UTC"));
    return $d->format($format);
}

function get_my_local_time($format = "Y-m-d H:i:s") {
    return date($format, strtotime(get_current_utc_time()) + get_timezone_offset());
}