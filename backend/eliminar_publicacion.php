<?php
require 'conexion.php';
session_start();

// 1. SEGURIDAD: Solo usuarios autenticados
if (!isset($_SESSION['usuario_rol'])) {
    echo json_encode(["status" => "error", "message" => "No autorizado"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'])) {
    $id = $data['id'];
    
    try {
        // 2. OBTENER DATOS DE LA PROPIEDAD ANTES DE BORRAR (Para el reporte)
        $check = $pdo->prepare("SELECT titulo FROM publicaciones WHERE id = ?");
        $check->execute([$id]);
        $publicacion = $check->fetch(PDO::FETCH_ASSOC);

        if (!$publicacion) {
            echo json_encode(["status" => "error", "message" => "La publicación no existe."]);
            exit();
        }

        $tituloPropiedad = $publicacion['titulo'];

        // 3. BORRADO FÍSICO DE ARCHIVOS (Fotos)
        $dir = "uploads/propiedades/$id/";
        if (is_dir($dir)) {
            $files = glob($dir . '*', GLOB_MARK);
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file); // Borra cada foto
                }
            }
            rmdir($dir); // Borra la carpeta de la propiedad
        }

        // 4. BORRADO DE LA BASE DE DATOS
        $stmt = $pdo->prepare("DELETE FROM publicaciones WHERE id = ?");
        $stmt->execute([$id]);

        // 5. REGISTRO EN AUDITORÍA
        // Usamos la función global definida en conexion.php
        registrarAccion($pdo, "Eliminó la propiedad: " . $tituloPropiedad . " (ID: #$id)", "Publicaciones");

        echo json_encode(["status" => "success", "message" => "Publicación y archivos eliminados correctamente."]);

    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Error al eliminar de la BD: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "ID de publicación no proporcionado."]);
}
?>