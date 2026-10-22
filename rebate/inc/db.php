<?php
// Database access (MySQL on cPanel; SQLite also works for local testing).

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  global $CFG;
  $pdo = new PDO($CFG['db']['dsn'], $CFG['db']['user'] ?? null, $CFG['db']['pass'] ?? null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
  if (db_driver($pdo) === 'sqlite') $pdo->exec('PRAGMA foreign_keys = ON');
  return $pdo;
}

function db_driver(?PDO $pdo = null): string {
  return ($pdo ?? db())->getAttribute(PDO::ATTR_DRIVER_NAME);
}

function q(string $sql, array $args = []): PDOStatement {
  $st = db()->prepare($sql);
  $st->execute($args);
  return $st;
}
function row(string $sql, array $args = []): ?array { $r = q($sql, $args)->fetch(); return $r ?: null; }
function rows(string $sql, array $args = []): array { return q($sql, $args)->fetchAll(); }
function val(string $sql, array $args = []) { $v = q($sql, $args)->fetchColumn(); return $v === false ? null : $v; }
function now(): string { return date('Y-m-d H:i:s'); }

function setting(string $key, $default = null) {
  static $cache = null;
  if ($cache === null) {
    $cache = [];
    try { foreach (rows('SELECT k, v FROM settings') as $r) $cache[$r['k']] = $r['v']; } catch (Throwable $e) {}
  }
  return array_key_exists($key, $cache) && $cache[$key] !== '' ? $cache[$key] : $default;
}
function set_setting(string $key, string $value): void {
  q('DELETE FROM settings WHERE k = ?', [$key]);
  q('INSERT INTO settings (k, v) VALUES (?, ?)', [$key, $value]);
}

/** Customer's withdrawable balance in USD = confirmed rebates − withdrawals (pending or paid). */
function user_balance(int $uid): array {
  $earned = (float) val('SELECT COALESCE(SUM(rebate_usd),0) FROM commissions WHERE user_id = ?', [$uid]);
  $paid   = (float) val("SELECT COALESCE(SUM(amount_usd),0) FROM withdrawals WHERE user_id = ? AND status = 'paid'", [$uid]);
  $pend   = (float) val("SELECT COALESCE(SUM(amount_usd),0) FROM withdrawals WHERE user_id = ? AND status = 'pending'", [$uid]);
  return ['earned' => $earned, 'paid' => $paid, 'pending' => $pend, 'available' => round($earned - $paid - $pend, 2)];
}

/** Lots traded (from MetaApi sync) after the last confirmed commission date, and the estimated rebate on them. */
function account_estimate(array $acc): array {
  $last = val('SELECT MAX(period) FROM commissions WHERE account_id = ?', [$acc['id']]);
  $lots = (float) val('SELECT COALESCE(SUM(lots),0) FROM lot_days WHERE account_id = ?' . ($last ? ' AND day > ?' : ''),
                      $last ? [$acc['id'], $last] : [$acc['id']]);
  $b = row('SELECT est_commission_per_lot, rebate_pct FROM brokers WHERE id = ?', [$acc['broker_id']]);
  $est = $b ? $lots * (float)$b['est_commission_per_lot'] * (float)$b['rebate_pct'] / 100 : 0;
  return ['lots' => $lots, 'est_usd' => round($est, 2), 'since' => $last];
}
