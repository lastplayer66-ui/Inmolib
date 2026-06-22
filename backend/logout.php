<?php
require 'conexion.php';
session_start();

// 1. REGISTRAR LA ACCIÓN EN AUDITORÍA (Opcional pero recomendado)
// Verificamos si hay una sesión activa para saber quién se está desconectando
if (isset($_SESSION['usuario_id'])) {
    registrarAccion($pdo, "Cerró sesión y salió del sistema", "Login");
}

// 2. VACIAR TODAS LAS VARIABLES DE SESIÓN
$_SESSION = array();

// 3. DESTRUIR LA COOKIE DE SESIÓN SI EXISTE
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. DESTRUIR LA SESIÓN TOTALMENTE
session_destroy();

// 5. REDIRIGIR AL INICIO (INDEX.php)
header("Location: ../index.php");
exit();
?>