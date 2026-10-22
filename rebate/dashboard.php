<?php
require __DIR__ . '/inc/bootstrap.php';
$u = require_login();
$bal = user_balance((int)$u['id']);
$accs = rows('SELECT a.*, b.name AS broker, b.rebate_pct FROM accounts a JOIN brokers b ON b.id = a.broker_id WHERE a.user_id = ? ORDER BY a.created_at DESC', [$u['id']]);

$estTotal = 0; $lotsMonth = 0;
$monthStart = date('Y-m-01');
foreach ($accs as &$a) {
  $a['est'] = account_estimate($a);
  $a['lots_month'] = (float) val('SELECT COALESCE(SUM(lots),0) FROM lot_days WHERE account_id = ? AND day >= ?', [$a['id'], $monthStart]);
  $a['confirmed'] = (float) val('SELECT COALESCE(SUM(rebate_usd),0) FROM commissions WHERE account_id = ?', [$a['id']]);
  $estTotal += $a['est']['est_usd']; $lotsMonth += $a['lots_month'];
}
unset($a);
$recent = rows('SELECT c.*, a.login, b.name AS broker FROM commissions c JOIN accounts a ON a.id = c.account_id JOIN brokers b ON b.id = c.broker_id WHERE c.user_id = ? ORDER BY c.period DESC, c.id DESC LIMIT 12', [$u['id']]);

page_start('Dashboard', 'dashboard.php');
?>
<div class="head-row"><div><h1>Hi, <?= e(strtok($u['name'], ' ')) ?></h1><p class="muted" style="margin:0">Your rebates at a glance.</p></div>
  <a class="btn btn-primary" href="<?= e(url('wallet.php')) ?>">Request payout</a></div>

<div class="grid g4">
  <div class="stat hl"><div class="k">Available</div><div class="v"><?= usd($bal['available']) ?></div><div class="s">Ready to withdraw (min <?= usd(setting('min_withdrawal', 10)) ?>)</div></div>
  <div class="stat"><div class="k">Pending estimate</div><div class="v"><?= usd($estTotal) ?></div><div class="s">From lots not yet confirmed by broker</div></div>
  <div class="stat"><div class="k">Lots this month</div><div class="v"><?= lots($lotsMonth) ?></div><div class="s">From connected MT4/MT5</div></div>
  <div class="stat"><div class="k">Total paid</div><div class="v"><?= usd($bal['paid']) ?></div><div class="s"><?= $bal['pending'] > 0 ? usd($bal['pending']) . ' payout in process' : 'Lifetime USDT payouts' ?></div></div>
</div>

<?php if (!$accs): ?>
  <div class="card section">
    <h2>Let's get you set up</h2>
    <p class="muted">You haven't connected a trading account yet. Open one with a partner broker through our link, then add it here.</p>
    <div class="btn-row"><a class="btn btn-primary" href="<?= e(url('brokers.php')) ?>">Open broker account</a><a class="btn btn-ghost" href="<?= e(url('accounts.php')) ?>">Connect existing account</a></div>
  </div>
<?php else: ?>
  <div class="section">
    <div class="head-row"><h2>Trading accounts</h2><a href="<?= e(url('accounts.php')) ?>">Manage →</a></div>
    <div class="table-wrap"><table>
      <tr><th>Broker</th><th>Account</th><th>Status</th><th class="num">Lots (month)</th><th class="num">Unconfirmed lots</th><th class="num">Est. rebate</th><th class="num">Confirmed rebate</th><th>Last sync</th></tr>
      <?php foreach ($accs as $a): ?>
      <tr>
        <td><?= e($a['broker']) ?></td>
        <td class="mono"><?= e($a['platform']) ?> · <?= e($a['login']) ?></td>
        <td><?= status_badge($a['status']) ?></td>
        <td class="num"><?= lots($a['lots_month']) ?></td>
        <td class="num"><?= lots($a['est']['lots']) ?></td>
        <td class="num"><?= usd($a['est']['est_usd']) ?></td>
        <td class="num"><?= usd($a['confirmed']) ?></td>
        <td class="small muted"><?= $a['last_sync'] ? e(date('d M, H:i', strtotime($a['last_sync']))) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
  </div>
<?php endif; ?>

<div class="section">
  <h2>Confirmed rebates</h2>
  <div class="table-wrap"><table>
    <tr><th>Period</th><th>Broker</th><th>Account</th><th class="num">Lots</th><th class="num">Broker commission</th><th class="num">Your share</th><th class="num">Rebate</th></tr>
    <?php foreach ($recent as $c): ?>
    <tr><td><?= e(date('d M Y', strtotime($c['period']))) ?></td><td><?= e($c['broker']) ?></td><td class="mono"><?= e($c['login']) ?></td>
      <td class="num"><?= lots($c['lots']) ?></td><td class="num"><?= usd($c['commission_usd']) ?></td><td class="num"><?= e(pct($c['rebate_pct'])) ?></td><td class="num"><b><?= usd($c['rebate_usd']) ?></b></td></tr>
    <?php endforeach; ?>
    <?php if (!$recent): ?><tr><td colspan="7" class="empty">No confirmed rebates yet. They appear here once the broker's IB commission report is processed.</td></tr><?php endif; ?>
  </table></div>
</div>
<?php page_end();
