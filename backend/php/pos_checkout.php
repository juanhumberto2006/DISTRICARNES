<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/sales_utils.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['ok' => false, 'message' => 'Datos inválidos']);
    exit;
}

$clientName = $input['client_name'] ?? 'Público General';
$clientEmail = $input['client_email'] ?? null;
$paymentMethod = $input['payment_method'] ?? 'cash';
$items = $input['items'] ?? [];
$total = floatval($input['total'] ?? 0);

if (empty($items)) {
    echo json_encode(['ok' => false, 'message' => 'El carrito está vacío']);
    exit;
}

try {
    $conexion->beginTransaction();

    // 1. Crear Orden
    // delivery_method = 'POS'
    // status = 'COMPLETED'
    $sqlOrder = "INSERT INTO orders (user_name, user_email, status, total, delivery_method, paypal_id) VALUES (?, ?, 'COMPLETED', ?, 'POS', ?)";
    $stmtOrder = $conexion->prepare($sqlOrder);
    // paypal_id usado aquí como referencia de pago (ej. 'CASH-TIMESTAMP')
    $ref = strtoupper($paymentMethod) . '-' . time();
    $stmtOrder->execute([$clientName, $clientEmail, $total, $ref]);
    $orderId = $conexion->lastInsertId();
    $stmtOrder->closeCursor();

    // 2. Insertar Items y Actualizar Stock
    $sqlItem = "INSERT INTO order_items (order_id, title, price, qty) VALUES (?, ?, ?, ?)";
    $stmtItem = $conexion->prepare($sqlItem);

    // Detectar columna ID de producto dinámicamente
    $idCol = 'id_producto'; // Default
    $stmtCols = $conexion->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'producto'");
    if ($stmtCols) {
        $pCols = [];
        while($r = $stmtCols->fetch(PDO::FETCH_ASSOC)) { $pCols[] = $r['column_name']; }
        $stmtCols->closeCursor();
        foreach (['id_producto','id','producto_id'] as $c) {
            if (in_array($c, $pCols)) { $idCol = $c; break; }
        }
    }

    $sqlStock = "UPDATE producto SET stock = stock - ? WHERE \"$idCol\" = ?";
    $stmtStock = $conexion->prepare($sqlStock);

    foreach ($items as $item) {
        $qty = intval($item['qty']);
        $price = floatval($item['price']);
        $title = $item['title'];
        $prodId = $item['id'];

        // Guardar item
        $stmtItem->execute([$orderId, $title, $price, $qty]);
        
        // Actualizar inventario
        // Validar stock antes? (Opcional, la UI ya lo hace, pero el backend debería ser seguro)
        // Por ahora confiamos en la transacción y check constraint (si existiera)
        $stmtStock->execute([$qty, $prodId]);
    }
    $stmtItem->closeCursor();
    $stmtStock->closeCursor();

    // 3. Registrar Venta en tabla 'venta'/'sales' para reportes
    // Asegurar que sales_utils.php está cargado y sync ejecutado si es necesario
    if (function_exists('sync_sales_from_orders')) {
        // Opcional: sync_sales_from_orders($conexion); // Puede ser pesado
    }
    
    $resSale = record_sale_for_order($conexion, $orderId);
    // Ignorar error si ya existe o no es crítico
    
    $conexion->commit();
    echo json_encode(['ok' => true, 'order_id' => $orderId, 'message' => 'Venta registrada']);

} catch (Exception $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log("POS Checkout Error: " . $e->getMessage());
    echo json_encode(['ok' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>