<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

try {
  $q = isset($_GET['q']) ? trim($_GET['q']) : '';
  $role = isset($_GET['role']) ? trim($_GET['role']) : '';

  $sql = "SELECT * FROM usuario";
  $where = [];
  $params = [];

  if ($q !== '') {
    // Busca en nombres, correo y teléfono/celular si existen
    // Usamos LOWER para búsqueda insensible a mayúsculas en Postgres
    $where[] = "(LOWER(nombres_completos) LIKE LOWER(?) OR LOWER(correo_electronico) LIKE LOWER(?) OR LOWER(COALESCE(telefono, celular, '')) LIKE LOWER(?))";
    $term = '%' . $q . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
  }
  if ($role !== '') {
    $where[] = "rol = ?";
    $params[] = $role;
  }
  
  if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
  }
  $sql .= ' ORDER BY id_usuario DESC';

  $stmt = $conexion->prepare($sql);
  if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => implode(" ", $conexion->errorInfo())]);
    exit;
  }
  
  $stmt->execute($params);
  
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok' => true, 'count' => count($users), 'users' => $users], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

// $conexion->close(); // No necesario en PDO
?>
