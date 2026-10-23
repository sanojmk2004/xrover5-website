<?php
// Health of the VPS lot sync, worked out from when the VPS last contacted the website.

/** @return array{state:string,label:string,minutes:?int,limit:int} state: off | never | ok | down */
function vps_status(): array {
  $interval = max(5, (int) setting('vps_interval', 30));
  $limit = max(60, $interval * 2 + 15);          // two missed runs + margin = down
  if (setting('lot_source', 'none') !== 'vps' || !setting('vps_token_hash')) return ['state' => 'off', 'label' => 'VPS sync not in use', 'minutes' => null, 'limit' => $limit];
  $seen = setting('vps_last_seen');
  if (!$seen) return ['state' => 'never', 'label' => 'VPS has not connected yet', 'minutes' => null, 'limit' => $limit];
  $min = (int) floor((time() - strtotime($seen)) / 60);
  $ago = $min < 1 ? 'just now' : ($min < 120 ? "$min min ago" : round($min / 60, 1) . ' hours ago');
  return $min <= $limit
    ? ['state' => 'ok', 'label' => "Running · last contact $ago", 'minutes' => $min, 'limit' => $limit]
    : ['state' => 'down', 'label' => "Not running · last contact $ago", 'minutes' => $min, 'limit' => $limit];
}
