<?php
session_start();
require 'backend/conexion.php';

// 1. Verificación de sesión
// Si no hay sesión, o el rol es un simple "usuario", lo expulsamos
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] === 'usuario') {
    header("Location: no_autorizado.php");
    exit();
}

$rolSesion = $_SESSION['usuario_rol'];
$idSesion = $_SESSION['usuario_id'];
$nombreCompleto = $_SESSION['usuario_nombre'];
$iniciales = strtoupper(substr($nombreCompleto, 0, 2));

try {
    // 2. Consultas Dinámicas según el Rol
    if ($rolSesion === 'admin') {
        // El Admin ve las estadísticas globales
        $stmtC = $pdo->query("SELECT COUNT(*) FROM publicaciones");
        $totalPropiedades = $stmtC->fetchColumn();

        $stmtV = $pdo->query("SELECT SUM(precio_uf) FROM publicaciones");
        $valorTotalUF = $stmtV->fetchColumn() ?: 0;
        
        $tituloDashboard = "Resumen Global de la Plataforma";
    } else {
        // Propietarios y Gestores ven SOLO lo suyo
        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM publicaciones WHERE id_dueno = ?");
        $stmtC->execute([$idSesion]);
        $totalPropiedades = $stmtC->fetchColumn();

        $stmtV = $pdo->prepare("SELECT SUM(precio_uf) FROM publicaciones WHERE id_dueno = ?");
        $stmtV->execute([$idSesion]);
        $valorTotalUF = $stmtV->fetchColumn() ?: 0;
        
        $tituloDashboard = "Mi Resumen de Gestión";
    }
} catch (PDOException $e) {
    $totalPropiedades = 0;
    $valorTotalUF = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - InmobiliariaLibre</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="img/1Logopng.PNG">
    
    <script src="js/bootstrap.bundle.min.js" defer></script>
    <style>
        .hover-admin:hover { background-color: rgba(255,255,255,0.1); color: #fff !important; text-decoration: none; }
        .card-stat { transition: transform 0.2s; border-left: 5px solid; }
        .card-stat:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-light">

    <div class="d-md-none bg-dark text-white p-3 d-flex justify-content-between align-items-center sticky-top shadow-sm">
        <a href="dashboard.php" class="text-decoration-none text-white fw-bold d-flex align-items-center">
            <img src="img/1Logopng.PNG" style="width: 30px;" class="me-2">
            Panel <?php echo $rolSesion; ?>
        </a>
        <button class="btn btn-outline-light border-0 fs-4 py-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuOffcanvas">
            ☰
        </button>
    </div>

    <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="menuOffcanvas">
        <div class="offcanvas-header bg-black bg-opacity-25 border-bottom border-secondary">
            <h5 class="offcanvas-title fw-bold d-flex align-items-center">
                <img src="img/1Logopng.PNG" style="width: 30px;" class="me-2">
                Menú Admin
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body px-0">
            <nav class="nav flex-column px-3 gap-2">
                <a class="nav-link text-white fw-bold py-3 bg-primary rounded shadow-sm" href="dashboard.php"><span class="me-2">🎛️</span> Resumen General</a>
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="admin_publicaciones.php"><span class="me-2">🏠</span> Publicaciones</a>
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
                <a class="nav-link text-white fw-bold py-3 bg-primary rounded shadow-sm" href="dashboard.php"><span class="me-2">🎛️</span> Resumen General</a>
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="admin_publicaciones.php"><span class="me-2">🏠</span> Publicaciones</a>
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
                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($nombreCompleto); ?></h6>
                    <small class="text-muted text-capitalize">Rol: <?php echo $rolSesion; ?></small>
                </div>
            </div>

            <h3 class="fw-bold mb-4"><?php echo $tituloDashboard; ?></h3>

            <?php if ($rolSesion === 'admin'): ?>
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6">
                    <a href="admin_publicaciones.php" class="btn btn-primary w-100 py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="border-radius: 12px;">
                        <span class="fs-4">🏠</span> Gestionar Publicaciones
                    </a>
                </div>
                <div class="col-12 col-sm-6">
                    <a href="admin_usuarios.php" class="btn btn-success w-100 py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="border-radius: 12px;">
                        <span class="fs-4">👥</span> Gestionar Usuarios
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6">
                    <div class="card card-stat border-0 shadow-sm p-4 h-100" style="border-radius: 12px; border-color: #0d6efd !important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-bold mb-1">PROPIEDADES ACTIVAS</div>
                                <h2 class="fw-bold mb-0 text-primary"><?php echo $totalPropiedades; ?></h2>
                            </div>
                            <div class="bg-primary bg-opacity-10 rounded p-3 text-primary fs-3">
                                🏠
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-md-6">
                    <div class="card card-stat border-0 shadow-sm p-4 h-100" style="border-radius: 12px; border-color: #198754 !important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small fw-bold mb-1">VALOR TOTAL DE CARTERA (UF)</div>
                                <h2 class="fw-bold mb-0 text-success"><?php echo number_format($valorTotalUF, 2, ',', '.'); ?></h2>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded p-3 text-success fs-3">
                                💰
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($rolSesion !== 'admin'): ?>
            <div class="card border-0 shadow-sm p-4" style="border-radius: 12px;">
                <h5 class="fw-bold mb-3 border-bottom pb-2">Gestión Rápida</h5>
                <p class="text-muted mb-4">Bienvenido al panel. Selecciona una acción para comenzar:</p>
                
                <div class="d-flex flex-wrap gap-3">
                    <a href="admin_publicaciones.php" class="btn btn-primary fw-bold py-2 px-4 shadow-sm">
                        Ver Mis Propiedades
                    </a>
                    <a href="admin_publicaciones.php" class="btn btn-outline-primary fw-bold py-2 px-4">
                        ➕ Añadir Nueva
                    </a>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>