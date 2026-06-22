<?php
session_start();

// Le decimos a JavaScript que responderemos en formato JSON
header('Content-Type: application/json');

// Verificación de seguridad: Solo dueños o admins pueden hacer esto
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] === 'usuario') {
    echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para realizar esta acción.']);
    exit();
}

// Capturamos el JSON que envía la función fetch() desde JS
$data = json_decode(file_get_contents('php://input'), true);

$id_publicacion = $data['id_publicacion'] ?? null;
$foto = $data['foto'] ?? null;

if (!$id_publicacion || !$foto) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos para procesar la solicitud.']);
    exit();
}

// basename() es VITAL por seguridad. Evita ataques de "Directory Traversal" (ej: ../../../etc/passwd)
$foto = basename($foto);
$id_publicacion = preg_replace('/[^0-9]/', '', $id_publicacion); // Solo números

// Ruta física del archivo en el servidor (salimos de 'backend' hacia 'uploads')
$rutaArchivo = "../uploads/propiedades/" . $id_publicacion . "/" . $foto;

if (file_exists($rutaArchivo)) {
    // Intentar borrar el archivo físico
    if (unlink($rutaArchivo)) {
        echo json_encode(['status' => 'success', 'message' => 'La fotografía ha sido eliminada.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar. Verifica los permisos de la carpeta en AWS (chown/chmod).']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'La imagen solicitada ya no existe en el servidor.']);
}
?>