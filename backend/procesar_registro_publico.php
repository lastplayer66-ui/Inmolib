<?php
// backend/procesar_registro_publico.php
require 'conexion.php';

// FUNCIONES DE VALIDACIÓN BACKEND
function esRutValido($rut) {
    if (!preg_match("/^[0-9]+-[0-9kK]{1}$/", $rut)) return false;
    $rut = preg_replace('/[^kK0-9]/i', '', $rut);
    $dv  = substr($rut, -1);
    $numero = substr($rut, 0, strlen($rut)-1);
    $i = 2; $suma = 0;
    foreach(array_reverse(str_split($numero)) as $v) {
        if($i==8) $i = 2;
        $suma += $v * $i;
        ++$i;
    }
    $dvr = 11 - ($suma % 11);
    if($dvr == 11) $dvr = 0;
    if($dvr == 10) $dvr = 'K';
    return (strtoupper($dvr) == strtoupper($dv));
}

// CAPTURA DE DATOS
$rut = trim($_POST['rut'] ?? '');
$nombre = trim($_POST['nombre'] ?? '');
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
$sexo = $_POST['sexo'] ?? '';
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$password = $_POST['password'] ?? '';
$rol = $_POST['rol'] ?? 'usuario'; // Puede ser 'usuario', 'gestor', o 'propietario'

// 1. REGLA ESTRICTA: El estado SIEMPRE nace en pendiente
$estado = 'pendiente';

// ==========================================
// VERIFICACIONES DE INTEGRIDAD
// ==========================================

if (!esRutValido($rut)) {
    echo json_encode(["status" => "error", "message" => "El RUT ingresado no es válido."]); exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Formato de correo inválido."]); exit();
}
if (!preg_match("/^9\d{8}$/", $telefono)) {
    echo json_encode(["status" => "error", "message" => "El teléfono debe tener 9 dígitos y comenzar con 9."]); exit();
}
$year_nacimiento = (int)date('Y', strtotime($fecha_nacimiento));
if ($year_nacimiento < 1945 || $year_nacimiento > 2025) {
    echo json_encode(["status" => "error", "message" => "Fecha de nacimiento fuera de rango (1945-2025)."]); exit();
}
if (strlen($password) < 6) {
    echo json_encode(["status" => "error", "message" => "La contraseña debe tener al menos 6 caracteres."]); exit();
}
// Evitar inyección de roles de administrador
if (!in_array($rol, ['usuario', 'propietario', 'gestor'])) {
    $rol = 'usuario';
}

try {
    // Verificar que no exista el correo o RUT
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR rut = ?");
    $check->execute([$email, $rut]);
    if ($check->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "El correo o RUT ya están registrados."]); exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Crear la cuenta
    $sql = "INSERT INTO usuarios (rut, nombre_completo, fecha_nacimiento, sexo, email, telefono, rol, estado, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$rut, $nombre, $fecha_nacimiento, $sexo, $email, $telefono, $rol, $estado, $hashed_password]);
    
    // ========================================================
    // NUEVO: INYECCIÓN DIRECTA AL LOG DE AUDITORÍA
    // Como no hay sesión activa, el autor se marca como "Sistema Público"
    // ========================================================
    $mensajeLog = "Nuevo registro en espera de aprobación: $nombre (Rol: $rol)";
    $stmtAud = $pdo->prepare("INSERT INTO auditoria (usuario_nombre, modulo, accion, fecha) VALUES (?, ?, ?, NOW())");
    $stmtAud->execute(['Sistema (Público)', 'Usuarios', $mensajeLog]);

    echo json_encode(["status" => "success", "message" => "Registro exitoso. Tu cuenta está en revisión. Un administrador debe aprobarla para que puedas ingresar."]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error al registrar en BD."]);
}
?>