<?php
require 'connection.php';

header('Content-Type: application/json');

// Parámetros de filtro
$range = $_GET['range'] ?? 'week';
$start = $_GET['start'] ?? null;
$end   = $_GET['end']   ?? null;

// Calcular fechas
$today = date('Y-m-d');
$startDate = $start;
$endDate = $end ?: $today;

if (!$start) {
    if ($range === 'today') {
        $startDate = $today;
    } elseif ($range === 'week') {
        $startDate = date('Y-m-d', strtotime('-1 week'));
    } elseif ($range === 'month') {
        $startDate = date('Y-m-01');
    } elseif ($range === 'year') {
        $startDate = date('Y-01-01');
    } else {
        $startDate = date('Y-m-d', strtotime('-1 week'));
    }
}

// Helper para detectar columnas
function colExists($pdo, $table, $col) {
    try {
        $stmt = $pdo->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = ? AND column_name = ?");
        $stmt->execute([$table, $col]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) { return false; }
}

// Detectar nombres de columnas clave
$userDateCol = colExists($pdo, 'usuario', 'created_at') ? 'created_at' : 'fecha_registro';
$userRoleCol = colExists($pdo, 'usuario', 'rol') ? 'rol' : 'role';

// 1. VENTAS
$salesData = [];
$totalSales = 0;
$totalOrders = 0;

try {
    // Usamos 'total' en lugar de 'total_amount' según orders_save.php
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as fecha, SUM(total) as total, COUNT(*) as count 
        FROM orders 
        WHERE created_at BETWEEN :start AND :end AND status != 'cancelled'
        GROUP BY DATE(created_at) 
        ORDER BY fecha ASC
    ");
    $stmt->execute(['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59']);
    $salesRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($salesRows as $row) {
        $totalSales += $row['total'];
        $totalOrders += $row['count'];
    }
    
    // Rellenar días
    $period = new DatePeriod(
        new DateTime($startDate),
        new DateInterval('P1D'),
        (new DateTime($endDate))->modify('+1 day')
    );
    
    $labels = [];
    $dataPoints = [];
    
    foreach ($period as $dt) {
        $d = $dt->format('Y-m-d');
        $found = false;
        foreach ($salesRows as $r) {
            if ($r['fecha'] === $d) {
                $labels[] = $dt->format('D d');
                $dataPoints[] = $r['total'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $labels[] = $dt->format('D d');
            $dataPoints[] = 0;
        }
    }
    
    $salesData = [
        'labels' => $labels,
        'data' => $dataPoints,
        'total' => $totalSales,
        'orders' => $totalOrders,
        'avg' => $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0
    ];

} catch (Exception $e) {
    $salesData = ['error' => $e->getMessage()];
}

// 2. PRODUCTOS
$productsData = [];
try {
    // Categorías
    // Ajuste: usar nombre de columna correcto para ID categoria
    // En admin_products_manage vimos que puede ser id_categoria o categoria_id
    $catIdCol = colExists($pdo, 'producto', 'id_categoria') ? 'id_categoria' : 'categoria_id';
    
    $stmt = $pdo->query("
        SELECT c.nombre as cat, COUNT(p.$catIdCol) as count 
        FROM categorias c 
        LEFT JOIN producto p ON p.$catIdCol = c.id_categoria 
        GROUP BY c.id_categoria, c.nombre
    ");
    $catRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $catLabels = [];
    $catData = [];
    $totalCats = count($catRows);
    
    foreach ($catRows as $r) {
        $catLabels[] = $r['cat'];
        $catData[] = $r['count'];
    }
    
    // Stock Bajo
    $stmt = $pdo->query("SELECT COUNT(*) as low FROM producto WHERE stock <= stock_minimo");
    $lowStock = $stmt->fetch(PDO::FETCH_ASSOC)['low'];
    
    // Top Producto (JOIN por nombre ya que order_items no tiene product_id)
    // Usamos 'qty' en lugar de 'quantity' según orders_save.php
    $stmt = $pdo->query("
        SELECT oi.title as nombre, SUM(oi.qty) as sold 
        FROM order_items oi 
        GROUP BY oi.title 
        ORDER BY sold DESC 
        LIMIT 1
    ");
    $topProd = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $productsData = [
        'catLabels' => $catLabels,
        'catData' => $catData,
        'totalCats' => $totalCats,
        'lowStock' => $lowStock,
        'topProduct' => $topProd ? $topProd['nombre'] : 'N/A',
        'topSold' => $topProd ? $topProd['sold'] : 0
    ];

} catch (Exception $e) {
    $productsData = ['error' => $e->getMessage()];
}

// 3. CLIENTES
$customersData = [];
try {
    // Nuevos
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuario WHERE \"$userDateCol\" BETWEEN :start AND :end AND \"$userRoleCol\" != 'admin'");
    $stmt->execute(['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59']);
    $newCust = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Recurrentes (por email en orders ya que user_id no siempre está)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count FROM (
            SELECT user_email FROM orders WHERE user_email IS NOT NULL GROUP BY user_email HAVING COUNT(*) > 1
        ) as recurring
    ");
    $recurringCust = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total clientes
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM usuario WHERE \"$userRoleCol\" != 'admin'");
    $totalCust = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $retention = $totalCust > 0 ? round(($recurringCust / $totalCust) * 100, 1) : 0;
    
    $customersData = [
        'new' => $newCust,
        'recurring' => $recurringCust,
        'vip' => 0,
        'retention' => $retention
    ];
} catch (Exception $e) {
    $customersData = ['error' => $e->getMessage()];
}

// 4. INVENTARIO
$inventoryData = [];
try {
    // Valor total (precio_compra_lote o precio_compra * stock)
    $buyPriceCol = colExists($pdo, 'producto', 'precio_compra_lote') ? 'precio_compra_lote' : 'precio_compra';
    
    $stmt = $pdo->query("SELECT SUM(\"$buyPriceCol\" * stock) as total_val, COUNT(*) as total_items FROM producto");
    $invRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN stock >= 20 THEN 1 ELSE 0 END) as high,
            SUM(CASE WHEN stock >= 10 AND stock < 20 THEN 1 ELSE 0 END) as medium,
            SUM(CASE WHEN stock >= 5 AND stock < 10 THEN 1 ELSE 0 END) as low,
            SUM(CASE WHEN stock < 5 THEN 1 ELSE 0 END) as critical
        FROM producto
    ");
    $invStatus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $inventoryData = [
        'value' => $invRow['total_val'] ?: 0,
        'items' => $invRow['total_items'] ?: 0,
        'status' => [
            $invStatus['high'] ?? 0,
            $invStatus['medium'] ?? 0,
            $invStatus['low'] ?? 0,
            $invStatus['critical'] ?? 0,
            0
        ]
    ];
} catch (Exception $e) {
    $inventoryData = ['error' => $e->getMessage()];
}

// 5. TOP PRODUCTOS TABLA
$topProducts = [];
try {
    // JOIN por nombre (title)
    $stmt = $pdo->prepare("
        SELECT 
            oi.title as nombre, 
            c.nombre as categoria, 
            SUM(oi.qty) as unidades, 
            SUM(oi.price * oi.qty) as ingresos, 
            p.stock 
        FROM order_items oi
        LEFT JOIN producto p ON LOWER(p.nombre) = LOWER(oi.title)
        LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
        JOIN orders o ON o.id = oi.order_id
        WHERE o.created_at BETWEEN :start AND :end
        GROUP BY oi.title, c.nombre, p.stock
        ORDER BY ingresos DESC
        LIMIT 5
    ");
    $stmt->execute(['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59']);
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $topProducts = [];
}

echo json_encode([
    'ok' => true,
    'range' => $range,
    'sales' => $salesData,
    'products' => $productsData,
    'customers' => $customersData,
    'inventory' => $inventoryData,
    'topTable' => $topProducts
]);
?>