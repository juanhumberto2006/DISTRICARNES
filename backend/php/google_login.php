<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
// Asegurar salida limpia en JSON (evitar warnings impresos)
ini_set('display_errors', '0');
ob_start();

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/conexion.php';

    if (!isset($_POST['credential']) || empty($_POST['credential'])) {
        throw new Exception('No se recibió el token de Google.');
    }
    $id_token = $_POST['credential'];

    $client = new Google_Client();
    $googleClientId = getenv('GOOGLE_CLIENT_ID') ?: '1089395533199-070ohtiul6msdderh593mlp8m7v7lv3j.apps.googleusercontent.com';
    $client->setClientId($googleClientId);

    try {
        $payload = $client->verifyIdToken($id_token);
    }
    catch (\Throwable $t) {
        throw new Exception('Fallo al verificar token con Google: ' . $t->getMessage());
    }

    if (!$payload) {
        throw new Exception('Token inválido o expirado.');
    }
    $userid = $payload['sub'] ?? null;
    $email = $payload['email'] ?? null;
    $name = $payload['name'] ?? 'Usuario Google';

    if (empty($email)) {
        throw new Exception('No se pudo obtener el correo electrónico de Google.');
    }

    $sql = "SELECT id_usuario, nombres_completos, correo_electronico, rol FROM usuario WHERE correo_electronico = ? LIMIT 1";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta de usuario.");
    }
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Usuario existente: iniciar sesión
        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['user_rol'] = $user['rol'];
        $_SESSION['logged_in'] = true;

        $redirect_url = ($user['rol'] === 'admin') ? '/admin/admin_dashboard.html' : '/index.html';

        $response = [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id_usuario'],
                'nombre' => $user['nombres_completos'],
                'email' => $user['correo_electronico'],
                'rol' => $user['rol']
            ],
            'redirect_url' => $redirect_url
        ];
    }
    else {
        // Nuevo usuario: Registrar e iniciar sesión
        $rol = 'cliente';
        // Force cedula to fit in 20 chars max. $userid is usually 21 characters numeric string
        $cedula = substr($userid ?: bin2hex(random_bytes(8)), 0, 20);
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $direccion_placeholder = 'No especificada';
        $celular_placeholder = '0000000000';

        $sql_insert = "INSERT INTO usuario (nombres_completos, correo_electronico, rol, cedula, contrasena, direccion, celular) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conexion->prepare($sql_insert);
        if (!$stmt_insert) {
            throw new Exception("Error al preparar el registro del usuario.");
        }

        if ($stmt_insert->execute([$name, $email, $rol, $cedula, $password, $direccion_placeholder, $celular_placeholder])) {
            $new_user_id = $conexion->lastInsertId();
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['user_rol'] = $rol;
            $_SESSION['logged_in'] = true;

            $redirect_url = '/index.html';

            $response = [
                'success' => true,
                'message' => 'Registration and login successful',
                'user' => [
                    'id' => $new_user_id,
                    'nombre' => $name,
                    'email' => $email,
                    'rol' => $rol
                ],
                'redirect_url' => $redirect_url
            ];
        }
        else {
            $errorInfo = $stmt_insert->errorInfo();
            throw new Exception('Error creando usuario: ' . ($errorInfo[2] ?? 'Operación fallida en la base de datos'));
        }
    }
}
catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log('Google Login Error: ' . $e->getMessage());
}

// Limpiar buffer de salida para asegurar JSON limpio
$output = ob_get_clean();
if (!empty($output)) {
    error_log("Google Login discarded output: " . $output);
}

echo json_encode($response);
exit;
