<?php
// Utilidades para registrar ventas cuando una orden se completa
// Crea la tabla 'sales' si no existe y registra una fila por cada orden COMPLETED

require_once __DIR__ . '/conexion.php';

function table_exists(PDO $db, string $name): bool {
  $stmt = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_name = ? LIMIT 1");
  $stmt->execute([$name]);
  $exists = (bool)$stmt->fetch();
  $stmt->closeCursor();
  return $exists;
}

function ensure_table_schema(PDO $db, string $table): void {
  // PostgreSQL syntax
  $sql = "CREATE TABLE IF NOT EXISTS \"$table\" (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    paypal_id VARCHAR(64) NULL,
    customer_email VARCHAR(255) NULL,
    customer_name VARCHAR(255) NULL,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )";
  // Add FK only if orders table exists (optional safety)
  // But usually we assume orders exists.
  // Note: ON DELETE CASCADE syntax is standard.
  try {
      $db->exec($sql);
      // Add FK constraint separately to avoid errors if it already exists or if orders doesn't exist yet
      // In a simple migration script, we might skip complex FK checks, 
      // but let's try to add it if possible.
      // For now, simple table creation is enough for the chart to work.
  } catch (PDOException $e) {
      // Ignore if table exists
  }
}

function choose_sales_table(PDO $db): string {
  if (table_exists($db, 'venta')) { return 'venta'; }
  if (table_exists($db, 'ventas')) { return 'ventas'; }
  if (table_exists($db, 'sales')) { return 'sales'; }
  ensure_table_schema($db, 'venta');
  return 'venta';
}

function ensure_sales_table(PDO $db): void {
  ensure_table_schema($db, 'sales');
}

function record_sale_for_order(PDO $db, int $orderId): array {
  $table = choose_sales_table($db);
  ensure_table_schema($db, $table);

  // Verificar si ya existe venta para esta orden
  $stmtChk = $db->prepare("SELECT id FROM \"$table\" WHERE order_id = ? LIMIT 1");
  $stmtChk->execute([$orderId]);
  if ($stmtChk->fetch()) {
    $stmtChk->closeCursor();
    return ['ok' => true, 'created' => false, 'reason' => 'already_recorded'];
  }
  $stmtChk->closeCursor();

  // Cargar datos de la orden
  // Assuming 'orders' table exists
  $stmt = $db->prepare("SELECT id, paypal_id, user_email, user_name, status, total, created_at FROM orders WHERE id = ? LIMIT 1");
  $stmt->execute([$orderId]);
  $order = $stmt->fetch(PDO::FETCH_ASSOC);
  $stmt->closeCursor();

  if (!$order) { return ['ok' => false, 'error' => 'order_not_found']; }
  if (strtoupper((string)$order['status']) !== 'COMPLETED') { return ['ok' => true, 'created' => false, 'reason' => 'not_completed']; }

  // Insertar venta usando la fecha de creación de la orden
  $paypalId = $order['paypal_id'] ?? null;
  $email = $order['user_email'] ?? null;
  $name = $order['user_name'] ?? null;
  $total = floatval($order['total'] ?? 0);
  $createdAt = $order['created_at'] ?? null;

  // Si tenemos created_at de la orden, usarlo; de lo contrario, dejar default
  if ($createdAt) {
    $stmtIns = $db->prepare("INSERT INTO \"$table\" (order_id, paypal_id, customer_email, customer_name, total, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $ok = $stmtIns->execute([$orderId, $paypalId, $email, $name, $total, $createdAt]);
  } else {
    $stmtIns = $db->prepare("INSERT INTO \"$table\" (order_id, paypal_id, customer_email, customer_name, total) VALUES (?, ?, ?, ?, ?)");
    $ok = $stmtIns->execute([$orderId, $paypalId, $email, $name, $total]);
  }
  
  $stmtIns->closeCursor();
  return ['ok' => $ok, 'created' => $ok];
}

function sync_sales_from_orders(PDO $db): void {
  $table = choose_sales_table($db);
  ensure_table_schema($db, $table);

  // Check if sales table is empty
  $stmtCount = $db->query("SELECT COUNT(*) FROM \"$table\"");
  if ($stmtCount && $stmtCount->fetchColumn() > 0) {
      // Already has data, assume it's fine or handled by events
      $stmtCount->closeCursor();
      return;
  }
  if ($stmtCount) $stmtCount->closeCursor();

  // Backfill from orders
  // Check if orders table exists first
  $stmtOrd = $db->query("SELECT 1 FROM information_schema.tables WHERE table_name = 'orders'");
  if (!$stmtOrd || !$stmtOrd->fetch()) {
      if ($stmtOrd) $stmtOrd->closeCursor();
      return; 
  }
  if ($stmtOrd) $stmtOrd->closeCursor();

  // Insert missing orders
  // We select orders that are COMPLETED and don't have a corresponding entry in sales table
  // (Though if table is empty, we just insert all COMPLETED)
  $sql = "INSERT INTO \"$table\" (order_id, paypal_id, customer_email, customer_name, total, created_at)
          SELECT id, paypal_id, user_email, user_name, total, created_at
          FROM orders
          WHERE status = 'COMPLETED'";
  
  try {
      $db->exec($sql);
  } catch (PDOException $e) {
      // Ignore duplicates if any
  }
}
?>