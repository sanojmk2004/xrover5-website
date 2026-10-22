<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/metaapi.php';
$u = require_login();
$brokers = rows('SELECT * FROM brokers WHERE active = 1 ORDER BY sort_order, name');

if (is_post()) {
  csrf_check();
  $action = $_POST['action'] ?? '';
  if ($action === 'add' || $action === 'update_pass') {
    $inv = (string)($_POST['investor'] ?? '');
    if ($action === 'add') {
      $bid = (int)($_POST['broker_id'] ?? 0);
      $b = row('SELECT * FROM brokers WHERE id = ? AND active = 1', [$bid]);
      $platform = in_array($_POST['platform'] ?? '', ['MT4', 'MT5'], true) ? $_POST['platform'] : '';
      $login = preg_replace('/\D/', '', post('login', 30));
      $server = post('server', 120);
      if (!$b || !$platform || strlen($login) < 4 || $server === '' || strlen($inv) < 4) {
        flash('Please fill in broker, platform, account number, server and investor password.', 'err');
      } elseif (!in_array($platform, explode(',', $b['platforms']), true)) {
        flash($b['name'] . ' does not offer ' . $platform . ' in our program.', 'err');
      } elseif ($other = row('SELECT user_id FROM accounts WHERE broker_id = ? AND login = ?', [$bid, $login])) {
        flash($other['user_id'] == $u['id'] ? 'You have already added this account.' : 'This account is already registered by another user. Contact support if it is yours.', 'err');
      } else {
        q('INSERT INTO accounts (user_id, broker_id, platform, login, server, investor_enc, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
          [$u['id'], $bid, $platform, $login, $server, encrypt_secret($inv), now()]);
        notify_admin('New trading account to verify', "{$u['name']} ({$u['email']}) added {$b['name']} $platform account $login on $server.\nVerify it in Admin → Accounts.");
        flash('Account added. We will confirm it is under our IB, usually within 24 hours.');
      }
    } else {
      $id = (int)($_POST['id'] ?? 0);
      if (strlen($inv) < 4) flash('Enter the new investor password.', 'err');
      elseif ($old = row('SELECT * FROM accounts WHERE id = ? AND user_id = ?', [$id, $u['id']])) {
        ma_remove($old);
        q("UPDATE accounts SET investor_enc = ?, sync_error = '', metaapi_id = '', metaapi_state = '' WHERE id = ?", [encrypt_secret($inv), $id]);
        flash('Investor password updated. Lot tracking will reconnect on the next sync.');
      }
    }
  } elseif ($action === 'remove') {
    $id = (int)($_POST['id'] ?? 0);
    $a = row('SELECT * FROM accounts WHERE id = ? AND user_id = ?', [$id, $u['id']]);
    if ($a && $a['status'] !== 'verified' && !val('SELECT COUNT(*) FROM commissions WHERE account_id = ?', [$id])) {
      q('DELETE FROM accounts WHERE id = ?', [$id]);
      flash('Account removed.');
    } else flash('Verified accounts with rebates cannot be removed. Contact support.', 'err');
  }
  redirect('accounts.php');
}

$accs = rows('SELECT a.*, b.name AS broker FROM accounts a JOIN brokers b ON b.id = a.broker_id WHERE a.user_id = ? ORDER BY a.created_at DESC', [$u['id']]);
$sel = (int)($_GET['broker'] ?? 0);
page_start('My trading accounts', 'accounts.php');
?>
<h1>My trading accounts</h1>

<div class="grid g2" style="align-items:start">
  <div class="card">
    <h2>Connect an MT4 / MT5 account</h2>
    <p class="muted small">Use the <b>investor (read-only)</b> password, never your master/trading password. Investor access can only view trades.</p>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="action" value="add">
      <div class="row2">
        <div class="field"><label>Broker</label>
          <select name="broker_id" required>
            <option value="">Choose…</option>
            <?php foreach ($brokers as $b): ?><option value="<?= $b['id'] ?>"<?= $sel === (int)$b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="field"><label>Platform</label>
          <select name="platform" required><option value="MT5">MT5</option><option value="MT4">MT4</option></select></div>
      </div>
      <div class="field"><label>Account number (login)</label><input type="text" name="login" inputmode="numeric" required placeholder="e.g. 51234567"></div>
      <div class="field"><label>Server</label><input type="text" name="server" required placeholder="e.g. OctaFX-Real2"><div class="hint">Exactly as shown in the broker's email or your MT4/MT5 login window.</div></div>
      <div class="field"><label>Investor password</label><input type="password" name="investor" required autocomplete="new-password"></div>
      <button class="btn btn-primary">Connect account</button>
    </form>
  </div>

  <div class="card">
    <h2>Don't have a partner account yet?</h2>
    <p class="muted">Rebates are paid only on accounts opened through our IB links.</p>
    <a class="btn btn-ghost" href="<?= e(url('brokers.php')) ?>">See partner brokers</a>
    <h3 style="margin-top:22px">Account status</h3>
    <p class="small muted" style="margin:0"><b>Pending</b>: we're checking the account is under our IB.<br><b>Verified</b>: lots are tracked and commissions count toward your rebate.<br><b>Rejected</b>: not under our IB. See the note, or contact support.</p>
  </div>
</div>

<div class="section">
  <h2>Your accounts</h2>
  <div class="table-wrap"><table>
    <tr><th>Broker</th><th>Platform</th><th>Account</th><th>Server</th><th>Status</th><th>Lot tracking</th><th></th></tr>
    <?php foreach ($accs as $a): ?>
    <tr>
      <td><?= e($a['broker']) ?></td><td><?= e($a['platform']) ?></td><td class="mono"><?= e($a['login']) ?></td><td class="small"><?= e($a['server']) ?></td>
      <td><?= status_badge($a['status']) ?><?php if ($a['admin_note']): ?><div class="small muted"><?= e($a['admin_note']) ?></div><?php endif; ?></td>
      <td class="small wrap-ok"><?php
        $src = setting('lot_source', 'none');
        if ($src === 'none' || ($src === 'vps' && $a['platform'] !== 'MT5')) echo '<span class="muted">Updated from broker reports</span>';
        elseif ($a['sync_error']) echo '<span style="color:var(--bear)">' . e($a['sync_error']) . '</span>';
        elseif ($a['last_sync']) echo 'Synced ' . e(date('d M, H:i', strtotime($a['last_sync'])));
        else echo '<span class="muted">Waiting for first sync</span>'; ?></td>
      <td>
        <details><summary class="small" style="cursor:pointer;color:var(--cyan)">Options</summary>
          <form method="post" style="margin-top:8px;display:flex;gap:6px;flex-wrap:wrap"><?= csrf_field() ?>
            <input type="hidden" name="action" value="update_pass"><input type="hidden" name="id" value="<?= $a['id'] ?>">
            <input type="password" name="investor" placeholder="New investor password" style="width:190px;padding:7px 10px" autocomplete="new-password">
            <button class="btn btn-ghost btn-sm">Update</button></form>
          <?php if ($a['status'] !== 'verified'): ?>
          <form method="post" style="margin-top:6px" onsubmit="return confirm('Remove this account?')"><?= csrf_field() ?>
            <input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-danger btn-sm">Remove</button></form>
          <?php endif; ?>
        </details>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$accs): ?><tr><td colspan="7" class="empty">No accounts connected yet.</td></tr><?php endif; ?>
  </table></div>
</div>
<?php page_end();
