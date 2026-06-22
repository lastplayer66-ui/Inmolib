<?php
session_start();

// Le decimos a JavaScript que responderemos en formato JSON
header('Content-Type: application/json');

// Verificación de seguridad
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] === 'usuario') {
    echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para realizar esta acción.']);
    exit();
}

// Capturamos el JSON
$data = json_decode(file_get_contents('php://input'), true);

$id_publicacion = $data['id_publicacion'] ?? null;
$foto = $data['foto'] ?? null;

if (!$id_publicacion || !$foto) {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos para procesar la solicitud.']);
    exit();
}

$foto = basename($foto);
$id_publicacion = preg_replace('/[^0-9]/', '', $id_publicacion);

$directorio = "../uploads/propiedades/" . $id_publicacion . "/";
$rutaObjetivo = $directorio . $foto;

// Verificar que la foto elegida realmente existe
if (!file_exists($rutaObjetivo)) {
    echo json_encode(['status' => 'error', 'message' => 'La fotografía seleccionada no se encontró en el servidor.']);
    exit();
}

try {
    // PASO 1: Buscar si ya existe otra imagen principal y quitarle el título "principal_"
    $imagenes = glob($directorio . "*.{jpg,jpeg,png,webp}", GLOB_BRACE);
    foreach ($imagenes as $img) {
        $nombreImg = basename($img);
        // Si el archivo empieza con "principal_"
        if (strpos($nombreImg, 'principal_') === 0) {
            $nuevoNombreVieja = str_replace('principal_', '', $nombreImg);
            rename($img, $directorio . $nuevoNombreVieja);
        }
    }

    // PASO 2: Renombrar la foto objetivo agregándole "principal_" al inicio
    // Limpiamos por si acaso ya lo tenía, para no generar "principal_principal_foto.jpg"
    $fotoLimpia = str_replace('principal_', '', $foto);
    $nuevoNombreObjetivo = "principal_" . $fotoLimpia;

    if (rename($rutaObjetivo, $directorio . $nuevoNombreObjetivo)) {
        echo json_encode(['status' => 'success', 'message' => 'Imagen establecida como portada.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se pudo renombrar el archivo por problemas de permisos en AWS.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error interno: ' . $e->getMessage()]);
}
?>