<?php
// XRover 5 IB Rebate — shared setup loaded by every page.
declare(strict_types=1);

// Secrets live OUTSIDE public_html so deploy.sh never overwrites them and they are never served.
// Default location: /home/<cpaneluser>/rebate-config.php  (public_html/rebate/inc -> up 3 levels)
$cfgFile = getenv('REBATE_CONFIG') ?: dirname(__DIR__, 3) . '/rebate-config.php';
if (!is_file($cfgFile)) {
  http_response_code(500);
  exit('Rebate portal is not configured yet. Copy rebate/inc/config.sample.php to ' . htmlspecialchars($cfgFile) . ' and fill it in.');
}
$CFG = require $cfgFile;

date_default_timezone_set($CFG['timezone'] ?? 'Asia/Kolkata');
define('BASE', rtrim($CFG['base_path'] ?? '/rebate', '/'));

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/crypto.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/layout.php';

if (PHP_SAPI !== 'cli') {
  $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443;
  session_name('xr5rebate');
  session_set_cookie_params(['lifetime' => 0, 'path' => BASE . '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
  session_start();
  header('X-Frame-Options: DENY');
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: strict-origin-when-cross-origin');
}
