<?php
require 'conexion.php';
session_start();

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "No autorizado"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'])) {
    try {
        // Obtenemos el nombre antes de borrarlo para el reporte
        $check = $pdo->prepare("SELECT nombre_completo, email FROM usuarios WHERE id = ?");
        $check->execute([$data['id']]);
        $user = $check->fetch();

        if ($user && $user['email'] === 'admin@mail.cl') {
            echo json_encode(["status" => "error", "message" => "La cuenta maestra no puede ser eliminada."]);
            exit();
        }

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$data['id']]);

        // REGISTRO EN AUDITORÍA
        registrarAccion($pdo, "Eliminó permanentemente al usuario: " . $user['nombre_completo'], "Usuarios");

        echo json_encode(["status" => "success", "message" => "Usuario eliminado correctamente."]);
        
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "No se pudo eliminar: " . $e->getMessage()]);
    }
}
?>