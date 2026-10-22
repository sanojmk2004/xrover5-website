<?php
// Counts the click and forwards to the broker's IB referral link.
require __DIR__ . '/inc/bootstrap.php';
$b = row('SELECT id, ib_link FROM brokers WHERE id = ? AND active = 1', [(int)($_GET['b'] ?? 0)]);
if (!$b || !preg_match('#^https://#i', $b['ib_link'])) redirect('brokers.php');
q('UPDATE brokers SET clicks = clicks + 1 WHERE id = ?', [$b['id']]);
header('Location: ' . $b['ib_link'], true, 302);
