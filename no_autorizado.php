<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - InmobiliariaLibre</title>
    <link rel="icon" href="img/1Logopng.PNG">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #f8f9fa; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            height: 100vh; 
            margin: 0; 
        }
        .auth-container { 
            max-width: 450px; 
            width: 100%; 
            padding: 15px; 
        }
    </style>
</head>
<body>

    <div class="container auth-container">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-top: 5px solid #dc3545 !important;">
            <div class="card-body p-4 text-center">
                
                <img src="img/1Logopng.PNG" alt="Logo" style="width: 80px; margin-bottom: 20px; filter: grayscale(100%); opacity: 0.8;">
                
                <h3 class="fw-bold text-danger mb-2">Acceso Restringido</h3>
                
                <p class="text-muted mb-4">
                    Lo sentimos, tu cuenta actual no tiene los permisos necesarios para acceder a esta sección del sistema.
                </p>
                
                <div class="p-3 bg-light rounded border border-danger border-opacity-25 mb-4">
                    <span class="text-danger fw-bold small">🛑 Código de Error: 403 Forbidden</span>
                </div>

                <a href="index.php" class="btn btn-primary w-100 fw-bold py-2 shadow-sm" style="border-radius: 8px;">
                    Volver al Inicio
                </a>
                
            </div>
        </div>
    </div>

</body>
</html>