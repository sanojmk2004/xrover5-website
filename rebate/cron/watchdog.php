<?php
// Emails the admin when the VPS lot sync stops contacting the website, and again when it is back.
// cPanel cron job, every 15 minutes:
//   */15 * * * * /usr/local/bin/php /home/CPANELUSER/public_html/rebate/cron/watchdog.php >/dev/null 2>&1
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/inc/bootstrap.php';
require dirname(__DIR__) . '/inc/vps_status.php';

set_setting('watchdog_last_run', now());
$st = vps_status();
$alerted = setting('vps_alert_sent');

if ($st['state'] === 'down' && !$alerted) {
  notify_admin('ALERT: XRover 5 VPS lot sync has stopped',
    "The VPS has not contacted the website for {$st['minutes']} minutes (last contact " . date('d M Y, H:i', strtotime((string) setting('vps_last_seen'))) . ").\n\n" .
    "Customers' lot sizes are not updating. Please:\n" .
    "1. Log in to the VPS with Remote Desktop.\n" .
    "2. Check the black 'XRover 5 lot sync' window is open. If not, double-click C:\\xr5sync\\start-sync.bat.\n" .
    "3. If the window shows errors, check C:\\xr5sync\\sync.log.\n\n" .
    "You'll get another email when it is running again.\n");
  set_setting('vps_alert_sent', now());
  echo "down - alert sent\n";
} elseif ($st['state'] === 'ok' && $alerted) {
  notify_admin('OK: XRover 5 VPS lot sync is running again',
    "The VPS lot sync is contacting the website again (" . $st['label'] . ").\n");
  set_setting('vps_alert_sent', '');
  echo "recovered - notice sent\n";
} else {
  echo $st['state'] . "\n";
}
