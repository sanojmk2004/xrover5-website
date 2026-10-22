<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require dirname(__DIR__) . '/inc/metaapi.php';
require_admin();

$reveal = null;
if (is_post()) {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  $a = row('SELECT a.*, u.email, u.name AS uname FROM accounts a JOIN users u ON u.id = a.user_id WHERE a.id = ?', [$id]);
  $act = $_POST['action'] ?? '';
  if ($a && $act === 'verify') {
    q("UPDATE accounts SET status = 'verified', admin_note = ? WHERE id = ?", [post('note', 255), $id]);
    send_mail($a['email'], 'Your trading account is verified', "Hi {$a['uname']},\n\nYour {$a['platform']} account {$a['login']} is verified for XRover 5 rebates. Lots you trade from now on count toward your rebate.\n\nXDemat");
    flash("Account {$a['login']} verified.");
  } elseif ($a && $act === 'reject') {
    ma_remove($a);
    q("UPDATE accounts SET status = 'rejected', admin_note = ?, metaapi_id = '', metaapi_state = '' WHERE id = ?", [post('note', 255) ?: 'Not found under our IB', $id]);
    flash("Account {$a['login']} rejected.");
  } elseif ($a && $act === 'pending') {
    q("UPDATE accounts SET status = 'pending' WHERE id = ?", [$id]);
  } elseif ($a && $act === 'sync') {
    flash("Sync {$a['login']}: " . ma_sync_account($a), 'info');
  } elseif ($a && $act === 'reveal') {
    $reveal = [$id, decrypt_secret((string)$a['investor_enc']) ?? '(cannot decrypt)'];
  }
  if (!$reveal) { header('Location: ' . $_SERVER['REQUEST_URI']); exit; }
}

$status = in_array($_GET['status'] ?? '', ['pending', 'verified', 'rejected'], true) ? $_GET['status'] : '';
$qs = trim((string)($_GET['q'] ?? ''));
$where = []; $args = [];
if ($status) { $where[] = 'a.status = ?'; $args[] = $status; }
if ($qs !== '') { $where[] = '(a.login LIKE ? OR u.email LIKE ? OR u.name LIKE ?)'; array_push($args, "%$qs%", "%$qs%", "%$qs%"); }
$accs = rows('SELECT a.*, u.name AS uname, u.email, u.phone, b.name AS broker,
  (SELECT COALESCE(SUM(lots),0) FROM lot_days l WHERE l.account_id = a.id) AS lots_all
  FROM accounts a JOIN users u ON u.id = a.user_id JOIN brokers b ON b.id = a.broker_id'
  . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY CASE a.status WHEN 'pending' THEN 0 ELSE 1 END, a.created_at DESC LIMIT 300", $args);

page_start('Accounts', 'admin/accounts.php', true);
?>
<div class="head-row"><h1>Trading accounts</h1>
  <form method="get" class="btn-row">
    <select name="status" style="width:auto"><option value="">All</option><?php foreach (['pending', 'verified', 'rejected'] as $st): ?><option<?= $status === $st ? ' selected' : '' ?>><?= $st ?></option><?php endforeach; ?></select>
    <input type="text" name="q" value="<?= e($qs) ?>" placeholder="Login, name or email" style="width:220px">
    <button class="btn btn-ghost btn-sm">Filter</button>
  </form></div>
<p class="muted small">Before verifying, check the account number appears under your IB in the broker's partner portal (Octa / Switch Markets). Only verified accounts are synced and can receive imported commissions.</p>

<div class="table-wrap"><table>
  <tr><th>Customer</th><th>Broker</th><th>Account</th><th>Server</th><th>Status</th><th class="num">Lots (all)</th><th>Sync</th><th>Actions</th></tr>
  <?php foreach ($accs as $a): ?>
  <tr>
    <td class="wrap-ok"><b><?= e($a['uname']) ?></b><div class="small muted"><?= e($a['email']) ?><br><?= e($a['phone']) ?></div></td>
    <td><?= e($a['broker']) ?></td>
    <td class="mono"><?= e($a['platform']) ?> · <?= e($a['login']) ?></td>
    <td class="small"><?= e($a['server']) ?></td>
    <td><?= status_badge($a['status']) ?><?php if ($a['admin_note']): ?><div class="small muted"><?= e($a['admin_note']) ?></div><?php endif; ?></td>
    <td class="num"><?= lots($a['lots_all']) ?></td>
    <td class="small wrap-ok" style="max-width:220px"><?= $a['last_sync'] ? e(date('d M H:i', strtotime($a['last_sync']))) : '—' ?>
      <?php if ($a['metaapi_state']): ?><div class="muted"><?= e($a['metaapi_state']) ?></div><?php endif; ?>
      <?php if ($a['sync_error']): ?><div style="color:var(--bear)"><?= e($a['sync_error']) ?></div><?php endif; ?></td>
    <td>
      <form method="post" class="btn-row" style="gap:6px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $a['id'] ?>">
        <input type="text" name="note" placeholder="Note (optional)" style="width:150px;padding:6px 9px">
        <?php if ($a['status'] !== 'verified'): ?><button class="btn btn-ok btn-sm" name="action" value="verify">Verify</button><?php endif; ?>
        <?php if ($a['status'] !== 'rejected'): ?><button class="btn btn-danger btn-sm" name="action" value="reject">Reject</button><?php endif; ?>
        <?php if ($a['status'] === 'verified'): ?><button class="btn btn-ghost btn-sm" name="action" value="sync">Sync now</button><?php endif; ?>
        <button class="btn btn-ghost btn-sm" name="action" value="reveal">Investor pw</button>
      </form>
      <?php if ($reveal && $reveal[0] === (int)$a['id']): ?><div class="mono small" style="margin-top:6px;color:var(--ember-hot)"><?= e($reveal[1]) ?></div><?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$accs): ?><tr><td colspan="8" class="empty">No accounts.</td></tr><?php endif; ?>
</table></div>
<?php page_end();
