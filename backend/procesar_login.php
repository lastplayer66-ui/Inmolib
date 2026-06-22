<?php
session_start();
require 'conexion.php'; // Al estar en la misma carpeta backend, se llama directamente

// Aseguramos que la respuesta siempre sea interpretada como JSON por el navegador
header('Content-Type: application/json');

// Verificar que vengan los datos obligatorios
$identificador = trim($_POST['email'] ?? ''); // Puede ser el Email o el RUT según el placeholder
$password = $_POST['password'] ?? '';

if (empty($identificador) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Por favor, completa todos los campos."]);
    exit();
}

try {
    // Buscar al usuario por Correo Electrónico o por RUT
    $sql = "SELECT id, rut, nombre_completo, password, rol, estado FROM usuarios WHERE email = ? OR rut = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$identificador, $identificador]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // 1. Verificar si el usuario existe
    if (!$usuario) {
        echo json_encode(["status" => "error", "message" => "El usuario o correo ingresado no existe en el sistema."]);
        exit();
    }

    // 2. Verificar si la contraseña es correcta
    if (!password_verify($password, $usuario['password'])) {
        echo json_encode(["status" => "error", "message" => "Contraseña incorrecta. Inténtalo de nuevo."]);
        exit();
    }

    // 3. VERIFICACIÓN DE ESTADO DE CUENTA (Tu regla de SweetAlert para cuentas inactivas)
    if ($usuario['estado'] === 'pendiente') {
        echo json_encode([
            "status" => "error", 
            "message" => "Tu cuenta aún está pendiente de aprobación por parte del Administrador. Por favor, espera la activación."
        ]);
        exit();
    }

    if ($usuario['estado'] === 'suspendida') {
        echo json_encode([
            "status" => "error", 
            "message" => "Tu cuenta se encuentra suspendida. Ponte en contacto con el soporte técnico."
        ]);
        exit();
    }

    // 4. LOGIN CORRECTO (Cuenta activa y credenciales válidas)
    // Inicializamos las variables de sesión globales
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_rut'] = $usuario['rut'];
    $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
    $_SESSION['usuario_rol'] = $usuario['rol'];

    // Opcional: Registrar la acción en tu log si usas la función
    if (function_exists('registrarAccion')) {
        registrarAccion($pdo, "Inició sesión en la plataforma", "Autenticación");
    }

    // Enviamos el éxito absoluto a SweetAlert2
    echo json_encode(["status" => "success", "message" => "¡Acceso autorizado! Redirigiendo..."]);
    exit();

} catch (PDOException $e) {
    // Si algo falla en la base de datos de AWS
    echo json_encode(["status" => "error", "message" => "Error interno del servidor: " . $e->getMessage()]);
    exit();
}