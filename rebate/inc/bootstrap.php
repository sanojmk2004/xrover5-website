<?php
// XRover 5 IB Rebate — shared setup loaded by every page.
declare(strict_types=1);

// Secrets live OUTSIDE public_html so deploy.sh never overwrites them and they are never served.
// Default location: /home/<cpaneluser>/rebate-config.php  (public_html/rebate/inc -> up 3 levels)
$cfgFile = getenv('REBATE_CONFIG') ?: dirname(__DIR__, 3) . '/rebate-config.php';
if (!is_file($cfgFile)) {
  http_response_code(500);
  // Setup steps are in the repo README (rebate-config.php goes one level above public_html).
  exit(PHP_SAPI === 'cli' ? "Missing config: $cfgFile\n" : '<!doctype html><meta name="viewport" content="width=device-width"><body style="background:#0a0b12;color:#eceef6;font-family:system-ui;display:grid;place-items:center;min-height:90vh;margin:0"><div style="text-align:center;padding:16px"><h1>XRover 5 IB Rebates</h1><p>Opening soon. <a style="color:#45cfff" href="/">Back to xrover5.com</a></p></div>');
}
$CFG = require $cfgFile;

// Any unexpected error: log it for the server error log and show a readable page instead of a blank 500.
set_exception_handler(function (Throwable $ex) {
  error_log('Rebate portal: ' . $ex);
  if (PHP_SAPI === 'cli') { fwrite(STDERR, $ex . "\n"); exit(1); }
  http_response_code(500);
  // Database errors show their short message (no file paths, no query values) so problems can be diagnosed.
  $hint = $ex instanceof PDOException ? ' Database error: ' . preg_replace('/\(using password:.*\)/', '', mb_substr($ex->getMessage(), 0, 300)) : '';
  echo '<!doctype html><meta name="viewport" content="width=device-width"><body style="background:#0a0b12;color:#eceef6;font-family:system-ui;padding:24px"><h1>Something went wrong</h1><p>Please try again in a minute.' . htmlspecialchars($hint) . '</p>';
});

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
