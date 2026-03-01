<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php'; // $conexion es un PDO (PostgreSQL)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Método no permitido']);
  exit;
}

$nombre    = trim($_POST['nombre'] ?? '');
$cedula    = trim($_POST['cedula'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$celular   = trim($_POST['celular'] ?? '');
$correo    = trim($_POST['email'] ?? '');
$clave     = trim($_POST['contrasena'] ?? '');

if (!$nombre || !$cedula || !$direccion || !$celular || !$correo || !$clave) {
  echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
  exit;
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
  echo json_encode(['success' => false, 'message' => 'Correo electrónico inválido']);
  exit;
}

try {
  // Crear tabla si no existe (sintaxis PostgreSQL)
  $conexion->exec("
    CREATE TABLE IF NOT EXISTS usuario (
      id_usuario SERIAL PRIMARY KEY,
      nombres_completos VARCHAR(255) NOT NULL,
      cedula VARCHAR(50) NOT NULL UNIQUE,
      direccion VARCHAR(255) NOT NULL,
      celular VARCHAR(50) NOT NULL,
      correo_electronico VARCHAR(255) NOT NULL UNIQUE,
      contrasena VARCHAR(255) NOT NULL,
      rol VARCHAR(50) NOT NULL DEFAULT 'trabajo',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
  ");

  // Duplicados por email
  $stmt = $conexion->prepare('SELECT 1 FROM usuario WHERE correo_electronico = ? LIMIT 1');
  $stmt->execute([$correo]);
  if ($stmt->fetchColumn()) {
    echo json_encode(['success' => false, 'message' => 'El correo ya está registrado']);
    exit;
  }

  // Duplicados por cédula
  $stmt = $conexion->prepare('SELECT 1 FROM usuario WHERE cedula = ? LIMIT 1');
  $stmt->execute([$cedula]);
  if ($stmt->fetchColumn()) {
    echo json_encode(['success' => false, 'message' => 'La cédula ya está registrada']);
    exit;
  }

  // Hashear contraseña
  $hash = password_hash($clave, PASSWORD_BCRYPT);

  // Insertar
  $stmt = $conexion->prepare('
    INSERT INTO usuario (nombres_completos, cedula, direccion, celular, correo_electronico, contrasena, rol)
    VALUES (?,?,?,?,?,?,?)
  ');
  $rol = 'trabajo';
  $ok = $stmt->execute([$nombre, $cedula, $direccion, $celular, $correo, $hash, $rol]);

  if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Registro exitoso']);
  } else {
    echo json_encode(['success' => false, 'message' => 'No se pudo registrar']);
  }
} catch (Throwable $e) {
  echo json_encode(['success' => false, 'message' => 'Error de servidor: ' . $e->getMessage()]);
}
?>
