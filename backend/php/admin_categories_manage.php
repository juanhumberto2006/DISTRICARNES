<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['ok' => false, 'message' => 'Método no permitido']);
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

$table = 'categorias';
$columns = getColumns($conexion, $table);
if (empty($columns)) {
  echo json_encode(['ok' => false, 'message' => 'Tabla categorias no encontrada']);
  exit;
}

// Detectar columnas comunes
$idCol = null;
foreach (['id', 'id_categoria', 'categoria_id'] as $c) { if (in_array($c, $columns, true)) { $idCol = $c; break; } }
$nameCol = in_array('nombre', $columns, true) ? 'nombre' : (in_array('name', $columns, true) ? 'name' : null);
if (!$nameCol) { echo json_encode(['ok' => false, 'message' => 'Columna nombre no encontrada']); exit; }

if ($action === 'create') {
  $nombre = trim((string)($_POST['nombre'] ?? ''));
  if ($nombre === '') { echo json_encode(['ok' => false, 'message' => 'Nombre de categoría requerido']); exit; }

  // Verificar duplicados (case-insensitive)
  $nombreEsc = $conexion->quote($nombre);
  // Postgres lower() works fine.
  $sqlCheck = "SELECT 1 FROM \"$table\" WHERE LOWER(TRIM(\"$nameCol\")) = LOWER(TRIM($nombreEsc)) LIMIT 1";
  
  try {
      $res = $conexion->query($sqlCheck);
      if ($res && $res->fetch()) { 
          echo json_encode(['ok' => false, 'message' => 'La categoría ya existe']); 
          $res->closeCursor(); 
          exit; 
      }
      if ($res) $res->closeCursor();
  } catch (PDOException $e) {
      // ignore or handle
  }

  // Insertar
  // Postgres uses double quotes for identifiers
  $stmt = $conexion->prepare("INSERT INTO \"$table\" (\"$nameCol\") VALUES (?)");
  $ok = $stmt->execute([$nombre]);
  $stmt->closeCursor();
  echo json_encode(['ok' => $ok, 'message' => $ok ? 'Categoría creada' : 'No se pudo crear la categoría']);
  // $conexion->close();
  exit;
}

echo json_encode(['ok' => false, 'message' => 'Acción no soportada']);
// $conexion->close();
?>