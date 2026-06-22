<?php
session_start();
require 'backend/conexion.php';

// 1. Obtener el ID de la propiedad desde la URL
$idPropiedad = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idPropiedad === 0) {
    header("Location: index.php");
    exit();
}

try {
    // MODIFICACIÓN: Se hace un LEFT JOIN con la tabla usuarios para obtener el teléfono del dueño
    $stmt = $pdo->prepare("SELECT p.*, u.telefono as telefono_dueno 
                           FROM publicaciones p 
                           LEFT JOIN usuarios u ON p.id_dueno = u.id 
                           WHERE p.id = ?");
    $stmt->execute([$idPropiedad]);
    $propiedad = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$propiedad) {
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error al conectar con la base de datos.");
}

// 3. Lógica para obtener las imágenes y poner la "principal" de primera
$rutaCarpeta = "uploads/propiedades/" . $idPropiedad . "/";
$imagenes = [];

if (is_dir($rutaCarpeta)) {
    $todasLasImagenes = glob($rutaCarpeta . "*.{jpg,jpeg,png,webp}", GLOB_BRACE);
    $principal = '';
    $otras = [];
    
    foreach ($todasLasImagenes as $img) {
        if (strpos(basename($img), 'principal_') === 0) {
            $principal = $img;
        } else {
            $otras[] = $img;
        }
    }
    
    if ($principal !== '') {
        $imagenes[] = $principal;
    }
    $imagenes = array_merge($imagenes, $otras);
}

function mostrarCaracteristica($tiene, $cantidad = null) {
    if ($tiene == 1) {
        return $cantidad ? "Sí ($cantidad)" : "Sí";
    }
    return "No";
}

$esUsuarioNormal = (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'usuario');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($propiedad['titulo']); ?> - InmobiliariaLibre</title>
    <link rel="icon" href="img/1Logopng.PNG">
    <link rel="stylesheet" href="css/estilo.css">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/bootstrap.bundle.min.js" defer></script>
</head>
<body class="bg-white">

    <header class="login-header py-3 mb-3">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="title-placeholder d-flex align-items-center">
                <a href="index.php" class="text-decoration-none d-flex align-items-center">
                    <img src="img/1Logopng.PNG" alt="Logo" class="me-3" style="width: 55px; height: auto;">
                    <div>
                        <h1 class="text-black fw-bold m-0 logo-text">InmobiliariaLibre</h1>
                    </div>
                </a>
            </div>
            <a href="index.php" class="btn btn-outline-dark btn-sm fw-bold">← Volver al inicio</a>
        </div>
    </header>

    <main class="container mb-5">
        
        <div class="d-flex justify-content-between align-items-center mb-3 small text-muted">
            <div id="det-miga">
                <a href="index.php" class="text-decoration-none text-primary">Inicio</a> > Inmuebles > <span class="text-dark fw-bold"><?php echo ucfirst(htmlspecialchars($propiedad['tipo_propiedad'])); ?></span>
            </div>
            <div class="d-flex gap-3">
                <a href="#" class="text-decoration-none text-primary">🔗 Compartir</a>
                
                <?php if ($esUsuarioNormal): ?>
                    <a href="#" onclick="toggleFavorito(<?php echo $idPropiedad; ?>); return false;" class="text-decoration-none text-danger fw-bold">
                        ❤️ Agregar a favoritos
                    </a>
                <?php else: ?>
                    <a href="#" onclick="alert('Debes iniciar sesión como usuario para guardar favoritos.'); return false;" class="text-decoration-none text-primary">
                        🤍 Agregar a favoritos
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4 mb-5" style="height: 400px;">
            
            <div class="col-12 col-lg-8 h-100">
                <div id="carruselPropiedad" class="carousel slide h-100 shadow-sm" data-bs-ride="carousel" style="border-radius: 8px; overflow: hidden; background-color: #000;">
                    
                    <?php if (empty($imagenes)): ?>
                        <div class="carousel-inner h-100">
                            <div class="carousel-item active h-100">
                                <img src="img/casas/1.jpg" class="d-block w-100 h-100" style="object-fit: cover;" alt="Sin imagen">
                            </div>
                        </div>
                        <div class="position-absolute" style="bottom: 15px; left: 15px; z-index: 10;">
                            <button class="btn btn-dark btn-sm fw-bold px-3 shadow" style="background-color: rgba(0,0,0,0.7); border: none;">📷 0 Fotos</button>
                        </div>
                    <?php else: ?>
                        <div class="carousel-indicators mb-2">
                            <?php foreach ($imagenes as $index => $img): ?>
                                <button type="button" data-bs-target="#carruselPropiedad" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                            <?php endforeach; ?>
                        </div>

                        <div class="carousel-inner h-100">
                            <?php foreach ($imagenes as $index => $img): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?> h-100">
                                    <img src="<?php echo $img; ?>" class="d-block w-100 h-100" style="object-fit: cover;" alt="Foto propiedad">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="position-absolute" style="bottom: 15px; left: 15px; z-index: 10;">
                            <button class="btn btn-dark btn-sm fw-bold px-3 shadow" style="background-color: rgba(0,0,0,0.7); border: none;">
                                📷 <?php echo count($imagenes); ?> Fotos
                            </button>
                        </div>

                        <button class="carousel-control-prev" type="button" data-bs-target="#carruselPropiedad" data-bs-slide="prev" style="width: 50px;">
                            <span class="carousel-control-prev-icon p-3" aria-hidden="true" style="background-color: rgba(0,0,0,0.6); border-radius: 4px;"></span>
                            <span class="visually-hidden">Anterior</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carruselPropiedad" data-bs-slide="next" style="width: 50px;">
                            <span class="carousel-control-next-icon p-3" aria-hidden="true" style="background-color: rgba(0,0,0,0.6); border-radius: 4px;"></span>
                            <span class="visually-hidden">Siguiente</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-lg-4 h-100">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 8px; background-color: #f8f9fa;">
                    <div class="card-body p-4 d-flex flex-column justify-content-center">
                        <div class="d-flex mb-4 p-3 bg-white rounded shadow-sm" style="border: 1px solid #e9ecef;">
                            <div class="me-3 fs-4">📅</div>
                            <p class="mb-0 text-dark small">Envía tu disponibilidad al gestor o dueño para que te contacte y coordine la visita.</p>
                        </div>
                        <button class="btn btn-primary w-100 mb-3 py-2 fw-bold shadow-sm" style="background-color: #3b82f6; border: none;">Solicitar visita</button>
                        
                        <!-- MODIFICACIÓN: Botón de WhatsApp funcional mostrando el número del dueño -->
                        <?php 
                            $telefonoDueno = $propiedad['telefono_dueno'] ?? '';
                            $numeroLimpio = preg_replace('/[^0-9]/', '', $telefonoDueno);
                        ?>
                        <a href="https://wa.me/56<?php echo $numeroLimpio; ?>" target="_blank" class="btn w-100 py-2 fw-bold text-primary shadow-sm bg-white" style="border: 1px solid #3b82f6;">
                            💬 WhatsApp: <?php echo !empty($telefonoDueno) ? htmlspecialchars($telefonoDueno) : 'No disponible'; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-8">
                
                <p class="text-muted small mb-1">
                    <?php echo ucfirst(htmlspecialchars($propiedad['tipo_propiedad'])); ?> | 
                    Publicado el: <?php echo date("d-m-Y", strtotime($propiedad['fecha_publicacion'])); ?>
                </p>
                <h3 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($propiedad['titulo']); ?></h3>
                
                <!-- MODIFICACIÓN: Se reemplaza la dirección vacía por Provincia, Comuna y Sector -->
                <a href="#" class="text-decoration-none text-primary mb-4 d-block">
                    📍 <?php echo htmlspecialchars(ucfirst($propiedad['sector'] ?? '') . ', ' . ucfirst($propiedad['comuna'] ?? '') . ' (Provincia de ' . ucfirst($propiedad['provincia'] ?? '') . ')'); ?>
                </a>

                <h1 class="fw-bold mb-0 text-primary">UF <?php echo number_format($propiedad['precio_uf'], 2, ',', '.'); ?></h1>
                <p class="text-muted mb-2 fs-5">$ <?php echo number_format($propiedad['precio_clp'], 0, ',', '.'); ?> CLP</p>
                
                <div class="d-flex flex-wrap gap-4 text-dark mt-4 mb-5 border-bottom pb-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-5">🛏️</span> <strong><?php echo htmlspecialchars($propiedad['dormitorios']); ?> dorm.</strong>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-5">🛁</span> <strong><?php echo htmlspecialchars($propiedad['banos']); ?> baños</strong>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-5">📐</span> <strong><?php echo htmlspecialchars($propiedad['area_total']); ?> m² totales</strong>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-5">🏠</span> <strong><?php echo htmlspecialchars($propiedad['area_construida']); ?> m² construidos</strong>
                    </div>
                </div>

                <h5 class="fw-bold mb-3">Descripción</h5>
                <p class="text-muted" style="text-align: justify; line-height: 1.6; white-space: pre-wrap;">
                    <?php echo htmlspecialchars($propiedad['descripcion']); ?>
                </p>

                <h4 class="fw-bold mb-4 mt-5">Características destacadas</h4>
                <div class="row g-4 mb-5 pb-4 border-bottom">
                    <div class="col-12 col-md-6 d-flex align-items-center">
                        <div class="me-3 text-muted fs-5" style="width: 24px; text-align: center;">📦</div>
                        <span class="text-muted me-1">Bodega:</span> 
                        <span class="fw-bold text-dark"><?php echo mostrarCaracteristica($propiedad['has_bodega'], $propiedad['qty_bodega']); ?></span>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-center">
                        <div class="me-3 text-muted fs-5" style="width: 24px; text-align: center;">🚗</div>
                        <span class="text-muted me-1">Estacionamientos:</span> 
                        <span class="fw-bold text-dark"><?php echo mostrarCaracteristica($propiedad['has_estacionamiento'], $propiedad['qty_estacionamiento']); ?></span>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-center">
                        <div class="me-3 text-muted fs-5" style="width: 24px; text-align: center;">🧺</div>
                        <span class="text-muted me-1">Logia:</span> 
                        <span class="fw-bold text-dark"><?php echo mostrarCaracteristica($propiedad['has_logia'], $propiedad['qty_logia']); ?></span>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-center">
                        <div class="me-3 text-muted fs-5" style="width: 24px; text-align: center;">🍳</div>
                        <span class="text-muted me-1">Cocina amoblada:</span> 
                        <span class="fw-bold text-dark"><?php echo mostrarCaracteristica($propiedad['has_cocina']); ?></span>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-center">
                        <div class="me-3 text-muted fs-5" style="width: 24px; text-align: center;">🏡</div>
                        <span class="text-muted me-1">Antejardín:</span> 
                        <span class="fw-bold text-dark"><?php echo mostrarCaracteristica($propiedad['has_antejardin']); ?></span>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-center">
                        <div class="me-3 text-muted fs-5" style="width: 24px; text-align: center;">🌳</div>
                        <span class="text-muted me-1">Patio trasero:</span> 
                        <span class="fw-bold text-dark"><?php echo mostrarCaracteristica($propiedad['has_patio']); ?></span>
                    </div>
                    <div class="col-12 col-md-6 d-flex align-items-center">
                        <div class="me-3 text-muted fs-5" style="width: 24px; text-align: center;">🏊</div>
                        <span class="text-muted me-1">Piscina:</span> 
                        <span class="fw-bold text-dark"><?php echo mostrarCaracteristica($propiedad['has_piscina'], $propiedad['qty_piscina']); ?></span>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <footer class="bg-white pb-4" style="border-top: 1px solid #e0e0e0; margin-top: 80px;">
        <div class="container text-center mt-4 pt-2">
            <p class="text-muted mb-0" style="font-size: 0.8rem;">
                Copyright © 2020-2026 InmobiliariaLibre Chile Ltda.<br>
                Av. del Mar 1000, Oficina 45, La Serena, Coquimbo - Chile.
            </p>
        </div>
    </footer>

    <script>
        async function toggleFavorito(id) {
            try {
                const resp = await fetch('backend/gestionar_favoritos.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id_publicacion: id })
                });
                
                const res = await resp.json();
                alert(res.message);
            } catch (err) { 
                alert("Error al conectar con el servidor."); 
            }
        }
    </script>

</body>
</html>