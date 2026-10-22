<?php
require __DIR__ . '/inc/bootstrap.php';
$u = current_user();
$brokers = rows('SELECT * FROM brokers WHERE active = 1 ORDER BY sort_order, name');
page_start('Open an account', 'brokers.php');
?>
<div class="head-row">
  <div><h1>Open your trading account</h1><p class="muted" style="margin:0">Use these links so your account is registered under XDemat. Rebates only work for accounts opened this way.</p></div>
</div>
<?php if (!$u): ?><div class="alert alert-info">Tip: <a href="<?= e(url('register.php')) ?>">create your free rebate account</a> first, so you can connect your trading account right after opening it.</div><?php endif; ?>

<div class="grid g2">
<?php foreach ($brokers as $b): ?>
  <div class="card broker">
    <span class="blogo" style="background:<?= e($b['logo_bg']) ?>"><?php if ($b['logo']): ?><img src="<?= e($b['logo']) ?>" alt="<?= e($b['name']) ?>"><?php else: ?><b><?= e($b['name']) ?></b><?php endif; ?></span>
    <div class="head-row" style="margin:0"><h2><?= e($b['name']) ?></h2><span class="rate"><?= e(pct($b['rebate_pct'])) ?> back</span></div>
    <p class="muted" style="margin:0"><?= e($b['description']) ?></p>
    <p class="small muted" style="margin:0">Platforms: <?= e(str_replace(',', ' / ', $b['platforms'])) ?></p>
    <div class="btn-row">
      <a class="btn btn-primary" href="<?= e(url('go.php?b=' . $b['id'])) ?>" target="_blank" rel="noopener sponsored">Open account ↗</a>
      <?php if ($u): ?><a class="btn btn-ghost" href="<?= e(url('accounts.php?broker=' . $b['id'])) ?>">I've opened it — connect</a><?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<div class="card section">
  <h2>After you open the account</h2>
  <ol class="muted" style="margin:0;padding-left:1.2em">
    <li>Fund and verify your account with the broker as usual.</li>
    <li>In the broker's client area, find your MT4/MT5 <b>account number</b>, <b>server name</b> and <b>investor (read-only) password</b>. Most brokers let you set or reset the investor password from the account settings.</li>
    <li>Add them under <a href="<?= e(url('accounts.php')) ?>">My Accounts</a>. We check the account is under our IB and then start tracking your lots.</li>
  </ol>
</div>
<?php page_end();
