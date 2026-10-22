<?php
// Endpoint used by the VPS lot-sync script (rebate/vps/xr5_lot_sync.py).
//   GET  ?action=jobs&broker_id=N   -> verified MT5 accounts of that broker, with investor password, to read
//   POST ?action=report  (JSON)     -> {account_id, from_day, days: {"Y-m-d": {lots, deals}}}  or  {account_id, error}
// Auth: header "X-Sync-Token: <token>" — generated in Admin > Settings; only its hash is stored.
require dirname(__DIR__) . '/inc/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(int $code, array $data): never { http_response_code($code); echo json_encode($data); exit; }

$hash = (string) setting('vps_token_hash', '');
$given = (string)($_SERVER['HTTP_X_SYNC_TOKEN'] ?? '');
if ($hash === '' || $given === '' || !hash_equals($hash, hash('sha256', $given))) { usleep(500000); out(401, ['error' => 'bad token']); }
if (setting('lot_source', 'none') !== 'vps') out(409, ['error' => 'Lot source is not set to VPS in Admin > Settings']);
set_setting('vps_last_seen', now());

$action = $_GET['action'] ?? '';

if ($action === 'jobs') {
  $bid = (int)($_GET['broker_id'] ?? 0);
  $list = [];
  foreach (rows("SELECT * FROM accounts WHERE status = 'verified' AND platform = 'MT5' AND broker_id = ? ORDER BY last_sync IS NOT NULL, last_sync ASC LIMIT 500", [$bid]) as $a) {
    $pw = decrypt_secret((string)$a['investor_enc']);
    if ($pw === null) { q('UPDATE accounts SET sync_error = ? WHERE id = ?', ['Saved password unreadable — please re-enter investor password.', $a['id']]); continue; }
    $list[] = ['account_id' => (int)$a['id'], 'login' => (int)$a['login'], 'server' => $a['server'], 'password' => $pw, 'from_day' => sync_from_day($a)];
  }
  out(200, ['accounts' => $list]);
}

if ($action === 'report' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
  $in = json_decode((string) file_get_contents('php://input'), true);
  if (!is_array($in)) out(400, ['error' => 'bad json']);
  $id = (int)($in['account_id'] ?? 0);
  $acc = row("SELECT * FROM accounts WHERE id = ? AND status = 'verified' AND platform = 'MT5'", [$id]);
  if (!$acc) out(404, ['error' => 'unknown account']);
  if (!empty($in['error'])) {
    q('UPDATE accounts SET sync_error = ?, last_sync = ? WHERE id = ?', [mb_substr((string)$in['error'], 0, 250), now(), $id]);
    out(200, ['ok' => true]);
  }
  $fromDay = (string)($in['from_day'] ?? '');
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDay)) out(400, ['error' => 'bad from_day']);
  $days = [];
  foreach ((array)($in['days'] ?? []) as $d => $v) {
    if (is_array($v)) $days[$d] = ['lots' => max(0, (float)($v['lots'] ?? 0)), 'deals' => max(0, (int)($v['deals'] ?? 0))];
  }
  save_lot_days($id, $fromDay, $days);
  out(200, ['ok' => true, 'days' => count($days)]);
}

out(400, ['error' => 'unknown action']);
