<?php
session_start();
require 'backend/conexion.php';

// Seguridad: Solo usuarios normales acceden aquí
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'usuario') {
    header("Location: index.php");
    exit();
}

$idUsuario = $_SESSION['usuario_id'];

try {
    // Consulta con JOIN para traer los datos de las propiedades que son favoritos del usuario
    $sql = "SELECT p.* FROM publicaciones p 
            INNER JOIN favoritos f ON p.id = f.id_publicacion 
            WHERE f.id_usuario = ? 
            ORDER BY f.fecha_agregado DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idUsuario]);
    $favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $favoritos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Favoritos - InmobiliariaLibre</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body class="bg-light">
    <header class="login-header py-3 border-bottom bg-white shadow-sm">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-decoration-none d-flex align-items-center">
                <img src="img/1Logopng.PNG" style="width: 45px;" class="me-2">
                <h1 class="h4 fw-bold m-0 text-dark">Mis Favoritos</h1>
            </a>
            <a href="index.php" class="btn btn-outline-dark btn-sm fw-bold">← Volver al Inicio</a>
        </div>
    </header>

    <main class="container mt-5 mb-5">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php if(empty($favoritos)): ?>
                <div class="col-12 text-center py-5">
                    <span class="fs-1">❤️</span>
                    <h4 class="text-muted mt-3">Aún no tienes propiedades favoritas.</h4>
                    <a href="index.php" class="btn btn-primary mt-3">Explorar Propiedades</a>
                </div>
            <?php else: foreach($favoritos as $p): 
                $idProp = $p['id'];
                $rutaCarpeta = "uploads/propiedades/$idProp/";
                $img = is_dir($rutaCarpeta) && !empty(glob($rutaCarpeta."*.*")) ? glob($rutaCarpeta."*.*")[0] : "img/casas/1.jpg";
            ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                        <img src="<?php echo $img; ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h4 class="text-primary fw-bold mb-1">UF <?php echo number_format($p['precio_uf'], 2, ',', '.'); ?></h4>
                            <h6 class="fw-bold"><?php echo htmlspecialchars($p['titulo']); ?></h6>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($p['direccion']); ?></p>
                            <div class="d-flex justify-content-between text-secondary small pt-3 border-top">
                                <span>🛏️ <?php echo $p['dormitorios']; ?> Hab</span> 
                                <span>🛁 <?php echo $p['banos']; ?> Baños</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 pb-3 d-flex gap-2">
                            <a href="detalles.php?id=<?php echo $p['id']; ?>" class="btn btn-dark btn-sm flex-grow-1 fw-bold">Detalles</a>
                            <button onclick="quitarFavorito(<?php echo $p['id']; ?>)" class="btn btn-outline-danger btn-sm">🗑️</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </main>

    <script>
        async function quitarFavorito(id) {
            if(!confirm("¿Quitar de tu lista de favoritos?")) return;
            const resp = await fetch('backend/gestionar_favoritos.php', {
                method: 'POST',
                body: JSON.stringify({ id_publicacion: id })
            });
            const res = await resp.json();
            if(res.status === 'success') location.reload();
        }
    </script>
</body>
</html>