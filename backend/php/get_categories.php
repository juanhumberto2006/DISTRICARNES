<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/conexion.php';

// Detectar columnas de producto para compatibilidad (texto vs FK)
function getColumns(PDO $db, string $table): array {
  $cols = [];
  $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = ?");
  $stmt->execute([$table]);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $cols[] = $row['column_name']; }
  $stmt->closeCursor();
  return $cols;
}

$productoCols = getColumns($conexion, 'producto');
$categoriaIdCol = null;
foreach (['categoria_id', 'id_categoria', 'category_id'] as $c) {
  if (in_array($c, $productoCols, true)) { $categoriaIdCol = $c; break; }
}
$categoriaTextCol = in_array('categoria', $productoCols, true) ? 'categoria' : null;

// Verificar si existe tabla 'categorias'
$hasCategorias = false;
$checkCat = $conexion->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'categorias' LIMIT 1");
if ($checkCat && $checkCat->fetch()) { $hasCategorias = true; $checkCat->closeCursor(); }

// Cargar categorías desde tabla 'categorias' si existe; de lo contrario, hacer fallback desde producto
// Detectar columnas reales en tabla categorias (id y nombre)
$categories = [];
if ($hasCategorias) {
  $catCols = getColumns($conexion, 'categorias');
  $idCol = null;
  foreach (['id', 'id_categoria', 'categoria_id'] as $c) { if (in_array($c, $catCols, true)) { $idCol = $c; break; } }
  $nameCol = in_array('nombre', $catCols, true) ? 'nombre' : (in_array('nombre_categoria', $catCols, true) ? 'nombre_categoria' : (in_array('name', $catCols, true) ? 'name' : null));

  // Si no se encuentran columnas conocidas, hacer fallback desde producto
  if ($idCol && $nameCol) {
    $sqlCategorias = "SELECT \"$idCol\" AS id_cat, \"$nameCol\" AS nom_cat FROM categorias ORDER BY \"$nameCol\"";
    $resultCat = $conexion->query($sqlCategorias);
    if ($resultCat) {
      while ($row = $resultCat->fetch(PDO::FETCH_ASSOC)) {
        $id = isset($row['id_cat']) ? (string)$row['id_cat'] : null;
        $nombre = trim((string)($row['nom_cat'] ?? ''));
        $name = mb_strtolower($nombre);
        $display = mb_strtoupper($nombre);

        // Contar productos por categoría (soporte para FK o texto)
        $count = 0;
        if ($categoriaIdCol && $id !== null) {
          $idEsc = $conexion->quote($id);
          $sqlCount = "SELECT COUNT(*) AS c FROM producto WHERE \"$categoriaIdCol\" = $idEsc";
          if ($resC = $conexion->query($sqlCount)) { $count = (int)($resC->fetch(PDO::FETCH_ASSOC)['c'] ?? 0); $resC->closeCursor(); }
        } elseif ($categoriaTextCol) {
          $nameEsc = $conexion->quote($name);
          // Postgres lower/trim
          $sqlCount = "SELECT COUNT(*) AS c FROM producto WHERE LOWER(TRIM(\"$categoriaTextCol\")) = $nameEsc";
          // Usar try/catch para evitar errores silenciosos
          try {
             if ($resC = $conexion->query($sqlCount)) { $count = (int)($resC->fetch(PDO::FETCH_ASSOC)['c'] ?? 0); $resC->closeCursor(); }
          } catch(PDOException $e) {}
        }

        $categories[] = [
          'id' => $id,
          'name' => $name,
          'display' => $display,
          'product_count' => $count
        ];
      }
      $resultCat->closeCursor();
    }
  } else {
    // Si no se detectan columnas esperadas, hacemos fallback
    $hasCategorias = false;
  }
}

if (!$hasCategorias) {
  // Fallback: derivar categorías desde producto y contar
  $result = $conexion->query("SELECT LOWER(TRIM(categoria)) AS categoria, COUNT(*) AS c FROM producto WHERE categoria IS NOT NULL AND categoria <> '' GROUP BY LOWER(TRIM(categoria)) ORDER BY categoria");
  if ($result) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
      $name = (string)$row['categoria'];
      // Si la categoría viene vacía tras trim, ignorar
      if ($name === '') continue;
      $display = mb_strtoupper($name);
      $count = (int)($row['c'] ?? 0);
      $categories[] = [
        'id' => $name, // Usar nombre como ID para el filtro
        'name' => $name,
        'display' => $display,
        'product_count' => $count
      ];
    }
    $result->closeCursor();
  }
}

echo json_encode([
  'ok' => true,
  'count' => count($categories),
  'categories' => $categories
], JSON_UNESCAPED_UNICODE);
?>