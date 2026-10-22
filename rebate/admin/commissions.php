<?php
// Import the broker's IB commission report (CSV) — the confirmed figures customers are paid on.
require dirname(__DIR__) . '/inc/bootstrap.php';
require_admin();
$brokers = rows('SELECT * FROM brokers ORDER BY sort_order, name');
$brokerById = array_column($brokers, null, 'id');

function num_clean(string $v): float {
  $v = preg_replace('/[^\d.\-]/', '', str_replace(',', '', $v));
  return is_numeric($v) ? (float)$v : 0.0;
}
function parse_day(string $v, string $order): ?string {
  $v = trim($v);
  if ($v === '') return null;
  if (preg_match('#^(\d{1,2})[/.\-](\d{1,2})[/.\-](\d{2,4})#', $v, $m)) {
    [$d, $mo] = $order === 'mdy' ? [$m[2], $m[1]] : [$m[1], $m[2]];
    $y = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
    return checkdate((int)$mo, (int)$d, (int)$y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
  }
  $t = strtotime($v);
  return $t ? date('Y-m-d', $t) : null;
}
function find_col(array $headers, string $wanted, string $regex): ?int {
  foreach ($headers as $i => $h) if ($wanted !== '' && strcasecmp(trim($h), $wanted) === 0) return $i;
  if ($wanted !== '') return null;
  foreach ($headers as $i => $h) if (preg_match($regex, $h)) return $i;
  return null;
}
function make_ref(int $bid, string $login, string $day, float $lots, float $comm, int $n): string {
  return sha1("$bid|$login|$day|" . number_format($lots, 2, '.', '') . '|' . number_format($comm, 2, '.', '') . "|$n");
}

$preview = $_SESSION['import'] ?? null;

if (is_post()) {
  csrf_check();
  $act = $_POST['action'] ?? '';

  if ($act === 'preview') {
    $bid = (int)($_POST['broker_id'] ?? 0);
    $b = $brokerById[$bid] ?? null;
    $file = $_FILES['csv']['tmp_name'] ?? '';
    if (!$b || !$file || !is_uploaded_file($file)) { flash('Choose a broker and a CSV file.', 'err'); redirect('admin/commissions.php'); }
    $map = ['account' => post('col_account', 80), 'commission' => post('col_commission', 80), 'lots' => post('col_lots', 80),
            'date' => post('col_date', 80), 'order' => ($_POST['date_order'] ?? 'dmy') === 'mdy' ? 'mdy' : 'dmy'];
    $raw = file_get_contents($file);
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', (string)$raw);
    $first = strtok($raw, "\n");
    $delim = substr_count($first, ';') > substr_count($first, ',') ? ';' : (substr_count($first, "\t") > substr_count($first, ',') ? "\t" : ',');
    $fh = fopen('php://memory', 'r+'); fwrite($fh, $raw); rewind($fh);
    $headers = fgetcsv($fh, 0, $delim, '"', '');
    $ci = [
      'account' => find_col($headers, $map['account'], '/account|login|client|mt4|mt5|trading/i'),
      'commission' => find_col($headers, $map['commission'], '/commission|reward|rebate|payout|amount|profit|earn/i'),
      'lots' => find_col($headers, $map['lots'], '/lot|volume/i'),
      'date' => find_col($headers, $map['date'], '/date|time|period|day/i'),
    ];
    if ($ci['account'] === null || $ci['commission'] === null) {
      fclose($fh);
      flash('Could not find the account or commission column. Headers in file: ' . implode(' | ', $headers) . '. Type the exact header names in the column boxes and try again.', 'err');
      redirect('admin/commissions.php');
    }
    $fallback = parse_day(post('period', 20), 'dmy') ?? date('Y-m-d');
    $acctIndex = [];
    foreach (rows('SELECT a.id, a.login, a.status, a.user_id, u.name FROM accounts a JOIN users u ON u.id = a.user_id WHERE a.broker_id = ?', [$bid]) as $r) $acctIndex[$r['login']] = $r;
    $out = []; $seen = [];
    while (($r = fgetcsv($fh, 0, $delim, '"', '')) !== false) {
      if (count(array_filter($r, fn($c) => trim((string)$c) !== '')) === 0) continue;
      $login = preg_replace('/\D/', '', (string)($r[$ci['account']] ?? ''));
      $comm = num_clean((string)($r[$ci['commission']] ?? ''));
      if ($login === '' || $comm == 0) continue;           // skips totals/blank lines
      $lots = $ci['lots'] !== null ? num_clean((string)($r[$ci['lots']] ?? '')) : 0;
      $day = ($ci['date'] !== null ? parse_day((string)($r[$ci['date']] ?? ''), $map['order']) : null) ?? $fallback;
      $k = "$login|$day|$lots|$comm"; $seen[$k] = ($seen[$k] ?? 0) + 1;
      $ref = make_ref($bid, $login, $day, $lots, $comm, $seen[$k]);
      $acc = $acctIndex[$login] ?? null;
      $state = !$acc ? 'unknown' : ($acc['status'] !== 'verified' ? 'not verified' : (val('SELECT 1 FROM commissions WHERE ref = ?', [$ref]) ? 'duplicate' : 'ok'));
      $out[] = ['login' => $login, 'day' => $day, 'lots' => $lots, 'comm' => $comm, 'ref' => $ref, 'state' => $state,
                'acc' => $acc['id'] ?? null, 'uid' => $acc['user_id'] ?? null, 'name' => $acc['name'] ?? ''];
    }
    fclose($fh);
    q('UPDATE brokers SET csv_map = ? WHERE id = ?', [json_encode($map), $bid]);
    $_SESSION['import'] = ['broker_id' => $bid, 'rows' => $out, 'file' => mb_substr($_FILES['csv']['name'] ?? 'report.csv', 0, 80)];
    redirect('admin/commissions.php#preview');
  }

  if ($act === 'confirm' && $preview) {
    $b = $brokerById[$preview['broker_id']];
    $batch = date('ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 4);
    $n = 0; $sum = 0;
    db()->beginTransaction();
    foreach ($preview['rows'] as $r) {
      if ($r['state'] !== 'ok' || val('SELECT 1 FROM commissions WHERE ref = ?', [$r['ref']])) continue;
      $reb = round($r['comm'] * (float)$b['rebate_pct'] / 100, 2);
      q("INSERT INTO commissions (user_id, account_id, broker_id, period, lots, commission_usd, rebate_pct, rebate_usd, source, ref, batch, created_at) VALUES (?,?,?,?,?,?,?,?, 'import', ?,?,?)",
        [$r['uid'], $r['acc'], $b['id'], $r['day'], $r['lots'], $r['comm'], $b['rebate_pct'], $reb, $r['ref'], $batch, now()]);
      $n++; $sum += $reb;
    }
    db()->commit();
    unset($_SESSION['import']);
    flash("Imported $n row(s) from {$preview['file']}. Customer rebates added: " . usd($sum) . '.');
    redirect('admin/commissions.php');
  }

  if ($act === 'cancel') { unset($_SESSION['import']); redirect('admin/commissions.php'); }

  if ($act === 'manual') {
    $acc = row("SELECT a.*, b.rebate_pct FROM accounts a JOIN brokers b ON b.id = a.broker_id WHERE a.id = ? AND a.status = 'verified'", [(int)($_POST['account_id'] ?? 0)]);
    $comm = num_clean(post('commission', 20)); $lotsV = num_clean(post('lots', 20));
    $day = parse_day(post('period', 20), 'dmy');
    if (!$acc || $comm == 0 || !$day) flash('Choose a verified account, a date and the commission amount.', 'err');
    else {
      $reb = round($comm * (float)$acc['rebate_pct'] / 100, 2);
      q("INSERT INTO commissions (user_id, account_id, broker_id, period, lots, commission_usd, rebate_pct, rebate_usd, source, ref, batch, created_at) VALUES (?,?,?,?,?,?,?,?, 'manual', ?, ?, ?)",
        [$acc['user_id'], $acc['id'], $acc['broker_id'], $day, $lotsV, $comm, $acc['rebate_pct'], $reb, sha1('manual|' . bin2hex(random_bytes(12))), 'manual-' . date('ymd-His'), now()]);
      flash('Added: commission ' . usd($comm) . ', customer rebate ' . usd($reb) . '.');
    }
    redirect('admin/commissions.php');
  }

  if ($act === 'delete_batch') {
    $bt = post('batch', 40);
    $cnt = q('DELETE FROM commissions WHERE batch = ?', [$bt])->rowCount();
    flash("Deleted batch $bt ($cnt rows).");
    redirect('admin/commissions.php');
  }
}

$batches = rows('SELECT c.batch, c.source, b.name AS broker, COUNT(*) AS n, SUM(c.lots) AS lots, SUM(c.commission_usd) AS comm, SUM(c.rebate_usd) AS reb, MIN(c.period) AS p1, MAX(c.period) AS p2, MAX(c.created_at) AS at
  FROM commissions c JOIN brokers b ON b.id = c.broker_id GROUP BY c.batch, c.source, b.name ORDER BY at DESC LIMIT 40');
$verified = rows("SELECT a.id, a.login, a.platform, b.name AS broker, u.name FROM accounts a JOIN brokers b ON b.id = a.broker_id JOIN users u ON u.id = a.user_id WHERE a.status = 'verified' ORDER BY b.name, a.login");
$selBroker = (int)($_GET['broker'] ?? ($brokers[0]['id'] ?? 0));
$map = json_decode((string)($brokerById[$selBroker]['csv_map'] ?? ''), true) ?: [];

page_start('Commissions', 'admin/commissions.php', true);
?>
<h1>Broker commissions</h1>
<p class="muted">Download the IB / partner commission report from each broker's partner portal (CSV), and import it here. Each row is matched to a verified customer account and the customer's share is added to their balance.</p>

<?php if ($preview): $p = $preview; $ok = array_filter($p['rows'], fn($r) => $r['state'] === 'ok'); $b = $brokerById[$p['broker_id']]; ?>
<div class="card" id="preview">
  <div class="head-row"><h2>Preview: <?= e($p['file']) ?> · <?= e($b['name']) ?></h2>
    <div class="btn-row">
      <form method="post" class="inline"><?= csrf_field() ?><button class="btn btn-primary" name="action" value="confirm"<?= $ok ? '' : ' disabled' ?>>Import <?= count($ok) ?> row(s)</button></form>
      <form method="post" class="inline"><?= csrf_field() ?><button class="btn btn-ghost" name="action" value="cancel">Cancel</button></form>
    </div></div>
  <p class="small muted">Commission <?= usd(array_sum(array_column($ok, 'comm'))) ?> → customer rebates <?= usd(array_sum(array_column($ok, 'comm')) * $b['rebate_pct'] / 100) ?> at <?= e(pct($b['rebate_pct'])) ?>. Rows marked unknown / not verified are skipped — verify those accounts first, then import the same file again (already-imported rows are skipped automatically).</p>
  <div class="table-wrap" style="max-height:440px;overflow:auto"><table>
    <tr><th>Account</th><th>Customer</th><th>Date</th><th class="num">Lots</th><th class="num">Commission</th><th>Result</th></tr>
    <?php foreach ($p['rows'] as $r): ?>
    <tr><td class="mono"><?= e($r['login']) ?></td><td><?= e($r['name']) ?></td><td><?= e($r['day']) ?></td><td class="num"><?= lots($r['lots']) ?></td><td class="num"><?= usd($r['comm']) ?></td>
      <td><?= $r['state'] === 'ok' ? '<span class="badge badge-ok">Import</span>' : '<span class="badge badge-' . ($r['state'] === 'duplicate' ? 'muted' : 'warn') . '">' . e(ucfirst($r['state'])) . '</span>' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$p['rows']): ?><tr><td colspan="6" class="empty">No usable rows found.</td></tr><?php endif; ?>
  </table></div>
</div>
<?php endif; ?>

<div class="grid g2 section" style="align-items:start">
  <div class="card">
    <h2>Import IB report (CSV)</h2>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="action" value="preview">
      <div class="row2">
        <div class="field"><label>Broker</label><select name="broker_id" onchange="location='?broker='+this.value">
          <?php foreach ($brokers as $b): ?><option value="<?= $b['id'] ?>"<?= $selBroker === (int)$b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?> (<?= e(pct($b['rebate_pct'])) ?>)</option><?php endforeach; ?></select></div>
        <div class="field"><label>CSV file</label><input type="file" name="csv" accept=".csv,text/csv" required></div>
      </div>
      <details<?= $map ? ' open' : '' ?>><summary class="small" style="cursor:pointer;color:var(--cyan);margin-bottom:10px">Column names (leave blank to auto-detect)</summary>
        <div class="row2">
          <div class="field"><label>Account column</label><input type="text" name="col_account" value="<?= e($map['account'] ?? '') ?>" placeholder="e.g. Account"></div>
          <div class="field"><label>Commission column (USD)</label><input type="text" name="col_commission" value="<?= e($map['commission'] ?? '') ?>" placeholder="e.g. Commission"></div>
          <div class="field"><label>Lots column</label><input type="text" name="col_lots" value="<?= e($map['lots'] ?? '') ?>" placeholder="e.g. Volume"></div>
          <div class="field"><label>Date column</label><input type="text" name="col_date" value="<?= e($map['date'] ?? '') ?>" placeholder="e.g. Close time"></div>
        </div>
        <div class="field"><label>Date format in file</label><select name="date_order"><option value="dmy">DD/MM/YYYY</option><option value="mdy"<?= ($map['order'] ?? '') === 'mdy' ? ' selected' : '' ?>>MM/DD/YYYY</option></select></div>
      </details>
      <div class="field"><label>Date to use if the file has no date column</label><input type="date" name="period" value="<?= date('Y-m-d') ?>"></div>
      <button class="btn btn-primary">Preview import</button>
    </form>
  </div>

  <div class="card">
    <h2>Add commission manually</h2>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="manual">
      <div class="field"><label>Account</label><select name="account_id" required><option value="">Choose verified account…</option>
        <?php foreach ($verified as $v): ?><option value="<?= $v['id'] ?>"><?= e($v['broker'] . ' · ' . $v['platform'] . ' ' . $v['login'] . ' · ' . $v['name']) ?></option><?php endforeach; ?></select></div>
      <div class="row2">
        <div class="field"><label>Date / period end</label><input type="date" name="period" value="<?= date('Y-m-d') ?>" required></div>
        <div class="field"><label>Lots</label><input type="number" name="lots" step="0.01" min="0" value="0"></div>
      </div>
      <div class="field"><label>Commission received (USD)</label><input type="number" name="commission" step="0.01" required><div class="hint">Customer's share is calculated with the broker's current rebate %.</div></div>
      <button class="btn btn-ghost">Add</button>
    </form>
  </div>
</div>

<div class="section">
  <h2>Recent imports</h2>
  <div class="table-wrap"><table>
    <tr><th>Batch</th><th>Broker</th><th>Period</th><th class="num">Rows</th><th class="num">Lots</th><th class="num">Commission</th><th class="num">Rebates</th><th></th></tr>
    <?php foreach ($batches as $bt): ?>
    <tr><td class="mono small"><?= e($bt['batch']) ?></td><td><?= e($bt['broker']) ?></td><td class="small"><?= e($bt['p1'] === $bt['p2'] ? $bt['p1'] : $bt['p1'] . ' → ' . $bt['p2']) ?></td>
      <td class="num"><?= (int)$bt['n'] ?></td><td class="num"><?= lots($bt['lots']) ?></td><td class="num"><?= usd($bt['comm']) ?></td><td class="num"><?= usd($bt['reb']) ?></td>
      <td><form method="post" onsubmit="return confirm('Delete this batch? Customer balances will go down by its rebates.')"><?= csrf_field() ?><input type="hidden" name="batch" value="<?= e($bt['batch']) ?>"><button class="btn btn-danger btn-sm" name="action" value="delete_batch">Delete</button></form></td></tr>
    <?php endforeach; ?>
    <?php if (!$batches): ?><tr><td colspan="8" class="empty">Nothing imported yet.</td></tr><?php endif; ?>
  </table></div>
</div>
<?php page_end();
