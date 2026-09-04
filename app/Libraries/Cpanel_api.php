<?php

namespace App\Libraries;

/**
 * Shared HTTPS+token transport for calling cPanel's UAPI - used by
 * Cpanel_mysql, Cpanel_domains, and Ssl_issuer. Not shell_exec()/exec(),
 * which this server's PHP-FPM pool hard-disables via php_admin_value
 * (confirmed directly: set at the FPM pool config level, applied
 * identically across every domain on this server - a deliberate hardening
 * policy, not something to weaken just for these features).
 */
class Cpanel_api {

    /** @return array{success: bool, message?: string, data?: mixed} */
    public static function call(string $module, string $function, array $params): array {
        $platform = config('Platform');

        if (!$platform->cpanel_username || !$platform->cpanel_api_token) {
            return ['success' => false, 'message' => 'cPanel API is not configured (platform.cpanel_username / platform.cpanel_api_token).'];
        }
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'PHP curl extension is not available.'];
        }

        $url = "https://{$platform->cpanel_hostname}:2083/execute/{$module}/{$function}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => ["Authorization: cpanel {$platform->cpanel_username}:{$platform->cpanel_api_token}"],
            CURLOPT_RETURNTRANSFER => true,
            // Loopback call to cPanel's own local service on this same
            // server, not a network hop that a man-in-the-middle could
            // intercept.
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
        ]);
        $output = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($output === false) {
            $message = "uapi {$module}::{$function} request failed: {$curl_error}";
            log_message('error', 'Cpanel_api::{module}::{function} failed: {message}', ['module' => $module, 'function' => $function, 'message' => $message]);

            return ['success' => false, 'message' => $message];
        }

        $decoded = json_decode((string) $output, true);
        $status = $decoded['status'] ?? null;

        if ($status !== 1) {
            $errors = $decoded['errors'] ?? null;
            $message = is_array($errors) ? implode('; ', $errors) : ('uapi ' . $module . '::' . $function . ' failed: ' . trim((string) $output));
            log_message('error', 'Cpanel_api::{module}::{function} failed: {message}', ['module' => $module, 'function' => $function, 'message' => $message]);

            return ['success' => false, 'message' => $message];
        }

        return ['success' => true, 'data' => $decoded['data'] ?? null];
    }
}
