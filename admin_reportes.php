<?php
session_start();
require 'backend/conexion.php';

// 1. CONTROL DE ACCESO
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: no_autorizado.php");
    exit();
}

$nombreCompleto = $_SESSION['usuario_nombre'];
$rolSesion = $_SESSION['usuario_rol'];
$iniciales = strtoupper(substr($nombreCompleto, 0, 2));

// 2. CONSULTAS PARA ESTADÍSTICAS
try {
    // Conteo de Usuarios
    $cantUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $cantPendientes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado = 'pendiente'")->fetchColumn();
    
    // Conteo de Publicaciones
    $cantPublicaciones = $pdo->query("SELECT COUNT(*) FROM publicaciones")->fetchColumn();
    
    // Valor total de la cartera en UF
    $totalUF = $pdo->query("SELECT SUM(precio_uf) FROM publicaciones")->fetchColumn() ?: 0;

    // 3. CONSULTA DE AUDITORÍA (Últimos 20 movimientos para la vista web)
    $stmtLogs = $pdo->prepare("SELECT * FROM auditoria ORDER BY fecha DESC LIMIT 20");
    $stmtLogs->execute();
    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_db = "Error al generar reportes: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Auditoría - InmobiliariaLibre</title>
    <link rel="icon" href="img/1Logopng.PNG">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="js/bootstrap.bundle.min.js" defer></script>
    <style>
        .hover-admin:hover { background-color: rgba(255,255,255,0.1); color: #fff !important; text-decoration: none; }
        .sidebar { width: 260px; position: sticky; top: 0; height: 100vh; }
        .card-stat { border: none; border-radius: 12px; transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
        @media (max-width: 768px) { .sidebar { display: none; } }
    </style>
</head>
<body class="bg-light">

    <div class="d-flex">
        <div class="sidebar bg-dark text-white shadow-sm d-none d-md-block">
            <div class="p-4 mb-3 bg-black bg-opacity-25 text-center">
                <a href="dashboard.php" class="text-decoration-none d-flex align-items-center text-white">
                <img src="img/1Logopng.PNG" style="width: 40px;" class="me-2">
                <span class="fw-bold">Panel <?php echo $rolSesion; ?></span>
                </a>
            </div>
            <nav class="nav flex-column px-3 gap-2">
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="dashboard.php"><span class="me-2">🎛️</span> Resumen General</a>
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="admin_publicaciones.php"><span class="me-2">🏠</span> Publicaciones</a>
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="admin_usuarios.php"><span class="me-2">👥</span> Usuarios</a>
                <a class="nav-link text-white fw-bold py-3 bg-info rounded shadow-sm" href="admin_reportes.php"><span class="me-2">📈</span> Reportes</a>
                <hr class="border-secondary">
                <a class="nav-link text-danger fw-bold py-3 rounded hover-admin" href="backend/logout.php">🚪 Cerrar Sesión</a>
            </nav>
        </div>

        <div class="flex-grow-1 p-3 p-md-4 w-100">
            
            <div class="d-flex align-items-center mb-4 bg-white p-3 rounded shadow-sm border-start border-info border-4">
                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm" style="width: 50px; height: 50px; font-size: 1.2rem;">
                    <?php echo $iniciales; ?>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($nombreCompleto); ?></h6>
                    <small class="text-muted text-capitalize">Rol: <?php echo $rolSesion; ?></small>
                </div>
            </div>

            <h3 class="fw-bold mb-4">Reportes del Sistema</h3>

            <div class="row g-3 mb-5">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card card-stat bg-white shadow-sm p-3 border-start border-primary border-4">
                        <div class="text-muted small fw-bold">TOTAL USUARIOS</div>
                        <div class="h2 fw-bold mb-0"><?php echo $cantUsuarios; ?></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card card-stat bg-white shadow-sm p-3 border-start border-warning border-4">
                        <div class="text-muted small fw-bold">USUARIOS PENDIENTES</div>
                        <div class="h2 fw-bold mb-0 text-warning"><?php echo $cantPendientes; ?></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card card-stat bg-white shadow-sm p-3 border-start border-success border-4">
                        <div class="text-muted small fw-bold">PROPIEDADES ACTIVAS</div>
                        <div class="h2 fw-bold mb-0"><?php echo $cantPublicaciones; ?></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card card-stat bg-white shadow-sm p-3 border-start border-info border-4">
                        <div class="text-muted small fw-bold">CARTERA TOTAL (UF)</div>
                        <div class="h2 fw-bold mb-0 text-info"><?php echo number_format($totalUF, 0, ',', '.'); ?></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4" style="border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h5 class="fw-bold mb-0">Historial de Actividad Reciente</h5>
                    <a href="backend/exportar_auditoria.php" class="btn btn-success btn-sm fw-bold shadow-sm">
                        📊 Exportar a Excel
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Usuario</th>
                                <th>Módulo</th>
                                <th>Acción Realizada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($logs)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No hay registros de actividad aún.</td></tr>
                            <?php else: foreach($logs as $log): ?>
                                <tr>
                                    <td class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($log['fecha'])); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($log['usuario_nombre']); ?></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo $log['modulo']; ?></span></td>
                                    <td class="small"><?php echo htmlspecialchars($log['accion']); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>