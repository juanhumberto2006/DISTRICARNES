<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php'; // PDO

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$email = isset($_GET['email']) ? trim($_GET['email']) : null;
if(!$email && is_array($input)){ $email = isset($input['email']) ? trim($input['email']) : null; }
if(!$email){ echo json_encode(['ok'=>false,'error'=>'email is required']); exit; }

try{
  // Preferir órdenes en PostgreSQL
  $stmt = $conexion->prepare("SELECT id, paypal_id, user_email, user_name, status, total, delivery_method, pay_method, address_json, schedule_json, created_at FROM orders_pg WHERE user_email = ? ORDER BY created_at DESC");
  $stmt->execute([$email]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if (!$rows || !count($rows)) {
    // Fallback a tablas antiguas si existen
    $stmt = $conexion->prepare("SELECT id, paypal_id, user_email, user_name, status, total, delivery_method, NULL as pay_method, address_json, schedule_json, created_at FROM orders WHERE user_email = ? ORDER BY created_at DESC");
    $stmt->execute([$email]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  $orders = [];
  foreach($rows as $row){
    $row['address'] = !empty($row['address_json']) ? (json_decode($row['address_json'], true) ?: null) : null;
    $row['schedule'] = !empty($row['schedule_json']) ? (json_decode($row['schedule_json'], true) ?: null) : null;
    unset($row['address_json'], $row['schedule_json']);
    // Items
    if (isset($row['id'])) {
      $items = [];
      try{
        $stI = $conexion->prepare('SELECT title, price, qty, image FROM order_items_pg WHERE order_id = ?');
        $stI->execute([$row['id']]);
        $items = $stI->fetchAll(PDO::FETCH_ASSOC);
        if (!$items || !count($items)) {
          $stI = $conexion->prepare('SELECT title, price, qty, image FROM order_items WHERE order_id = ?');
          $stI->execute([$row['id']]);
          $items = $stI->fetchAll(PDO::FETCH_ASSOC);
        }
      }catch(Throwable $e){}
      $row['items'] = $items ?: [];
    }
    $orders[] = $row;
  }
  echo json_encode(['ok'=>true,'orders'=>$orders]);
}catch(Throwable $e){
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
?> 
