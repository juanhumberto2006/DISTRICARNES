<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php'; // PDO PostgreSQL

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) { echo json_encode(['ok'=>false,'error'=>'Invalid JSON']); exit; }

$paypalId = $input['paypal_id'] ?? null;
$status   = $input['status'] ?? 'PENDING';
$total    = isset($input['total']) ? floatval($input['total']) : 0.0;
$delivery = $input['delivery'] ?? 'domicilio';
$address  = $input['address'] ?? [];
$schedule = $input['schedule'] ?? [];
$items    = $input['items'] ?? [];
$user     = $input['user'] ?? [];
$payMethod= 'paypal';

try {
  // Tablas en PostgreSQL
  $conexion->exec("
    CREATE TABLE IF NOT EXISTS orders_pg (
      id SERIAL PRIMARY KEY,
      paypal_id VARCHAR(64) NULL,
      user_email VARCHAR(255) NULL,
      user_name VARCHAR(255) NULL,
      status VARCHAR(32) NOT NULL,
      total NUMERIC(12,2) NOT NULL DEFAULT 0,
      delivery_method VARCHAR(32) NOT NULL,
      pay_method VARCHAR(32) NULL,
      address_json JSONB NULL,
      schedule_json JSONB NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
  ");
  $conexion->exec("
    CREATE TABLE IF NOT EXISTS order_items_pg (
      id SERIAL PRIMARY KEY,
      order_id INT NOT NULL REFERENCES orders_pg(id) ON DELETE CASCADE,
      title VARCHAR(255),
      price NUMERIC(12,2) NOT NULL DEFAULT 0,
      qty INT NOT NULL DEFAULT 1,
      image TEXT NULL
    )
  ");

  $stmt = $conexion->prepare("
    INSERT INTO orders_pg (paypal_id, user_email, user_name, status, total, delivery_method, pay_method, address_json, schedule_json)
    VALUES (?,?,?,?,?,?,?,?::jsonb,?::jsonb)
    RETURNING id
  ");
  $userEmail = $user['email'] ?? null;
  $userName  = $user['name'] ?? null;
  $stmt->execute([$paypalId, $userEmail, $userName, $status, $total, $delivery, $payMethod, json_encode($address), json_encode($schedule)]);
  $orderId = intval($stmt->fetchColumn());

  if (is_array($items)) {
    $ins = $conexion->prepare('INSERT INTO order_items_pg (order_id, title, price, qty, image) VALUES (?,?,?,?,?)');
    foreach ($items as $it) {
      $title = $it['title'] ?? ($it['name'] ?? 'Producto');
      $price = floatval($it['price'] ?? 0);
      $qty   = intval($it['qty'] ?? ($it['quantity'] ?? 1));
      $img   = $it['image'] ?? ($it['img'] ?? null);
      $ins->execute([$orderId, $title, $price, $qty, $img]);
    }
  }

  echo json_encode(['ok'=>true, 'order_id'=>$orderId]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
?> 
