<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Método no permitido']);
  exit;
}

$action = $_POST['action'] ?? '';

function getColumns(PDO $db, string $table): array {
  $cols = [];
  $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = ?");
  $stmt->execute([$table]);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $cols[] = $row['column_name']; }
  $stmt->closeCursor();
  return $cols;
}

function findIdColumn(array $cols): ?string {
  foreach (['id_usuario', 'id', 'usuario_id'] as $c) {
    if (in_array($c, $cols, true)) return $c;
  }
  return null;
}

$table = 'usuario';
$columns = getColumns($conexion, $table);
$idCol = findIdColumn($columns);
if (!$idCol) {
  echo json_encode(['success' => false, 'message' => 'No se encontró columna ID en usuario']);
  exit;
}

if ($action === 'toggle_status') {
  $userId = $_POST['user_id'] ?? null;
  if (!$userId) { echo json_encode(['success' => false, 'message' => 'ID faltante']); exit; }
  // Usamos columna rol para alternar entre 'trabajo' y 'admin'
  $roleCol = in_array('rol', $columns, true) ? 'rol' : null;
  if (!$roleCol) { echo json_encode(['success' => false, 'message' => 'Columna rol no encontrada']); exit; }
  
  $stmt = $conexion->prepare("SELECT \"$roleCol\" FROM \"$table\" WHERE \"$idCol\" = ? LIMIT 1");
  $stmt->execute([$userId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $stmt->closeCursor();
  
  if (!$row) { echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']); exit; }
  $current = strtolower((string)$row[$roleCol]);
  $new = ($current === 'admin') ? 'trabajo' : 'admin';
  
  $stmt2 = $conexion->prepare("UPDATE \"$table\" SET \"$roleCol\" = ? WHERE \"$idCol\" = ?");
  $ok = $stmt2->execute([$new, $userId]);
  $stmt2->closeCursor();
  
  echo json_encode(['success' => $ok, 'message' => $ok ? 'Rol actualizado' : 'No se pudo actualizar el rol']);
  exit;
}

if ($action === 'delete') {
  $userId = $_POST['user_id'] ?? null;
  if (!$userId) { echo json_encode(['success' => false, 'message' => 'ID faltante']); exit; }
  $stmt = $conexion->prepare("DELETE FROM \"$table\" WHERE \"$idCol\" = ?");
  $ok = $stmt->execute([$userId]);
  $stmt->closeCursor();
  echo json_encode(['success' => $ok, 'message' => $ok ? 'Usuario eliminado' : 'No se pudo eliminar el usuario']);
  exit;
}

if ($action === 'create' || $action === 'update') {
  $first = $_POST['first_name'] ?? '';
  $last = $_POST['last_name'] ?? '';
  $name = trim($first . ' ' . $last);
  $email = $_POST['email'] ?? '';
  $cedula = $_POST['cedula'] ?? '';
  $phone = $_POST['phone'] ?? '';
  $address = $_POST['address'] ?? '';
  $userType = $_POST['user_type'] ?? 'user';
  $password = $_POST['password'] ?? '';

  $role = 'trabajo';
  if ($userType === 'staff') {
      $role = 'admin';
  } elseif ($userType === 'empleado') {
      $role = 'empleado';
  }

  // Mapear solo columnas existentes
  $data = [];
  if (in_array('nombres_completos', $columns, true)) $data['nombres_completos'] = $name;
  if (in_array('correo_electronico', $columns, true)) $data['correo_electronico'] = $email;
  if (in_array('cedula', $columns, true) && $cedula !== '') $data['cedula'] = $cedula;
  // Soportar ambos nombres de columna: 'telefono' o 'celular'
  if (in_array('telefono', $columns, true)) {
      $data['telefono'] = $phone;
  } elseif (in_array('celular', $columns, true)) {
      $data['celular'] = $phone;
  }
  if (in_array('direccion', $columns, true)) $data['direccion'] = $address;
  if (in_array('rol', $columns, true)) $data['rol'] = $role;
  if ($password !== '' && in_array('contrasena', $columns, true)) {
       // Hash password if not empty
       $data['contrasena'] = password_hash($password, PASSWORD_BCRYPT);
  }

    if ($action === 'create') {
    // Campos adicionales obligatorios para sistema de ventas
    if (in_array('usuario_usuario', $columns, true)) {
        // Generar usuario basado en email si no existe
        $parts = explode('@', $email);
        $data['usuario_usuario'] = $parts[0];
    }
    // Asegurar que nombres y apellidos se guarden en las columnas del sistema de ventas
    if (in_array('usuario_nombre', $columns, true)) $data['usuario_nombre'] = $first ?: 'Sin Nombre';
    if (in_array('usuario_apellido', $columns, true)) $data['usuario_apellido'] = $last ?: 'Sin Apellido';
    
    // Asignar caja por defecto (obligatorio para ventas)
    if (in_array('caja_id', $columns, true)) $data['caja_id'] = 1; 
    
    // Manejo de Cédula (Obligatorio en BD)
    if (in_array('cedula', $columns, true)) {
        if ($cedula !== '') {
            $data['cedula'] = $cedula;
        } else {
            // Generar cédula temporal si no viene en el POST
            $data['cedula'] = 'TEMP-' . time();
        }
    }

    // Establecer marca de tiempo de creación si aplica
    if (in_array('created_at', $columns, true)) {
        $data['created_at'] = date('Y-m-d H:i:s');
    }

    if (empty($data)) { echo json_encode(['success' => false, 'message' => 'No hay campos válidos']); exit; }
    
    // Construcción de la consulta INSERT
    $colsKeys = array_keys($data);
    $colsStr = implode(', ', array_map(function($k) { return "\"$k\""; }, $colsKeys));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    
    $sql = "INSERT INTO \"$table\" ($colsStr) VALUES ($placeholders)";
    $stmt = $conexion->prepare($sql);
    $values = array_values($data);
    
    try {
        $ok = $stmt->execute($values);
        $stmt->closeCursor();
        echo json_encode(['success' => true, 'message' => 'Usuario creado correctamente']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al crear usuario: ' . $e->getMessage()]);
    }
    exit;
  } else {
    $userId = $_POST['user_id'] ?? null;
    if (!$userId) { echo json_encode(['success' => false, 'message' => 'ID faltante']); exit; }
    
    // Si se está actualizando, asegurarnos de actualizar los campos del sistema de ventas también
    if (in_array('usuario_nombre', $columns, true)) $data['usuario_nombre'] = $first;
    if (in_array('usuario_apellido', $columns, true)) $data['usuario_apellido'] = $last;

    if (empty($data)) { echo json_encode(['success' => false, 'message' => 'No hay campos válidos']); exit; }
    
    // Actualizar marca de tiempo de modificación si existe
    if (in_array('updated_at', $columns, true)) {
        $data['updated_at'] = date('Y-m-d H:i:s');
    }
    
    // Asegurar que nombres y apellidos se actualicen en las columnas del sistema de ventas
    if (in_array('usuario_nombre', $columns, true)) $data['usuario_nombre'] = $first ?: 'Sin Nombre';
    if (in_array('usuario_apellido', $columns, true)) $data['usuario_apellido'] = $last ?: 'Sin Apellido';
    if (in_array('usuario_usuario', $columns, true)) {
        $parts = explode('@', $email);
        $data['usuario_usuario'] = $parts[0];
    }
    
    $setParts = [];
    foreach (array_keys($data) as $col) { $setParts[] = "\"$col\" = ?"; }
    $sql = "UPDATE \"$table\" SET " . implode(', ', $setParts) . " WHERE \"$idCol\" = ?";
    $stmt = $conexion->prepare($sql);
    $values = array_values($data);
    $values[] = $userId;
    
    try {
        $ok = $stmt->execute($values);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
        exit;
    }
    $stmt->closeCursor();
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Usuario actualizado' : 'No se pudo actualizar el usuario']);
    exit;
  }
}

echo json_encode(['success' => false, 'message' => 'Acción no soportada']);
// $conexion->close();
?>
