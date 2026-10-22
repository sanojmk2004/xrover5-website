<?php
require dirname(__DIR__) . '/inc/bootstrap.php';
require_admin();

if (is_post()) {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  $plat = array_values(array_intersect(['MT4', 'MT5'], (array)($_POST['platforms'] ?? [])));
  $f = [
    'name' => post('name', 80), 'logo' => post('logo', 255), 'logo_bg' => preg_match('/^#[0-9a-fA-F]{3,8}$/', post('logo_bg', 20)) ? post('logo_bg', 20) : '#151823',
    'ib_link' => post('ib_link', 500), 'description' => post('description', 500), 'platforms' => implode(',', $plat ?: ['MT4', 'MT5']),
    'rebate_pct' => max(0, min(100, (float)post('rebate_pct', 10))), 'est_commission_per_lot' => max(0, (float)post('est_commission_per_lot', 12)),
    'sort_order' => (int)post('sort_order', 5), 'active' => empty($_POST['active']) ? 0 : 1,
  ];
  if ($f['name'] === '' || !preg_match('#^https://#i', $f['ib_link'])) flash('Name and an https:// IB referral link are required.', 'err');
  elseif ($id) {
    $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($f)));
    q("UPDATE brokers SET $sets WHERE id = ?", [...array_values($f), $id]);
    flash("Saved {$f['name']}. New rebate % applies to commissions imported from now on.");
  } else {
    q('INSERT INTO brokers (' . implode(',', array_keys($f)) . ') VALUES (' . implode(',', array_fill(0, count($f), '?')) . ')', array_values($f));
    flash("Added {$f['name']}.");
  }
  redirect('admin/brokers.php');
}

$brokers = rows('SELECT * FROM brokers ORDER BY sort_order, name');
$brokers[] = ['id' => 0, 'name' => '', 'logo' => '', 'logo_bg' => '#151823', 'ib_link' => '', 'description' => '', 'platforms' => 'MT4,MT5',
              'rebate_pct' => 50, 'est_commission_per_lot' => 0, 'sort_order' => 9, 'active' => 1, 'clicks' => 0];
page_start('Brokers', 'admin/brokers.php', true);
?>
<h1>Brokers</h1>
<p class="muted"><b>Customer rebate %</b> is the share of the IB commission you give back. <b>Est. commission per lot</b> is what the broker typically pays you per round-turn lot; it's only used to show customers an estimate before the real report is imported.</p>
<div class="grid g2">
<?php foreach ($brokers as $b): $pl = explode(',', $b['platforms']); ?>
  <form method="post" class="card">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
    <div class="head-row"><h2><?= $b['id'] ? e($b['name']) : 'Add broker' ?></h2><?php if ($b['id']): ?><span class="small muted"><?= (int)$b['clicks'] ?> link clicks</span><?php endif; ?></div>
    <div class="row2">
      <div class="field"><label>Name</label><input type="text" name="name" value="<?= e($b['name']) ?>" required></div>
      <div class="field"><label>Sort order</label><input type="number" name="sort_order" value="<?= (int)$b['sort_order'] ?>"></div>
    </div>
    <div class="field"><label>IB referral link (account opening)</label><input type="url" name="ib_link" value="<?= e($b['ib_link']) ?>" required placeholder="https://"></div>
    <div class="row2">
      <div class="field"><label>Customer rebate %</label><input type="number" name="rebate_pct" step="0.01" min="0" max="100" value="<?= e($b['rebate_pct']) ?>"></div>
      <div class="field"><label>Est. commission per lot (USD)</label><input type="number" name="est_commission_per_lot" step="0.01" min="0" value="<?= e($b['est_commission_per_lot']) ?>"></div>
    </div>
    <div class="field"><label>Description</label><input type="text" name="description" value="<?= e($b['description']) ?>"></div>
    <div class="row2">
      <div class="field"><label>Logo image URL</label><input type="text" name="logo" value="<?= e($b['logo']) ?>" placeholder="/assets/broker-x.png"></div>
      <div class="field"><label>Logo background</label><input type="text" name="logo_bg" value="<?= e($b['logo_bg']) ?>"></div>
    </div>
    <div class="btn-row" style="margin-bottom:14px">
      <label class="check"><input type="checkbox" name="platforms[]" value="MT4"<?= in_array('MT4', $pl) ? ' checked' : '' ?>> MT4</label>
      <label class="check"><input type="checkbox" name="platforms[]" value="MT5"<?= in_array('MT5', $pl) ? ' checked' : '' ?>> MT5</label>
      <label class="check"><input type="checkbox" name="active" value="1"<?= $b['active'] ? ' checked' : '' ?>> Shown on website</label>
    </div>
    <button class="btn btn-primary"><?= $b['id'] ? 'Save' : 'Add broker' ?></button>
  </form>
<?php endforeach; ?>
</div>
<?php page_end();
