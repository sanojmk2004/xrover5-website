<?php
// Logged-in user's own details and password change.
require __DIR__ . '/inc/bootstrap.php';
$u = require_login();

if (is_post()) {
  csrf_check();
  if (($_POST['action'] ?? '') === 'details') {
    $name = post('name', 120); $phone = preg_replace('/[^\d+ ]/', '', post('phone', 30));
    if ($name === '') flash('Name cannot be empty.', 'err');
    else { q('UPDATE users SET name = ?, phone = ? WHERE id = ?', [$name, $phone, $u['id']]); flash('Details saved.'); }
  } else {
    $cur = (string)($_POST['current'] ?? ''); $new = (string)($_POST['new'] ?? '');
    $min = $u['role'] === 'admin' ? 10 : 8;
    if (!password_verify($cur, $u['pass_hash'])) flash('Current password is incorrect.', 'err');
    elseif (strlen($new) < $min) flash("New password must be at least $min characters.", 'err');
    elseif ($new !== (string)($_POST['confirm'] ?? '')) flash('New passwords do not match.', 'err');
    else { q('UPDATE users SET pass_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]); flash('Password changed.'); }
  }
  redirect('profile.php');
}

page_start('My profile', 'profile.php');
?>
<h1>My profile</h1>
<div class="grid g2" style="align-items:start">
  <form method="post" class="card">
    <?= csrf_field() ?><input type="hidden" name="action" value="details">
    <h2>Your details</h2>
    <div class="field"><label>Email</label><input type="email" value="<?= e($u['email']) ?>" disabled><div class="hint">Contact support to change your email.</div></div>
    <div class="field"><label>Full name</label><input type="text" name="name" value="<?= e($u['name']) ?>" required></div>
    <div class="field"><label>WhatsApp number</label><input type="tel" name="phone" value="<?= e($u['phone']) ?>"></div>
    <button class="btn btn-ghost">Save details</button>
  </form>
  <form method="post" class="card" autocomplete="off">
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <h2>Change password</h2>
    <div class="field"><label>Current password</label><input type="password" name="current" required autocomplete="current-password"></div>
    <div class="field"><label>New password</label><input type="password" name="new" required minlength="8" autocomplete="new-password"></div>
    <div class="field"><label>Repeat new password</label><input type="password" name="confirm" required minlength="8" autocomplete="new-password"></div>
    <button class="btn btn-primary">Change password</button>
  </form>
</div>
<?php page_end();
