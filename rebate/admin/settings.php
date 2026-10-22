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
  <div class="card">
    <h2>MetaApi (MT4/MT5 lot tracking)</h2>
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
