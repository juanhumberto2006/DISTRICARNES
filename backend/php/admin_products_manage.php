<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Método no permitido']);
  exit;
}

$action = $_POST['action'] ?? '';

// Utilidades de esquema
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
if (!$idCol) {
  echo json_encode(['success' => false, 'message' => 'No se encontró columna ID en producto']);
  exit;
}

// Toggle activo/inactivo
if ($action === 'toggle') {
  $productId = $_POST['product_id'] ?? null;
  if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'ID de producto faltante']);
    exit;
  }

  // Detectar columna de estado entre varias opciones comunes
  $statusCandidates = ['estado','activo','status','disponible','habilitado'];
  $statusCol = null;
  foreach ($statusCandidates as $c) {
    if (in_array($c, $columns, true)) { $statusCol = $c; break; }
  }
  // Si no existe ninguna, intentar crear la columna 'estado' como SMALLINT DEFAULT 1
  if (!$statusCol) {
    try {
        $conexion->exec("ALTER TABLE \"$table\" ADD COLUMN \"estado\" SMALLINT NOT NULL DEFAULT 1");
        // Recargar columnas y reintentar
        $columns = getColumns($conexion, $table);
        if (in_array('estado', $columns, true)) {
          $statusCol = 'estado';
        } else {
          throw new Exception("Columna no creada");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Columna de estado no encontrada y no se pudo crear']);
        exit;
    }
  }

  $stmt = $conexion->prepare("SELECT \"$statusCol\" FROM \"$table\" WHERE \"$idCol\" = ? LIMIT 1");
  $stmt->execute([$productId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  
  if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    $stmt->closeCursor();
    exit;
  }
  $current = $row[$statusCol];
  $stmt->closeCursor();

  // Determinar nuevo estado (verificando tipo de columna primero)
  try {
      $stmtType = $conexion->prepare("SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?");
      $stmtType->execute([$table, $statusCol]);
      $tRow = $stmtType->fetch(PDO::FETCH_ASSOC);
      $dataType = $tRow['data_type'] ?? '';
      $stmtType->closeCursor();
      
      if (stripos($dataType, 'bool') !== false) {
          // Determinar nuevo estado basado en el actual (Boolean)
          if ($current === true || $current === 't' || $current === 'true' || $current === '1' || $current === 1) {
              $new = 'false';
          } else {
              $new = 'true';
          }
      } else {
          // Si NO es booleano, mantener lógica string/numérica
          if (is_numeric($current)) {
              $new = ((int)$current) === 1 ? 0 : 1;
          } else {
              $lc = strtolower((string)$current);
              $new = ($lc === 'activo' || $lc === 'active') ? 'inactivo' : 'activo';
          }
      }
  } catch (Exception $e) {
      // Fallback
      $lc = strtolower((string)$current);
      $new = ($lc === 'activo' || $lc === 'active') ? 'inactivo' : 'activo';
  }

  $stmt2 = $conexion->prepare("UPDATE \"$table\" SET \"$statusCol\" = ? WHERE \"$idCol\" = ?");
  try {
      $ok = $stmt2->execute([$new, $productId]);
  } catch (PDOException $e) {
      echo json_encode(['success' => false, 'message' => 'Error al actualizar estado: ' . $e->getMessage()]);
      exit;
  }
  $stmt2->closeCursor();

  echo json_encode(['success' => $ok, 'message' => $ok ? 'Estado actualizado' : 'No se pudo actualizar el estado']);
  // $conexion->close();
  exit;
}

// Eliminar producto
if ($action === 'delete') {
  $productId = $_POST['product_id'] ?? null;
  if (!$productId) {
    echo json_encode(['success' => false, 'message' => 'ID de producto faltante']);
    exit;
  }

  // Intentar obtener columna de imagen para eliminar archivo físico
  $imageCol = null;
  foreach (['imagen','image','imagen_url','image_url','foto','imagen_producto','url_imagen'] as $c) {
    if (in_array($c, $columns, true)) { $imageCol = $c; break; }
  }
  $oldImage = null;
  if ($imageCol) {
    $stmtImg = $conexion->prepare("SELECT \"$imageCol\" FROM \"$table\" WHERE \"$idCol\" = ? LIMIT 1");
    $stmtImg->execute([$productId]);
    $rowImg = $stmtImg->fetch(PDO::FETCH_ASSOC);
    if ($rowImg) {
      $oldImage = $rowImg[$imageCol] ?? null;
    }
    $stmtImg->closeCursor();
  }

  $stmt = $conexion->prepare("DELETE FROM \"$table\" WHERE \"$idCol\" = ?");
  $ok = $stmt->execute([$productId]);
  $stmt->closeCursor();

  // Si se eliminó el registro, intentar borrar el archivo de imagen
  if ($ok && $oldImage) {
    $rootDir = dirname(__DIR__, 2);
    // Convertir ruta web a ruta filesystem
    $fsPath = $rootDir . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $oldImage), DIRECTORY_SEPARATOR);
    @unlink($fsPath);
  }

  echo json_encode(['success' => $ok, 'message' => $ok ? 'Producto eliminado' : 'No se pudo eliminar el producto']);
  // $conexion->close();
  exit;
}

// Crear / Actualizar producto
if ($action === 'create' || $action === 'update') {
  // Helper para subir imagen con validaciones
  function upload_product_image(array $file, string $rootDir): array {
    $result = ['ok' => false, 'path' => null, 'error' => null];
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $result['error'] = 'Archivo de imagen no recibido';
      return $result;
    }
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) { $result['error'] = 'Archivo vacío'; return $result; }
    if ($size > 5 * 1024 * 1024) { $result['error'] = 'La imagen supera 5MB'; return $result; }
    $type = (string)($file['type'] ?? '');
    if (strpos($type, 'image/') !== 0) { $result['error'] = 'Tipo de archivo no permitido'; return $result; }
    $origName = basename((string)$file['name']);
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!$ext) {
      // Intentar deducir por MIME
      $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
      $ext = $map[$type] ?? 'jpg';
    }
    $safeName = uniqid('prod_', true) . '.' . $ext;
    $uploadDir = $rootDir . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'products';
    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
    $dest = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) { $result['error'] = 'No se pudo guardar la imagen'; return $result; }
    $result['ok'] = true; $result['path'] = '/static/images/products/' . $safeName; return $result;
  }

  $fields = [
    'nombre' => $_POST['productName'] ?? null,
    'precio' => isset($_POST['productPrice']) ? (float)$_POST['productPrice'] : null,
    'stock' => isset($_POST['productStock']) ? (int)$_POST['productStock'] : null,
    'stock_minimo' => isset($_POST['stock_minimo']) ? (int)$_POST['stock_minimo'] : null,
    'fecha_vencimiento' => $_POST['productExpiry'] ?? null,
    'lote' => ($_POST['batchNumber'] ?? null),
    'precio_compra' => isset($_POST['purchasePrice']) ? (float)$_POST['purchasePrice'] : null,
    'descripcion' => $_POST['productDescription'] ?? ($_POST['descripcion'] ?? null),
    'subcategoria' => $_POST['subcategoria'] ?? null,
    'codigo' => $_POST['productCode'] ?? null,
    'tipo_unidad' => $_POST['unitType'] ?? null,
    'marca' => $_POST['productBrand'] ?? null,
    'modelo' => $_POST['productModel'] ?? null,
    'estado' => $_POST['productStatus'] ?? 'Activo'
  ];

  // Corrección para PostgreSQL: Verificar si la columna 'estado' es de tipo BOOLEAN
  if (in_array('estado', $columns, true)) {
      try {
          $stmtType = $conexion->prepare("SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = 'estado'");
          $stmtType->execute([$table]);
          $tRow = $stmtType->fetch(PDO::FETCH_ASSOC);
          $dataType = $tRow['data_type'] ?? '';
          $stmtType->closeCursor();
          
          if (stripos($dataType, 'bool') !== false) {
              // Convertir 'Activo'/'Inactivo' a representación booleana aceptada por Postgres
              $rawStatus = $fields['estado'];
              $isTrue = ($rawStatus === 'Activo' || $rawStatus === 'Active' || $rawStatus == 1 || $rawStatus === 'true');
              $fields['estado'] = $isTrue ? 'true' : 'false';
          }
      } catch (Exception $e) {
          // Ignorar error de verificación de tipo
      }
  }

  // Asegurar un mínimo de 5 unidades para stock_minimo
  if (!isset($fields['stock_minimo']) || $fields['stock_minimo'] === null) {
    $fields['stock_minimo'] = 5;
  } else {
    $fields['stock_minimo'] = max(5, (int)$fields['stock_minimo']);
  }

  // Mapear categoría enviada desde el formulario a la columna correcta
  $selectedCategory = $_POST['productCategory'] ?? null;
  $catIdCol = null;
  foreach (['categoria_id','id_categoria','category_id'] as $c) {
    if (in_array($c, $columns, true)) { $catIdCol = $c; break; }
  }
  $catTextCol = in_array('categoria', $columns, true) ? 'categoria' : null;
  if ($selectedCategory !== null) {
    if ($catIdCol) {
      // Usar el ID de categoría (FK)
      $fields[$catIdCol] = $selectedCategory;
    } elseif ($catTextCol) {
      // Usar el nombre de categoría en texto
      $fields[$catTextCol] = $selectedCategory;
    }
  } else {
    // Si no se envió categoría pero es requerida, poner 1
    if ($catIdCol && !isset($fields[$catIdCol])) {
        $fields[$catIdCol] = 1;
    }
  }

  // Mapear sinónimos de columnas si las originales no existen
  if (!in_array('precio', $columns, true) && in_array('precio_venta', $columns, true) && isset($fields['precio'])) {
    $fields['precio_venta'] = $fields['precio'];
    unset($fields['precio']);
  }
  if (!in_array('fecha_vencimiento', $columns, true) && in_array('fecha_caducidad', $columns, true) && isset($fields['fecha_vencimiento'])) {
    $fields['fecha_caducidad'] = $fields['fecha_vencimiento'];
    unset($fields['fecha_vencimiento']);
  }
  if (!in_array('lote', $columns, true) && in_array('numero_lote', $columns, true) && isset($fields['lote'])) {
    $fields['numero_lote'] = $fields['lote'];
    unset($fields['lote']);
  }
  if (!in_array('precio_compra', $columns, true) && in_array('precio_compra_lote', $columns, true) && isset($fields['precio_compra'])) {
    $fields['precio_compra_lote'] = $fields['precio_compra'];
    // Si existe precio_abastecimiento y no se envió, usar el mismo precio de compra
    if (in_array('precio_abastecimiento', $columns, true) && !isset($fields['precio_abastecimiento'])) {
        $fields['precio_abastecimiento'] = $fields['precio_compra'];
    }
    unset($fields['precio_compra']);
  }
  // Generar número de lote automáticamente si no se envió y existe alguna columna compatible
  $batchCandidates = ['lote','numero_lote','num_lote','lote_numero'];
  $existingBatchCol = null;
  foreach ($batchCandidates as $bc) {
    if (in_array($bc, $columns, true)) { $existingBatchCol = $bc; break; }
  }
  // Si no se envió lote en ningún alias, generarlo
  $hasBatchValue = false;
  foreach ($batchCandidates as $bc) {
    if (isset($fields[$bc]) && $fields[$bc] !== null && $fields[$bc] !== '') { $hasBatchValue = true; break; }
  }
  if ($existingBatchCol && !$hasBatchValue) {
    // Ej: L20241012-ABC123
    $rand = bin2hex(random_bytes(3));
    $autoBatch = 'L' . date('Ymd') . '-' . strtoupper(substr($rand, 0, 6));
    $fields[$existingBatchCol] = $autoBatch;
    // Remover otros alias vacíos si existían
    foreach ($batchCandidates as $bc) {
      if ($bc !== $existingBatchCol) { unset($fields[$bc]); }
    }
  }
  // Adicionales: num_lote/lote_numero, valor_compra/costo_compra, inscripcion, min_stock/stock_min
  if (!in_array('lote', $columns, true) && isset($fields['lote'])) {
    foreach (['num_lote','lote_numero'] as $alt) {
      if (in_array($alt, $columns, true)) { $fields[$alt] = $fields['lote']; unset($fields['lote']); break; }
    }
  }
  if (!in_array('precio_compra', $columns, true) && isset($fields['precio_compra'])) {
    foreach (['valor_compra','costo_compra','precio_lote_compra'] as $alt) {
      if (in_array($alt, $columns, true)) { $fields[$alt] = $fields['precio_compra']; unset($fields['precio_compra']); break; }
    }
  }
  if (!in_array('descripcion', $columns, true) && isset($fields['descripcion'])) {
    foreach (['inscripcion'] as $alt) {
      if (in_array($alt, $columns, true)) { $fields[$alt] = $fields['descripcion']; unset($fields['descripcion']); break; }
    }
  }
  if (!in_array('stock_minimo', $columns, true) && isset($fields['stock_minimo'])) {
    foreach (['min_stock','stock_min'] as $alt) {
      if (in_array($alt, $columns, true)) { $fields[$alt] = $fields['stock_minimo']; unset($fields['stock_minimo']); break; }
    }
  }

  // Manejo de imagen si la columna existe
  $imageCol = null;
  foreach (['imagen','image','imagen_url','image_url','foto','imagen_producto','url_imagen'] as $c) {
    if (in_array($c, $columns, true)) { $imageCol = $c; break; }
  }
  // Si no existe ninguna columna de imagen, crear `imagen`
  if (!$imageCol) {
      // Intentar ver si existe `imagen_producto`
      if (in_array('imagen_producto', $columns, true)) {
          $imageCol = 'imagen_producto';
      } else {
        @$conexion->query("ALTER TABLE \"$table\" ADD COLUMN \"imagen\" VARCHAR(500) NULL");
        $columns = getColumns($conexion, $table);
        if (in_array('imagen', $columns, true)) { $imageCol = 'imagen'; }
      }
  }

  // Asegurar mapeo de id_categoria si existe
  if (isset($fields['categoria_id']) && !in_array('categoria_id', $columns, true) && in_array('id_categoria', $columns, true)) {
      $fields['id_categoria'] = $fields['categoria_id'];
      unset($fields['categoria_id']);
  }
  
  // Asegurar mapeo de id_proveedor si es requerido y no enviado
  // En este caso, si id_proveedor es NOT NULL en DB, necesitamos enviarlo.
  // Podríamos poner un valor por defecto o NULL si la BD lo permite.
  // Asumiremos 1 si no se envía, o NULL.
  if (in_array('id_proveedor', $columns, true) && !isset($fields['id_proveedor'])) {
      $fields['id_proveedor'] = 1; // Default provider ID
  }

  // En creación: manejar nueva imagen directamente
  if ($action === 'create' && $imageCol && isset($_FILES['productImage']) && is_array($_FILES['productImage'])) {
    $rootDir = dirname(__DIR__, 2); // DISTRICARNES
    $up = upload_product_image($_FILES['productImage'], $rootDir);
    if ($up['ok']) { $fields[$imageCol] = $up['path']; }
    elseif ($up['error']) { echo json_encode(['success' => false, 'message' => $up['error']]); exit; }
  }

  // Solo usar columnas existentes
  $filtered = [];
  foreach ($fields as $col => $val) {
    if ($val !== null && in_array($col, $columns, true)) {
      $filtered[$col] = $val;
    }
  }

  if ($action === 'create') {
    if (empty($filtered)) {
      echo json_encode(['success' => false, 'message' => 'No hay campos válidos para crear']);
      exit;
    }
    
    try {
        $colsStr = '"' . implode('","', array_keys($filtered)) . '"';
        $placeholders = rtrim(str_repeat('?,', count($filtered)), ',');
        $stmt = $conexion->prepare("INSERT INTO \"$table\" ($colsStr) VALUES ($placeholders)");
        $values = array_values($filtered);
        $ok = $stmt->execute($values);
        $stmt->closeCursor();
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Producto creado' : 'No se pudo crear el producto']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()]);
    }
    exit;
  } else {
    $productId = $_POST['product_id'] ?? null;
    if (!$productId) {
      echo json_encode(['success' => false, 'message' => 'ID de producto faltante']);
      exit;
    }
    // En actualización: si hay nueva imagen, subirla y eliminar la anterior
    if ($imageCol && isset($_FILES['productImage']) && is_array($_FILES['productImage'])) {
      // Obtener imagen previa
      $prev = null;
      $stmtPrev = $conexion->prepare("SELECT \"$imageCol\" FROM \"$table\" WHERE \"$idCol\" = ? LIMIT 1");
      $stmtPrev->execute([$productId]);
      $rPrev = $stmtPrev->fetch(PDO::FETCH_ASSOC);
      if ($rPrev) {
        $prev = $rPrev[$imageCol] ?? null;
      }
      $stmtPrev->closeCursor();
      $rootDir = dirname(__DIR__, 2);
      $up = upload_product_image($_FILES['productImage'], $rootDir);
      if ($up['ok']) {
        $newRel = $up['path'];
        $fields[$imageCol] = $newRel;
        $filtered[$imageCol] = $newRel;
        if ($prev) {
          $fsPrev = $rootDir . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $prev), DIRECTORY_SEPARATOR);
          @unlink($fsPrev);
        }
      } elseif ($up['error'] && ($_FILES['productImage']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        echo json_encode(['success' => false, 'message' => $up['error']]); exit;
      }
    }
    if (empty($filtered)) {
      echo json_encode(['success' => false, 'message' => 'No hay campos válidos para actualizar']);
      exit;
    }
    $setParts = [];
    foreach (array_keys($filtered) as $col) {
      $setParts[] = "\"$col\" = ?";
    }
    $sql = "UPDATE \"$table\" SET " . implode(', ', $setParts) . " WHERE \"$idCol\" = ?";
    $stmt = $conexion->prepare($sql);
    $values = array_values($filtered);
    $values[] = $productId;
    $ok = $stmt->execute($values);
    $stmt->closeCursor();
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Producto actualizado' : 'No se pudo actualizar el producto']);
    // $conexion->close();
    exit;
  }
}

echo json_encode(['success' => false, 'message' => 'Acción no soportada']);
// $conexion->close();
?>