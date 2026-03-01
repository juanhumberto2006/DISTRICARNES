<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php'; // PDO PostgreSQL

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$email = isset($input['user_email']) ? trim($input['user_email']) : '';
if ($email === '') {
  echo json_encode(['ok' => false, 'error' => 'user_email requerido']);
  exit;
}

try {
  $conexion->exec("
    CREATE TABLE IF NOT EXISTS user_addresses (
      id SERIAL PRIMARY KEY,
      user_email VARCHAR(255) NOT NULL,
      street VARCHAR(255) NOT NULL,
      city VARCHAR(120) NOT NULL,
      dept VARCHAR(120) NOT NULL,
      zip VARCHAR(32) NULL,
      notes TEXT NULL,
      lat DOUBLE PRECISION NULL,
      lng DOUBLE PRECISION NULL,
      is_default BOOLEAN NOT NULL DEFAULT FALSE,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
  ");
  $stmt = $conexion->prepare('SELECT id, street, city, dept, zip, notes, lat, lng, is_default FROM user_addresses WHERE user_email = ? ORDER BY is_default DESC, id DESC');
  $stmt->execute([$email]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['ok' => true, 'addresses' => $rows]);
} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?> 
