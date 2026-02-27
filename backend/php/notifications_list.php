<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

function format_order_event(array $row): array {
  $id = intval($row['id']);
  $status = strtoupper((string)$row['status']);
  $user = isset($row['user_name']) ? $row['user_name'] : 'Cliente';
  $created = $row['created_at'];
  $total = isset($row['total']) ? floatval($row['total']) : 0.0;

  $type = 'order';
  $title = '';
  $message = '';

  if ($status === 'PENDING') {
    $type = 'order';
    $title = "Nuevo pedido #$id";
    $message = "$user realizó un pedido";
  } else if ($status === 'PROCESSING') {
    $type = 'order';
    $title = "Pedido en proceso #$id";
    $message = "El pedido está siendo preparado/envíado";
  } else if ($status === 'COMPLETED') {
    $type = 'sale';
    $title = "Orden completada #$id";
    $message = "Venta registrada: $" . number_format($total, 2);
  } else if ($status === 'CANCELLED') {
    $type = 'order';
    $title = "Pedido cancelado #$id";
    $message = "El pedido fue cancelado";
  } else {
    $type = 'order';
    $title = "Actualización de pedido #$id";
    $message = "Estado: $status";
  }

  return [
    'type' => $type,
    'title' => $title,
    'message' => $message,
    'created_at' => $created,
    'link' => "./admin_orders.html?orderId=$id"
  ];
}

function format_user_event(array $row): array {
    $name = isset($row['nombres_completos']) ? $row['nombres_completos'] : 'Usuario';
    $created = $row['created_at'];
    return [
        'type' => 'user',
        'title' => 'Nuevo usuario registrado',
        'message' => "$name se ha unido al sistema",
        'created_at' => $created,
        'link' => './admin_users.html'
    ];
}

function table_exists(PDO $db, string $name): bool {
    $stmt = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_name = ? LIMIT 1");
    $stmt->execute([$name]);
    $exists = (bool)$stmt->fetch();
    $stmt->closeCursor();
    return $exists;
}

$notifications = [];

try {
    // 1. Notificaciones de Órdenes Recientes
    if (table_exists($conexion, 'orders')) {
        $stmt = $conexion->prepare("SELECT id, user_name, status, total, created_at FROM orders ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = format_order_event($row);
        }
        $stmt->closeCursor();
    }

    // 2. Notificaciones de Nuevos Usuarios
    if (table_exists($conexion, 'usuario')) {
        $stmt = $conexion->prepare("SELECT nombres_completos, created_at FROM usuario WHERE rol != 'admin' ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = format_user_event($row);
        }
        $stmt->closeCursor();
    }

    // 3. Alerta de Stock Bajo (simplificado para PDO)
    if (table_exists($conexion, 'producto')) {
        $stmt = $conexion->query("SELECT COUNT(*) AS cnt FROM producto WHERE stock < 10");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cnt = isset($row['cnt']) ? intval($row['cnt']) : 0;
        if ($cnt > 0) {
            array_unshift($notifications, [
                'type' => 'inventory',
                'title' => 'Alerta de stock bajo',
                'message' => "$cnt productos con stock crítico (<10)",
                'created_at' => date('Y-m-d H:i:s'),
                'link' => './admin_inventory.html'
            ]);
        }
    }

    // Ordenar todas por fecha descendente
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limitar a 20
    $notifications = array_slice($notifications, 0, 20);

    // Contar no leídas (última hora)
    $unread = 0;
    $threshold = strtotime('-60 minutes');
    foreach ($notifications as $n) {
        $ts = strtotime($n['created_at']);
        if ($ts !== false && $ts >= $threshold) { $unread++; }
    }

    echo json_encode(['ok' => true, 'notifications' => $notifications, 'unread_count' => $unread]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
exit;
?>