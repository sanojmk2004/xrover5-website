<?php
require __DIR__ . '/inc/bootstrap.php';
$brokers = rows('SELECT * FROM brokers WHERE active = 1 ORDER BY sort_order, name');
$maxPct = $brokers ? max(array_map(fn($b) => (float)$b['rebate_pct'], $brokers)) : 50;
$upTo = $brokers && min(array_map(fn($b) => (float)$b['rebate_pct'], $brokers)) < $maxPct ? 'up to ' : '';
$estPerLot = $brokers ? max(array_map(fn($b) => (float)$b['est_commission_per_lot'], $brokers)) : 0;
page_start('Get paid back on every lot', 'index.php');
?>
<section class="hero">
  <div>
    <div class="eyebrow">XDemat IB Rebate Program</div>
    <h1>Trade as usual.<br>Get commission back in USDT.</h1>
    <p class="lead">Open your MT4 or MT5 account with our partner brokers through XDemat. The broker pays us an introducing-broker commission on your trades, and we share <b><?= $upTo ?><?= e(pct($maxPct)) ?></b> of it back to you, paid to your crypto wallet.</p>
    <div class="btn-row">
      <a class="btn btn-primary" href="<?= e(url(current_user() ? 'brokers.php' : 'register.php')) ?>">Start earning rebates</a>
      <a class="btn btn-ghost" href="#how">How it works</a>
    </div>
  </div>
  <?php if ($estPerLot > 0): ?>
  <div class="calc">
    <label for="lotsIn">Lots you trade per month</label>
    <input id="lotsIn" type="number" min="0" step="1" value="20">
    <p class="small muted" style="margin:12px 0 0">Estimated monthly rebate</p>
    <output id="rebOut">—</output>
    <p class="small muted" style="margin:6px 0 0">Estimate only. Your actual rebate is <?= $upTo ?><?= e(pct($maxPct)) ?> of the commission the broker actually pays on your account.</p>
  </div>
  <script>
  (function(){var i=document.getElementById('lotsIn'),o=document.getElementById('rebOut'),r=<?= json_encode($estPerLot * $maxPct / 100) ?>;
  function u(){o.textContent='$'+((parseFloat(i.value)||0)*r).toFixed(2)}i.addEventListener('input',u);u();})();
  </script>
  <?php else: ?>
  <div class="calc">
    <h3>Paid in crypto</h3>
    <p class="muted">Add your USDT wallet (TRC20 or BEP20) and request a payout whenever your balance reaches the minimum of <?= usd(setting('min_withdrawal', 10)) ?>.</p>
  </div>
  <?php endif; ?>
</section>

<section class="section" id="how">
  <h2>How it works</h2>
  <ol class="steps">
    <li><b>Create your free account</b><span>Register on this page with your email and WhatsApp number.</span></li>
    <li><b>Open a broker account via our link</b><span>Choose Octa or Switch Markets below. Using our link puts your account under XDemat as introducing broker.</span></li>
    <li><b>Connect MT4 / MT5 (read-only)</b><span>Add your account number, server and <i>investor</i> password so we can see your traded lots. Investor access cannot place trades or withdraw.</span></li>
    <li><b>Get paid in USDT</b><span>When the broker pays our commission, your share is added to your balance. Request a payout to your saved wallet.</span></li>
  </ol>
</section>

<section class="section">
  <div class="head-row"><h2>Partner brokers</h2><a href="<?= e(url('brokers.php')) ?>">All brokers →</a></div>
  <div class="grid g3">
    <?php foreach ($brokers as $b): ?>
      <div class="card broker">
        <span class="blogo" style="background:<?= e($b['logo_bg']) ?>"><?php if ($b['logo']): ?><img src="<?= e($b['logo']) ?>" alt="<?= e($b['name']) ?>"><?php else: ?><b><?= e($b['name']) ?></b><?php endif; ?></span>
        <h3 style="margin:0"><?= e($b['name']) ?></h3>
        <div class="rate"><?= e(pct($b['rebate_pct'])) ?> commission back</div>
        <p class="muted small" style="margin:0"><?= e($b['description']) ?> · <?= e(str_replace(',', ' / ', $b['platforms'])) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section card">
  <h2>Questions</h2>
  <div class="grid g2">
    <div><h3>Does it cost me anything?</h3><p class="muted">No. Your spreads and commissions are the broker's normal ones. The rebate comes out of the commission the broker pays XDemat.</p></div>
    <div><h3>Is my investor password safe?</h3><p class="muted">The investor (read-only) password can only view trades. It cannot trade or move money. We store it encrypted and use it only to read your lot history.</p></div>
    <div><h3>I already have an account with the broker.</h3><p class="muted">Rebates only work for accounts opened through our link. Open a new account via the button on the Brokers page; existing accounts can sometimes be moved by the broker on request.</p></div>
    <div><h3>When do I get paid?</h3><p class="muted">Rebates are confirmed when the broker's IB commission report arrives. Payout requests are processed <?= e(setting('payout_days', 'weekly')) ?> in USDT.</p></div>
  </div>
</section>
<?php page_end();
