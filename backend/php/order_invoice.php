<?php
// Página HTML de factura imprimible (formato profesional, español, QR)
require_once __DIR__ . '/conexion.php';          // PDO (PostgreSQL)
require_once __DIR__ . '/paypal_config.php';
require_once __DIR__ . '/factus_config.php';     // Datos de empresa

$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if ($orderId <= 0) { http_response_code(400); echo 'order_id requerido'; exit; }

function money_es($n) {
  // Formato español: separador miles '.', decimales ','
  return '$' . number_format((float)$n, 0, ',', '.');
}

// Cargar orden e ítems desde orders_pg/order_items_pg (PostgreSQL) o, si no existen, intentar 'orders'/'order_items'
$order = null;
$items = [];
try {
  $stmt = $conexion->prepare('SELECT id, paypal_id, user_email, user_name, status, total, delivery_method, address_json, schedule_json, created_at, factus_invoice_id, factus_number, factus_status, factus_pdf_url FROM orders_pg WHERE id = ?');
  $stmt->execute([$orderId]);
  $order = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($order) {
    $stmtI = $conexion->prepare('SELECT title, price, qty, image FROM order_items_pg WHERE order_id = ?');
    $stmtI->execute([$orderId]);
    $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Throwable $e) { /* fallback abajo */ }

if (!$order) {
  try {
    $stmt = $conexion->prepare('SELECT id, paypal_id, user_email, user_name, status, total, delivery_method, address_json, schedule_json, created_at, factus_invoice_id, factus_number, factus_status, factus_pdf_url FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($order) {
      $stmtI = $conexion->prepare('SELECT title, price, qty, image FROM order_items WHERE order_id = ?');
      $stmtI->execute([$orderId]);
      $items = $stmtI->fetchAll(PDO::FETCH_ASSOC);
    }
  } catch (Throwable $e) { /* si no existe, caerá abajo */ }
}

if (!$order) { http_response_code(404); echo 'Orden no encontrada'; exit; }

$address  = !empty($order['address_json']) ? (json_decode($order['address_json'], true) ?: []) : [];
$schedule = !empty($order['schedule_json']) ? (json_decode($order['schedule_json'], true) ?: []) : [];

// Datos empresa (desde factus_config si existen, con fallbacks)
$companyName    = defined('FACTUS_COMPANY_NAME') ? FACTUS_COMPANY_NAME : 'DistriCarnes Hermanos Navarro';
$companyEmail   = defined('FACTUS_COMPANY_EMAIL') ? FACTUS_COMPANY_EMAIL : 'districarneshermanosnavarro@gmail.com';
$companyPhone   = defined('FACTUS_COMPANY_PHONE') ? FACTUS_COMPANY_PHONE : '+57 301 5210177';
$companyAddress = defined('FACTUS_COMPANY_ADDRESS') ? FACTUS_COMPANY_ADDRESS : 'OLAYA HERRERA, Cartagena de Indias';
$companyNit     = defined('FACTUS_COMPANY_NIT') ? ('NIT ' . FACTUS_COMPANY_NIT) : 'NIT 900000000-0';

// Moneda y cálculos
$currency = 'COP';
$subtotal = 0.0;
foreach ($items as $it) { $subtotal += (float)($it['price'] ?? 0) * (int)($it['qty'] ?? 1); }
$taxRate = 0.0; // Ajustar si manejas IVA
$tax = $subtotal * $taxRate;
$total = (float)($order['total'] ?? 0);
if ($total <= 0) { $total = $subtotal + $tax; }

// Número/Código de factura (único por orden)
$createdAt = !empty($order['created_at']) ? strtotime($order['created_at']) : time();
$invoiceCode = 'FAC-' . date('Ymd', $createdAt) . '-' . strtoupper(base_convert($orderId, 10, 36));

// URL absoluta de la factura para QR
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/backend/php/order_invoice.php'), '/');
$invoiceUrl = $scheme . '://' . $host . $base . '/order_invoice.php?order_id=' . urlencode((string)$orderId);
$qrUrl = 'https://chart.googleapis.com/chart?chs=160x160&cht=qr&choe=UTF-8&chl=' . urlencode($invoiceUrl);

?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Factura <?php echo htmlspecialchars($invoiceCode); ?> • DistriCarnes</title>
  <style>
    body { font-family: Arial, Helvetica, sans-serif; color:#222; margin:24px; }
    .header { display:flex; justify-content:space-between; align-items:flex-start; gap:18px; }
    .brand { display:flex; align-items:center; gap:12px; }
    .brand img { height:56px; object-fit:contain; }
    .company { line-height:1.4; }
    .customer { line-height:1.4; text-align:right; }
    h1 { margin:0 0 4px 0; font-size:22px; text-transform:uppercase; letter-spacing:.3px; }
    .meta { margin-top:6px; color:#666; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:10px; border:1px solid #e5e5e5; }
    th { background:#fafafa; text-align:left; }
    .totals { margin-top:10px; }
    .totals td { padding:6px 0; }
    .actions { margin:16px 0; display:flex; gap:10px; }
    .badge { display:inline-block; padding:4px 8px; border-radius:16px; font-size:12px; }
    .badge-paid { background:#e6ffed; color:#0a7a29; border:1px solid #b8f2c2; }
    .footer { margin-top:18px; font-size:12px; color:#666; }
    @media print { .actions { display:none; } }
  </style>
</head>
<body>
  <div class="actions">
    <button onclick="window.print()" style="padding:8px 16px; background:#007bff; color:#fff; border:none; border-radius:4px; font-size:14px; cursor:pointer;">Imprimir</button>
    <button onclick="window.print()" style="padding:8px 16px; background:#28a745; color:#fff; border:none; border-radius:4px; font-size:14px; cursor:pointer;">Descargar PDF</button>
  </div>
  <div class="header">
    <div class="brand">
      <?php
        $root = dirname(__DIR__);
        $logoPath = $root . '/assets/icon/LOGO-DISTRICARNES.png';
        if (file_exists($logoPath)) {
          $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
          echo '<img src="'.$logoData.'" alt="DistriCarnes"/>';
        }
      ?>
      <div class="company">
        <h1>Factura <?php echo htmlspecialchars($invoiceCode); ?></h1>
        <div><strong><?php echo htmlspecialchars($companyName); ?></strong></div>
        <div><?php echo htmlspecialchars($companyNit); ?></div>
        <div><?php echo htmlspecialchars($companyAddress); ?></div>
        <div>Tel: <?php echo htmlspecialchars($companyPhone); ?> • Email: <?php echo htmlspecialchars($companyEmail); ?></div>
        <div class="meta">Moneda: <?php echo htmlspecialchars($currency); ?></div>
      </div>
    </div>
    <div>
      <img src="<?php echo htmlspecialchars($qrUrl); ?>" alt="QR Factura" style="width:120px;height:120px;border:1px solid #eee;padding:4px;border-radius:6px;">
      <div class="meta" style="text-align:center;margin-top:4px;">Escanéame</div>
    </div>
    <div class="customer">
      <div><strong>Cliente</strong></div>
      <div><?php echo htmlspecialchars($order['user_name'] ?: ''); ?></div>
      <div><?php echo htmlspecialchars($order['user_email'] ?: ''); ?></div>
      <div><?php echo htmlspecialchars(($address['street'] ?? '') . ', ' . ($address['city'] ?? '') . ', ' . ($address['dept'] ?? '')); ?></div>
      <div>Fecha: <?php echo htmlspecialchars(date('Y-m-d H:i', $createdAt)); ?></div>
      <div class="meta">Pago: <?php echo htmlspecialchars($order['paypal_id'] ? 'PayPal' : ($order['pay_method'] ?? '')); ?><?php if(!empty($order['paypal_id'])){ echo ' • Transacción ' . htmlspecialchars($order['paypal_id']); } ?></div>
      <div class="meta">Estado: <span class="badge badge-paid"><?php echo htmlspecialchars($order['status']); ?></span></div>
      <?php if(!empty($order['factus_number']) || !empty($order['factus_invoice_id'])): ?>
        <div class="meta">Factura electrónica Factus: <strong><?php echo htmlspecialchars($order['factus_number'] ?: $order['factus_invoice_id']); ?></strong></div>
        <?php if(!empty($order['factus_pdf_url'])): ?>
          <div class="meta"><a href="<?php echo htmlspecialchars($order['factus_pdf_url']); ?>" target="_blank">Descargar PDF Factus</a></div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <hr />
  <h3>Detalle de productos</h3>
  <table>
    <thead>
      <tr>
        <th>Producto</th>
        <th style="text-align:right;">Precio (<?php echo htmlspecialchars($currency); ?>)</th>
        <th style="text-align:center;">Cant.</th>
        <th style="text-align:right;">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($items as $it): $line = (float)($it['price'] ?? 0) * (int)($it['qty'] ?? 1); ?>
      <tr>
        <td><?php echo htmlspecialchars(($it['title'] ?? '') ?: 'Producto'); ?></td>
        <td style="text-align:right;"><?php echo money_es($it['price'] ?? 0); ?></td>
        <td style="text-align:center;"><?php echo intval($it['qty'] ?? 1); ?></td>
        <td style="text-align:right;"><?php echo money_es($line); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <table class="totals">
    <tr><td style="text-align:right;">Subtotal: <?php echo money_es($subtotal); ?></td></tr>
    <tr><td style="text-align:right;">Impuestos: <?php echo money_es($tax); ?></td></tr>
    <tr><td style="text-align:right;"><strong>Total: <?php echo money_es($total); ?></strong></td></tr>
  </table>
  <?php
    $delivery = $order['delivery_method'] ?: 'domicilio';
    $addrLine = htmlspecialchars(($address['street'] ?? '') . ', ' . ($address['city'] ?? '') . ', ' . ($address['dept'] ?? ''));
    $notesLine = htmlspecialchars($address['notes'] ?? '');
    $scheduleStr = '';
    if (is_array($schedule) && !empty($schedule)) {
      $parts = [];
      foreach ($schedule as $k=>$v) { if($v){ $parts[] = ucfirst($k) . ': ' . htmlspecialchars($v); } }
      $scheduleStr = implode(' • ', $parts);
    }
  ?>
  <?php if ($delivery === 'punto'): ?>
    <div style="margin-top:14px;">
      <h3>Retiro en punto de entrega</h3>
      <p>Agencia Mercado Libre – EFACTY PASEO DE BOLÍVAR – CRA 17 51-43 – Paseo De Bolívar</p>
      <p class="meta">Horario: Lu a Sá 8:30–12:30 • Lu a Vi 14:30–19:30 • Sá 14:30–17:00 • Do 10:00–13:00</p>
    </div>
  <?php else: ?>
    <div style="margin-top:14px;">
      <h3>Entrega a domicilio</h3>
      <p><?php echo $addrLine; ?></p>
      <?php if($notesLine): ?><p class="meta">Indicaciones: <?php echo $notesLine; ?></p><?php endif; ?>
      <?php if($scheduleStr): ?><p class="meta">Horario preferido: <?php echo $scheduleStr; ?></p><?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="footer">
    <p>Gracias por tu compra. Conserva esta factura para tus registros.</p>
    <p>Este documento corresponde a una factura de venta emitida por <?php echo htmlspecialchars($companyName); ?>. Ante cualquier inquietud contáctanos: <?php echo htmlspecialchars($companyEmail); ?>.</p>
    <p>Dirección: <?php echo htmlspecialchars($companyAddress); ?> • Tel: <?php echo htmlspecialchars($companyPhone); ?>.</p>
    <p>Código de verificación: <strong><?php echo htmlspecialchars($invoiceCode); ?></strong> • Escanea el QR para ver esta factura.</p>
  </div>

  <script>
    // Si viene con ?print=1, abrir el diálogo automáticamente
    const url = new URL(window.location.href);
    if (url.searchParams.get('print') === '1') { setTimeout(()=>window.print(), 200); }
  </script>
</body>
</html>
