<?php
// One-time setup: creates the tables, the two brokers and the first admin login.
// Locks itself automatically once an admin exists.
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/schema.php';

foreach (schema_sql(db_driver()) as $sql) db()->exec($sql);

// Refuse to run inside another app's database (e.g. the CRM), where a 'users' table already exists with other columns.
try { db()->query('SELECT pass_hash, role, status FROM users LIMIT 1'); }
catch (Throwable $ex) {
  page_start('Setup');
  echo '<div class="card form-narrow"><h1>Wrong database</h1><p>The database in rebate-config.php already has a <b>users</b> table from another app (for example the CRM). The rebate portal needs its own empty database.</p><p class="muted">cPanel → MySQL Databases: create a new database (e.g. ibrebate) and user, add the user with ALL PRIVILEGES, put those details in rebate-config.php, then open this page again.</p></div>';
  page_end();
  exit;
}

$lockFile = dirname($cfgFile) . '/rebate-installed.lock'; // also switches off check.php
if (val("SELECT COUNT(*) FROM users WHERE role = 'admin'") > 0) {
  @touch($lockFile);
  page_start('Setup');
  echo '<div class="card form-narrow"><h1>Already set up</h1><p class="muted">Tables are up to date. An admin account exists, so setup is locked.</p><a class="btn btn-primary" href="' . e(url('login.php')) . '">Go to login</a></div>';
  page_end();
  exit;
}

$err = '';
if (is_post()) {
  csrf_check();
  $name = post('name', 120); $email = strtolower(post('email', 190)); $pass = (string)($_POST['password'] ?? '');
  try { app_key(); } catch (Throwable $ex) { $err = $ex->getMessage(); }
  if (!$err && (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 10)) $err = 'Enter a name, a valid email and a password of at least 10 characters.';
  if (!$err) {
    q("INSERT INTO users (name, email, pass_hash, role, created_at) VALUES (?, ?, ?, 'admin', ?)", [$name, $email, password_hash($pass, PASSWORD_DEFAULT), now()]);
    if (!val('SELECT COUNT(*) FROM brokers')) {
      q("INSERT INTO brokers (name, logo, logo_bg, ib_link, description, platforms, rebate_pct, est_commission_per_lot, sort_order) VALUES (?,?,?,?,?,?,?,?,?)",
        ['Octa', '/assets/broker-octa.png', '#c4c0ad', 'https://clickto.trade/bWvhiqPpr8h?ib=7377800',
         'Forex, gold and CFD trading on MT4 and MT5.', 'MT4,MT5', 50, 0, 1]);
      q("INSERT INTO brokers (name, logo, logo_bg, ib_link, description, platforms, rebate_pct, est_commission_per_lot, sort_order) VALUES (?,?,?,?,?,?,?,?,?)",
        ['Switch Markets', '/assets/broker-switch.png', '#000c2e', 'https://cabinet.switchmarkets.com/links/go/14777',
         'Forex, gold and CFD trading on MT4 and MT5.', 'MT4,MT5', 50, 0, 2]);
    }
    foreach (['min_withdrawal' => '10', 'metaapi_region' => 'new-york', 'payout_days' => 'every Monday'] as $k => $v) {
      if (setting($k) === null) set_setting($k, $v);
    }
    @touch($lockFile);
    flash('Setup complete. Log in with your new admin account, then set rebate % and estimated commission per lot under Admin → Brokers.');
    redirect('login.php');
  }
}

page_start('Setup');
?>
<div class="card form-narrow">
  <h1>Rebate portal setup</h1>
  <p class="muted">Tables were created. Create the first admin login.</p>
  <?php if ($err): ?><div class="alert alert-err"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label>Your name</label><input type="text" name="name" required value="<?= e(post('name')) ?>"></div>
    <div class="field"><label>Admin email</label><input type="email" name="email" required value="<?= e(post('email')) ?>"></div>
    <div class="field"><label>Password (min 10 characters)</label><input type="password" name="password" required minlength="10"></div>
    <button class="btn btn-primary">Create admin</button>
  </form>
</div>
<?php page_end();
