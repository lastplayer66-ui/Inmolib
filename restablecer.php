<?php
require 'backend/conexion.php';

$token = $_GET['token'] ?? '';
$valido = false;
$mensaje = '';
$exito = false;

// 1. VERIFICAR SI EL TOKEN EXISTE Y NO ESTÁ CADUCADO
if (!empty($token)) {
    // Buscamos un usuario que tenga ese token y que la fecha de expiración sea mayor a la fecha/hora actual
    $stmt = $pdo->prepare("SELECT id, email FROM usuarios WHERE token_recuperacion = ? AND expiracion_token > NOW()");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $valido = true;
    } else {
        $mensaje = "El enlace de recuperación ha caducado o no es válido. Por favor, solicita uno nuevo desde la página principal.";
    }
} else {
    $mensaje = "No se ha proporcionado un código de recuperación.";
}

// 2. PROCESAR EL FORMULARIO CUANDO EL USUARIO ENVÍA SU NUEVA CONTRASEÑA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valido) {
    $nueva_password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirm_password'] ?? '';

    if (strlen($nueva_password) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($nueva_password !== $confirmar_password) {
        $mensaje = "Las contraseñas no coinciden.";
    } else {
        // Encriptar nueva contraseña
        $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);
        
        // Actualizar contraseña y DESTRUIR EL TOKEN por seguridad
        $update = $pdo->prepare("UPDATE usuarios SET password = ?, token_recuperacion = NULL, expiracion_token = NULL WHERE id = ?");
        $update->execute([$hashed_password, $usuario['id']]);
        
        $exito = true;
        $valido = false; // Ocultamos el formulario
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - InmobiliariaLibre</title>
    <link rel="icon" href="img/1Logopng.PNG">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; min-height: 100vh; }
        .recovery-container { max-width: 450px; width: 100%; margin: auto; padding: 20px; }
    </style>
</head>
<body>

    <div class="recovery-container">
        <div class="text-center mb-4">
            <img src="img/1Logopng.PNG" alt="Logo" style="width: 70px; margin-bottom: 10px;">
            <h2 class="fw-bold text-primary">InmobiliariaLibre</h2>
            <p class="text-muted">Recuperación de cuenta</p>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-top: 5px solid #0d6efd !important;">
            <div class="card-body p-4">
                
                <?php if ($exito): ?>
                    <div class="text-center py-4">
                        <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                            ✓
                        </div>
                        <h4 class="fw-bold text-success">¡Contraseña Actualizada!</h4>
                        <p class="text-muted mt-2">Tu contraseña se ha restablecido correctamente. Ya puedes acceder a tu cuenta.</p>
                        <a href="index.php" class="btn btn-primary w-100 fw-bold mt-3">Ir a Iniciar Sesión</a>
                    </div>

                <?php elseif (!$valido): ?>
                    <div class="text-center py-4">
                        <div class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                            ✕
                        </div>
                        <h5 class="fw-bold text-danger">Enlace inválido</h5>
                        <p class="text-muted mt-2"><?php echo htmlspecialchars($mensaje); ?></p>
                        <a href="index.php" class="btn btn-outline-secondary w-100 fw-bold mt-3">Volver al inicio</a>
                    </div>

                <?php else: ?>
                    <h5 class="fw-bold mb-3 text-center">Crea tu nueva contraseña</h5>
                    <p class="small text-muted text-center mb-4">Ingresa una nueva contraseña para la cuenta <strong><?php echo htmlspecialchars($usuario['email']); ?></strong></p>
                    
                    <?php if (!empty($mensaje)): ?>
                        <div class="alert alert-danger small py-2 px-3"><?php echo htmlspecialchars($mensaje); ?></div>
                    <?php endif; ?>

                    <form action="restablecer.php?token=<?php echo htmlspecialchars($token); ?>" method="POST" id="formRestablecer">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nueva Contraseña <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="in-pass" class="form-control" minlength="6" placeholder="Mínimo 6 caracteres" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Confirmar Contraseña <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" id="in-confirm" class="form-control" placeholder="Repite tu contraseña" required>
                            <div class="invalid-feedback" style="font-size: 0.75rem;">Las contraseñas no coinciden.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Guardar nueva contraseña</button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('formRestablecer');
            if (form) {
                const pass = document.getElementById('in-pass');
                const confirm = document.getElementById('in-confirm');

                form.addEventListener('submit', (e) => {
                    if (pass.value !== confirm.value) {
                        e.preventDefault();
                        confirm.classList.add('is-invalid');
                    } else {
                        confirm.classList.remove('is-invalid');
                    }
                });

                // Quitar la alerta roja en cuanto el usuario empiece a corregir
                confirm.addEventListener('input', () => {
                    confirm.classList.remove('is-invalid');
                });
            }
        });
    </script>
</body>
</html>