<?php
require __DIR__ . '/inc/bootstrap.php';
if (current_user()) redirect('dashboard.php');

$err = '';
if (is_post()) {
  csrf_check();
  if (login_throttled()) $err = 'Too many attempts. Please wait 15 minutes and try again.';
  else {
    $u = row('SELECT * FROM users WHERE email = ?', [strtolower(post('email', 190))]);
    if ($u && password_verify((string)($_POST['password'] ?? ''), $u['pass_hash'])) {
      if ($u['status'] !== 'active') $err = 'This account is disabled. Please contact support.';
      else {
        if (password_needs_rehash($u['pass_hash'], PASSWORD_DEFAULT)) q('UPDATE users SET pass_hash = ? WHERE id = ?', [password_hash($_POST['password'], PASSWORD_DEFAULT), $u['id']]);
        login_user($u);
        $to = $_SESSION['after_login'] ?? ''; unset($_SESSION['after_login']);
        if ($to && str_starts_with($to, BASE . '/')) { header('Location: ' . $to); exit; }
        redirect($u['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
      }
    } else { login_failed(); $err = 'Email or password is incorrect.'; }
  }
}

page_start('Log in', 'login.php');
?>
<div class="card form-narrow">
  <h1>Log in</h1>
  <?php if ($err): ?><div class="alert alert-err"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label for="m">Email</label><input id="m" type="email" name="email" required value="<?= e(post('email')) ?>" autocomplete="email"></div>
    <div class="field"><label for="pw">Password</label><input id="pw" type="password" name="password" required autocomplete="current-password"></div>
    <button class="btn btn-primary" style="width:100%">Log in</button>
  </form>
  <p class="small muted" style="margin-top:16px"><a href="<?= e(url('forgot.php')) ?>">Forgot password?</a> · New here? <a href="<?= e(url('register.php')) ?>">Create an account</a></p>
</div>
<?php page_end();
