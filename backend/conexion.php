<?php
$host = 'localhost';
$dbname = 'inmobiliarialibredb';
$user = 'Admin1';
$pass = 'nsx123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["status" => "error", "message" => "Error de conexión: " . $e->getMessage()]));
}

function registrarAccion($pdo, $accion, $modulo) {
    if (session_status() === PHP_SESSION_NONE) session_start();

    // Solo intentamos insertar si tenemos los datos del usuario
    if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_nombre'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO auditoria (id_usuario, usuario_nombre, accion, modulo) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['usuario_id'],
                $_SESSION['usuario_nombre'],
                $accion,
                $modulo
            ]);
        } catch (Exception $e) {
            // Log de error interno para el desarrollador
            error_log("Fallo en auditoría: " . $e->getMessage());
        }
    }
}