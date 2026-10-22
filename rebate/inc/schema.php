<?php
// Table definitions. Written once by install.php. Works on MySQL (cPanel) and SQLite (local testing).

function schema_sql(string $driver): array {
  $my  = $driver === 'mysql';
  $id  = $my ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
  $ref = $my ? 'INT UNSIGNED' : 'INTEGER';
  $end = $my ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
  $txt = $my ? 'TEXT' : 'TEXT';
  return [
"CREATE TABLE IF NOT EXISTS settings (
  k VARCHAR(64) NOT NULL PRIMARY KEY,
  v $txt
)$end",

"CREATE TABLE IF NOT EXISTS users (
  id $id,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(30) NOT NULL DEFAULT '',
  pass_hash VARCHAR(255) NOT NULL,
  role VARCHAR(10) NOT NULL DEFAULT 'customer',
  status VARCHAR(10) NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  last_login DATETIME NULL
)$end",

"CREATE TABLE IF NOT EXISTS password_resets (
  id $id,
  user_id $ref NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used TINYINT NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)$end",

"CREATE TABLE IF NOT EXISTS brokers (
  id $id,
  name VARCHAR(80) NOT NULL,
  logo VARCHAR(255) NOT NULL DEFAULT '',
  logo_bg VARCHAR(20) NOT NULL DEFAULT '#151823',
  ib_link VARCHAR(500) NOT NULL,
  description VARCHAR(500) NOT NULL DEFAULT '',
  platforms VARCHAR(20) NOT NULL DEFAULT 'MT4,MT5',
  servers $txt,
  rebate_pct DECIMAL(5,2) NOT NULL DEFAULT 50,
  est_commission_per_lot DECIMAL(10,2) NOT NULL DEFAULT 0,
  csv_map $txt,
  sort_order INT NOT NULL DEFAULT 0,
  active TINYINT NOT NULL DEFAULT 1,
  clicks INT NOT NULL DEFAULT 0
)$end",

"CREATE TABLE IF NOT EXISTS accounts (
  id $id,
  user_id $ref NOT NULL,
  broker_id $ref NOT NULL,
  platform VARCHAR(5) NOT NULL,
  login VARCHAR(30) NOT NULL,
  server VARCHAR(120) NOT NULL,
  investor_enc $txt,
  status VARCHAR(12) NOT NULL DEFAULT 'pending',
  admin_note VARCHAR(255) NOT NULL DEFAULT '',
  metaapi_id VARCHAR(80) NOT NULL DEFAULT '',
  metaapi_state VARCHAR(40) NOT NULL DEFAULT '',
  sync_error VARCHAR(255) NOT NULL DEFAULT '',
  last_sync DATETIME NULL,
  synced_until DATETIME NULL,
  created_at DATETIME NOT NULL,
  UNIQUE (broker_id, login),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (broker_id) REFERENCES brokers(id)
)$end",

"CREATE TABLE IF NOT EXISTS lot_days (
  account_id $ref NOT NULL,
  day DATE NOT NULL,
  lots DECIMAL(12,2) NOT NULL DEFAULT 0,
  deals INT NOT NULL DEFAULT 0,
  PRIMARY KEY (account_id, day),
  FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
)$end",

"CREATE TABLE IF NOT EXISTS commissions (
  id $id,
  user_id $ref NOT NULL,
  account_id $ref NOT NULL,
  broker_id $ref NOT NULL,
  period DATE NOT NULL,
  lots DECIMAL(12,2) NOT NULL DEFAULT 0,
  commission_usd DECIMAL(12,2) NOT NULL,
  rebate_pct DECIMAL(5,2) NOT NULL,
  rebate_usd DECIMAL(12,2) NOT NULL,
  source VARCHAR(10) NOT NULL DEFAULT 'import',
  ref CHAR(40) NOT NULL UNIQUE,
  batch VARCHAR(40) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
)$end",

"CREATE TABLE IF NOT EXISTS wallets (
  id $id,
  user_id $ref NOT NULL,
  network VARCHAR(20) NOT NULL,
  address VARCHAR(120) NOT NULL,
  label VARCHAR(60) NOT NULL DEFAULT '',
  is_default TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)$end",

"CREATE TABLE IF NOT EXISTS withdrawals (
  id $id,
  user_id $ref NOT NULL,
  amount_usd DECIMAL(12,2) NOT NULL,
  network VARCHAR(20) NOT NULL,
  address VARCHAR(120) NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'pending',
  tx_hash VARCHAR(120) NOT NULL DEFAULT '',
  admin_note VARCHAR(255) NOT NULL DEFAULT '',
  requested_at DATETIME NOT NULL,
  processed_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)$end",
  ];
}
