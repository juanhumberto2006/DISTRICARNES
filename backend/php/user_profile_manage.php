<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/conexion.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
  exit;
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión.']);
  exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$userId = (int)$_SESSION['user_id'];

switch ($action) {
  case 'get_profile':
    getProfile($conexion, $userId);
    break;
  case 'update_profile':
    updateProfile($conexion, $userId);
    break;
  case 'change_password':
    changePassword($conexion, $userId);
    break;
  default:
    echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
    break;
}

function getProfile($conexion, $userId) {
  $sql = "SELECT id_usuario, nombres_completos, correo_electronico, rol FROM usuario WHERE id_usuario = ? LIMIT 1";
  $stmt = $conexion->prepare($sql);
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && $res->num_rows === 1) {
    $u = $res->fetch_assoc();
    echo json_encode([
      'success' => true,
      'user' => [
        'id' => $u['id_usuario'],
        'nombres_completos' => $u['nombres_completos'],
        'correo_electronico' => $u['correo_electronico'],
        'rol' => $u['rol']
      ]
    ]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
  }
  $stmt->close();
}

function updateProfile($conexion, $userId) {
  $fullName = isset($_POST['fullName']) ? trim($_POST['fullName']) : '';
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';

  if ($fullName === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Por favor, completa todos los campos.']);
    return;
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Correo electrónico inválido.']);
    return;
  }
  $sql_check = "SELECT id_usuario FROM usuario WHERE correo_electronico = ? AND id_usuario != ?";
  $stmt_check = $conexion->prepare($sql_check);
  $stmt_check->bind_param("si", $email, $userId);
  $stmt_check->execute();
  $r = $stmt_check->get_result();
  if ($r && $r->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'El correo ya está en uso por otro usuario.']);
    $stmt_check->close();
    return;
  }
  $stmt_check->close();

  $sql = "UPDATE usuario SET nombres_completos = ?, correo_electronico = ? WHERE id_usuario = ?";
  $stmt = $conexion->prepare($sql);
  $stmt->bind_param("ssi", $fullName, $email, $userId);
  if ($stmt->execute()) {
    $_SESSION['user_name'] = $fullName;
    $_SESSION['user_email'] = $email;
    echo json_encode(['success' => true, 'message' => 'Perfil actualizado.']);
  } else {
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el perfil.']);
  }
  $stmt->close();
}

function changePassword($conexion, $userId) {
  $currentPassword = isset($_POST['currentPassword']) ? $_POST['currentPassword'] : '';
  $newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';
  $confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

  if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    echo json_encode(['success' => false, 'message' => 'Completa todos los campos.']);
    return;
  }
  if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres.']);
    return;
  }
  if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Las nuevas contraseñas no coinciden.']);
    return;
  }
  $sql_get = "SELECT contrasena FROM usuario WHERE id_usuario = ?";
  $stmt_get = $conexion->prepare($sql_get);
  $stmt_get->bind_param("i", $userId);
  $stmt_get->execute();
  $result = $stmt_get->get_result();
  if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (!password_verify($currentPassword, $user['contrasena'])) {
      echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta.']);
      $stmt_get->close();
      return;
    }
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $sql_upd = "UPDATE usuario SET contrasena = ? WHERE id_usuario = ?";
    $stmt_upd = $conexion->prepare($sql_upd);
    $stmt_upd->bind_param("si", $newHash, $userId);
    if ($stmt_upd->execute()) {
      echo json_encode(['success' => true, 'message' => 'Contraseña actualizada.']);
    } else {
      echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña.']);
    }
    $stmt_upd->close();
  } else {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
  }
  $stmt_get->close();
}

$conexion->close();
?>
