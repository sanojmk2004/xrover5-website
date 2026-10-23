<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require dirname(__DIR__) . '/inc/vps_status.php';
require_admin();
$m0 = date('Y-m-01');
$s = [
  'users'    => (int) val("SELECT COUNT(*) FROM users WHERE role = 'customer'"),
  'acc_pend' => (int) val("SELECT COUNT(*) FROM accounts WHERE status = 'pending'"),
  'acc_ok'   => (int) val("SELECT COUNT(*) FROM accounts WHERE status = 'verified'"),
  'wd_pend'  => (float) val("SELECT COALESCE(SUM(amount_usd),0) FROM withdrawals WHERE status = 'pending'"),
  'wd_pendn' => (int) val("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'"),
  'comm_m'   => (float) val('SELECT COALESCE(SUM(commission_usd),0) FROM commissions WHERE period >= ?', [$m0]),
  'reb_m'    => (float) val('SELECT COALESCE(SUM(rebate_usd),0) FROM commissions WHERE period >= ?', [$m0]),
  'comm_all' => (float) val('SELECT COALESCE(SUM(commission_usd),0) FROM commissions'),
  'reb_all'  => (float) val('SELECT COALESCE(SUM(rebate_usd),0) FROM commissions'),
  'paid_all' => (float) val("SELECT COALESCE(SUM(amount_usd),0) FROM withdrawals WHERE status = 'paid'"),
  'lots_m'   => (float) val('SELECT COALESCE(SUM(lots),0) FROM lot_days WHERE day >= ?', [$m0]),
  'sync_err' => (int) val("SELECT COUNT(*) FROM accounts WHERE status = 'verified' AND sync_error <> ''"),
];
$brokers = rows("SELECT b.name, b.clicks, b.rebate_pct,
  (SELECT COUNT(*) FROM accounts a WHERE a.broker_id = b.id AND a.status = 'verified') AS accs,
  (SELECT COALESCE(SUM(commission_usd),0) FROM commissions c WHERE c.broker_id = b.id) AS comm,
  (SELECT COALESCE(SUM(rebate_usd),0) FROM commissions c WHERE c.broker_id = b.id) AS reb
  FROM brokers b ORDER BY b.sort_order");
page_start('Admin overview', 'admin/index.php', true);
?>
<h1>Rebate admin</h1>
<?php $vs = vps_status(); if ($vs['state'] !== 'off'):
  $wd = setting('watchdog_last_run'); $wdOk = $wd && time() - strtotime($wd) < 3600;
  $cls = $vs['state'] === 'ok' ? 'ok' : ($vs['state'] === 'down' ? 'err' : 'info');
  $failed = (int) val("SELECT COUNT(*) FROM accounts WHERE status = 'verified' AND platform = 'MT5' AND sync_error <> ''"); ?>
  <div class="alert alert-<?= $cls ?>">
    <b>VPS lot sync: <?= $vs['state'] === 'ok' ? '✔' : '✘' ?> <?= e($vs['label']) ?></b>
    <?php if (setting('vps_started_at')): ?><span class="small muted"> · started <?= e(date('d M, H:i', strtotime(setting('vps_started_at')))) ?><?= setting('vps_host') ? ' on ' . e(setting('vps_host')) : '' ?></span><?php endif; ?>
    <?php if ($vs['state'] === 'down'): ?><div class="small">Log in to the VPS and double-click C:\xr5sync\start-sync.bat.</div><?php endif; ?>
    <?php if ($failed): ?><div class="small"><a href="accounts.php?status=verified"><?= $failed ?> account(s) failing to log in</a> (wrong investor password or server).</div><?php endif; ?>
    <div class="small muted"><?= $wdOk ? 'Email alerts are on.' : 'Email alerts are off — add the watchdog cron job in cPanel (see Settings).' ?></div>
  </div>
<?php endif; ?>
<?php if (setting('lot_source', 'none') === 'metaapi' && !setting('metaapi_token_enc')): ?><div class="alert alert-info">MetaApi isn't connected yet, so lots are not read from MT4/MT5. Add your token in <a href="settings.php">Settings</a>. Commission imports work without it.</div><?php endif; ?>
<div class="grid g4">
  <a class="stat" href="accounts.php?status=pending" style="text-decoration:none;color:inherit"><div class="k">Accounts to verify</div><div class="v"><?= $s['acc_pend'] ?></div><div class="s"><?= $s['acc_ok'] ?> verified</div></a>
  <a class="stat hl" href="withdrawals.php" style="text-decoration:none;color:inherit"><div class="k">Payouts to send</div><div class="v"><?= usd($s['wd_pend']) ?></div><div class="s"><?= $s['wd_pendn'] ?> request(s)</div></a>
  <div class="stat"><div class="k">Commission this month</div><div class="v"><?= usd($s['comm_m']) ?></div><div class="s">Rebates <?= usd($s['reb_m']) ?> · You keep <?= usd($s['comm_m'] - $s['reb_m']) ?></div></div>
  <div class="stat"><div class="k">Lots this month</div><div class="v"><?= lots($s['lots_m']) ?></div><div class="s">From MetaApi sync<?= $s['sync_err'] ? ' · <a href="accounts.php?status=verified">' . $s['sync_err'] . ' sync error(s)</a>' : '' ?></div></div>
</div>
<div class="grid g4 section" style="margin-top:18px">
  <div class="stat"><div class="k">Customers</div><div class="v"><?= $s['users'] ?></div></div>
  <div class="stat"><div class="k">All-time commission</div><div class="v"><?= usd($s['comm_all']) ?></div></div>
  <div class="stat"><div class="k">All-time rebates</div><div class="v"><?= usd($s['reb_all']) ?></div></div>
  <div class="stat"><div class="k">Owed to customers</div><div class="v"><?= usd($s['reb_all'] - $s['paid_all']) ?></div><div class="s">Rebates not yet paid out</div></div>
</div>
<div class="section">
  <h2>By broker</h2>
  <div class="table-wrap"><table>
    <tr><th>Broker</th><th class="num">Link clicks</th><th class="num">Verified accounts</th><th class="num">Customer %</th><th class="num">Commission</th><th class="num">Rebates</th><th class="num">Net to XDemat</th></tr>
    <?php foreach ($brokers as $b): ?>
    <tr><td><?= e($b['name']) ?></td><td class="num"><?= (int)$b['clicks'] ?></td><td class="num"><?= (int)$b['accs'] ?></td><td class="num"><?= e(pct($b['rebate_pct'])) ?></td>
      <td class="num"><?= usd($b['comm']) ?></td><td class="num"><?= usd($b['reb']) ?></td><td class="num"><b><?= usd($b['comm'] - $b['reb']) ?></b></td></tr>
    <?php endforeach; ?>
  </table></div>
</div>
<?php page_end();
