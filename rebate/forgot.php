<?php
// Password reset: emails a one-hour link; the reset form is on the same page (?t=token).
require __DIR__ . '/inc/bootstrap.php';
global $CFG;

$token = (string)($_GET['t'] ?? $_POST['t'] ?? '');
$reset = null;
if ($token !== '') {
  $reset = row('SELECT * FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > ?', [hash('sha256', $token), now()]);
}

if (is_post()) {
  csrf_check();
  if ($token !== '') {
    $pass = (string)($_POST['password'] ?? '');
    if (!$reset) { flash('This reset link has expired. Please request a new one.', 'err'); redirect('forgot.php'); }
    if (strlen($pass) < 8) { flash('Password must be at least 8 characters.', 'err'); redirect('forgot.php?t=' . urlencode($token)); }
    q('UPDATE users SET pass_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $reset['user_id']]);
    q('UPDATE password_resets SET used = 1 WHERE user_id = ?', [$reset['user_id']]);
    flash('Password changed. Please log in.');
    redirect('login.php');
  }
  $u = row("SELECT * FROM users WHERE email = ? AND status = 'active'", [strtolower(post('email', 190))]);
  if ($u && (int)val('SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND expires_at > ?', [$u['id'], now()]) < 3) {
    $t = bin2hex(random_bytes(24));
    q('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)', [$u['id'], hash('sha256', $t), date('Y-m-d H:i:s', time() + 3600)]);
    $link = rtrim($CFG['site_url'] ?? '', '/') . url('forgot.php?t=' . $t);
    send_mail($u['email'], 'Reset your XRover 5 Rebates password',
      "Hi {$u['name']},\n\nUse this link within one hour to set a new password:\n$link\n\nIf you did not ask for this, ignore this email.\n\nXDemat");
  }
  flash('If that email is registered, a reset link is on its way. Check spam too.', 'info');
  redirect('forgot.php');
}

page_start('Reset password');
?>
<div class="card form-narrow">
  <?php if ($token !== '' && $reset): ?>
    <h1>Set a new password</h1>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="t" value="<?= e($token) ?>">
      <div class="field"><label>New password</label><input type="password" name="password" required minlength="8" autocomplete="new-password"></div>
      <button class="btn btn-primary">Save password</button>
    </form>
  <?php elseif ($token !== ''): ?>
    <h1>Link expired</h1><p class="muted">This reset link is no longer valid.</p><a class="btn btn-primary" href="<?= e(url('forgot.php')) ?>">Request a new link</a>
  <?php else: ?>
    <h1>Forgot password</h1>
    <p class="muted">Enter your email and we'll send you a reset link.</p>
    <form method="post">
      <?= csrf_field() ?>
      <div class="field"><label>Email</label><input type="email" name="email" required></div>
      <button class="btn btn-primary">Send reset link</button>
    </form>
  <?php endif; ?>
</div>
<?php page_end();
