<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 2) {
    echo json_encode(['ok' => true, 'clients' => []]);
    exit;
}

try {
    // Buscar en usuario (nombre, email, cedula/telefono si existen)
    // Postgres ILIKE es case insensitive, pero usamos LOWER para compatibilidad general si no usamos ILIKE
    $term = '%' . $q . '%';
    
    // Detectar columnas disponibles
    $cols = [];
    $stmtCols = $conexion->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'usuario'");
    $stmtCols->execute();
    while ($r = $stmtCols->fetch(PDO::FETCH_ASSOC)) { $cols[] = $r['column_name']; }
    $stmtCols->closeCursor();

    $where = [];
    $params = [];
    
    if (in_array('nombres_completos', $cols)) {
        $where[] = "LOWER(nombres_completos) LIKE LOWER(?)";
        $params[] = $term;
    }
    if (in_array('correo_electronico', $cols)) {
        $where[] = "LOWER(correo_electronico) LIKE LOWER(?)";
        $params[] = $term;
    }
    if (in_array('cedula', $cols)) {
        // Cast a texto por si es numérico
        $where[] = "CAST(cedula AS TEXT) LIKE ?";
        $params[] = $term;
    }
    
    if (empty($where)) {
        echo json_encode(['ok' => true, 'clients' => []]);
        exit;
    }
    
    $sql = "SELECT id_usuario, nombres_completos, correo_electronico FROM usuario WHERE " . implode(' OR ', $where) . " LIMIT 10";
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    
    $clients = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $clients[] = [
            'id' => $row['id_usuario'],
            'name' => $row['nombres_completos'],
            'email' => $row['correo_electronico']
        ];
    }
    $stmt->closeCursor();
    
    echo json_encode(['ok' => true, 'clients' => $clients]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>