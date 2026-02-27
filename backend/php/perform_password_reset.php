<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Método no permitido']);
  exit;
}

$token = trim($_POST['token'] ?? '');
$password = trim($_POST['password'] ?? '');
if ($token === '' || $password === '') {
  echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
  exit;
}
if (strlen($password) < 8) {
  echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres']);
  exit;
}

try {
  $tokenHash = hash('sha256', $token);
  $stmt = $conexion->prepare('SELECT id, user_id, expires_at, used FROM password_resets WHERE token_hash = ? LIMIT 1');
  $stmt->execute([$tokenHash]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Token inválido']);
    exit;
  }

  if (strtotime($row['expires_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'El enlace ha expirado']);
    exit;
  }
  if ((int)$row['used'] === 1 || $row['used'] === true) {
    echo json_encode(['success' => false, 'message' => 'Este enlace ya fue usado']);
    exit;
  }

  $userId = (int)$row['user_id'];

  $hash = password_hash($password, PASSWORD_BCRYPT);
  $up = $conexion->prepare('UPDATE usuario SET contrasena = ? WHERE id_usuario = ?');
  $ok = $up->execute([$hash, $userId]);
  if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la contraseña']);
    exit;
  }

  $mark = $conexion->prepare('UPDATE password_resets SET used = TRUE WHERE id = ?');
  $mark->execute([$row['id']]);

  echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente', 'redirect_url' => '../login/login.html']);
} catch (Throwable $e) {
  error_log('perform_password_reset.php error: ' . $e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}

// PDO no requiere cierre explícito
?>
