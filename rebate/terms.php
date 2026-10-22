<?php
require __DIR__ . '/inc/bootstrap.php';
page_start('Rebate terms');
$custom = setting('terms_html');
?>
<div class="card" style="max-width:820px">
  <h1>Rebate terms</h1>
  <?php if ($custom): ?>
    <div><?= $custom /* admin-written HTML from Settings */ ?></div>
  <?php else: ?>
    <ol class="muted">
      <li>Rebates apply only to trading accounts opened through the XDemat partner links on this website and accepted by the broker under XDemat's introducing-broker (IB) account.</li>
      <li>Your rebate is the percentage shown for each broker, applied to the IB commission the broker actually pays XDemat for your account. If the broker does not pay, or later reverses, a commission, no rebate is due for it.</li>
      <li>Lot figures from your connected MT4/MT5 account are an estimate. Only commissions confirmed by the broker's IB report are added to your withdrawable balance.</li>
      <li>Payouts are made in USDT (or another listed stablecoin) to the wallet address you provide. Check the address and network carefully: crypto transfers cannot be reversed, and XDemat is not responsible for funds sent to an address you entered incorrectly. Network fees may be deducted.</li>
      <li>The minimum payout is <?= usd(setting('min_withdrawal', 10)) ?>. XDemat may change rebate percentages with notice on this page; changes apply to commissions confirmed after the change.</li>
      <li>Accounts used for abusive trading, arbitrage of the rebate itself, or that breach the broker's terms may be excluded and pending rebates cancelled.</li>
      <li>You are responsible for making sure that trading with the broker is legal where you live and for any taxes on rebates you receive.</li>
      <li>Trading forex and CFDs is high risk. Rebates are not a reason to trade more than you otherwise would.</li>
    </ol>
  <?php endif; ?>
</div>
<?php page_end();
