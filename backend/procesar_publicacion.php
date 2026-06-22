<?php
session_start();
require 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] === 'usuario') {
    echo json_encode(['status' => 'error', 'message' => 'No tienes permisos para realizar esta acción.']);
    exit();
}

try {
    $id_publicacion = !empty($_POST['id_publicacion']) ? (int)$_POST['id_publicacion'] : null;
    $id_dueno = $_POST['id_dueno'] ?? null;
    $estado = $_POST['estado'] ?? 'activa';
    $provincia = trim($_POST['provincia'] ?? '');
    $comuna = trim($_POST['comuna'] ?? '');
    $sector = trim($_POST['sector'] ?? '');
    $latitud = $_POST['latitud'] ?? null;
    $longitud = $_POST['longitud'] ?? null;
    $tipo_propiedad = $_POST['tipo_propiedad'] ?? '';
    $fecha_publicacion = $_POST['fecha_publicacion'] ?? date('Y-m-d');
    
    // ========================================================
    // BARRERA DE SEGURIDAD ANTI-INYECCIÓN Y ANTI-XSS
    // ========================================================
    $titulo_raw = trim($_POST['titulo'] ?? '');
    $descripcion_raw = trim($_POST['descripcion'] ?? '');

    // 1. Eliminar cualquier etiqueta de código oculta (Ej: <script>, <b>)
    $titulo = strip_tags($titulo_raw);
    $descripcion = strip_tags($descripcion_raw);

    // 2. Bloquear símbolos peligrosos en el Título (Comillas, punto y coma, diagonales, <>)
    if (preg_match('/[<>;\'"\\\]/', $titulo)) {
        echo json_encode(['status' => 'error', 'message' => 'Seguridad: El título contiene caracteres no permitidos (comillas, < >, ;).']);
        exit();
    }
    // 3. Bloquear símbolos de etiquetas HTML en la Descripción
    if (preg_match('/[<>]/', $descripcion)) {
        echo json_encode(['status' => 'error', 'message' => 'Seguridad: La descripción contiene símbolos de código no permitidos (< >).']);
        exit();
    }
    // ========================================================

    $precio_clp = !empty($_POST['precio_clp']) ? (int)$_POST['precio_clp'] : 0;
    $precio_uf = !empty($_POST['precio_uf']) ? (float)$_POST['precio_uf'] : 0;
    $area_total = !empty($_POST['area_total']) ? (float)$_POST['area_total'] : 0;
    $area_construida = !empty($_POST['area_construida']) ? (float)$_POST['area_construida'] : 0;

    $dormitorios = !empty($_POST['dormitorios']) ? (int)$_POST['dormitorios'] : 0;
    $banos = !empty($_POST['banos']) ? (int)$_POST['banos'] : 0;

    $has_bodega = isset($_POST['has_bodega']) ? 1 : 0;
    $qty_bodega = $has_bodega ? (int)($_POST['qty_bodega'] ?? 1) : 0;

    $has_estacionamiento = isset($_POST['has_estacionamiento']) ? 1 : 0;
    $qty_estacionamiento = $has_estacionamiento ? (int)($_POST['qty_estacionamiento'] ?? 1) : 0;

    $has_logia = isset($_POST['has_logia']) ? 1 : 0;
    $qty_logia = $has_logia ? (int)($_POST['qty_logia'] ?? 1) : 0;

    $has_piscina = isset($_POST['has_piscina']) ? 1 : 0;
    $qty_piscina = $has_piscina ? (int)($_POST['qty_piscina'] ?? 1) : 0;

    $has_cocina = isset($_POST['has_cocina']) ? 1 : 0;
    $has_antejardin = isset($_POST['has_antejardin']) ? 1 : 0;
    $has_patio = isset($_POST['has_patio']) ? 1 : 0;
    
    $has_balcon = isset($_POST['has_balcon']) ? 1 : 0;

    if (empty($id_dueno) || empty($titulo) || empty($descripcion) || empty($latitud)) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios o ubicación en el mapa.']);
        exit();
    }

    $pdo->beginTransaction();

    if ($id_publicacion) {
        $sql = "UPDATE publicaciones SET 
                id_dueno=?, estado=?, provincia=?, comuna=?, sector=?, latitud=?, longitud=?, 
                tipo_propiedad=?, fecha_publicacion=?, titulo=?, descripcion=?, 
                precio_clp=?, precio_uf=?, area_total=?, area_construida=?, 
                dormitorios=?, banos=?, has_bodega=?, qty_bodega=?, has_estacionamiento=?, qty_estacionamiento=?, 
                has_logia=?, qty_logia=?, has_cocina=?, has_antejardin=?, has_patio=?, has_piscina=?, qty_piscina=?, has_balcon=?
                WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_dueno, $estado, $provincia, $comuna, $sector, $latitud, $longitud,
            $tipo_propiedad, $fecha_publicacion, $titulo, $descripcion,
            $precio_clp, $precio_uf, $area_total, $area_construida,
            $dormitorios, $banos, $has_bodega, $qty_bodega, $has_estacionamiento, $qty_estacionamiento,
            $has_logia, $qty_logia, $has_cocina, $has_antejardin, $has_patio, $has_piscina, $qty_piscina, $has_balcon,
            $id_publicacion
        ]);

        $mensajeExito = "Propiedad actualizada exitosamente.";

    } else {
        $sql = "INSERT INTO publicaciones (
                    id_dueno, estado, provincia, comuna, sector, latitud, longitud, 
                    tipo_propiedad, fecha_publicacion, titulo, descripcion, 
                    precio_clp, precio_uf, area_total, area_construida, 
                    dormitorios, banos, has_bodega, qty_bodega, has_estacionamiento, qty_estacionamiento, 
                    has_logia, qty_logia, has_cocina, has_antejardin, has_patio, has_piscina, qty_piscina, has_balcon
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_dueno, $estado, $provincia, $comuna, $sector, $latitud, $longitud,
            $tipo_propiedad, $fecha_publicacion, $titulo, $descripcion,
            $precio_clp, $precio_uf, $area_total, $area_construida,
            $dormitorios, $banos, $has_bodega, $qty_bodega, $has_estacionamiento, $qty_estacionamiento,
            $has_logia, $qty_logia, $has_cocina, $has_antejardin, $has_patio, $has_piscina, $qty_piscina, $has_balcon
        ]);

        $id_publicacion = $pdo->lastInsertId(); 
        $mensajeExito = "Propiedad publicada exitosamente.";
    }

    // PROCESAMIENTO DE FOTOGRAFÍAS
    if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
        $directorioDestino = "../uploads/propiedades/" . $id_publicacion . "/";

        if (!file_exists($directorioDestino)) {
            mkdir($directorioDestino, 0775, true);
        }

        $totalArchivos = count($_FILES['fotos']['name']);
        
        if ($totalArchivos > 10) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Límite de seguridad: Máximo 10 fotografías.']);
            exit();
        }

        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];

        for ($i = 0; $i < $totalArchivos; $i++) {
            $nombreArchivo = $_FILES['fotos']['name'][$i];
            $tmpArchivo = $_FILES['fotos']['tmp_name'][$i];
            $errorArchivo = $_FILES['fotos']['error'][$i];

            if ($errorArchivo === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));

                if (in_array($ext, $extensionesPermitidas)) {
                    $nuevoNombre = "img_" . time() . "_" . $i . "." . $ext;
                    $rutaFinal = $directorioDestino . $nuevoNombre;
                    move_uploaded_file($tmpArchivo, $rutaFinal);
                }
            }
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => $mensajeExito]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Error de Base de Datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Error del Servidor: ' . $e->getMessage()]);
}
?>