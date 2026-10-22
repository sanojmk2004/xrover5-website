<?php
// Small helpers: escaping, CSRF, flash messages, redirects, money formatting, mail.

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function usd($n): string { return '$' . number_format((float)$n, 2); }
function lots($n): string { return number_format((float)$n, 2); }
function post(string $k, int $max = 500): string { return mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max); }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'; }

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
  if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
    http_response_code(400);
    exit('Your session expired. Please go back, refresh the page and try again.');
  }
}

function flash(string $msg, string $type = 'ok'): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): string {
  $out = '';
  foreach ($_SESSION['flash'] ?? [] as [$t, $m]) $out .= '<div class="alert alert-' . e($t) . '">' . e($m) . '</div>';
  unset($_SESSION['flash']);
  return $out;
}

const NETWORKS = [
  'USDT-TRC20' => 'USDT · TRON (TRC20)',
  'USDT-BEP20' => 'USDT · BNB Smart Chain (BEP20)',
  'USDT-ERC20' => 'USDT · Ethereum (ERC20)',
  'USDC-BEP20' => 'USDC · BNB Smart Chain (BEP20)',
];

function valid_address(string $network, string $addr): bool {
  if (str_ends_with($network, 'TRC20')) return (bool)preg_match('/^T[1-9A-HJ-NP-Za-km-z]{33}$/', $addr);
  return (bool)preg_match('/^0x[0-9a-fA-F]{40}$/', $addr); // BEP20 / ERC20
}
function short_addr(string $a): string { return strlen($a) > 16 ? substr($a, 0, 8) . '…' . substr($a, -6) : $a; }

function send_mail(string $to, string $subject, string $body): bool {
  global $CFG;
  $from = $CFG['mail_from'] ?? 'info@xrover5.com';
  $headers = "From: XRover 5 Rebates <$from>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
  $subj = '=?UTF-8?B?' . base64_encode($subject) . '?=';
  return @mail($to, $subj, $body, $headers, '-f' . $from) || @mail($to, $subj, $body, $headers);
}
function notify_admin(string $subject, string $body): void {
  global $CFG;
  if (!empty($CFG['admin_email'])) send_mail($CFG['admin_email'], $subject, $body);
}

function status_badge(string $s): string {
  $map = ['pending' => 'warn', 'verified' => 'ok', 'paid' => 'ok', 'active' => 'ok', 'rejected' => 'bad', 'disabled' => 'bad'];
  return '<span class="badge badge-' . ($map[$s] ?? 'muted') . '">' . e(ucfirst($s)) . '</span>';
}

function pct($n): string { return rtrim(rtrim(number_format((float)$n, 2), '0'), '.') . '%'; }
