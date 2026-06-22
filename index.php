<?php
session_start();
require 'backend/conexion.php';

// ============================================================
// MODIFICACIÓN 1: SQL CON DETECCIÓN DE FAVORITOS Y ESTADO (Corregido con parámetros nombrados)
// ============================================================
try {
    $condiciones = [];
    $parametros = [];
    
    $idUsuarioLogueado = $_SESSION['usuario_id'] ?? 0;
    $parametros[':id_usuario'] = $idUsuarioLogueado;

    // REGLA DE NEGOCIO: Mostrar estrictamente solo las propiedades activas
    $condiciones[] = "estado = :estado";
    $parametros[':estado'] = 'activa';

    if (!empty($_GET['provincia'])) {
        $condiciones[] = "provincia = :provincia";
        $parametros[':provincia'] = $_GET['provincia'];
    }
    if (!empty($_GET['comuna'])) {
        $condiciones[] = "comuna = :comuna";
        $parametros[':comuna'] = $_GET['comuna'];
    }
    if (!empty($_GET['sector'])) {
        $condiciones[] = "sector = :sector";
        $parametros[':sector'] = $_GET['sector'];
    }
    if (!empty($_GET['tipo'])) {
        $condiciones[] = "tipo_propiedad = :tipo";
        $parametros[':tipo'] = $_GET['tipo'];
    }

    // Consulta que detecta si cada propiedad es favorita del usuario actual
    $sql = "SELECT *, 
            (SELECT COUNT(*) FROM favoritos f WHERE f.id_publicacion = publicaciones.id AND f.id_usuario = :id_usuario) as es_favorito 
            FROM publicaciones";
            
    if (count($condiciones) > 0) {
        $sql .= " WHERE " . implode(" AND ", $condiciones);
    }
    $sql .= " ORDER BY fecha_publicacion DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    $propiedades = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $propiedades = [];
}

// 2. VERIFICAR SI HAY SESIÓN ACTIVA
$isLoggedIn = isset($_SESSION['usuario_id']);
$rol = $isLoggedIn ? $_SESSION['usuario_rol'] : null;
$nombre = $isLoggedIn ? $_SESSION['usuario_nombre'] : '';
$iniciales = $isLoggedIn ? strtoupper(substr($nombre, 0, 2)) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InmobiliariaLibre</title>
    <link rel="icon" href="img/1Logopng.PNG">
    <link rel="stylesheet" href="css/estilo.css">
    <link href="css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous" defer></script>
    
    <script src="js/script.js" defer></script>
</head>
<body>

    <div class="modal fade" id="modalRecuperar" tabindex="-1" aria-labelledby="modalRecuperarLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="border-radius: 12px;">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body px-4 pb-5 pt-0">
                    <div class="text-center mb-4">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                            <span class="fs-1">🔐</span>
                        </div>
                        <h4 class="fw-bold text-dark" id="modalRecuperarLabel">Recuperar Contraseña</h4>
                        <p class="text-muted small px-3">Ingresa el correo electrónico asociado a tu cuenta y te enviaremos las instrucciones para restablecerla.</p>
                    </div>
                    <form id="formRecuperar">
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Correo Electrónico</label>
                            <input type="email" class="form-control py-2" placeholder="ejemplo@correo.com" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="border-radius: 8px;">Enviar enlace de recuperación</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <header class="login-header py-3">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
            
            <div class="title-placeholder mb-3 mb-md-0 d-flex align-items-center">
                <img src="img/1Logopng.PNG" alt="Logo InmobiliariaLibre" class="me-3" style="width: 55px; height: auto;">
                <div>
                    <h1 class="text-black fw-bold m-0 logo-text">InmobiliariaLibre</h1>
                    <p class="text-black-50 small m-0">La mejor plataforma para propiedades de Chile</p>
                </div>
            </div>

            <div class="d-flex flex-column align-items-end">
                <?php if ($isLoggedIn): ?>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 45px; height: 45px; font-size: 1.1rem;">
                                <?php echo $iniciales; ?>
                            </div>
                            <span class="fw-bold text-dark" style="font-size: 1.1rem;"><?php echo htmlspecialchars($nombre); ?></span>
                        </div>
                        <?php if ($rol === 'usuario'): ?>
                            <a href="favoritos.php" class="btn btn-outline-danger fw-bold shadow-sm d-flex align-items-center gap-2">❤️ Ver Favoritos</a>
                        <?php else: ?>
                            <a href="dashboard.php" class="btn btn-outline-primary fw-bold shadow-sm d-flex align-items-center gap-2">🎛️ Mi Panel</a>
                        <?php endif; ?>
                        <a href="backend/logout.php" class="btn btn-dark fw-bold shadow-sm">Cerrar Sesión</a>
                    </div>
                <?php else: ?>
                    <form id="loginForm" class="d-flex gap-2 align-items-end mb-1">
                        <div class="d-flex flex-column">
                            <input type="text" class="form-control form-control-sm" style="width: 160px;" id="email" name="email" placeholder="Usuario o Email" required>
                        </div>
                        <div class="d-flex flex-column">
                            <div class="mb-1">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#modalRecuperar" class="small text-decoration-none" style="font-size: 0.85rem;">¿Olvidaste tu contraseña?</a>
                            </div>
                            <input type="password" class="form-control form-control-sm" style="width: 160px;" id="password" name="password" placeholder="Contraseña" required>
                        </div>
                        <div><button type="submit" class="btn btn-dark btn-sm fw-bold px-3">Login</button></div>
                    </form>
                    <div class="mt-2 w-100 text-end pe-1">
                        <span class="small text-muted" style="font-size: 0.85rem;">¿Publicas propiedades? <a href="registro.html" class="text-decoration-none fw-bold text-primary">Regístrate como Corredor/Dueño</a></span>
                        <hr class="my-1" style="opacity: 0.15;">
                        <span class="small text-muted" style="font-size: 0.85rem;">¿No tienes cuenta de usuario? <a href="registro_usuario.html" class="text-decoration-none fw-bold text-success">¡Crea una aquí!</a></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="container mt-4 pt-2 mb-5">
        <section class="filtros-container mb-4">
            <div class="card shadow-sm border-0 bg-white" style="border-radius: 10px;">
                <div class="card-body">
                    <form class="row g-3 align-items-end" id="formFiltrosHome" action="index.php" method="GET">
                        <div class="col-12 col-md-2">
                            <label for="provincia" class="form-label small text-muted fw-bold mb-1">Provincia</label>
                            <select id="provincia" name="provincia" class="form-select form-select-sm">
                                <option value="">Seleccione...</option>
                                <option value="elqui">Elqui</option>
                                <option value="limari">Limarí</option>
                                <option value="choapa">Choapa</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="comuna" class="form-label small text-muted fw-bold mb-1">Comuna</label>
                            <select id="comuna" name="comuna" class="form-select form-select-sm" disabled>
                                <option value="">Elige provincia primero</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="sector" class="form-label small text-muted fw-bold mb-1">Sector</label>
                            <select id="sector" name="sector" class="form-select form-select-sm" disabled>
                                <option value="">Elige comuna primero</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label for="tipoPropiedad" class="form-label small text-muted fw-bold mb-1">Propiedad</label>
                            <select id="tipoPropiedad" name="tipo" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                <option value="casa">Casa</option>
                                <option value="departamento">Departamento</option>
                                <option value="parcela">Parcela</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">Buscar</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section class="propiedades-grid mt-5">
            <h3 class="mb-4 text-dark fw-bold fs-5">
                <?php echo empty($_GET) ? 'Propiedades Destacadas' : 'Resultados de Búsqueda'; ?>
            </h3>

            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php if(empty($propiedades)): ?>
                    <div class="col-12 text-center py-5 w-100">
                        <p class="text-muted fs-5">No hay propiedades activas que coincidan con tu búsqueda.</p>
                    </div>
                <?php else: foreach($propiedades as $p): 
                    $idProp = $p['id'];
                    $rutaCarpeta = "uploads/propiedades/" . $idProp . "/";
                    $imagenPrincipal = "img/casas/1.jpg"; 
                    
                    if (is_dir($rutaCarpeta)) {
                        $imagenes = glob($rutaCarpeta . "*.{jpg,jpeg,png,webp}", GLOB_BRACE);
                        foreach ($imagenes as $img) {
                            if (strpos(basename($img), 'principal_') === 0) {
                                $imagenPrincipal = $img;
                                break;
                            }
                        }
                        if ($imagenPrincipal === "img/casas/1.jpg" && !empty($imagenes)) {
                            $imagenPrincipal = $imagenes[0];
                        }
                    }
                ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0" style="border-radius: 10px; overflow: hidden; position: relative;">
                        <?php if (isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'usuario'): ?>
                            <button onclick="toggleFavorito(<?php echo $p['id']; ?>, this)" 
                                    class="btn <?php echo ($p['es_favorito'] > 0) ? 'btn-danger' : 'btn-light'; ?> btn-sm position-absolute shadow-sm" 
                                    style="top: 10px; right: 10px; z-index: 5; border-radius: 50%; width: 35px; height: 35px; padding: 0;">❤️</button>
                        <?php endif; ?>
                        
                        <img src="<?php echo $imagenPrincipal; ?>" class="card-img-top" alt="Propiedad" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h4 class="card-title text-primary fw-bold mb-1"><?php echo number_format($p['precio_uf'], 2, ',', '.'); ?> UF</h4>
                            <h6 class="card-subtitle mb-2 text-dark fw-semibold"><?php echo htmlspecialchars($p['titulo']); ?></h6>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($p['comuna']); ?><?php echo !empty($p['sector']) ? ', ' . htmlspecialchars($p['sector']) : ''; ?></p>
                            <div class="d-flex justify-content-between text-secondary small pt-3 border-top">
                                <span>🛏️ <?php echo $p['dormitorios']; ?> Hab</span> 
                                <span>🛁 <?php echo $p['banos']; ?> Baños</span> 
                                <span>📐 <?php echo $p['area_total']; ?> m²</span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 pb-3 pt-0">
                            <a href="detalles.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-dark btn-sm w-100 fw-bold">Ver Detalles</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </section>
    </main>

    <footer class="bg-white pb-4" style="border-top: 1px solid #e0e0e0; margin-top: 80px; position: relative;">
        <div class="text-center" style="position: absolute; top: -33px; left: 0; right: 0;">
            <button class="btn btn-light bg-white border border-bottom-0 shadow-none" style="border-radius: 8px 8px 0 0; font-size: 0.9rem; color: #333; padding: 6px 16px;">Más información ⌃</button>
        </div>
        <div class="container text-center text-md-start mt-4 pt-2">
            <ul class="list-inline mb-2" style="font-size: 0.85rem;">
                <li class="list-inline-item me-3"><a href="#" class="text-dark text-decoration-none hover-underline">Trabaja con nosotros</a></li>
                <li class="list-inline-item me-3"><a href="#" class="text-dark text-decoration-none hover-underline">Términos y condiciones</a></li>
                <li class="list-inline-item me-3"><a href="#" class="text-dark text-decoration-none hover-underline">Ayuda</a></li>
            </ul>
            <p class="text-muted mb-0" style="font-size: 0.8rem;">Copyright © 2020-2026 InmobiliariaLibre Chile Ltda.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const datosRegion = {
                elqui: {
                    "La Serena": ["Centro", "Avenida del Mar", "Puerta del Mar", "San Joaquín", "Cerro Grande", "Altovalsol", "Las Compañías", "La Florida", "El Milagro", "Colina El Pino", "Caleta San Pedro", "El Romeral"],
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

            const selectProv = document.getElementById('provincia');
            const selectComu = document.getElementById('comuna');
            const selectSect = document.getElementById('sector');

            function populateComunas(prov, selected = '') {
                selectComu.innerHTML = '<option value="">Todas las comunas...</option>';
                if (prov && datosRegion[prov]) {
                    selectComu.disabled = false;
                    Object.keys(datosRegion[prov]).forEach(c => {
                        let opt = new Option(c, c);
                        if(c === selected) opt.selected = true;
                        selectComu.add(opt);
                    });
                } else { selectComu.disabled = true; }
            }

            function populateSectores(prov, comu, selected = '') {
                selectSect.innerHTML = '<option value="">Todos los sectores...</option>';
                if (prov && comu && datosRegion[prov][comu]) {
                    selectSect.disabled = false;
                    datosRegion[prov][comu].forEach(s => {
                        let opt = new Option(s, s);
                        if(s === selected) opt.selected = true;
                        selectSect.add(opt);
                    });
                } else { selectSect.disabled = true; }
            }

            selectProv.addEventListener('change', (e) => { populateComunas(e.target.value); selectSect.innerHTML = '<option value="">Elige comuna primero</option>'; selectSect.disabled = true; });
            selectComu.addEventListener('change', (e) => populateSectores(selectProv.value, e.target.value));

            const params = new URLSearchParams(window.location.search);
            const pProv = params.get('provincia');
            const pComu = params.get('comuna');
            const pSect = params.get('sector');
            const pTipo = params.get('tipo');

            if (pProv) {
                selectProv.value = pProv;
                populateComunas(pProv, pComu);
                if (pComu) { populateSectores(pProv, pComu, pSect); }
            }
            if (pTipo) document.getElementById('tipoPropiedad').value = pTipo;
        });

        async function toggleFavorito(id, btn) {
            try {
                const resp = await fetch('backend/gestionar_favoritos.php', { 
                    method: 'POST', 
                    headers: {'Content-Type': 'application/json'}, 
                    body: JSON.stringify({ id_publicacion: id }) 
                });
                const res = await resp.json();
                
                if (res.status === 'success') {
                    if (res.action === 'added') {
                        btn.classList.replace('btn-light', 'btn-danger');
                    } else {
                        btn.classList.replace('btn-danger', 'btn-light');
                    }
                }
            } catch (err) { Swal.fire('Error', 'Error de conexión al gestionar favoritos.', 'error'); }
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            // --- RECUPERACIÓN DE CONTRASEÑA ---
            const formRecuperar = document.getElementById('formRecuperar');
            if (formRecuperar) {
                formRecuperar.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const inputEmail = formRecuperar.querySelector('input[type="email"]');
                    const email = inputEmail.value;
                    const btnSubmit = formRecuperar.querySelector('button[type="submit"]');
                    const textoOriginal = btnSubmit.innerText;
                    
                    btnSubmit.innerText = "Enviando correo...";
                    btnSubmit.disabled = true;

                    const formData = new FormData();
                    formData.append('email', email);

                    try {
                        const resp = await fetch('backend/procesar_recuperacion.php', { method: 'POST', body: formData });
                        const res = await resp.json();
                        
                        if (res.status === 'success') {
                            const modalInstancia = bootstrap.Modal.getInstance(document.getElementById('modalRecuperar'));
                            modalInstancia.hide();
                            formRecuperar.reset();
                            
                            Swal.fire({
                                icon: 'success',
                                title: '¡Correo enviado!',
                                text: res.message,
                                confirmButtonColor: '#198754'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de Recuperación',
                                text: res.message,
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    } catch (err) {
                        Swal.fire('Error', 'Error de conexión al intentar enviar el correo.', 'error');
                    } finally {
                        btnSubmit.innerText = textoOriginal;
                        btnSubmit.disabled = false;
                    }
                });
            }

            // --- INICIO DE SESIÓN ---
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', async (e) => {
                    e.preventDefault(); 
                    
                    const emailInput = document.getElementById('email').value;
                    const passwordInput = document.getElementById('password').value;

                    Swal.fire({
                        title: 'Iniciando sesión...',
                        text: 'Verificando credenciales...',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    try {
                        const formData = new FormData();
                        formData.append('email', emailInput);
                        formData.append('password', passwordInput);

                        const resp = await fetch('backend/procesar_login.php', { 
                            method: 'POST', 
                            body: formData 
                        });
                        
                        const res = await resp.json();

                        if (res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Bienvenido!',
                                text: 'Acceso autorizado exitosamente.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload(); 
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Acceso Denegado',
                                text: res.message, 
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    } catch (err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de red',
                            text: 'No se pudo conectar con el servidor.',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>