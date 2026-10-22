<?php
// Login state and access control.

function current_user(): ?array {
  static $u = false;
  if ($u !== false) return $u;
  $u = null;
  if (!empty($_SESSION['uid'])) {
    $u = row("SELECT * FROM users WHERE id = ? AND status = 'active'", [$_SESSION['uid']]);
    if (!$u) unset($_SESSION['uid']);
  }
  return $u;
}
function require_login(): array {
  $u = current_user();
  if (!$u) { $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? ''; redirect('login.php'); }
  return $u;
}
function require_admin(): array {
  $u = require_login();
  if ($u['role'] !== 'admin') { http_response_code(403); exit('Admins only.'); }
  return $u;
}
function login_user(array $u): void {
  session_regenerate_id(true);
  $_SESSION['uid'] = (int)$u['id'];
  q('UPDATE users SET last_login = ? WHERE id = ?', [now(), $u['id']]);
}

/** Simple brute-force brake: max 8 failed logins per 15 minutes per session/IP pair. */
function login_throttled(): bool {
  $f = array_filter($_SESSION['fails'] ?? [], fn($t) => $t > time() - 900);
  $_SESSION['fails'] = $f;
  return count($f) >= 8;
}
function login_failed(): void { $_SESSION['fails'][] = time(); usleep(400000); }
