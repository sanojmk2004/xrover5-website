<?php
// Copy this file to /home/<your-cpanel-user>/rebate-config.php (one level ABOVE public_html)
// and fill in the values. Never commit the real file to GitHub.
return [
  // cPanel > MySQL Databases: create a database + user, give the user ALL PRIVILEGES on it.
  'db' => [
    'dsn'  => 'mysql:host=localhost;dbname=CPANELUSER_rebate;charset=utf8mb4',
    'user' => 'CPANELUSER_rebate',
    'pass' => 'CHANGE-ME',
  ],

  // 32 random bytes, base64. Encrypts investor passwords. Generate once with:
  //   php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
  // KEEP A BACKUP — if this key is lost, saved investor passwords cannot be decrypted.
  'app_key' => 'PASTE-BASE64-KEY-HERE',

  'site_url'   => 'https://xrover5.com',   // no trailing slash
  'base_path'  => '/rebate',
  'timezone'   => 'Asia/Kolkata',

  // Mailbox on this hosting used as the sender for password resets / notifications
  'mail_from'  => 'info@xrover5.com',
  'admin_email'=> 'info@xrover5.com',
];
