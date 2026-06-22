<?php
session_start();
require 'backend/conexion.php';

// 1. Verificar sesión
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] === 'usuario') {
    header("Location: no_autorizado.php");
    exit();
}

$rolSesion = $_SESSION['usuario_rol'];
$idSesion = $_SESSION['usuario_id'];
$nombreCompleto = $_SESSION['usuario_nombre'];
$iniciales = strtoupper(substr($nombreCompleto, 0, 2));

// 2. Lógica de Filtro por Rol
try {
    if ($rolSesion === 'admin') {
        $sqlProps = "SELECT p.*, u.nombre_completo as nombre_dueno 
                     FROM publicaciones p 
                     LEFT JOIN usuarios u ON p.id_dueno = u.id 
                     ORDER BY p.fecha_publicacion DESC";
        $stmt = $pdo->prepare($sqlProps);
        $stmt->execute();
        
        $stmtU = $pdo->prepare("SELECT id, nombre_completo, rol FROM usuarios WHERE rol IN ('propietario', 'gestor') ORDER BY nombre_completo ASC");
        $stmtU->execute();
        $listaDuenos = $stmtU->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sqlProps = "SELECT p.*, u.nombre_completo as nombre_dueno 
                     FROM publicaciones p 
                     LEFT JOIN usuarios u ON p.id_dueno = u.id 
                     WHERE p.id_dueno = ? 
                     ORDER BY p.fecha_publicacion DESC";
        $stmt = $pdo->prepare($sqlProps);
        $stmt->execute([$idSesion]);
        
        $stmtU = $pdo->prepare("SELECT id, nombre_completo, rol FROM usuarios WHERE id = ?");
        $stmtU->execute([$idSesion]);
        $listaDuenos = $stmtU->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // LECTURA PREVIA DE IMÁGENES PARA LA TABLA Y EDICIÓN
    foreach ($publicaciones as &$p) {
        $idProp = $p['id'];
        $rutaCarpeta = "uploads/propiedades/" . $idProp . "/";
        $fotos = [];
        $fotoPrincipal = 'img/placeholder.jpg';
        if (is_dir($rutaCarpeta)) {
            $imagenes = glob($rutaCarpeta . "*.{jpg,jpeg,png,webp}", GLOB_BRACE);
            foreach ($imagenes as $img) {
                $nombreArchivo = basename($img);
                $fotos[] = $nombreArchivo;
                if (strpos($nombreArchivo, 'principal_') === 0) {
                    $fotoPrincipal = $img;
                }
            }
            if ($fotoPrincipal === 'img/placeholder.jpg' && count($imagenes) > 0) {
                $fotoPrincipal = $imagenes[0];
            }
        }
        $p['fotos_array'] = $fotos;
        $p['foto_mostrar'] = $fotoPrincipal;
    }
    unset($p);

} catch (PDOException $e) {
    $publicaciones = [];
    $listaDuenos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Publicaciones - InmobiliariaLibre</title>
    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link rel="icon" href="img/1Logopng.PNG">
    <script src="js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .hover-admin:hover { background-color: rgba(255,255,255,0.1); color: #fff !important; text-decoration: none; }
        .fila-editando { background-color: rgba(13, 110, 253, 0.1) !important; border-left: 5px solid #0d6efd; }
    </style>
</head>
<body class="bg-light">

    <div class="d-md-none bg-dark text-white p-3 d-flex justify-content-between align-items-center sticky-top shadow-sm">
        <a href="dashboard.php" class="text-decoration-none text-white fw-bold d-flex align-items-center">
            <img src="img/1Logopng.PNG" style="width: 30px;" class="me-2"> Admin Panel
        </a>
        <button class="btn btn-outline-light border-0 fs-4 py-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuOffcanvas">☰</button>
    </div>

    <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="menuOffcanvas">
        <div class="offcanvas-header bg-black bg-opacity-25 border-bottom border-secondary">
            <h5 class="offcanvas-title fw-bold d-flex align-items-center">
                <img src="img/1Logopng.PNG" style="width: 30px;" class="me-2"> Menú Admin
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body px-0">
            <nav class="nav flex-column px-3 gap-2">
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="dashboard.php"><span class="me-2">🎛️</span> Resumen General</a>
                <a class="nav-link text-white fw-bold py-3 bg-primary rounded" href="admin_publicaciones.php"><span class="me-2">🏠</span> Publicaciones</a>
                <?php if($rolSesion === 'admin'): ?>
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="admin_usuarios.php"><span class="me-2">👥</span> Usuarios</a>
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="admin_reportes.php"><span class="me-2">📈</span> Reportes</a>
                <?php endif; ?>
                <hr class="border-secondary">
                <a class="nav-link text-danger fw-bold py-3 rounded hover-admin" href="backend/logout.php">🚪 Cerrar Sesión</a>
            </nav>
        </div>
    </div>

    <div class="d-flex">
        
        <div class="bg-dark text-white vh-100 shadow-sm d-none d-md-block" style="width: 260px; position: sticky; top: 0;">
            <div class="p-4 mb-3 bg-black bg-opacity-25">
                <a href="dashboard.php" class="text-decoration-none d-flex align-items-center text-white">
                    <img src="img/1Logopng.PNG" style="width: 40px;" class="me-2">
                    <span class="fw-bold">Panel <?php echo $rolSesion; ?></span>
                </a>
            </div>
            <nav class="nav flex-column px-3 gap-2">
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="dashboard.php"><span class="me-2">🎛️</span> Resumen General</a>
                <a class="nav-link text-white fw-bold py-3 bg-primary rounded" href="admin_publicaciones.php"><span class="me-2">🏠</span> Publicaciones</a>
                <?php if($rolSesion === 'admin'): ?>
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="admin_usuarios.php"><span class="me-2">👥</span> Usuarios</a>
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="admin_reportes.php"><span class="me-2">📈</span> Reportes</a>
                <?php endif; ?>
                <hr class="border-secondary">
                <a class="nav-link text-danger fw-bold py-3 rounded hover-admin" href="backend/logout.php">🚪 Cerrar Sesión</a>
            </nav>
        </div>

        <div class="flex-grow-1 p-3 p-md-4 w-100" style="overflow-x: hidden;">
            
            <div class="d-flex align-items-center mb-4 bg-white p-3 rounded shadow-sm border-start border-primary border-4">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm" style="width: 50px; height: 50px; font-size: 1.2rem;">
                    <?php echo $iniciales; ?>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($nombreCompleto ?? ''); ?></h6>
                    <small class="text-muted text-capitalize">Rol: <?php echo $rolSesion; ?></small>
                </div>
            </div>

            <h3 class="fw-bold mb-4">Gestión de Publicaciones</h3>
            
            <div class="row g-4">
                
                <div class="col-12 col-xl-7 order-1 order-xl-2">
                    <div class="card border-0 shadow-sm p-3 p-md-4" style="border-radius: 12px;">
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Listado de Propiedades</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" style="min-width: 600px;">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Propiedad</th>
                                        <th>Precio</th>
                                        <th>Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($publicaciones)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No tienes propiedades registradas.</td></tr>
                                    <?php else: foreach($publicaciones as $p): ?>
                                        <tr id="fila-prop-<?php echo $p['id']; ?>">
                                            <td class="fw-bold text-muted small">#<?php echo $p['id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($p['foto_mostrar']); ?>?v=<?php echo time(); ?>" alt="Prop" class="me-3 shadow-sm" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                                    <div>
                                                        <div class="fw-bold prop-titulo"><?php echo htmlspecialchars($p['titulo'] ?? ''); ?></div>
                                                        <div class="small text-muted prop-tipo-text">
                                                            <?php echo ucfirst($p['tipo_propiedad'] ?? ''); ?> • 
                                                            <?php echo htmlspecialchars($p['comuna'] ?? ''); ?> 
                                                            <?php echo !empty($p['sector']) ? ', ' . htmlspecialchars($p['sector']) : ''; ?>
                                                        </div>
                                                        <div class="small text-primary fw-bold mt-1">Dueño: <?php echo !empty($p['nombre_dueno']) ? htmlspecialchars($p['nombre_dueno']) : 'Sin asignar'; ?></div>
                                                    </div>
                                                </div>
                                                
                                                <input type="hidden" class="p-dueno" value="<?php echo htmlspecialchars($p['id_dueno'] ?? ''); ?>">
                                                <input type="hidden" class="p-prov" value="<?php echo htmlspecialchars($p['provincia'] ?? ''); ?>">
                                                <input type="hidden" class="p-comu" value="<?php echo htmlspecialchars($p['comuna'] ?? ''); ?>">
                                                <input type="hidden" class="p-sect" value="<?php echo htmlspecialchars($p['sector'] ?? ''); ?>">
                                                
                                                <input type="hidden" class="p-lat" value="<?php echo htmlspecialchars($p['latitud'] ?? ''); ?>">
                                                <input type="hidden" class="p-lng" value="<?php echo htmlspecialchars($p['longitud'] ?? ''); ?>">

                                                <input type="hidden" class="p-tipo" value="<?php echo htmlspecialchars($p['tipo_propiedad'] ?? ''); ?>">
                                                <input type="hidden" class="p-fecha" value="<?php echo htmlspecialchars($p['fecha_publicacion'] ?? ''); ?>">
                                                <input type="hidden" class="p-desc" value="<?php echo htmlspecialchars($p['descripcion'] ?? ''); ?>">
                                                <input type="hidden" class="p-clp" value="<?php echo htmlspecialchars($p['precio_clp'] ?? 0); ?>">
                                                <input type="hidden" class="p-uf" value="<?php echo htmlspecialchars($p['precio_uf'] ?? 0); ?>">
                                                <input type="hidden" class="p-at" value="<?php echo htmlspecialchars($p['area_total'] ?? 0); ?>">
                                                <input type="hidden" class="p-ac" value="<?php echo htmlspecialchars($p['area_construida'] ?? 0); ?>">
                                                <input type="hidden" class="p-dorm" value="<?php echo htmlspecialchars($p['dormitorios'] ?? 0); ?>">
                                                <input type="hidden" class="p-ban" value="<?php echo htmlspecialchars($p['banos'] ?? 0); ?>">
                                                
                                                <input type="hidden" class="p-has-bod" value="<?php echo htmlspecialchars($p['has_bodega'] ?? 0); ?>">
                                                <input type="hidden" class="p-qty-bod" value="<?php echo htmlspecialchars($p['qty_bodega'] ?? 0); ?>">
                                                <input type="hidden" class="p-has-est" value="<?php echo htmlspecialchars($p['has_estacionamiento'] ?? 0); ?>">
                                                <input type="hidden" class="p-qty-est" value="<?php echo htmlspecialchars($p['qty_estacionamiento'] ?? 0); ?>">
                                                <input type="hidden" class="p-has-log" value="<?php echo htmlspecialchars($p['has_logia'] ?? 0); ?>">
                                                <input type="hidden" class="p-qty-log" value="<?php echo htmlspecialchars($p['qty_logia'] ?? 0); ?>">
                                                <input type="hidden" class="p-has-coc" value="<?php echo htmlspecialchars($p['has_cocina'] ?? 0); ?>">
                                                <input type="hidden" class="p-has-ant" value="<?php echo htmlspecialchars($p['has_antejardin'] ?? 0); ?>">
                                                <input type="hidden" class="p-has-pat" value="<?php echo htmlspecialchars($p['has_patio'] ?? 0); ?>">
                                                <input type="hidden" class="p-has-pis" value="<?php echo htmlspecialchars($p['has_piscina'] ?? 0); ?>">
                                                <input type="hidden" class="p-qty-pis" value="<?php echo htmlspecialchars($p['qty_piscina'] ?? 0); ?>">
                                                <input type="hidden" class="p-has-balcon" value="<?php echo htmlspecialchars($p['has_balcon'] ?? 0); ?>">
                                                
                                                <input type="hidden" class="p-fotos-json" value='<?php echo json_encode($p['fotos_array']); ?>'>
                                                <input type="hidden" class="p-estado" value="<?php echo htmlspecialchars($p['estado'] ?? 'activa'); ?>">
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary">UF <?php echo number_format((float)($p['precio_uf'] ?? 0), 2, ',', '.'); ?></div>
                                                <div class="small text-muted fw-normal">$ <?php echo number_format((float)($p['precio_clp'] ?? 0), 0, ',', '.'); ?> CLP</div>
                                            </td>
                                            <td>
                                                <?php 
                                                    $est = strtolower($p['estado'] ?? 'activa');
                                                    $bgEst = 'bg-success';
                                                    if($est === 'inactiva' || $est === 'desactivada') $bgEst = 'bg-secondary';
                                                    if($est === 'suspendida') $bgEst = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $bgEst; ?>"><?php echo ucfirst($est); ?></span>
                                            </td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-danger btn-borrar-prop" data-id="<?php echo $p['id']; ?>" title="Eliminar">🗑️</button>
                                                <button class="btn btn-sm btn-outline-primary ms-1 btn-editar-prop" data-id="<?php echo $p['id']; ?>" title="Editar">✏️</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-5 order-2 order-xl-1">
                    <div class="card border-0 shadow-sm p-3 p-md-4" style="border-radius: 12px; position: sticky; top: 20px; border-top: 5px solid #0d6efd;">
                        <h5 id="form-titulo" class="fw-bold mb-3 border-bottom pb-2">Datos de la Propiedad</h5>
                        
                        <form id="formCrudPublicacion" class="needs-validation" novalidate enctype="multipart/form-data">
                            
                            <input type="hidden" name="id_publicacion" id="id_publicacion">
                            <input type="hidden" name="foto_principal" id="in-foto-principal">

                            <div class="row g-2 mb-3 p-2 bg-light border-start border-primary border-4 rounded">
                                <div class="col-12 col-md-6">
                                    <label class="small text-primary fw-bold">Propietario / Gestor <span class="text-danger">*</span></label>
                                    <select name="id_dueno" id="in-dueno" class="form-select form-select-sm" required>
                                        <option value="">Seleccione al dueño...</option>
                                        <?php foreach($listaDuenos as $dueno): ?>
                                            <option value="<?php echo $dueno['id']; ?>">
                                                <?php echo htmlspecialchars($dueno['nombre_completo'] ?? ''); ?> (<?php echo ucfirst($dueno['rol']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="small text-primary fw-bold">Estado Publicación <span class="text-danger">*</span></label>
                                    <select name="estado" id="in-estado" class="form-select form-select-sm" required>
                                        <option value="activa" selected>Activa (Visible)</option>
                                        <option value="inactiva">Inactiva (Oculta)</option>
                                        <option value="suspendida">Suspendida</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-12 col-md-4">
                                    <label class="small text-muted fw-bold">Provincia <span class="text-danger">*</span></label>
                                    <select name="provincia" id="in-prov" class="form-select form-select-sm" required>
                                        <option value="">Seleccione...</option>
                                        <option value="elqui">Elqui</option>
                                        <option value="limari">Limarí</option>
                                        <option value="choapa">Choapa</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="small text-muted fw-bold">Comuna <span class="text-danger">*</span></label>
                                    <select name="comuna" id="in-comu" class="form-select form-select-sm" required>
                                        <option value="">Provincia primero...</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="small text-muted fw-bold">Sector <span class="text-danger">*</span></label>
                                    <input type="text" name="sector" id="in-sect" class="form-control form-control-sm" placeholder="Ej: Peñuelas" list="lista-sectores" autocomplete="off" required>
                                    <datalist id="lista-sectores"></datalist>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Ubicación Exacta en Mapa <span class="text-danger">*</span></label>
                                <div id="mapa-leaflet" style="height: 250px; width: 100%; border-radius: 8px; border: 1px solid #ced4da; z-index: 1;"></div>
                                <input type="hidden" name="latitud" id="in-lat" required>
                                <input type="hidden" name="longitud" id="in-lng" required>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="small text-muted fw-bold">Tipo de Propiedad <span class="text-danger">*</span></label>
                                    <select name="tipo_propiedad" id="in-tipo" class="form-select form-select-sm" required>
                                        <option value="">Seleccione...</option>
                                        <option value="casa">Casa</option>
                                        <option value="departamento">Departamento</option>
                                        <option value="parcela">Parcela</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted fw-bold">Fecha Publicación <span class="text-danger">*</span></label>
                                    <input type="date" name="fecha_publicacion" id="in-fecha-pub" class="form-control form-control-sm" min="1945-01-01" max="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="small text-muted fw-bold">Título <span class="text-danger">*</span></label>
                                <input type="text" name="titulo" id="in-titulo" class="form-control form-control-sm" placeholder="Ej: Hermosa casa..." minlength="10" required>
                                <div id="warn-titulo" class="text-danger fw-bold d-none mt-1" style="font-size: 0.70rem;">⚠️ Caracteres no permitidos detectados ( < > ; ' " \ )</div>
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Descripción <span class="text-danger">*</span></label>
                                <textarea name="descripcion" id="in-desc" class="form-control form-control-sm" rows="3" minlength="30" required></textarea>
                                <div id="warn-desc" class="text-danger fw-bold d-none mt-1" style="font-size: 0.70rem;">⚠️ Has introducido símbolos de código no permitidos ( < > )</div>
                            </div>

                            <div class="p-2 mb-3 bg-light border rounded">
                                <label class="small text-primary fw-bold mb-1">Valores (Conversión Automática) <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="text" id="precioCLP_display" class="form-control" placeholder="CLP" required>
                                            <input type="hidden" id="precioCLP" name="precio_clp">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">UF</span>
                                            <input type="number" id="precioUF" name="precio_uf" class="form-control" placeholder="UF" step="0.01" min="1" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="small text-muted fw-bold">Área Total (m²) <span class="text-danger">*</span></label>
                                    <input type="number" name="area_total" id="in-area-t" class="form-control form-control-sm" min="1" required>
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted fw-bold">Área Const. (m²) <span class="text-danger">*</span></label>
                                    <input type="number" name="area_construida" id="in-area-c" class="form-control form-control-sm" min="0" required>
                                </div>
                            </div>

                            <div class="row g-2 mb-3" id="row-dorm-banos">
                                <div class="col-6">
                                    <label class="small text-muted fw-bold">Dormitorios <span class="text-danger">*</span></label>
                                    <input type="number" name="dormitorios" id="in-dorm" class="form-control form-control-sm" min="0" required>
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted fw-bold">Baños <span class="text-danger">*</span></label>
                                    <input type="number" name="banos" id="in-banos" class="form-control form-control-sm" min="0" required>
                                </div>
                            </div>

                            <label class="small text-muted fw-bold mb-2">Características adicionales</label>
                            <div class="row row-cols-1 row-cols-sm-2 g-2 mb-3">
                                <div class="col" id="col-bodega"><div class="d-flex justify-content-between align-items-center border rounded px-2 bg-white shadow-sm" style="height: 48px;"><div class="form-check mb-0"><input type="checkbox" name="has_bodega" class="form-check-input chk-feature" id="chkBod" data-target="qtyBod"><label class="form-check-label small mt-1" for="chkBod">Bodega</label></div><input type="number" name="qty_bodega" class="form-control form-control-sm invisible text-center" id="qtyBod" placeholder="Cant." min="1" max="10" style="width: 60px; height: 28px;"></div></div>
                                <div class="col" id="col-estacionamiento"><div class="d-flex justify-content-between align-items-center border rounded px-2 bg-white shadow-sm" style="height: 48px;"><div class="form-check mb-0"><input type="checkbox" name="has_estacionamiento" class="form-check-input chk-feature" id="chkEst" data-target="qtyEst"><label class="form-check-label small mt-1" for="chkEst">Estacionamiento</label></div><input type="number" name="qty_estacionamiento" class="form-control form-control-sm invisible text-center" id="qtyEst" placeholder="Cant." min="1" max="30" style="width: 60px; height: 28px;"></div></div>
                                <div class="col" id="col-logia"><div class="d-flex justify-content-between align-items-center border rounded px-2 bg-white shadow-sm" style="height: 48px;"><div class="form-check mb-0"><input type="checkbox" name="has_logia" class="form-check-input chk-feature" id="chkLog" data-target="qtyLog"><label class="form-check-label small mt-1" for="chkLog">Logia</label></div><input type="number" name="qty_logia" class="form-control form-control-sm invisible text-center" id="qtyLog" placeholder="Cant." min="1" max="5" style="width: 60px; height: 28px;"></div></div>
                                <div class="col" id="col-cocina"><div class="d-flex justify-content-between align-items-center border rounded px-2 bg-white shadow-sm" style="height: 48px;"><div class="form-check mb-0"><input type="checkbox" name="has_cocina" class="form-check-input" id="chkCoc"><label class="form-check-label small mt-1" for="chkCoc">Cocina Amoblada</label></div></div></div>
                                <div class="col" id="col-antejardin"><div class="d-flex justify-content-between align-items-center border rounded px-2 bg-white shadow-sm" style="height: 48px;"><div class="form-check mb-0"><input type="checkbox" name="has_antejardin" class="form-check-input" id="chkAnt"><label class="form-check-label small mt-1" for="chkAnt">Antejardín</label></div></div></div>
                                <div class="col" id="col-patio"><div class="d-flex justify-content-between align-items-center border rounded px-2 bg-white shadow-sm" style="height: 48px;"><div class="form-check mb-0"><input type="checkbox" name="has_patio" class="form-check-input" id="chkPat"><label class="form-check-label small mt-1" for="chkPat">Patio Trasero</label></div></div></div>
                                <div class="col" id="col-piscina"><div class="d-flex justify-content-between align-items-center border rounded px-2 bg-white shadow-sm" style="height: 48px;"><div class="form-check mb-0"><input type="checkbox" name="has_piscina" class="form-check-input chk-feature" id="chkPis" data-target="qtyPis"><label class="form-check-label small mt-1" for="chkPis">Piscina</label></div><input type="number" name="qty_piscina" class="form-control form-control-sm invisible text-center" id="qtyPis" placeholder="Cant." min="1" max="10" style="width: 60px; height: 28px;"></div></div>
                                <div class="col" id="col-balcon"><div class="d-flex justify-content-between align-items-center border rounded px-2 bg-white shadow-sm" style="height: 48px;"><div class="form-check mb-0"><input type="checkbox" name="has_balcon" class="form-check-input" id="chkBal"><label class="form-check-label small mt-1" for="chkBal">Balcón</label></div></div></div>
                            </div>

                            <div class="mb-4">
                                <label class="small text-muted fw-bold">Fotografías (Mínimo 1, Máx. 10) <span class="text-danger">*</span></label>
                                <input type="file" name="fotos[]" id="in-fotos" class="form-control form-control-sm" accept=".jpg, .jpeg, .png, .webp" multiple required>
                                <div class="invalid-feedback" style="font-size: 0.75rem;">Debes seleccionar al menos una imagen válida.</div>
                                
                                <div id="admin-fotos-container" class="mt-3 d-none p-3 bg-white border border-secondary border-opacity-25 shadow-sm rounded">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="small fw-bold text-primary mb-0">Administrar Fotos Subidas</h6>
                                        <span class="badge bg-secondary" id="conteo-fotos">0 / 10</span>
                                    </div>
                                    <div id="galeria-fotos" class="d-flex flex-wrap gap-3">
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-sm-row gap-2">
                                <button type="submit" id="btnSubmitProp" class="btn btn-primary btn-sm flex-grow-1 fw-bold py-2">Guardar Publicación</button>
                                <button type="button" id="btnResetProp" class="btn btn-outline-secondary btn-sm py-2">Limpiar / Nuevo</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBorrarProp" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">Eliminar Publicación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p>¿Estás seguro de eliminar esta publicación?</p>
                </div>
                <div class="modal-footer justify-content-center bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="confirmarBorrarProp" class="btn btn-danger fw-bold">Eliminar Definitivamente</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNotificacion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white" id="notif-header">
                    <h5 class="modal-title fw-bold" id="notif-title">Notificación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p id="notif-message" class="mb-0 fs-5"></p>
                </div>
                <div class="modal-footer justify-content-center bg-light">
                    <button type="button" class="btn fw-bold" id="notif-btn" data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let mapa, marcador;
        const defaultLoc = [-29.90453, -71.24894]; 

        document.addEventListener('DOMContentLoaded', () => {
            mapa = L.map('mapa-leaflet').setView(defaultLoc, 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(mapa);

            marcador = L.marker(defaultLoc, {draggable: true}).addTo(mapa);

            const actualizarInputs = (latlng) => {
                document.getElementById('in-lat').value = latlng.lat.toFixed(8);
                document.getElementById('in-lng').value = latlng.lng.toFixed(8);
            };

            marcador.on('dragend', function (e) { actualizarInputs(marcador.getLatLng()); });
            mapa.on('click', function (e) { marcador.setLatLng(e.latlng); actualizarInputs(e.latlng); });
            setTimeout(() => { mapa.invalidateSize(); }, 500);

            const form = document.getElementById('formCrudPublicacion');
            const btnSubmit = document.getElementById('btnSubmitProp');
            const btnReset = document.getElementById('btnResetProp');
            const formTitulo = document.getElementById('form-titulo');
            
            const datosRegion = {
                elqui: {
                    "La Serena": ["Centro", "Avenida del Mar", "Puerta del Mar", "San Joaquín", "Cerro Grande","Altovalsol", "Las Compañías", "La Florida", "El Milagro", "Colina El Pino", "Caleta San Pedro", "El Romeral"],
                    "Coquimbo": ["Centro", "Peñuelas", "Sindempart", "La Herradura", "Tierras Blancas", "Punta Mira", "El Llano", "San Juan", "Guanaqueros", "Tongoy", "Totoralillo", "Pan de Azúcar"],
                    "Andacollo": ["Centro", "Casco Histórico", "Maitén"],
                    "La Higuera": ["Centro", "Caleta Hornos", "Chungungo", "Punta de Choros"],
                    "Paihuano": ["Centro", "Pisco Elqui", "Montegrande", "Horcón"],
                    "Vicuña": ["Centro", "Villaseca", "Peralillo", "Diaguitas", "Rivadavia", "El Tambo"]
                },
                limari: {
                    "Ovalle": ["Centro", "Parte Alta", "Población Limarí", "El Portal", "Tuquí", "Sotaquí", "Cerrillos de Tamaya", "Huamalata"],
                    "Combarbalá": ["Centro", "Quilitapia", "Cogotí"],
                    "Monte Patria": ["Centro", "Chañaral Alto", "El Palqui", "Huana", "Rapel"],
                    "Punitaqui": ["Centro", "Pueblo Viejo", "Mina Quiles"],
                    "Río Hurtado": ["Samo Alto", "Pichasca", "Serón", "Hurtado"]
                },
                choapa: {
                    "Illapel": ["Centro", "Parte Alta", "Villa San Rafael", "Asiento Viejo"],
                    "Canela": ["Canela Baja", "Canela Alta", "Huentelauquén", "Mincha"],
                    "Los Vilos": ["Centro", "Pichidangui", "Caimanes", "Quilimarí"],
                    "Salamanca": ["Centro", "Chillepín", "Batuco", "Tranquilla", "Cuncumén"]
                }
            };

            const inProv = document.getElementById('in-prov');
            const inComu = document.getElementById('in-comu');
            const inSect = document.getElementById('in-sect');
            const dataSectores = document.getElementById('lista-sectores');

            function actualizarComunas(prov, comuPrevia = '') {
                inComu.innerHTML = '<option value="">Seleccione...</option>';
                dataSectores.innerHTML = '';
                if(datosRegion[prov]) {
                    Object.keys(datosRegion[prov]).forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c; opt.textContent = c;
                        if(c === comuPrevia) opt.selected = true;
                        inComu.appendChild(opt);
                    });
                }
            }

            function actualizarSectores(prov, comu) {
                dataSectores.innerHTML = '';
                if(datosRegion[prov] && datosRegion[prov][comu]) {
                    datosRegion[prov][comu].forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s;
                        dataSectores.appendChild(opt);
                    });
                }
            }

            inProv.addEventListener('change', (e) => { actualizarComunas(e.target.value); inSect.value = ''; });
            inComu.addEventListener('change', (e) => { actualizarSectores(inProv.value, e.target.value); inSect.value = ''; });

            const inputCLPDisplay = document.getElementById('precioCLP_display');
            const inputCLPHidden = document.getElementById('precioCLP');
            const inputUF = document.getElementById('precioUF');
            const VALOR_UF = 37500;

            if(inputCLPDisplay && inputUF && inputCLPHidden) {
                inputCLPDisplay.addEventListener('input', (e) => {
                    let rawValue = e.target.value.replace(/\D/g, ''); 
                    if (rawValue !== '') {
                        inputCLPHidden.value = rawValue;
                        e.target.value = new Intl.NumberFormat('es-CL').format(rawValue);
                        inputUF.value = (parseInt(rawValue) / VALOR_UF).toFixed(2);
                    } else {
                        inputCLPHidden.value = ''; e.target.value = ''; inputUF.value = '';
                    }
                });

                inputUF.addEventListener('input', (e) => {
                    const uf = parseFloat(e.target.value);
                    if (!isNaN(uf)) {
                        let clpCalculado = Math.round(uf * VALOR_UF);
                        inputCLPHidden.value = clpCalculado;
                        inputCLPDisplay.value = new Intl.NumberFormat('es-CL').format(clpCalculado);
                    } else {
                        inputCLPHidden.value = ''; inputCLPDisplay.value = '';
                    }
                });
            }

            const checkboxesFeature = document.querySelectorAll('.chk-feature');
            checkboxesFeature.forEach(chk => {
                chk.addEventListener('change', (e) => {
                    const targetId = e.target.getAttribute('data-target');
                    if (targetId) {
                        const inputQty = document.getElementById(targetId);
                        if (inputQty) {
                            if (e.target.checked) {
                                inputQty.classList.remove('invisible');
                                inputQty.setAttribute('required', 'required');
                                if(!inputQty.value) inputQty.value = "1";
                                inputQty.focus();
                            } else {
                                inputQty.classList.add('invisible');
                                inputQty.removeAttribute('required');
                                inputQty.value = "";
                            }
                        }
                    }
                });
            });

            // ==========================================
            // LÓGICA DINÁMICA ESTRICTA: TIPO Y ÁREA
            // ==========================================
            const inTipo = document.getElementById('in-tipo');
            const inAreaC = document.getElementById('in-area-c');
            
            const rowDormBanos = document.getElementById('row-dorm-banos');
            const inDorm = document.getElementById('in-dorm');
            const inBanos = document.getElementById('in-banos');

            const colBodega = document.getElementById('col-bodega');
            const colEstacionamiento = document.getElementById('col-estacionamiento');
            const colLogia = document.getElementById('col-logia');
            const colCocina = document.getElementById('col-cocina');
            const colAntejardin = document.getElementById('col-antejardin');
            const colPatio = document.getElementById('col-patio');
            const colPiscina = document.getElementById('col-piscina');
            const colBalcon = document.getElementById('col-balcon');

            function desmarcarOcultos(colElement) {
                const chk = colElement.querySelector('input[type="checkbox"]');
                if(chk && chk.checked) {
                    chk.checked = false;
                    const event = new Event('change');
                    chk.dispatchEvent(event); 
                }
            }

            function manejarDinamismo() {
                const tipo = inTipo.value;
                const areaConst = parseFloat(inAreaC.value) || 0;

                rowDormBanos.classList.remove('d-none');
                inDorm.setAttribute('required', 'required');
                inBanos.setAttribute('required', 'required');

                colBodega.classList.remove('d-none');
                colEstacionamiento.classList.remove('d-none');
                colLogia.classList.remove('d-none');
                colCocina.classList.remove('d-none');
                colAntejardin.classList.remove('d-none');
                colPatio.classList.remove('d-none');
                colPiscina.classList.remove('d-none');
                
                colBalcon.classList.add('d-none');
                desmarcarOcultos(colBalcon);

                if (tipo === 'parcela') {
                    colAntejardin.classList.add('d-none'); desmarcarOcultos(colAntejardin);
                    colPatio.classList.add('d-none'); desmarcarOcultos(colPatio);

                    if (areaConst === 0) {
                        rowDormBanos.classList.add('d-none');
                        inDorm.removeAttribute('required');
                        inBanos.removeAttribute('required');
                        inDorm.value = ''; inBanos.value = '';

                        colBodega.classList.add('d-none'); desmarcarOcultos(colBodega);
                        colLogia.classList.add('d-none'); desmarcarOcultos(colLogia);
                        colCocina.classList.add('d-none'); desmarcarOcultos(colCocina);
                    }
                }

                if (tipo === 'departamento') {
                    colAntejardin.classList.add('d-none'); desmarcarOcultos(colAntejardin);
                    colPatio.classList.add('d-none'); desmarcarOcultos(colPatio);
                    colBalcon.classList.remove('d-none');
                }
            }

            inTipo.addEventListener('change', manejarDinamismo);
            inAreaC.addEventListener('input', manejarDinamismo);

            // ==========================================
            // LÓGICA DE FOTOGRAFÍAS Y TARJETA FANTASMA
            // ==========================================
            const inFotos = document.getElementById('in-fotos');
            
            inFotos.addEventListener('change', function() {
                const fotosExistentes = document.querySelectorAll('#galeria-fotos .card-foto:not(.ghost-card)').length;
                if (this.files.length + fotosExistentes > 10) {
                    Swal.fire('Límite excedido', `Ya tienes ${fotosExistentes} fotos subidas. Solo puedes subir ${10 - fotosExistentes} más.`, 'warning');
                    this.value = ''; 
                    return;
                }
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                for(let i=0; i<this.files.length; i++) {
                    if(!validTypes.includes(this.files[i].type)) {
                        Swal.fire('Archivo no válido', `El archivo ${this.files[i].name} no es una imagen permitida (JPG, PNG, WEBP).`, 'error');
                        this.value = '';
                        break;
                    }
                }
            });

            function renderGaleriaFotos(idProp, fotosArray) {
                const adminContainer = document.getElementById('admin-fotos-container');
                const galeria = document.getElementById('galeria-fotos');
                const conteo = document.getElementById('conteo-fotos');
                
                let html = '';
                
                if (fotosArray && fotosArray.length > 0) {
                    adminContainer.classList.remove('d-none');
                    conteo.innerText = `${fotosArray.length} / 10`;
                    
                    fotosArray.forEach(foto => {
                        const esPrincipal = foto.includes('principal_');
                        const badge = `<span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2 shadow-sm badge-principal ${!esPrincipal ? 'd-none' : ''}" style="font-size:0.75rem;">Principal</span>`;
                        const btnEstrella = `<button type="button" class="btn btn-sm btn-warning py-0 px-2 btn-principal ${esPrincipal ? 'd-none' : ''}" data-id="${idProp}" data-foto="${foto}" title="Seleccionar como Principal">⭐</button>`;
                        
                        const card = `
                            <div class="position-relative shadow-sm card-foto" data-foto="${foto}" style="width: 140px; border-radius: 8px;">
                                <img src="uploads/propiedades/${idProp}/${foto}?v=${Date.now()}" class="img-thumbnail p-0 border-0" style="height: 140px; width: 140px; object-fit: cover; border-radius: 8px;">
                                ${badge}
                                <div class="d-flex justify-content-between position-absolute w-100 bottom-0 start-0 p-1 bg-dark bg-opacity-75" style="border-radius: 0 0 8px 8px;">
                                    ${btnEstrella}
                                    <button type="button" class="btn btn-sm btn-danger py-0 px-2 ms-auto btn-eliminar-foto" data-id="${idProp}" data-foto="${foto}" title="Eliminar Fotografía">🗑️</button>
                                </div>
                            </div>
                        `;
                        html += card;
                    });

                    if (fotosArray.length < 10) {
                        html += `
                            <div class="position-relative shadow-sm card-foto ghost-card d-flex align-items-center justify-content-center" 
                                 style="width: 140px; height: 140px; border-radius: 8px; border: 2px dashed #ced4da; cursor: pointer; background-color: #f8f9fa; transition: all 0.2s;" 
                                 onclick="document.getElementById('in-fotos').click();" 
                                 onmouseover="this.style.backgroundColor='#e9ecef'; this.style.borderColor='#6c757d';" 
                                 onmouseout="this.style.backgroundColor='#f8f9fa'; this.style.borderColor='#ced4da';">
                                <div class="text-center">
                                    <span class="fs-2 text-secondary d-block mb-1">➕</span>
                                    <span class="small text-secondary fw-bold">Añadir más</span>
                                </div>
                            </div>
                        `;
                        inFotos.classList.add('d-none'); 
                    } else {
                        inFotos.classList.add('d-none'); 
                    }

                    galeria.innerHTML = html;

                    document.querySelectorAll('.btn-eliminar-foto').forEach(btn => {
                        btn.addEventListener('click', async function(e) {
                            e.preventDefault();
                            const id = this.getAttribute('data-id');
                            const foto = this.getAttribute('data-foto');
                            
                            const result = await Swal.fire({
                                title: '¿Eliminar foto?', text: "Esta acción no se puede deshacer.", icon: 'warning', 
                                showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: 'Sí, eliminar'
                            });
                            
                            if (result.isConfirmed) {
                                try {
                                    const resp = await fetch('backend/eliminar_foto.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id_publicacion: id, foto: foto}) });
                                    const res = await resp.json();
                                    if(res.status === 'success') location.reload();
                                    else Swal.fire('Error', res.message, 'error');
                                } catch(err) { Swal.fire('Error', 'Fallo de red al conectar con AWS', 'error'); }
                            }
                        });
                    });

                    document.querySelectorAll('.btn-principal').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            const fotoSeleccionada = this.getAttribute('data-foto');
                            document.getElementById('in-foto-principal').value = fotoSeleccionada;

                            document.querySelectorAll('.card-foto:not(.ghost-card)').forEach(card => {
                                const badge = card.querySelector('.badge-principal');
                                const btnE = card.querySelector('.btn-principal');
                                if (card.getAttribute('data-foto') === fotoSeleccionada) {
                                    badge.classList.remove('d-none');
                                    btnE.classList.add('d-none');
                                } else {
                                    badge.classList.add('d-none');
                                    btnE.classList.remove('d-none');
                                }
                            });
                        });
                    });
                } else {
                    adminContainer.classList.add('d-none');
                    inFotos.classList.remove('d-none'); 
                }
            }

            document.querySelectorAll('.btn-editar-prop').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    document.querySelectorAll('tr').forEach(tr => tr.classList.remove('fila-editando'));
                    const fila = document.getElementById('fila-prop-' + id);
                    fila.classList.add('fila-editando');

                    document.getElementById('id_publicacion').value = id;
                    document.getElementById('in-foto-principal').value = ''; 
                    document.getElementById('in-dueno').value = fila.querySelector('.p-dueno').value;
                    document.getElementById('in-estado').value = fila.querySelector('.p-estado').value;
                    
                    const provGuardada = fila.querySelector('.p-prov').value;
                    const comuGuardada = fila.querySelector('.p-comu').value;
                    document.getElementById('in-prov').value = provGuardada;
                    actualizarComunas(provGuardada, comuGuardada);
                    actualizarSectores(provGuardada, comuGuardada);
                    document.getElementById('in-sect').value = fila.querySelector('.p-sect').value;

                    const latGuardada = parseFloat(fila.querySelector('.p-lat').value);
                    const lngGuardada = parseFloat(fila.querySelector('.p-lng').value);
                    
                    if (!isNaN(latGuardada) && !isNaN(lngGuardada)) {
                        const pos = [latGuardada, lngGuardada];
                        marcador.setLatLng(pos);
                        mapa.setView(pos, 15);
                        document.getElementById('in-lat').value = latGuardada;
                        document.getElementById('in-lng').value = lngGuardada;
                    } else {
                        marcador.setLatLng(defaultLoc);
                        mapa.setView(defaultLoc, 13);
                        document.getElementById('in-lat').value = '';
                        document.getElementById('in-lng').value = '';
                    }
                    mapa.invalidateSize(); 
                    
                    document.getElementById('in-area-t').value = fila.querySelector('.p-at').value;
                    document.getElementById('in-area-c').value = fila.querySelector('.p-ac').value;
                    
                    const tipoGuardado = fila.querySelector('.p-tipo').value;
                    document.getElementById('in-tipo').value = tipoGuardado;
                    
                    manejarDinamismo(); 
                    
                    document.getElementById('in-fecha-pub').value = fila.querySelector('.p-fecha').value;
                    
                    const inTitulo = document.getElementById('in-titulo');
                    inTitulo.value = fila.querySelector('.prop-titulo').innerText;
                    inTitulo.dispatchEvent(new Event('input')); // Dispara la validación JS
                    
                    const inDesc = document.getElementById('in-desc');
                    inDesc.value = fila.querySelector('.p-desc').value;
                    inDesc.dispatchEvent(new Event('input')); // Dispara la validación JS
                    
                    const clpValue = fila.querySelector('.p-clp').value;
                    inputCLPHidden.value = clpValue;
                    inputCLPDisplay.value = new Intl.NumberFormat('es-CL').format(clpValue);
                    inputUF.value = fila.querySelector('.p-uf').value;

                    document.getElementById('in-dorm').value = fila.querySelector('.p-dorm').value;
                    document.getElementById('in-banos').value = fila.querySelector('.p-ban').value;

                    const setCheck = (selector, chkId, qtyId = null) => {
                        const val = fila.querySelector(selector).value;
                        const chk = document.getElementById(chkId);
                        chk.checked = (val == 1);
                        if(qtyId) {
                            const qty = document.getElementById(qtyId);
                            if(chk.checked) {
                                qty.value = fila.querySelector('.p-qty-' + qtyId.substring(3).toLowerCase()).value;
                                qty.classList.remove('invisible');
                                qty.setAttribute('required', 'required');
                            } else {
                                qty.value = "";
                                qty.classList.add('invisible');
                                qty.removeAttribute('required');
                            }
                        }
                    };

                    setCheck('.p-has-bod', 'chkBod', 'qtyBod');
                    setCheck('.p-has-est', 'chkEst', 'qtyEst');
                    setCheck('.p-has-log', 'chkLog', 'qtyLog');
                    setCheck('.p-has-pis', 'chkPis', 'qtyPis');
                    
                    document.getElementById('chkCoc').checked = (fila.querySelector('.p-has-coc').value == 1);
                    document.getElementById('chkAnt').checked = (fila.querySelector('.p-has-ant').value == 1);
                    document.getElementById('chkPat').checked = (fila.querySelector('.p-has-pat').value == 1);
                    document.getElementById('chkBal').checked = (fila.querySelector('.p-has-balcon').value == 1);

                    const fotosJsonStr = fila.querySelector('.p-fotos-json').value;
                    const fotosJson = fotosJsonStr ? JSON.parse(fotosJsonStr) : [];
                    renderGaleriaFotos(id, fotosJson);

                    if(fotosJson.length > 0) {
                        inFotos.removeAttribute('required');
                    } else {
                        inFotos.setAttribute('required', 'required');
                        inFotos.classList.remove('d-none');
                    }

                    formTitulo.innerText = "Actualizar Propiedad #" + id;
                    btnSubmit.innerText = "Actualizar Propiedad";
                    btnSubmit.classList.replace('btn-primary', 'btn-warning'); 
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });

            btnReset.addEventListener('click', () => {
                form.reset();
                form.classList.remove('was-validated'); 
                document.getElementById('id_publicacion').value = "";
                document.getElementById('in-foto-principal').value = "";
                
                // Ocultar mensajes de seguridad al limpiar
                document.getElementById('warn-titulo').classList.add('d-none');
                document.getElementById('in-titulo').setCustomValidity('');
                document.getElementById('warn-desc').classList.add('d-none');
                document.getElementById('in-desc').setCustomValidity('');
                
                inComu.innerHTML = '<option value="">Provincia primero...</option>';
                dataSectores.innerHTML = '';
                
                manejarDinamismo();

                marcador.setLatLng(defaultLoc);
                mapa.setView(defaultLoc, 13);
                document.getElementById('in-lat').value = '';
                document.getElementById('in-lng').value = '';

                document.getElementById('admin-fotos-container').classList.add('d-none');
                inFotos.setAttribute('required', 'required');
                inFotos.classList.remove('d-none');

                formTitulo.innerText = "Datos de la Propiedad";
                btnSubmit.innerText = "Guardar Publicación";
                btnSubmit.classList.replace('btn-warning', 'btn-primary');
                document.querySelectorAll('tr').forEach(tr => tr.classList.remove('fila-editando'));
                document.querySelectorAll('[id^="qty"]').forEach(i => {
                    i.classList.add('invisible');
                    i.removeAttribute('required');
                });
            });

            // ==========================================
            // LÓGICA DE ADVERTENCIAS DE SEGURIDAD EN TIEMPO REAL
            // ==========================================
            const inTituloForm = document.getElementById('in-titulo');
            const warnTituloForm = document.getElementById('warn-titulo');
            inTituloForm.addEventListener('input', function() {
                if (/[<>;'\"\\]/.test(this.value)) {
                    warnTituloForm.classList.remove('d-none');
                    this.setCustomValidity('Inválido'); // Bloquea el submit nativo
                } else {
                    warnTituloForm.classList.add('d-none');
                    this.setCustomValidity(''); // Libera el bloqueo
                }
            });

            const inDescForm = document.getElementById('in-desc');
            const warnDescForm = document.getElementById('warn-desc');
            inDescForm.addEventListener('input', function() {
                if (/[<>]/.test(this.value)) {
                    warnDescForm.classList.remove('d-none');
                    this.setCustomValidity('Inválido');
                } else {
                    warnDescForm.classList.add('d-none');
                    this.setCustomValidity('');
                }
            });

            const modalNotifElement = document.getElementById('modalNotificacion');
            const modalNotif = new bootstrap.Modal(modalNotifElement);
            const notifHeader = document.getElementById('notif-header');
            const notifTitle = document.getElementById('notif-title');
            const notifMessage = document.getElementById('notif-message');
            const notifBtn = document.getElementById('notif-btn');
            let reloadOnClose = false;

            modalNotifElement.addEventListener('hidden.bs.modal', function () {
                if (reloadOnClose) location.reload();
            });

            function showNotification(type, message) {
                if (type === 'success') {
                    notifHeader.className = 'modal-header bg-success text-white';
                    notifTitle.innerText = '¡Actualización Exitosa!';
                    notifBtn.className = 'btn btn-success fw-bold';
                    reloadOnClose = true;
                } else {
                    notifHeader.className = 'modal-header bg-danger text-white';
                    notifTitle.innerText = 'Error al Guardar';
                    notifBtn.className = 'btn btn-danger fw-bold';
                    reloadOnClose = false;
                }
                notifMessage.innerText = message;
                modalNotif.show();
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                if (!form.checkValidity()) {
                    e.stopPropagation();
                    form.classList.add('was-validated');
                    
                    // SWEETALERT PARA CAMPOS INCOMPLETOS O INVÁLIDOS
                    Swal.fire({
                        icon: 'warning',
                        title: 'Formulario Incompleto o Inválido',
                        text: 'Por favor, revisa todos los campos marcados en rojo antes de guardar. Asegúrate de no utilizar símbolos prohibidos.',
                        confirmButtonColor: '#0d6efd',
                        confirmButtonText: 'Revisar'
                    });
                    
                    return; 
                }

                const formData = new FormData(form);
                try {
                    const resp = await fetch('backend/procesar_publicacion.php', { method: 'POST', body: formData });
                    const res = await resp.json();
                    
                    if (res.status === 'success') {
                        const idPub = document.getElementById('id_publicacion').value;
                        const fotoPrincipal = document.getElementById('in-foto-principal').value;
                        
                        if (idPub && fotoPrincipal) {
                            await fetch('backend/establecer_principal.php', { 
                                method: 'POST', 
                                headers: {'Content-Type': 'application/json'}, 
                                body: JSON.stringify({id_publicacion: idPub, foto: fotoPrincipal}) 
                            });
                        }
                        showNotification('success', res.message || "La publicación y sus fotos han sido guardadas y actualizadas correctamente.");
                    } else {
                        showNotification('error', res.message || "Error al actualizar la publicación.");
                    }
                } catch (err) { 
                    showNotification('error', "Fallo de conexión con el servidor. Por favor, revisa tu conexión a internet e inténtalo de nuevo.");
                }
            });

            const modalBorrar = new bootstrap.Modal(document.getElementById('modalBorrarProp'));
            let idBorrar = null;
            document.querySelectorAll('.btn-borrar-prop').forEach(btn => {
                btn.addEventListener('click', function() {
                    idBorrar = this.getAttribute('data-id');
                    modalBorrar.show();
                });
            });
            document.getElementById('confirmarBorrarProp').addEventListener('click', async () => {
                const resp = await fetch('backend/eliminar_publicacion.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id: idBorrar})
                });
                const res = await resp.json();
                if(res.status === 'success') location.reload();
                else alert(res.message);
            });
            
            manejarDinamismo();
        });
    </script>
</body>
</html>