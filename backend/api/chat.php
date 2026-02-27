<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
@ini_set('display_errors', '0');

require_once __DIR__ . '/../php/conexion.php';

$apiKey = getenv('OPENAI_API_KEY') ?: '';
if ($apiKey === '') {
  $keyFile = __DIR__ . '/../php/openai.key';
  if (is_readable($keyFile)) {
    $apiKey = trim((string)file_get_contents($keyFile));
  }
}
if ($apiKey === '') {
  echo json_encode(['error' => 'OpenAI API key no configurada. Define OPENAI_API_KEY o crea backend/php/openai.key con tu clave.']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userMessage = trim((string)($data['message'] ?? ''));
if ($userMessage === '') {
  echo json_encode(['error' => 'Mensaje vacío']);
  exit;
}

// Extraer palabras clave (con tolerancia si mbstring o PCRE unicode no están disponibles)
$text = function_exists('mb_strtolower') ? mb_strtolower($userMessage, 'UTF-8') : strtolower($userMessage);
$pattern = '/[^\p{L}\p{N}]+/u';
if (@preg_match($pattern, 'a') === false) {
  $pattern = '/[^A-Za-z0-9áéíóúüñÁÉÍÓÚÜÑ]+/'; // fallback sin propiedades unicode
}
$tokens = preg_split($pattern, $text, -1, PREG_SPLIT_NO_EMPTY);
$stop = ['de','la','el','los','las','y','o','a','al','del','con','en','por','para','un','una','unos','unas','que','cual','cuál','cuales','cuáles','precio','precios','cuesta','vale','valen','costo','costos','oferta','ofertas','descuento','descuentos','hay','tienen','disponible','disponibles','stock','pesos'];
$words = array_values(array_filter($tokens, function($w) use ($stop){
  $len = function_exists('mb_strlen') ? mb_strlen($w, 'UTF-8') : strlen($w);
  return $len >= 3 && !in_array($w, $stop, true);
}));
$words = array_slice($words, 0, 4);

// Buscar coincidencias en producto
$facts = '';
if (!empty($words)) {
  $conds = [];
  $params = [];
  foreach ($words as $w) {
    $conds[] = 'LOWER(nombre) LIKE ?';
    $params[] = '%' . $w . '%';
  }
  $sql = 'SELECT id_producto, nombre, precio_venta, stock FROM producto WHERE ' . implode(' AND ', $conds) . ' ORDER BY precio_venta ASC LIMIT 6';
  try {
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
      $lines = [];
      foreach ($rows as $r) {
        $nombre = trim((string)($r['nombre'] ?? ''));
        $precio = isset($r['precio_venta']) ? number_format((float)$r['precio_venta'], 0, ',', '.') : 'N/D';
        $stock = isset($r['stock']) ? (int)$r['stock'] : null;
        $stockTxt = ($stock !== null) ? ($stock > 0 ? ('En stock: ' . $stock) : 'Sin stock') : 'Stock N/D';
        $lines[] = '- ' . $nombre . ': $ ' . $precio . '. ' . $stockTxt . '.';
      }
      $facts = "Productos coincidentes en base de datos:\n" . implode("\n", $lines);
    }
  } catch (Throwable $e) {
    error_log('Chat DB search error: ' . $e->getMessage());
  }
}

$system = "Eres el asistente virtual de Districarnes Hermanos Navarro. Responde de forma breve, precisa y amable. ";
$system .= "Si el usuario pregunta por precios o disponibilidad y te doy un 'contexto de productos', usa EXCLUSIVAMENTE esos datos para responder (no inventes precios). ";
$system .= "Si no hay datos en el contexto que coincidan, dilo y sugiere visitar el catálogo.";

$messages = [
  ['role' => 'system', 'content' => $system],
];
if ($facts !== '') {
  $messages[] = ['role' => 'system', 'content' => "Contexto de productos (fuente BD):\n" . $facts];
}
$messages[] = ['role' => 'user', 'content' => $userMessage];

$payload = [
  'model' => 'gpt-4o-mini',
  'temperature' => 0.3,
  'messages' => $messages,
  'max_tokens' => 400
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
  'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$timeout = 25;
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
// Permitir desarrollo local si el CA bundle no está disponible
$skipVerify = getenv('OPENAI_SKIP_SSL_VERIFY') === '1';
if (!$skipVerify) {
  $host = $_SERVER['HTTP_HOST'] ?? '';
  if (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false) {
    $skipVerify = true;
  }
}
if ($skipVerify) {
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
}
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode >= 400) {
  $msg = 'No se pudo obtener respuesta del asistente.';
  if ($response && $httpCode >= 400) {
    $errJson = json_decode($response, true);
    if (isset($errJson['error']['message'])) {
      $msg .= ' ' . $errJson['error']['message'];
    } else {
      $msg .= " (HTTP $httpCode)";
    }
  } elseif ($err) {
    $msg .= ' (' . $err . ')';
  }
  error_log('OpenAI error: ' . ($err ?: $response ?: 'unknown'));
  // Fallback local cuando cuota/billing impide usar OpenAI
  $lower = strtolower($msg);
  $quotaish = (strpos($lower, 'quota') !== false) || (strpos($lower, 'billing') !== false) || $httpCode == 429;
  if ($quotaish) {
    $fallback = "En este momento no puedo conectar con el asistente en la nube (límite de uso alcanzado). ";
    if ($facts !== '') {
      $fallback .= "Te dejo información directa de nuestro catálogo:\n" . $facts . "\n\n";
      $fallback .= "Para más detalles, visita el catálogo de productos.";
    } else {
      $fallback .= "Intenta consultar por un producto específico (ej. 'precio de posta') o abre el catálogo.";
    }
    echo json_encode(['reply' => nl2br($fallback), 'facts' => ($facts !== '' ? $facts : null), 'fallback' => true]);
    exit;
  }
  echo json_encode(['error' => $msg]);
  exit;
}

$json = json_decode($response, true);
$reply = $json['choices'][0]['message']['content'] ?? null;
if (!$reply) { $reply = 'Lo siento, no pude generar una respuesta en este momento.'; }

echo json_encode([
  'reply' => $reply,
  'facts' => ($facts !== '' ? $facts : null)
]);
