<?php
require_once __DIR__ . '/conexion.php';

$orderId = $_GET['id'] ?? 0;

if (!$orderId) die("ID de orden no válido");

// Fetch Order
$stmt = $conexion->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt->closeCursor();

if (!$order) die("Orden no encontrada");

// Fetch Items
$stmtI = $conexion->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmtI->execute([$orderId]);
$items = $stmtI->fetchAll(PDO::FETCH_ASSOC);
$stmtI->closeCursor();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ticket #<?= $orderId ?></title>
    <style>
        body { font-family: 'Courier New', monospace; font-size: 12px; width: 300px; margin: 0 auto; background: white; color: black; padding: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        .item { margin-bottom: 5px; }
        .item-row { display: flex; justify-content: space-between; }
        .item-name { display: block; margin-bottom: 2px; }
        .total { font-weight: bold; font-size: 14px; text-align: right; margin-top: 10px; }
        .footer { text-align: center; margin-top: 20px; font-size: 10px; }
        .btn-print { 
            background: #ff0000; color: white; border: none; padding: 10px; width: 100%; 
            cursor: pointer; font-size: 14px; margin-top: 20px; border-radius: 4px;
        }
        @media print { .no-print { display: none; } body { margin: 0; padding: 0; } }
    </style>
</head>
<body>
    <div class="header">
        <h3 style="margin:5px 0">DISTRICARNES</h3>
        <p style="margin:2px 0">Hermanos Navarro</p>
        <p style="margin:10px 0 2px 0">Ticket de Venta #<?= $orderId ?></p>
        <p style="margin:2px 0"><?= $order['created_at'] ?></p>
    </div>
    
    <div class="divider"></div>
    
    <div>
        <p style="margin:5px 0"><strong>Cliente:</strong> <?= htmlspecialchars($order['user_name'] ?: 'Público General') ?></p>
        <?php if($order['user_email']): ?>
        <p style="margin:2px 0; font-size:10px;"><?= htmlspecialchars($order['user_email']) ?></p>
        <?php endif; ?>
        <p style="margin:5px 0"><strong>Pago:</strong> <?= strpos($order['paypal_id'], 'CASH') !== false ? 'Efectivo' : (strpos($order['paypal_id'], 'CARD') !== false ? 'Tarjeta' : 'Otro') ?></p>
    </div>

    <div class="divider"></div>

    <?php foreach($items as $item): ?>
    <div class="item">
        <span class="item-name"><?= htmlspecialchars($item['title']) ?></span>
        <div class="item-row">
            <span><?= $item['qty'] ?> x $<?= number_format($item['price'], 0) ?></span>
            <span>$<?= number_format($item['price'] * $item['qty'], 0) ?></span>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="divider"></div>

    <div class="total">
        TOTAL: $<?= number_format($order['total'], 0) ?>
    </div>

    <div class="footer">
        <p>¡Gracias por su compra!</p>
        <p>Conserve este ticket para reclamos</p>
    </div>
    
    <button class="no-print btn-print" onclick="window.print()">Imprimir Ticket</button>
    <button class="no-print btn-print" style="background:#333; margin-top:10px;" onclick="window.close()">Cerrar</button>

    <script>
        // Auto print on load (optional, maybe annoying during dev)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>