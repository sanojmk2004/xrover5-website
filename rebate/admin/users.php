<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
$me = require_admin();

if (is_post()) {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  $u = row('SELECT * FROM users WHERE id = ?', [$id]);
  $act = $_POST['action'] ?? '';
  if ($u && $id !== (int)$me['id']) {
    if ($act === 'toggle') { q('UPDATE users SET status = ? WHERE id = ?', [$u['status'] === 'active' ? 'disabled' : 'active', $id]); flash('Updated ' . $u['email']); }
    if ($act === 'role')   { q('UPDATE users SET role = ? WHERE id = ?', [$u['role'] === 'admin' ? 'customer' : 'admin', $id]); flash('Role changed for ' . $u['email']); }
  }
  if ($u && $act === 'resetpw') {
    $tmp = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(9))), 0, 10);
    q('UPDATE users SET pass_hash = ? WHERE id = ?', [password_hash($tmp, PASSWORD_DEFAULT), $id]);
    flash("Temporary password for {$u['email']}: $tmp  (share it privately; they can change it via Forgot password)", 'info');
  }
  header('Location: ' . $_SERVER['REQUEST_URI']); exit;
}

$qs = trim((string)($_GET['q'] ?? ''));
$users = rows("SELECT u.*,
  (SELECT COUNT(*) FROM accounts a WHERE a.user_id = u.id) AS accs,
  (SELECT COALESCE(SUM(rebate_usd),0) FROM commissions c WHERE c.user_id = u.id) AS earned,
  (SELECT COALESCE(SUM(amount_usd),0) FROM withdrawals w WHERE w.user_id = u.id AND w.status IN ('paid','pending')) AS withdrawn
  FROM users u" . ($qs !== '' ? ' WHERE u.email LIKE ? OR u.name LIKE ? OR u.phone LIKE ?' : '') . ' ORDER BY u.created_at DESC LIMIT 300',
  $qs !== '' ? ["%$qs%", "%$qs%", "%$qs%"] : []);
page_start('Customers', 'admin/users.php', true);
?>
<div class="head-row"><h1>Customers</h1>
  <form method="get" class="btn-row"><input type="text" name="q" value="<?= e($qs) ?>" placeholder="Name, email or phone" style="width:240px"><button class="btn btn-ghost btn-sm">Search</button></form></div>
<div class="table-wrap"><table>
  <tr><th>Customer</th><th>Phone</th><th>Joined</th><th class="num">Accounts</th><th class="num">Earned</th><th class="num">Balance</th><th>Status</th><th></th></tr>
  <?php foreach ($users as $u): ?>
  <tr>
    <td class="wrap-ok"><b><?= e($u['name']) ?></b><?= $u['role'] === 'admin' ? ' <span class="badge badge-warn">Admin</span>' : '' ?><div class="small muted"><?= e($u['email']) ?></div></td>
    <td class="small"><?php $wa = preg_replace('/\D/', '', $u['phone']); ?><?= $wa ? '<a href="https://wa.me/' . e($wa) . '" target="_blank" rel="noopener">' . e($u['phone']) . '</a>' : '' ?></td>
    <td class="small"><?= e(date('d M Y', strtotime($u['created_at']))) ?></td>
    <td class="num"><a href="accounts.php?q=<?= urlencode($u['email']) ?>"><?= (int)$u['accs'] ?></a></td>
    <td class="num"><?= usd($u['earned']) ?></td>
    <td class="num"><?= usd($u['earned'] - $u['withdrawn']) ?></td>
    <td><?= status_badge($u['status']) ?></td>
    <td><form method="post" class="btn-row" style="gap:6px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>">
      <?php if ((int)$u['id'] !== (int)$me['id']): ?>
        <button class="btn btn-ghost btn-sm" name="action" value="toggle"><?= $u['status'] === 'active' ? 'Disable' : 'Enable' ?></button>
        <button class="btn btn-ghost btn-sm" name="action" value="role" onclick="return confirm('Change admin rights for this user?')"><?= $u['role'] === 'admin' ? 'Remove admin' : 'Make admin' ?></button>
      <?php endif; ?>
      <button class="btn btn-ghost btn-sm" name="action" value="resetpw" onclick="return confirm('Set a new temporary password?')">Reset password</button>
    </form></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$users): ?><tr><td colspan="8" class="empty">No customers found.</td></tr><?php endif; ?>
</table></div>
<?php page_end();
