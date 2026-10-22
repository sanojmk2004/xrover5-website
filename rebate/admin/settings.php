<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require dirname(__DIR__) . '/inc/metaapi.php';
require_admin();

if (is_post()) {
  csrf_check();
  if (($_POST['action'] ?? '') === 'test') {
    try { [$code, $b] = ma_http('GET', MA_PROV . '/users/current/accounts?limit=1');
      flash($code === 200 ? 'MetaApi connection works.' : ma_err($b, $code), $code === 200 ? 'ok' : 'err');
    } catch (Throwable $e) { flash($e->getMessage(), 'err'); }
    redirect('admin/settings.php');
  }
  if (($_POST['action'] ?? '') === 'vps_token') {
    $t = bin2hex(random_bytes(24));
    set_setting('vps_token_hash', hash('sha256', $t));
    $_SESSION['new_vps_token'] = $t;   // shown once on the next page load
    flash('New VPS sync token created. Copy it into config.json on the VPS now — it will not be shown again. Any old token stops working.');
    redirect('admin/settings.php#lots');
  }
  set_setting('lot_source', in_array($_POST['lot_source'] ?? '', ['none', 'vps', 'metaapi'], true) ? $_POST['lot_source'] : 'none');
  set_setting('min_withdrawal', (string)max(1, (float)post('min_withdrawal', 10)));
  set_setting('payout_days', post('payout_days', 60) ?: 'weekly');
  set_setting('metaapi_region', preg_replace('/[^a-z0-9-]/', '', post('metaapi_region', 40)) ?: 'new-york');
  set_setting('metaapi_undeploy', empty($_POST['metaapi_undeploy']) ? '0' : '1');
  set_setting('terms_html', (string)($_POST['terms_html'] ?? ''));
  $tok = trim((string)($_POST['metaapi_token'] ?? ''));
  if ($tok === '-') set_setting('metaapi_token_enc', '');
  elseif ($tok !== '') set_setting('metaapi_token_enc', encrypt_secret($tok));
  flash('Settings saved.');
  redirect('admin/settings.php');
}
page_start('Settings', 'admin/settings.php', true);
$hasTok = (bool) setting('metaapi_token_enc');
$src = setting('lot_source', 'none');
$newTok = $_SESSION['new_vps_token'] ?? null; unset($_SESSION['new_vps_token']);
$seen = setting('vps_last_seen');
$brokerList = rows('SELECT id, name FROM brokers ORDER BY sort_order, name');
?>
<h1>Settings</h1>
<form method="post" class="grid g2" style="align-items:start">
  <?= csrf_field() ?>
  <div class="card">
    <h2>Payouts</h2>
    <div class="field"><label>Minimum payout (USD)</label><input type="number" name="min_withdrawal" step="0.01" min="1" value="<?= e(setting('min_withdrawal', 10)) ?>"></div>
    <div class="field"><label>Payout schedule (shown to customers)</label><input type="text" name="payout_days" value="<?= e(setting('payout_days', 'every Monday')) ?>"><div class="hint">e.g. "every Monday" or "within 48 hours"</div></div>
    <h2 style="margin-top:24px">Rebate terms (optional)</h2>
    <div class="field"><textarea name="terms_html" rows="8" placeholder="Leave empty to use the built-in terms. Basic HTML allowed."><?= e(setting('terms_html', '')) ?></textarea></div>
  </div>
  <div class="card" id="lots">
    <h2>Live lot tracking</h2>
    <p class="small muted">Payouts always come from imported broker IB reports. Live tracking only shows customers their lots in between.</p>
    <div class="field">
      <label class="check"><input type="radio" name="lot_source" value="none"<?= $src === 'none' ? ' checked' : '' ?>> <span><b>Off</b> — lots appear when you import broker reports</span></label>
      <label class="check"><input type="radio" name="lot_source" value="vps"<?= $src === 'vps' ? ' checked' : '' ?>> <span><b>Own VPS (free, MT5 only)</b> — MT5 terminals on your Windows VPS read accounts one by one</span></label>
      <label class="check"><input type="radio" name="lot_source" value="metaapi"<?= $src === 'metaapi' ? ' checked' : '' ?>> <span><b>MetaApi (paid, MT4 + MT5)</b></span></label>
    </div>
    <div style="border-top:1px solid var(--line);padding-top:14px;margin-bottom:18px">
      <h3>Own VPS sync</h3>
      <?php if ($newTok): ?><div class="alert alert-info">Sync token (copy now): <b class="mono" style="word-break:break-all"><?= e($newTok) ?></b></div><?php endif; ?>
      <p class="small muted" style="margin-bottom:8px">Status: <?= setting('vps_token_hash') ? ($seen ? 'VPS last connected ' . e(date('d M Y, H:i', strtotime($seen))) : 'token created, VPS has not connected yet') : 'no token yet' ?></p>
      <p class="small muted" style="margin-bottom:8px">Broker IDs for config.json: <?php foreach ($brokerList as $b) echo '<span class="mono">' . e($b['name']) . ' = ' . (int)$b['id'] . '</span> &nbsp; '; ?></p>
      <div class="btn-row">
        <button class="btn btn-ghost btn-sm" name="action" value="vps_token" onclick="return confirm('Create a new token? The old one stops working.')"><?= setting('vps_token_hash') ? 'Create new token' : 'Create sync token' ?></button>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('vps/xr5_lot_sync.py')) ?>" download>Download VPS script</a>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('vps/config.example.json')) ?>" download>Download config example</a>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('vps/start-sync.bat')) ?>" download>Download start-sync.bat</a>
      </div>
    </div>
    <h3>MetaApi</h3>
    <p class="small muted">Create an account at metaapi.cloud, copy your API token, and paste it here. Each connected trading account uses a MetaApi cloud terminal, billed by MetaApi.</p>
    <div class="field"><label>API token <?= $hasTok ? '<span class="badge badge-ok">Saved</span>' : '<span class="badge badge-warn">Not set</span>' ?></label>
      <input type="password" name="metaapi_token" autocomplete="off" placeholder="<?= $hasTok ? 'Leave blank to keep current · type - to remove' : 'Paste token' ?>"></div>
    <div class="field"><label>Region</label><select name="metaapi_region">
      <?php foreach (['new-york', 'london', 'singapore', 'vint-hill'] as $r): ?><option<?= setting('metaapi_region', 'new-york') === $r ? ' selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select>
      <div class="hint">Must match the region your MetaApi plan uses.</div></div>
    <div class="field"><label class="check"><input type="checkbox" name="metaapi_undeploy" value="1"<?= setting('metaapi_undeploy', '1') === '1' ? ' checked' : '' ?>> <span>Undeploy terminals after each sync (much cheaper; lots update every 2 cron runs)</span></label></div>
    <div class="btn-row"><button class="btn btn-primary">Save settings</button>
      <?php if ($hasTok): ?><button class="btn btn-ghost" name="action" value="test">Test connection</button><?php endif; ?></div>
  </div>
</form>
<?php page_end();
