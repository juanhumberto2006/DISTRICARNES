<?php
header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) { echo json_encode(['ok'=>false,'error'=>'JSON inválido']); exit; }

$items = $input['items'] ?? [];
$delivery = $input['delivery'] ?? 'domicilio';

// Config por variables de entorno (COP)
$FREE_THRESHOLD = intval(getenv('FREE_SHIPPING_THRESHOLD') ?: 80000);
$BASE = intval(getenv('SHIPPING_BASE') ?: 7000);
$PER_ITEM = intval(getenv('SHIPPING_PER_ITEM') ?: 1000);

if ($delivery === 'punto') {
  echo json_encode(['ok'=>true,'cost'=>0,'free'=>true,'reason'=>'pickup_point']);
  exit;
}

$subtotal = 0;
$qtyTotal = 0;
$anyFree = false;
foreach ($items as $it) {
  $price = floatval($it['price'] ?? 0);
  $qty   = intval($it['qty'] ?? ($it['quantity'] ?? 1));
  $subtotal += $price * $qty;
  $qtyTotal += $qty;
  if (!empty($it['free_shipping'])) { $anyFree = true; }
}

if ($anyFree || $subtotal >= $FREE_THRESHOLD) {
  echo json_encode(['ok'=>true,'cost'=>0,'free'=>true,'reason'=>$anyFree?'item_free':'threshold']);
  exit;
}

$cost = $BASE + max(0, $qtyTotal - 1) * $PER_ITEM;
echo json_encode(['ok'=>true,'cost'=>$cost,'free'=>false,'reason'=>'standard']);
?> 
