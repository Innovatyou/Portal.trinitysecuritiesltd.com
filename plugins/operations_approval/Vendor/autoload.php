<?php
defined('PLUGINPATH') or exit('No direct script access allowed');

/*
 * Manually vendored (no Composer at the app root - same pattern as
 * plugins/CustomersApi/Vendor) from setasign/fpdi v2.6.8, MIT licensed
 * (see setasign/fpdi/LICENSE.txt). Only setasign/fpdi itself is vendored -
 * setasign/fpdi-tcpdf turned out to be an empty metadata package; the
 * actual TCPDF bridge class (setasign\Fpdi\Tcpdf\Fpdi) lives inside FPDI
 * itself. tecnickcom/tcpdf is deliberately NOT vendored here - the app
 * already bundles its own copy (app/ThirdParty/tcpdf/tcpdf.php, loaded via
 * App\Libraries\Pdf), and loading a second copy would fatal on "Cannot
 * redeclare class TCPDF". require_once-ing it below first, before FPDI's
 * autoloader is even registered, is what makes the FPDI TCPDF bridge class
 * (`extends \TCPDF` internally) resolve against that single copy.
 */

if (!class_exists('TCPDF', false)) {
    require_once APPPATH . 'ThirdParty/tcpdf/tcpdf.php';
}

spl_autoload_register(function ($class) {
    $prefix = 'setasign\\Fpdi\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/setasign/fpdi/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
