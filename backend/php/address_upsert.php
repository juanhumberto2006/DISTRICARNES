<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php'; // PDO PostgreSQL

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) { echo json_encode(['ok'=>false,'error'=>'JSON inválido']); exit; }

$email = trim($input['user_email'] ?? '');
$id    = isset($input['id']) ? intval($input['id']) : null;
$street= trim($input['street'] ?? '');
$city  = trim($input['city'] ?? '');
$dept  = trim($input['dept'] ?? '');
$zip   = trim($input['zip'] ?? '');
$notes = trim($input['notes'] ?? '');
$lat   = isset($input['lat']) ? floatval($input['lat']) : null;
$lng   = isset($input['lng']) ? floatval($input['lng']) : null;
$isDefault = boolval($input['is_default'] ?? false);

if ($email === '' || $street === '' || $city === '' || $dept === '') {
  echo json_encode(['ok'=>false,'error'=>'Datos incompletos']);
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

  if ($isDefault) {
    $stmt = $conexion->prepare('UPDATE user_addresses SET is_default = FALSE WHERE user_email = ?');
    $stmt->execute([$email]);
  }

  if ($id) {
    $stmt = $conexion->prepare('
      UPDATE user_addresses
      SET street = ?, city = ?, dept = ?, zip = ?, notes = ?, lat = ?, lng = ?, is_default = COALESCE(?, is_default), updated_at = CURRENT_TIMESTAMP
      WHERE id = ? AND user_email = ?
      RETURNING id
    ');
    $stmt->execute([$street, $city, $dept, $zip, $notes, $lat, $lng, $isDefault, $id, $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $addrId = $row ? intval($row['id']) : $id;
  } else {
    // Si es el primer domicilio del usuario, márcalo por defecto
    if (!$isDefault) {
      $stmtCount = $conexion->prepare('SELECT COUNT(1) FROM user_addresses WHERE user_email = ?');
      $stmtCount->execute([$email]);
      $count = intval($stmtCount->fetchColumn() ?: 0);
      if ($count === 0) $isDefault = true;
    }
    $stmt = $conexion->prepare('
      INSERT INTO user_addresses (user_email, street, city, dept, zip, notes, lat, lng, is_default)
      VALUES (?,?,?,?,?,?,?,?,?)
      RETURNING id
    ');
    $stmt->execute([$email, $street, $city, $dept, $zip, $notes, $lat, $lng, $isDefault]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $addrId = intval($row['id']);
  }

  echo json_encode(['ok'=>true, 'id'=>$addrId]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
?> 
