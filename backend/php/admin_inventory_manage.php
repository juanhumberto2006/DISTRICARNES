<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Método no permitido']);
  exit;
}

$action = $_POST['action'] ?? '';
if ($action !== 'restock') {
  echo json_encode(['success' => false, 'message' => 'Acción inválida']);
  exit;
}

// Campos desde el formulario de inventario
$productId = $_POST['product_id'] ?? null;
$addQuantity = isset($_POST['add_quantity']) ? (int)$_POST['add_quantity'] : null;
$notes = $_POST['notes'] ?? null;

if (!$productId || $addQuantity === null || $addQuantity < 1) {
  echo json_encode(['success' => false, 'message' => 'Datos incompletos para reabastecer']);
  exit;
}

// Detectar columnas de la tabla producto
function getColumns(PDO $db, string $table): array {
  $cols = [];
  $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = ?");
  $stmt->execute([$table]);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cols[] = $row['column_name'];
  }
  $stmt->closeCursor();
  return $cols;
}

function findIdColumn(array $cols): ?string {
  foreach (['id', 'id_producto', 'producto_id', 'idProduct'] as $c) {
    if (in_array($c, $cols, true)) return $c;
  }
  return null;
}

$table = 'producto';
$columns = getColumns($conexion, $table);
$idCol = findIdColumn($columns);
$stockCol = in_array('stock', $columns, true) ? 'stock' : null;

if (!$idCol || !$stockCol) {
  echo json_encode(['success' => false, 'message' => 'Estructura de tabla inesperada (id/stock)']);
  exit;
}

// Obtener stock actual
$stmt = $conexion->prepare("SELECT \"$stockCol\" FROM \"$table\" WHERE \"$idCol\" = ? LIMIT 1");
$stmt->execute([$productId]);
// fetch directly
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
  echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
  $stmt->closeCursor();
  exit;
}
$currentStock = (int)$row[$stockCol];
$stmt->closeCursor();

$newStock = $currentStock + $addQuantity;

// Actualizar stock
$stmt2 = $conexion->prepare("UPDATE \"$table\" SET \"$stockCol\" = ? WHERE \"$idCol\" = ?");
$ok = $stmt2->execute([$newStock, $productId]);
$stmt2->closeCursor();

if ($ok) {
  echo json_encode(['success' => true, 'message' => 'Stock actualizado correctamente', 'new_stock' => $newStock]);
} else {
  echo json_encode(['success' => false, 'message' => 'No fue posible actualizar el stock']);
}

// $conexion->close();
?>