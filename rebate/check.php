<?php
// Setup checker: shows why the rebate portal can't start (PHP version, extensions, config, database).
// Written in old-style PHP so it runs even on PHP versions too old for the portal itself.
// Turns itself off once install.php has created the admin (home/rebate-installed.lock).
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');

$home = dirname(dirname(dirname(__FILE__)));
$cfgFile = getenv('REBATE_CONFIG') ? getenv('REBATE_CONFIG') : $home . '/rebate-config.php';
if (file_exists(dirname($cfgFile) . '/rebate-installed.lock')) { header('HTTP/1.0 404 Not Found'); exit('Not found'); }

$rows = array();
function add_row(&$rows, $label, $ok, $detail) { $rows[] = array($label, $ok, $detail); }

$ver = PHP_VERSION;
add_row($rows, 'PHP version', version_compare($ver, '8.1.0', '>='), $ver . (version_compare($ver, '8.1.0', '>=') ? '' : ' — needs 8.1 or higher: cPanel > Select PHP Version'));
foreach (array('pdo_mysql', 'sodium', 'curl', 'mbstring') as $ext) {
  $has = extension_loaded($ext);
  add_row($rows, 'Extension ' . $ext, $has, $has ? 'loaded' : 'missing — cPanel > Select PHP Version > Extensions, tick ' . $ext);
}

$cfgOk = file_exists($cfgFile);
add_row($rows, 'Config file', $cfgOk, $cfgOk ? 'found' : 'not found. It must be named rebate-config.php and sit in the same folder that contains public_html');
$cfg = null;
if ($cfgOk) {
  try {
    $cfg = include $cfgFile;
    add_row($rows, 'Config readable', is_array($cfg), is_array($cfg) ? 'ok' : 'file does not return the settings — make sure it starts with <?php and you pasted the whole text');
  } catch (Throwable $e) {
    add_row($rows, 'Config readable', false, 'syntax error in rebate-config.php: ' . htmlspecialchars($e->getMessage()) . ' (line ' . $e->getLine() . ') — usually a missing quote or comma');
    $cfg = null;
  }
}
if (is_array($cfg)) {
  $key = isset($cfg['app_key']) ? base64_decode($cfg['app_key'], true) : false;
  add_row($rows, 'app_key', $key !== false && strlen($key) === 32, ($key !== false && strlen($key) === 32) ? 'ok' : 'app_key is wrong — copy it exactly, including the = at the end');
  if (extension_loaded('pdo_mysql')) {
    try {
      $pdo = new PDO($cfg['db']['dsn'], isset($cfg['db']['user']) ? $cfg['db']['user'] : null, isset($cfg['db']['pass']) ? $cfg['db']['pass'] : null, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
      $dbn = $pdo->query('SELECT DATABASE()')->fetchColumn();
      add_row($rows, 'Database connection', true, 'connected to ' . htmlspecialchars((string)$dbn));
      $foreign = false;
      try { $pdo->query('SELECT 1 FROM users LIMIT 1'); try { $pdo->query('SELECT pass_hash FROM users LIMIT 1'); } catch (Throwable $e2) { $foreign = true; } } catch (Throwable $e3) {}
      add_row($rows, 'Own database', !$foreign, $foreign ? 'this database belongs to another app (it has a different users table, e.g. the CRM). Create a separate database for the rebate portal' : 'ok');
    } catch (Throwable $e) {
      $m = $e->getMessage();
      $hint = 'check the database name, user and password in rebate-config.php';
      if (strpos($m, '1045') !== false) $hint = 'username or password is wrong, or the user is not added to the database (cPanel > MySQL Databases > Add User To Database, ALL PRIVILEGES)';
      if (strpos($m, '1044') !== false) $hint = 'user has no access to this database — cPanel > MySQL Databases > Add User To Database, tick ALL PRIVILEGES';
      if (strpos($m, '1049') !== false) $hint = 'database name is wrong — use the full name with the prefix, as listed under Current Databases';
      add_row($rows, 'Database connection', false, htmlspecialchars(preg_replace('/\(using password:.*\)/', '', $m)) . ' — ' . $hint);
    }
  }
}
$allOk = true; foreach ($rows as $r) if (!$r[1]) $allOk = false;
?><!doctype html><html><head><meta name="viewport" content="width=device-width"><title>Rebate setup check</title>
<style>body{background:#0a0b12;color:#eceef6;font-family:system-ui,sans-serif;padding:24px;max-width:820px;margin:auto}td{padding:10px;border-bottom:1px solid #272c3d;vertical-align:top}.ok{color:#34d399}.bad{color:#fb6f84}a{color:#45cfff}</style></head><body>
<h1>Rebate portal — setup check</h1>
<table><?php foreach ($rows as $r): ?><tr><td><?php echo $r[0]; ?></td><td class="<?php echo $r[1] ? 'ok' : 'bad'; ?>"><?php echo $r[1] ? '✔' : '✘'; ?></td><td><?php echo $r[2]; ?></td></tr><?php endforeach; ?></table>
<p><?php echo $allOk ? 'All good — now open <a href="install.php">install.php</a>.' : 'Fix the red items above, then reload this page.'; ?></p>
</body></html>
