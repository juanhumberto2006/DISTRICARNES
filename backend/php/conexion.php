<?php
// ===============================
// Configuración de la base de datos
// ===============================

$host = getenv('HOST') ?: ;
$port = getenv('DB_PORT') ?: ; // Puerto por defecto PostgreSQL
$database = getenv('DB_NAME') ?: ;
$username = getenv('DB_USER') ?: ; // Usuario por defecto
$password = getenv('DB_PASSWORD') ?: ; // <-- pon aquí tu clave real


$conexion = new PDO(
    "pgsql:host=$host;port=$port;dbname=$database;sslmode=require",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
sslmode=require
// ===============================
// Crear conexión con PDO PostgreSQL
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

try {

    $conexion = new PDO(
        "pgsql:host=$host;port=$port;dbname=$database",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Mostrar errores
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Resultados como array
            PDO::ATTR_EMULATE_PREPARES => false, // Seguridad
        ]
    );

    // Forzar UTF-8
    $conexion->exec("SET NAMES 'UTF8'");

} catch (PDOException $e) {

    // Cabecera JSON si falla
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a PostgreSQL.',
        'error_details' => $e->getMessage() // Solo para depuración
    ]);

    exit();
}
?>
