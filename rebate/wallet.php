<?php
require __DIR__ . '/inc/bootstrap.php';
$u = require_login();
$uid = (int)$u['id'];
$min = (float) setting('min_withdrawal', 10);

if (is_post()) {
  csrf_check();
  $action = $_POST['action'] ?? '';
  if ($action === 'add_wallet') {
    $net = $_POST['network'] ?? '';
    $addr = post('address', 120);
    if (!isset(NETWORKS[$net])) flash('Choose a network.', 'err');
    elseif (!valid_address($net, $addr)) flash('That address does not look like a valid ' . NETWORKS[$net] . ' address. TRC20 addresses start with T, BEP20/ERC20 with 0x.', 'err');
    elseif (val('SELECT COUNT(*) FROM wallets WHERE user_id = ?', [$uid]) >= 5) flash('You can save up to 5 wallets.', 'err');
    else {
      $first = !val('SELECT COUNT(*) FROM wallets WHERE user_id = ?', [$uid]);
      q('INSERT INTO wallets (user_id, network, address, label, is_default, created_at) VALUES (?, ?, ?, ?, ?, ?)', [$uid, $net, $addr, post('label', 60), $first ? 1 : 0, now()]);
      flash('Wallet saved. Double-check the address: crypto payouts cannot be reversed.');
    }
  } elseif ($action === 'default_wallet') {
    $id = (int)$_POST['id'];
    if (row('SELECT id FROM wallets WHERE id = ? AND user_id = ?', [$id, $uid])) {
      q('UPDATE wallets SET is_default = 0 WHERE user_id = ?', [$uid]);
      q('UPDATE wallets SET is_default = 1 WHERE id = ?', [$id]);
    }
  } elseif ($action === 'delete_wallet') {
    q('DELETE FROM wallets WHERE id = ? AND user_id = ?', [(int)$_POST['id'], $uid]);
    flash('Wallet removed.');
  } elseif ($action === 'withdraw') {
    $amount = round((float)($_POST['amount'] ?? 0), 2);
    $w = row('SELECT * FROM wallets WHERE id = ? AND user_id = ?', [(int)($_POST['wallet_id'] ?? 0), $uid]);
    db()->beginTransaction();
    if (db_driver() === 'mysql') q('SELECT id FROM users WHERE id = ? FOR UPDATE', [$uid]); // one payout request at a time per user
    $bal = user_balance($uid);
    if (!$w) { flash('Choose a wallet.', 'err'); }
    elseif (val("SELECT COUNT(*) FROM withdrawals WHERE user_id = ? AND status = 'pending'", [$uid])) { flash('You already have a payout in process. Please wait until it is paid.', 'err'); }
    elseif ($amount < $min) { flash('Minimum payout is ' . usd($min) . '.', 'err'); }
    elseif ($amount > $bal['available']) { flash('Amount is more than your available balance of ' . usd($bal['available']) . '.', 'err'); }
    else {
      q("INSERT INTO withdrawals (user_id, amount_usd, network, address, status, requested_at) VALUES (?, ?, ?, ?, 'pending', ?)", [$uid, $amount, $w['network'], $w['address'], now()]);
      notify_admin('Rebate payout request ' . usd($amount), "{$u['name']} ({$u['email']}) requested " . usd($amount) . " to {$w['network']} {$w['address']}.\nProcess it in Admin → Payouts.");
      flash('Payout requested. We pay ' . setting('payout_days', 'weekly') . ' and will add the transaction hash here.');
    }
    db()->commit();
  }
  redirect('wallet.php');
}

$bal = user_balance($uid);
$wallets = rows('SELECT * FROM wallets WHERE user_id = ? ORDER BY is_default DESC, id', [$uid]);
$wds = rows('SELECT * FROM withdrawals WHERE user_id = ? ORDER BY requested_at DESC LIMIT 50', [$uid]);
$hasPending = (bool) array_filter($wds, fn($w) => $w['status'] === 'pending');
page_start('Wallet & payouts', 'wallet.php');

function explorer(string $net, string $tx): string {
  if ($tx === '') return '';
  $base = str_ends_with($net, 'TRC20') ? 'https://tronscan.org/#/transaction/' : (str_ends_with($net, 'BEP20') ? 'https://bscscan.com/tx/' : 'https://etherscan.io/tx/');
  return '<a class="mono small" href="' . e($base . $tx) . '" target="_blank" rel="noopener">' . e(short_addr($tx)) . ' ↗</a>';
}
?>
<h1>Wallet & payouts</h1>
<div class="grid g4">
  <div class="stat hl"><div class="k">Available</div><div class="v"><?= usd($bal['available']) ?></div></div>
  <div class="stat"><div class="k">In process</div><div class="v"><?= usd($bal['pending']) ?></div></div>
  <div class="stat"><div class="k">Total paid</div><div class="v"><?= usd($bal['paid']) ?></div></div>
  <div class="stat"><div class="k">Total earned</div><div class="v"><?= usd($bal['earned']) ?></div></div>
</div>

<div class="grid g2 section" style="align-items:start">
  <div class="card">
    <h2>Request a payout</h2>
    <?php if (!$wallets): ?>
      <p class="muted">Add a wallet first (right).</p>
    <?php elseif ($hasPending): ?>
      <p class="muted">Your payout is being processed. You can request another once it's paid.</p>
    <?php elseif ($bal['available'] < $min): ?>
      <p class="muted">Minimum payout is <?= usd($min) ?>. You have <?= usd($bal['available']) ?> available.</p>
    <?php else: ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="withdraw">
      <div class="field"><label>Amount (USD)</label><input type="number" name="amount" step="0.01" min="<?= $min ?>" max="<?= $bal['available'] ?>" value="<?= number_format($bal['available'], 2, '.', '') ?>" required></div>
      <div class="field"><label>Send to</label><select name="wallet_id" required>
        <?php foreach ($wallets as $w): ?><option value="<?= $w['id'] ?>"><?= e(NETWORKS[$w['network']] ?? $w['network']) ?> · <?= e(short_addr($w['address'])) ?><?= $w['label'] ? ' (' . e($w['label']) . ')' : '' ?></option><?php endforeach; ?>
      </select></div>
      <button class="btn btn-primary">Request payout</button>
      <p class="hint">Paid <?= e(setting('payout_days', 'weekly')) ?>. Network fee may be deducted.</p>
    </form>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Payout wallets</h2>
    <?php foreach ($wallets as $w): ?>
      <div style="display:flex;gap:10px;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--line);flex-wrap:wrap">
        <div><b><?= e(NETWORKS[$w['network']] ?? $w['network']) ?></b> <?= $w['is_default'] ? '<span class="badge badge-ok">Default</span>' : '' ?><?= $w['label'] ? ' <span class="muted small">' . e($w['label']) . '</span>' : '' ?>
          <div class="mono small muted" style="word-break:break-all"><?= e($w['address']) ?></div></div>
        <div class="btn-row">
          <?php if (!$w['is_default']): ?><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="default_wallet"><input type="hidden" name="id" value="<?= $w['id'] ?>"><button class="btn btn-ghost btn-sm">Make default</button></form><?php endif; ?>
          <form method="post" class="inline" onsubmit="return confirm('Remove this wallet?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_wallet"><input type="hidden" name="id" value="<?= $w['id'] ?>"><button class="btn btn-danger btn-sm">Remove</button></form>
        </div>
      </div>
    <?php endforeach; ?>
    <form method="post" style="margin-top:16px" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="action" value="add_wallet">
      <div class="field"><label>Network</label><select name="network" required>
        <?php foreach (NETWORKS as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
        <div class="hint">Pick the same network your wallet/exchange shows for the deposit address. Wrong network = lost funds.</div></div>
      <div class="field"><label>Wallet address</label><input type="text" name="address" required spellcheck="false" class="mono"></div>
      <div class="field"><label>Label (optional)</label><input type="text" name="label" placeholder="e.g. Binance, Trust Wallet"></div>
      <button class="btn btn-ghost">Save wallet</button>
    </form>
  </div>
</div>

<div class="section">
  <h2>Payout history</h2>
  <div class="table-wrap"><table>
    <tr><th>Requested</th><th class="num">Amount</th><th>Network</th><th>Address</th><th>Status</th><th>Transaction</th></tr>
    <?php foreach ($wds as $w): ?>
    <tr><td><?= e(date('d M Y', strtotime($w['requested_at']))) ?></td><td class="num"><?= usd($w['amount_usd']) ?></td><td><?= e($w['network']) ?></td>
      <td class="mono small"><?= e(short_addr($w['address'])) ?></td><td><?= status_badge($w['status']) ?><?php if ($w['admin_note']): ?><div class="small muted"><?= e($w['admin_note']) ?></div><?php endif; ?></td>
      <td><?= explorer($w['network'], $w['tx_hash']) ?: '<span class="muted">—</span>' ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$wds): ?><tr><td colspan="6" class="empty">No payouts yet.</td></tr><?php endif; ?>
  </table></div>
</div>
<?php page_end();
