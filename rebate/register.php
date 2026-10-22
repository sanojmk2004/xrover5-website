<?php
require __DIR__ . '/inc/bootstrap.php';
if (current_user()) redirect('dashboard.php');

$err = '';
if (is_post()) {
  csrf_check();
  $name  = post('name', 120);
  $email = strtolower(post('email', 190));
  $phone = preg_replace('/[^\d+ ]/', '', post('phone', 30));
  $pass  = (string)($_POST['password'] ?? '');
  if (!empty($_POST['website'])) redirect('index.php'); // spam trap
  if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Please enter your name and a valid email.';
  elseif (strlen(preg_replace('/\D/', '', $phone)) < 10) $err = 'Please enter your WhatsApp number with country code.';
  elseif (strlen($pass) < 8) $err = 'Password must be at least 8 characters.';
  elseif (empty($_POST['terms'])) $err = 'Please accept the rebate terms.';
  elseif (val('SELECT id FROM users WHERE email = ?', [$email])) $err = 'An account with this email already exists. Please log in.';
  else {
    q('INSERT INTO users (name, email, phone, pass_hash, created_at) VALUES (?, ?, ?, ?, ?)',
      [$name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT), now()]);
    login_user(row('SELECT * FROM users WHERE email = ?', [$email]));
    flash('Welcome! Next step: open a trading account through one of our broker links below.');
    redirect('brokers.php');
  }
}

page_start('Create your account', 'register.php');
?>
<div class="card form-narrow">
  <h1>Join free</h1>
  <p class="muted">Create your rebate account, open a broker account through our link, and get commission back on every lot.</p>
  <?php if ($err): ?><div class="alert alert-err"><?= e($err) ?></div><?php endif; ?>
  <form method="post" autocomplete="on">
    <?= csrf_field() ?>
    <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
    <div class="field"><label for="n">Full name</label><input id="n" type="text" name="name" required value="<?= e(post('name')) ?>" autocomplete="name"></div>
    <div class="field"><label for="m">Email</label><input id="m" type="email" name="email" required value="<?= e(post('email')) ?>" autocomplete="email"></div>
    <div class="field"><label for="p">WhatsApp number</label><input id="p" type="tel" name="phone" required placeholder="+91 98765 43210" value="<?= e(post('phone')) ?>" autocomplete="tel"></div>
    <div class="field"><label for="pw">Password</label><input id="pw" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
    <div class="field"><label class="check"><input type="checkbox" name="terms" value="1" required> <span>I agree to the <a href="<?= e(url('terms.php')) ?>" target="_blank">rebate terms</a> and understand trading carries high risk.</span></label></div>
    <button class="btn btn-primary" style="width:100%">Create account</button>
  </form>
  <p class="small muted" style="margin-top:16px">Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a></p>
</div>
<?php page_end();
