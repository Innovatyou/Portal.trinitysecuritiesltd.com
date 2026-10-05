<?php

namespace operations_approval\Libraries;

/**
 * Signing key for the mobile app's JWTs. Reuses the CustomersApi plugin's
 * key while it exists (so already-issued tokens stay valid), otherwise
 * falls back to - and on first use creates - this plugin's own key, so the
 * app keeps working with that plugin uninstalled.
 */
class Mobile_token_secret
{
    public static function get(): string
    {
        $db = db_connect('default');
        $table = $db->getPrefix() . 'settings';
        foreach (['customersapi_secret_key', 'operations_mobile_secret_key'] as $name) {
            $row = $db->table($table)->select('setting_value')->where(['setting_name' => $name, 'deleted' => 0])->get()->getRow();
            if (!empty($row->setting_value)) return (string) $row->setting_value;
        }
        $secret = bin2hex(random_bytes(32));
        // Upsert: a soft-deleted row with this name would otherwise block the
        // insert (unique key) and hand out a different key on every request.
        $db->query("INSERT INTO `$table` (`setting_name`,`setting_value`,`type`,`deleted`) VALUES ('operations_mobile_secret_key',?,'app',0) ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`),`deleted`=0", [$secret]);
        return $secret;
    }
}
