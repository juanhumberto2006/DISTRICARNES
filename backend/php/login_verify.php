<?php
session_start();

// Establecer cabecera JSON
header('Content-Type: application/json; charset=utf-8');

// Incluir conexión
include_once __DIR__ . '/conexion.php';

// Solo permitir POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// Validar que existan los campos
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, completa todos los campos.']);
    exit;
}

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El formato del correo electrónico no es válido.']);
    exit;
}

try {
    // Buscar por email y verificar hash de contraseña
    $sql = "SELECT id_usuario, nombres_completos, correo_electronico, contrasena, rol 
            FROM usuario 
            WHERE correo_electronico = ? LIMIT 1";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$email]);
    
    // En PDO obtenemos el resultado directamente con fetch()
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (!password_verify($password, $user['contrasena'])) {
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas. Verifica tu correo y contraseña.']);
            $stmt->closeCursor();
            exit;
        }

        // Guardar información completa en sesión
        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['user_email'] = $user['correo_electronico'];
        $_SESSION['user_name'] = $user['nombres_completos'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['logged_in'] = true;

        // Determinar URL de redirección según el rol
        $redirect_url = '';
        if ($user['rol'] === 'admin') {
            $redirect_url = '../admin/admin_dashboard.html';
        } elseif ($user['rol'] === 'trabajo') {
            $redirect_url = '../index.html';
        } elseif ($user['rol'] === 'empleado') {
            $redirect_url = '../VENTAS/';
        }

        // Respuesta exitosa con información del usuario
        echo json_encode([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'user' => [
                'id' => $user['id_usuario'],
                'nombre' => $user['nombres_completos'],
                'email' => $user['correo_electronico'],
                'rol' => $user['rol']
            ],
            'redirect_url' => $redirect_url
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas. Verifica tu correo y contraseña.']);
    }

    $stmt->closeCursor();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en el servidor. Inténtalo más tarde.']);
    error_log("Error en login_verify.php: " . $e->getMessage());
}

// $conexion->close(); No necesario en PDO, se cierra al acabar el script
?>