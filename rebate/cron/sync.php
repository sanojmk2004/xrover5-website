<?php
// Lot sync from MT4/MT5 via MetaApi. Run from a cPanel cron job, e.g. every 30 minutes:
//   */30 * * * * /usr/local/bin/php /home/CPANELUSER/public_html/rebate/cron/sync.php >/dev/null 2>&1
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/inc/bootstrap.php';
require dirname(__DIR__) . '/inc/metaapi.php';

if (setting('lot_source', 'none') !== 'metaapi') { echo "Lot source is not MetaApi (Admin > Settings); skipping.\n"; exit(0); }
if (!ma_token()) { echo "MetaApi token not set; skipping.\n"; exit(0); }

$lock = fopen(sys_get_temp_dir() . '/xr5-rebate-sync.lock', 'c');
if (!flock($lock, LOCK_EX | LOCK_NB)) { echo "Already running.\n"; exit(0); }

$accs = rows("SELECT * FROM accounts WHERE status = 'verified' ORDER BY last_sync IS NOT NULL, last_sync ASC LIMIT 200");
foreach ($accs as $a) {
  $r = ma_sync_account($a);
  echo date('H:i:s') . "  #{$a['id']} {$a['platform']} {$a['login']}: $r\n";
}
