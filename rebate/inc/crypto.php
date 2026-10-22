<?php
// Investor passwords are stored encrypted (libsodium secretbox) with the app_key from the config file.

function app_key(): string {
  global $CFG;
  $k = base64_decode($CFG['app_key'] ?? '', true);
  if ($k === false || strlen($k) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
    throw new RuntimeException('app_key in rebate-config.php must be 32 random bytes, base64-encoded.');
  }
  return $k;
}
function encrypt_secret(string $plain): string {
  $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
  return base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, app_key()));
}
function decrypt_secret(string $enc): ?string {
  $raw = base64_decode($enc, true);
  if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) return null;
  $out = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
                                      substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), app_key());
  return $out === false ? null : $out;
}
