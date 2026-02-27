<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

try {
    // Consultar directamente la tabla de órdenes para obtener datos en tiempo real
    // Esto evita problemas de sincronización con tablas secundarias
    
    $from = isset($_GET['from']) ? trim($_GET['from']) : '';
    $to   = isset($_GET['to']) ? trim($_GET['to']) : '';

    // Consulta base a la tabla orders
    // Filtramos por estado COMPLETED para solo contar ventas reales
    $query = "SELECT id, paypal_id, user_email as customer_email, user_name as customer_name, total, created_at 
              FROM orders 
              WHERE status = 'COMPLETED'";
    
    $params = [];

    if ($from !== '') { 
        $query .= ' AND created_at >= ?'; 
        $params[] = $from; 
    }
    if ($to !== '') { 
        $query .= ' AND created_at <= ?'; 
        $params[] = $to; 
    }
    
    $query .= ' ORDER BY created_at DESC';

    $stmt = $conexion->prepare($query);
    if(!$stmt){ 
        // Si falla (ej. tabla no existe), devolver array vacío
        echo json_encode(['ok'=>true, 'sales'=>[]]); 
        exit; 
    }

    $ok = $stmt->execute($params);
    if(!$ok){ 
        echo json_encode(['ok'=>false, 'error'=> implode(' ', $stmt->errorInfo())]); 
        exit; 
    }

    $sales = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
      $sales[] = [
        'id' => intval($row['id']),
        'order_id' => intval($row['id']), // En orders, id es el order_id
        'paypal_id' => $row['paypal_id'],
        'customer_email' => $row['customer_email'],
        'customer_name' => $row['customer_name'],
        'total' => floatval($row['total']),
        'created_at' => $row['created_at'],
      ];
    }
    $stmt->closeCursor();

    echo json_encode(['ok'=>true, 'sales'=>$sales]);
} catch (Exception $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
exit;
?>
