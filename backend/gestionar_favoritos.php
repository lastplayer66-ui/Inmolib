<?php
require 'conexion.php';
session_start();

// Solo usuarios normales pueden tener favoritos
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'usuario') {
    echo json_encode(["status" => "error", "message" => "Acción no permitida"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$id_usuario = $_SESSION['usuario_id'];
$id_pub = $data['id_publicacion'] ?? null;

if (!$id_pub) {
    echo json_encode(["status" => "error", "message" => "ID de propiedad no válido"]);
    exit();
}

try {
    // Verificar si ya es favorito
    $check = $pdo->prepare("SELECT id FROM favoritos WHERE id_usuario = ? AND id_publicacion = ?");
    $check->execute([$id_usuario, $id_pub]);

    if ($check->rowCount() > 0) {
        // Si existe, lo quitamos
        $del = $pdo->prepare("DELETE FROM favoritos WHERE id_usuario = ? AND id_publicacion = ?");
        $del->execute([$id_usuario, $id_pub]);
        echo json_encode(["status" => "success", "action" => "removed", "message" => "Quitado de favoritos"]);
    } else {
        // Si no existe, lo agregamos
        $ins = $pdo->prepare("INSERT INTO favoritos (id_usuario, id_publicacion) VALUES (?, ?)");
        $ins->execute([$id_usuario, $id_pub]);
        echo json_encode(["status" => "success", "action" => "added", "message" => "Agregado a favoritos"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error de BD: " . $e->getMessage()]);
}