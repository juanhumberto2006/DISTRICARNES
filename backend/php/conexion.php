<?php
// ===============================
// Configuración de la base de datos
// ===============================

$host = getenv('HOST') ?: '';
$port = getenv('DB_PORT') ?: '5432';
$database = getenv('DB_NAME') ?: '';
$username = getenv('DB_USER') ?: '';
$password = getenv('DB_PASSWORD') ?: '';

// ===============================
// Verificar que existan las variables
// ===============================

if (empty($host) || empty($database) || empty($username) || empty($password)) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => 'Faltan variables de entorno para la conexión.',
        'error_details' => 'Verifica HOST, DB_NAME, DB_USER y DB_PASSWORD en Render'
    ]);
    exit();
}

// ===============================
// Verificar driver PostgreSQL
// ===============================

if (!in_array('pgsql', PDO::getAvailableDrivers(), true)) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'message' => 'Falta habilitar el driver de PostgreSQL para PDO.',
        'error_details' => 'pdo_pgsql no está disponible en php.ini'
    ]);
    exit();
}

// ===============================
// Crear conexión con PDO PostgreSQL
// ===============================

try {
    $conexion = new PDO(
        "pgsql:host=$host;port=$port;dbname=$database;sslmode=require",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Forzar UTF-8
    $conexion->exec("SET NAMES 'UTF8'");

} catch (PDOException $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a PostgreSQL.',
        'error_details' => $e->getMessage()
    ]);

    exit();
}
?>
