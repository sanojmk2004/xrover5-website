<?php
// Page header/footer in the XRover 5 site style.

function page_start(string $title, string $active = '', bool $admin = false): void {
  $u = current_user();
  $nav = [];
  if ($admin) {
    $nav = ['admin/index.php' => 'Overview', 'admin/accounts.php' => 'Accounts', 'admin/commissions.php' => 'Commissions',
            'admin/withdrawals.php' => 'Payouts', 'admin/users.php' => 'Customers', 'admin/brokers.php' => 'Brokers',
            'admin/settings.php' => 'Settings', 'dashboard.php' => '← Customer view'];
  } elseif ($u) {
    $nav = ['dashboard.php' => 'Dashboard', 'brokers.php' => 'Open Account', 'accounts.php' => 'My Accounts', 'wallet.php' => 'Wallet & Payouts'];
    if ($u['role'] === 'admin') $nav['admin/index.php'] = 'Admin';
  } else {
    $nav = ['index.php' => 'How it works', 'brokers.php' => 'Brokers', 'login.php' => 'Log in'];
  }
  $css = url('assets/rebate.css') . '?v=' . @filemtime(dirname(__DIR__) . '/assets/rebate.css');
  ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?> | XRover 5 IB Rebates</title>
<meta name="description" content="Open your trading account with Octa or Switch Markets through XDemat and get part of the broker's IB commission back on every lot you trade, paid in USDT.">
<meta name="theme-color" content="#0a0b12">
<link rel="icon" type="image/png" href="/assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Unbounded:wght@400;600;800&family=Manrope:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="<?= e($css) ?>">
</head>
<body>
<header class="nav">
  <div class="wrap nav-inner">
    <a class="brand" href="/" aria-label="XRover 5 home"><img src="/assets/xrover5-logo.webp" alt="XRover 5" width="107" height="46"></a>
    <span class="brand-tag"><?= $admin ? 'Rebate Admin' : 'IB Rebates' ?></span>
    <button class="menu-btn" type="button" onclick="document.body.classList.toggle('nav-open')" aria-label="Menu">Menu</button>
    <nav class="links" aria-label="Rebate menu">
      <?php foreach ($nav as $href => $label): ?>
        <a href="<?= e(url($href)) ?>"<?= $active === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <?php if ($u): ?>
        <a href="<?= e(url('logout.php')) ?>" class="btn btn-ghost btn-sm">Log out</a>
      <?php else: ?>
        <a href="<?= e(url('register.php')) ?>" class="btn btn-primary btn-sm">Join free</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="wrap page">
<?= flashes() ?>
<?php
}

function page_end(): void { ?>
</main>
<footer>
  <div class="wrap">
    <div class="foot">
      <img src="/assets/xdemat-logo.webp" alt="XDemat" width="150" height="73">
      <nav aria-label="Footer"><a href="/">XRover 5 Home</a><a href="<?= e(url('index.php')) ?>">IB Rebates</a><a href="<?= e(url('terms.php')) ?>">Rebate Terms</a><a href="/contact/">Contact</a></nav>
      <small>© <?= date('Y') ?> XDemat. All rights reserved.</small>
    </div>
    <p class="risk"><b>Risk disclaimer:</b> Trading forex and CFDs involves a high level of risk and may not be suitable for all investors. You could lose some or all of your invested capital. Rebates are a share of the introducing-broker commission the broker pays XDemat and are not a reason to trade more. Make sure trading with an offshore broker is permitted where you live. Nothing on this website is financial advice.</p>
  </div>
</footer>
</body>
</html>
<?php }
