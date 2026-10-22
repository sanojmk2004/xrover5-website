<?php
// MetaApi (metaapi.cloud) connector: reads lots traded on a customer's MT4/MT5 account
// using the investor (read-only) password. Shared hosting cannot talk to MetaTrader servers
// directly, so MetaApi runs the terminal in the cloud and we read deal history over REST.
//
// Lifecycle per account (one step per cron run, so a run never waits long):
//   no metaapi_id  -> create cloud account with investor password (+ deploy)
//   UNDEPLOYED     -> deploy
//   DEPLOYED & CONNECTED -> read deals, store lots per day, optionally undeploy to save cost
// Docs: https://metaapi.cloud/docs/provisioning/  and  https://metaapi.cloud/docs/client/

define('MA_PROV', $GLOBALS['CFG']['metaapi_provisioning_url'] ?? 'https://mt-provisioning-api-v1.agiliumtrade.agiliumtrade.ai');

function ma_token(): ?string {
  $enc = setting('metaapi_token_enc');
  return $enc ? decrypt_secret($enc) : null;
}
function ma_region(): string { return preg_replace('/[^a-z0-9-]/', '', (string)setting('metaapi_region', 'new-york')) ?: 'new-york'; }
function ma_client(): string { return $GLOBALS['CFG']['metaapi_client_url'] ?? ('https://mt-client-api-v1.' . ma_region() . '.agiliumtrade.ai'); }

/** @return array{0:int,1:mixed} [http status, decoded body] */
function ma_http(string $method, string $url, ?array $body = null, array $extraHeaders = []): array {
  $token = ma_token();
  if (!$token) throw new RuntimeException('MetaApi token not set (Admin → Settings).');
  $ch = curl_init($url);
  $headers = array_merge(['auth-token: ' . $token, 'Accept: application/json'], $extraHeaders);
  if ($body !== null) { $headers[] = 'Content-Type: application/json'; curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); }
  curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_TIMEOUT => 40, CURLOPT_CONNECTTIMEOUT => 10]);
  $raw = curl_exec($ch);
  if ($raw === false) { $err = curl_error($ch); curl_close($ch); throw new RuntimeException('Network error: ' . $err); }
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$code, $raw === '' ? null : json_decode($raw, true)];
}
function ma_err($body, int $code): string {
  $m = is_array($body) ? ($body['message'] ?? $body['error'] ?? '') : '';
  return mb_substr("MetaApi $code" . ($m ? ": $m" : ''), 0, 250);
}

function ma_set(int $id, array $fields): void {
  $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($fields)));
  q("UPDATE accounts SET $sets WHERE id = ?", [...array_values($fields), $id]);
}

/** Runs one lifecycle step for an account. Returns a short status string for logs. */
function ma_sync_account(array $acc): string {
  $id = (int)$acc['id'];
  try {
    // 1) Create the cloud account
    if ($acc['metaapi_id'] === '') {
      $pass = decrypt_secret((string)$acc['investor_enc']);
      if ($pass === null) { ma_set($id, ['sync_error' => 'Saved password unreadable — please re-enter investor password.']); return 'bad-password-store'; }
      $txid = str_starts_with($acc['metaapi_state'], 'creating:') ? substr($acc['metaapi_state'], 9) : bin2hex(random_bytes(16));
      [$code, $b] = ma_http('POST', MA_PROV . '/users/current/accounts', [
        'name' => 'rebate-' . $id . '-' . $acc['login'], 'login' => (string)$acc['login'], 'password' => $pass,
        'server' => $acc['server'], 'platform' => strtolower($acc['platform']), 'magic' => 0, 'type' => 'cloud-g2',
        'region' => ma_region(), 'reliability' => 'regular', 'manualTrades' => false,
      ], ['transaction-id: ' . $txid]);
      if ($code === 202) { ma_set($id, ['metaapi_state' => 'creating:' . $txid, 'sync_error' => '']); return 'creating'; }
      if ($code === 201 && !empty($b['id'])) {
        ma_set($id, ['metaapi_id' => $b['id'], 'metaapi_state' => $b['state'] ?? 'DEPLOYING', 'sync_error' => '']);
        if (($b['state'] ?? '') === 'UNDEPLOYED') ma_http('POST', MA_PROV . '/users/current/accounts/' . rawurlencode($b['id']) . '/deploy');
        return 'created';
      }
      ma_set($id, ['metaapi_state' => '', 'sync_error' => $code === 400 ? 'Broker server or login not recognised. Check server name and account number.' : ma_err($b, $code)]);
      return 'create-failed';
    }

    // 2) Check state / connection
    $mid = rawurlencode($acc['metaapi_id']);
    [$code, $info] = ma_http('GET', MA_PROV . "/users/current/accounts/$mid");
    if ($code === 404) { ma_set($id, ['metaapi_id' => '', 'metaapi_state' => '']); return 'recreate'; }
    if ($code !== 200) { ma_set($id, ['sync_error' => ma_err($info, $code)]); return 'state-failed'; }
    $state = $info['state'] ?? ''; $conn = $info['connectionStatus'] ?? '';
    ma_set($id, ['metaapi_state' => $state . ($conn ? "/$conn" : '')]);
    if ($state === 'UNDEPLOYED') { ma_http('POST', MA_PROV . "/users/current/accounts/$mid/deploy"); return 'deploying'; }
    if ($state !== 'DEPLOYED') return 'waiting:' . $state;
    if ($conn === 'DISCONNECTED_FROM_BROKER') {
      ma_set($id, ['sync_error' => 'Cannot log in to broker — check investor password and server.']);
      return 'broker-login-failed';
    }
    if ($conn !== 'CONNECTED') return 'connecting';

    // 3) Read deal history and store lots per day. Re-read the last 3 days each time so late
    //    history from the terminal is picked up; whole days are replaced.
    $from = $acc['synced_until'] ? strtotime($acc['synced_until']) - 3 * 86400 : strtotime($acc['created_at']) - 7 * 86400;
    $fromDay = gmdate('Y-m-d', $from);
    $start = $fromDay . 'T00:00:00.000Z';
    $end = gmdate('Y-m-d\TH:i:s.000\Z', time() + 60);
    $byDay = [];
    for ($offset = 0; $offset < 50000; $offset += 1000) {
      [$code, $deals] = ma_http('GET', ma_client() . "/users/current/accounts/$mid/history-deals/time/" . rawurlencode($start) . '/' . rawurlencode($end) . "?offset=$offset&limit=1000");
      if ($code !== 200 || !is_array($deals)) { ma_set($id, ['sync_error' => ma_err($deals, $code)]); return 'history-failed'; }
      foreach ($deals as $d) {
        // Count each closed position once (round-turn lots), the usual basis for IB commission.
        if (!in_array($d['type'] ?? '', ['DEAL_TYPE_BUY', 'DEAL_TYPE_SELL'], true)) continue;
        if (!in_array($d['entryType'] ?? '', ['DEAL_ENTRY_OUT', 'DEAL_ENTRY_INOUT', 'DEAL_ENTRY_OUT_BY'], true)) continue;
        $day = substr((string)($d['time'] ?? ''), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) || $day < $fromDay) continue;
        $byDay[$day]['lots'] = ($byDay[$day]['lots'] ?? 0) + (float)($d['volume'] ?? 0);
        $byDay[$day]['deals'] = ($byDay[$day]['deals'] ?? 0) + 1;
      }
      if (count($deals) < 1000) break;
    }
    db()->beginTransaction();
    q('DELETE FROM lot_days WHERE account_id = ? AND day >= ?', [$id, $fromDay]);
    foreach ($byDay as $day => $v) q('INSERT INTO lot_days (account_id, day, lots, deals) VALUES (?, ?, ?, ?)', [$id, $day, round($v['lots'], 2), $v['deals']]);
    db()->commit();
    ma_set($id, ['synced_until' => now(), 'last_sync' => now(), 'sync_error' => '']);

    if (setting('metaapi_undeploy', '1') === '1') ma_http('POST', MA_PROV . "/users/current/accounts/$mid/undeploy");
    return 'synced:' . count($byDay) . 'd';
  } catch (Throwable $ex) {
    if (db()->inTransaction()) db()->rollBack();
    ma_set($id, ['sync_error' => mb_substr($ex->getMessage(), 0, 250)]);
    return 'error';
  }
}

/** Removes the cloud account (when an account is rejected or its password changes). */
function ma_remove(array $acc): void {
  if ($acc['metaapi_id'] === '' || !ma_token()) return;
  try { ma_http('DELETE', MA_PROV . '/users/current/accounts/' . rawurlencode($acc['metaapi_id'])); } catch (Throwable $e) {}
}
