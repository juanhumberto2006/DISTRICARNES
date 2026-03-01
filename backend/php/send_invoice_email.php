<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';     // PDO
require_once __DIR__ . '/email_config.php';
require_once __DIR__ . '/smtp_mailer.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$orderId = isset($input['order_id']) ? intval($input['order_id']) : 0;
$toEmail = isset($input['to']) ? trim($input['to']) : null;
if($orderId <= 0){ echo json_encode(['ok'=>false,'error'=>'order_id is required']); exit; }

try {
  // Cargar orden desde orders_pg primero; fallback a orders
  $stmt = $conexion->prepare('SELECT id, paypal_id, user_email, user_name, status, total, delivery_method, pay_method, address_json, schedule_json, created_at, factus_invoice_id, factus_number, factus_status, factus_pdf_url FROM orders_pg WHERE id = ?');
  $stmt->execute([$orderId]);
  $order = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$order) {
    $stmt = $conexion->prepare('SELECT id, paypal_id, user_email, user_name, status, total, delivery_method, NULL as pay_method, address_json, schedule_json, created_at, factus_invoice_id, factus_number, factus_status, factus_pdf_url FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
  }
  if(!$order){ echo json_encode(['ok'=>false,'error'=>'Order not found']); exit; }
  if(!$toEmail){ $toEmail = $order['user_email']; }
  if(!$toEmail){ echo json_encode(['ok'=>false,'error'=>'Recipient email is required']); exit; }

  // Items
  $stI = $conexion->prepare('SELECT title, price, qty FROM order_items_pg WHERE order_id = ?');
  $stI->execute([$orderId]);
  $items = $stI->fetchAll(PDO::FETCH_ASSOC);
  if (!$items || !count($items)) {
    $stI = $conexion->prepare('SELECT title, price, qty FROM order_items WHERE order_id = ?');
    $stI->execute([$orderId]);
    $items = $stI->fetchAll(PDO::FETCH_ASSOC);
  }

  $address = !empty($order['address_json']) ? (json_decode($order['address_json'], true) ?: []) : [];

  // Empresa
  $companyName = 'DistriCarnes Hermanos Navarro';
  $companyEmail = MAIL_FROM;
  $companyPhone = '+57 301 5210177';
  $companyAddress = 'OLAYA HERRERA, Cartagena de Indias';
  $currency = 'COP';

  $root = dirname(__DIR__);
  $logoPath = $root . '/assets/icon/LOGO-DISTRICARNES.png';
  $logoData = (file_exists($logoPath)) ? ('data:image/png;base64,' . base64_encode(file_get_contents($logoPath))) : '';

  $itemsHtml = '';
  $subtotal = 0.0;
  foreach($items as $it){
    $line = floatval($it['price']) * intval($it['qty']);
    $subtotal += $line;
    $itemsHtml .= '<tr>'
      . '<td style="padding:8px;border:1px solid #ddd;">' . htmlspecialchars($it['title'] ?: 'Producto') . '</td>'
      . '<td style="padding:8px;border:1px solid #ddd;text-align:right;">$' . number_format(floatval($it['price']), 0, ',', '.') . '</td>'
      . '<td style="padding:8px;border:1px solid #ddd;text-align:center;">' . intval($it['qty']) . '</td>'
      . '<td style="padding:8px;border:1px solid #ddd;text-align:right;">$' . number_format($line, 0, ',', '.') . '</td>'
      . '</tr>';
  }
  $IVA_RATE = 0.19;
  $base = $subtotal / (1 + $IVA_RATE);
  $tax = max(0, $subtotal - $base);
  $total = floatval($order['total']);
  if($total <= 0){ $total = $subtotal; }
  $shipping = max(0, $total - ($subtotal)); // IVA asumido incluido en precios

  $createdAt = !empty($order['created_at']) ? strtotime($order['created_at']) : time();
  $invoiceCode = 'FAC-' . date('Ymd', $createdAt) . '-' . strtoupper(base_convert($orderId, 10, 36));

  $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
    . '<title>Factura ' . htmlspecialchars($invoiceCode) . ' • DistriCarnes</title>'
    . '<style>body{font-family:Arial,Helvetica,sans-serif;color:#222;margin:16px} .header{display:flex;gap:12px;align-items:center} .header img{height:56px} .company{line-height:1.4} .meta{color:#666} table{width:100%;border-collapse:collapse;margin-top:8px} th,td{padding:10px;border:1px solid #e5e5e5} th{background:#fafafa;text-align:left} .totals td{padding:6px 0} .footer{margin-top:18px;font-size:12px;color:#666}</style></head><body>'
    . '<div class="header">'
      . ($logoData ? ('<img src="' . $logoData . '" alt="DistriCarnes"/>') : '')
      . '<div class="company">'
        . '<h2 style="margin:0">' . htmlspecialchars($companyName) . '</h2>'
        . '<div>NIT 900000000-0</div>'
        . '<div>' . htmlspecialchars($companyAddress) . '</div>'
        . '<div>Tel: ' . htmlspecialchars($companyPhone) . ' • Email: ' . htmlspecialchars($companyEmail) . '</div>'
        . '<div class="meta">Moneda: ' . htmlspecialchars($currency) . '</div>'
      . '</div>'
    . '</div>'
    . '<h3 style="margin-top:10px;">Factura ' . htmlspecialchars($invoiceCode) . '</h3>'
    . '<p><strong>Cliente:</strong> ' . htmlspecialchars($order['user_name'] ?: '') . ' (' . htmlspecialchars($toEmail) . ')</p>'
    . '<p class="meta">Fecha: ' . htmlspecialchars(date('Y-m-d H:i', $createdAt)) . ' • Pago: ' . htmlspecialchars($order['paypal_id'] ? 'PayPal' : ($order['pay_method'] ?? '')) . (!empty($order['paypal_id']) ? (' • Transacción ' . htmlspecialchars($order['paypal_id'])) : '') . '</p>'
    . '<table><thead><tr><th>Producto</th><th style="text-align:right;">Precio (' . htmlspecialchars($currency) . ')</th><th style="text-align:center;">Cant.</th><th style="text-align:right;">Subtotal</th></tr></thead><tbody>' . $itemsHtml . '</tbody></table>'
    . '<table class="totals">'
      . '<tr><td style="text-align:right;">Base (sin IVA): $' . number_format($base, 0, ',', '.') . '</td></tr>'
      . '<tr><td style="text-align:right;">IVA (incl.): $' . number_format($tax, 0, ',', '.') . '</td></tr>'
      . '<tr><td style="text-align:right;">Envío: ' . ($shipping > 0 ? ('$' . number_format($shipping, 0, ',', '.')) : 'Gratis') . '</td></tr>'
      . '<tr><td style="text-align:right;"><strong>Total: $' . number_format($total, 0, ',', '.') . '</strong></td></tr>'
    . '</table>'
    . '<p style="margin-top:14px;color:#555;">Método de entrega: ' . htmlspecialchars($order['delivery_method']) . '</p>'
    . '<div class="footer"><p>Gracias por tu compra. Conserva esta factura para tus registros.</p><p>Este documento corresponde a una factura de venta emitida por ' . htmlspecialchars($companyName) . '.</p></div>'
    . '</body></html>';

  $subject = 'Factura de compra ' . $invoiceCode . ' - ' . $companyName;
  $cfg = [ 'host'=>SMTP_HOST, 'port'=>SMTP_PORT, 'secure'=>SMTP_SECURE, 'user'=>SMTP_USER, 'pass'=>SMTP_PASS ];
  $send = smtp_send_mail($toEmail, $subject, $html, MAIL_FROM, MAIL_FROM_NAME, $cfg, 'text/html');
  if(!$send['ok']){ echo json_encode(['ok'=>false,'error'=>$send['error'] ?? 'send failed']); exit; }

  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
?> 
