<?php
// Payout queue: send USDT from your own wallet/exchange, then paste the transaction hash here.
require dirname(__DIR__) . '/inc/bootstrap.php';
require_admin();

if (is_post()) {
  csrf_check();
  $w = row('SELECT w.*, u.email, u.name FROM withdrawals w JOIN users u ON u.id = w.user_id WHERE w.id = ?', [(int)($_POST['id'] ?? 0)]);
  $act = $_POST['action'] ?? '';
  if ($w && $w['status'] === 'pending' && $act === 'paid') {
    $tx = preg_replace('/[^0-9a-zA-Z]/', '', post('tx', 120));
    if (strlen($tx) < 20) flash('Paste the blockchain transaction hash (TXID) to mark as paid.', 'err');
    else {
      q("UPDATE withdrawals SET status = 'paid', tx_hash = ?, admin_note = ?, processed_at = ? WHERE id = ?", [$tx, post('note', 255), now(), $w['id']]);
      send_mail($w['email'], 'Your rebate payout has been sent', "Hi {$w['name']},\n\nWe sent " . usd($w['amount_usd']) . " ({$w['network']}) to {$w['address']}.\nTransaction: $tx\n\nThank you for trading with XDemat.");
      flash('Marked as paid: ' . usd($w['amount_usd']) . " to {$w['name']}.");
    }
  } elseif ($w && $w['status'] === 'pending' && $act === 'reject') {
    q("UPDATE withdrawals SET status = 'rejected', admin_note = ?, processed_at = ? WHERE id = ?", [post('note', 255) ?: 'Rejected', now(), $w['id']]);
    flash('Payout rejected; the amount is back in the customer\'s available balance.');
  }
  redirect('admin/withdrawals.php');
}

$pending = rows("SELECT w.*, u.name, u.email, u.phone FROM withdrawals w JOIN users u ON u.id = w.user_id WHERE w.status = 'pending' ORDER BY w.requested_at");
$done = rows("SELECT w.*, u.name FROM withdrawals w JOIN users u ON u.id = w.user_id WHERE w.status <> 'pending' ORDER BY w.processed_at DESC LIMIT 100");
page_start('Payouts', 'admin/withdrawals.php', true);
?>
<h1>Payouts</h1>
<p class="muted">Send each payout from your wallet or exchange on the <b>exact network shown</b>, then paste the TXID and mark it paid. The customer gets an email with the transaction.</p>
<div class="table-wrap"><table>
  <tr><th>Requested</th><th>Customer</th><th class="num">Amount</th><th>Network & address</th><th>Balance check</th><th>Action</th></tr>
  <?php foreach ($pending as $w): $bal = user_balance((int)$w['user_id']); ?>
  <tr>
    <td class="small"><?= e(date('d M Y H:i', strtotime($w['requested_at']))) ?></td>
    <td class="wrap-ok"><b><?= e($w['name']) ?></b><div class="small muted"><?= e($w['email']) ?> · <?= e($w['phone']) ?></div></td>
    <td class="num"><b><?= usd($w['amount_usd']) ?></b></td>
    <td class="wrap-ok"><span class="badge badge-warn"><?= e($w['network']) ?></span><div class="mono small" style="word-break:break-all;max-width:300px;margin-top:4px"><?= e($w['address']) ?></div></td>
    <td class="small"><?= $bal['available'] >= 0 ? '<span style="color:var(--bull)">OK</span>' : '<span style="color:var(--bear)">Over by ' . usd(-$bal['available']) . '</span>' ?><div class="muted">Earned <?= usd($bal['earned']) ?></div></td>
    <td>
      <form method="post" style="display:grid;gap:6px;min-width:260px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $w['id'] ?>">
        <input type="text" name="tx" placeholder="Transaction hash (TXID)" class="mono" style="padding:7px 10px">
        <input type="text" name="note" placeholder="Note (optional / reason)" style="padding:7px 10px">
        <div class="btn-row"><button class="btn btn-ok btn-sm" name="action" value="paid">Mark paid</button>
          <button class="btn btn-danger btn-sm" name="action" value="reject" onclick="return confirm('Reject this payout?')">Reject</button></div>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$pending): ?><tr><td colspan="6" class="empty">No payouts waiting.</td></tr><?php endif; ?>
</table></div>

<div class="section">
  <h2>History</h2>
  <div class="table-wrap"><table>
    <tr><th>Processed</th><th>Customer</th><th class="num">Amount</th><th>Network</th><th>Status</th><th>TXID / note</th></tr>
    <?php foreach ($done as $w): ?>
    <tr><td class="small"><?= $w['processed_at'] ? e(date('d M Y', strtotime($w['processed_at']))) : '' ?></td><td><?= e($w['name']) ?></td><td class="num"><?= usd($w['amount_usd']) ?></td><td><?= e($w['network']) ?></td>
      <td><?= status_badge($w['status']) ?></td><td class="mono small"><?= e($w['tx_hash'] ? short_addr($w['tx_hash']) : '') ?> <span class="muted"><?= e($w['admin_note']) ?></span></td></tr>
    <?php endforeach; ?>
    <?php if (!$done): ?><tr><td colspan="6" class="empty">No processed payouts yet.</td></tr><?php endif; ?>
  </table></div>
</div>
<?php page_end();
