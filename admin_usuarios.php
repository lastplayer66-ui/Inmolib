<?php
session_start();
require 'backend/conexion.php';

// 1. Verificar sesión y permisos de Administrador
if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: no_autorizado.php");
    exit();
}

$nombreCompleto = $_SESSION['usuario_nombre'];
$rolSesion = $_SESSION['usuario_rol'];
$iniciales = strtoupper(substr($nombreCompleto, 0, 2));

try {
    $stmt = $pdo->prepare("SELECT id, rut, nombre_completo, fecha_nacimiento, sexo, email, telefono, rol, estado, n_propiedad FROM usuarios ORDER BY id DESC");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuarios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Usuarios - InmobiliariaLibre</title>
    <link rel="icon" href="img/1Logopng.PNG">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="js/bootstrap.bundle.min.js" defer></script>
    <style>
        .hover-admin:hover { background-color: rgba(255,255,255,0.1); color: #fff !important; text-decoration: none; }
        .sidebar { width: 260px; position: sticky; top: 0; height: 100vh; }
        .fila-editando { background-color: rgba(25, 135, 84, 0.1) !important; border-left: 5px solid #198754; }
        @media (max-width: 768px) { .sidebar { display: none; } }
    </style>
</head>
<body class="bg-light">

    <div class="d-md-none bg-dark text-white p-3 d-flex justify-content-between align-items-center sticky-top shadow-sm">
        <a href="dashboard.php" class="text-decoration-none text-white fw-bold d-flex align-items-center">
            <img src="img/1Logopng.PNG" style="width: 30px;" class="me-2"> Panel <?php echo $rolSesion; ?>
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
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="admin_publicaciones.php"><span class="me-2">🏠</span> Publicaciones</a>
                <a class="nav-link text-white fw-bold py-3 bg-success rounded" href="admin_usuarios.php"><span class="me-2">👥</span> Usuarios</a>
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="admin_reportes.php"><span class="me-2">📈</span> Reportes</a>
                <hr class="border-secondary">
                <a class="nav-link text-danger fw-bold py-3 rounded hover-admin" href="backend/logout.php">🚪 Cerrar Sesión</a>
            </nav>
        </div>
    </div>

    <div class="d-flex">
        
        <div class="sidebar bg-dark text-white vh-100 shadow-sm d-none d-md-block">
            <div class="p-4 mb-3 bg-black bg-opacity-25">
                <a href="dashboard.php" class="text-decoration-none d-flex align-items-center text-white">
                    <img src="img/1Logopng.PNG" style="width: 40px;" class="me-2">
                    <span class="fw-bold">Panel <?php echo $rolSesion; ?></span>
                </a>
            </div>
            <nav class="nav flex-column px-3 gap-2">
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="dashboard.php"><span class="me-2">🎛️</span> Resumen General</a>
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="admin_publicaciones.php"><span class="me-2">🏠</span> Publicaciones</a>
                <a class="nav-link text-white fw-bold py-3 bg-success rounded shadow-sm" href="admin_usuarios.php"><span class="me-2">👥</span> Usuarios</a>
                <a class="nav-link text-white-50 py-3 rounded hover-admin" href="admin_reportes.php"><span class="me-2">📈</span> Reportes</a>
                <hr class="border-secondary">
                <a class="nav-link text-danger fw-bold py-3 rounded hover-admin" href="backend/logout.php">🚪 Cerrar Sesión</a>
            </nav>
        </div>

        <div class="flex-grow-1 p-3 p-md-4 w-100" style="overflow-x: hidden;">
            
            <div class="d-flex align-items-center mb-4 bg-white p-3 rounded shadow-sm border-start border-success border-4">
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm" style="width: 50px; height: 50px; font-size: 1.2rem;">
                    <?php echo $iniciales; ?>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($nombreCompleto); ?></h6>
                    <small class="text-muted text-capitalize">Rol: <?php echo $rolSesion; ?></small>
                </div>
            </div>

            <h3 class="fw-bold mb-4">Gestión de Usuarios (CRUD)</h3>
            
            <div class="row g-4">
                
                <div class="col-12 col-xl-8 order-1 order-xl-2">
                    <div class="card border-0 shadow-sm p-3 p-md-4" style="border-radius: 12px;">
                        <h5 class="fw-bold mb-3 border-bottom pb-2 text-success">Listado de Usuarios</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" style="min-width: 600px;">
                                <thead class="table-light">
                                    <tr><th>RUT</th><th>Nombre</th><th>Rol</th><th>Estado</th><th class="text-end">Acciones</th></tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($usuarios)): ?>
                                        <tr><td colspan="5" class="text-center py-4">No hay usuarios registrados.</td></tr>
                                    <?php else: foreach($usuarios as $u): ?>
                                        <tr id="fila-user-<?php echo $u['id']; ?>">
                                            <td class="fw-bold small p-rut"><?php echo htmlspecialchars($u['rut']); ?></td>
                                            <td>
                                                <div class="fw-bold p-nombre"><?php echo htmlspecialchars($u['nombre_completo']); ?></div>
                                                <div class="small text-muted p-email"><?php echo htmlspecialchars($u['email']); ?></div>
                                                
                                                <input type="hidden" class="u-fnac" value="<?php echo $u['fecha_nacimiento']; ?>">
                                                <input type="hidden" class="u-sexo" value="<?php echo $u['sexo']; ?>">
                                                <input type="hidden" class="u-fono" value="<?php echo htmlspecialchars($u['telefono']); ?>">
                                                <input type="hidden" class="u-rol" value="<?php echo $u['rol']; ?>">
                                                <input type="hidden" class="u-estado" value="<?php echo $u['estado']; ?>">
                                                <input type="hidden" class="u-prop" value="<?php echo htmlspecialchars($u['n_propiedad'] ?? ''); ?>">
                                            </td>
                                            <td>
                                                <?php 
                                                    $bgRol = 'bg-secondary';
                                                    if($u['rol'] == 'admin') $bgRol = 'bg-danger';
                                                    if($u['rol'] == 'gestor') $bgRol = 'bg-primary';
                                                    if($u['rol'] == 'propietario') $bgRol = 'bg-info text-dark';
                                                ?>
                                                <span class="badge <?php echo $bgRol; ?>"><?php echo ucfirst($u['rol']); ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                    $bgEst = 'bg-success';
                                                    $txtEst = 'Activa';
                                                    if($u['estado'] == 'suspendida') { $bgEst = 'bg-danger'; $txtEst = 'Suspendida'; }
                                                    if($u['estado'] == 'pendiente') { $bgEst = 'bg-warning text-dark'; $txtEst = 'Pendiente'; }
                                                ?>
                                                <span class="badge <?php echo $bgEst; ?>"><?php echo $txtEst; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <?php if($u['email'] === 'admin@mail.cl'): ?>
                                                    <button class="btn btn-sm btn-outline-secondary" disabled title="El Super Admin principal no puede ser borrado">🔒</button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-danger btn-borrar-usr" data-id="<?php echo $u['id']; ?>" title="Eliminar">🗑️</button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-success ms-1 btn-editar-usr" data-id="<?php echo $u['id']; ?>" title="Editar">✏️</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4 order-2 order-xl-1">
                    <div class="card border-0 shadow-sm p-3 p-md-4" style="border-radius: 12px; position: sticky; top: 20px; border-top: 4px solid #198754;">
                        <h5 id="form-titulo" class="fw-bold mb-3 border-bottom pb-2 text-success">Formulario de Usuario</h5>
                        
                        <form id="formCrudUsuario" class="needs-validation" novalidate enctype="multipart/form-data">
                            <input type="hidden" name="id_usuario" id="id_usuario">

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">RUT <span class="text-danger">*</span></label>
                                <input type="text" name="rut" id="in-rut" class="form-control form-control-sm" placeholder="Ej: 11222333-4" pattern="^[0-9]+-[0-9kK]{1}$" required>
                                <div class="form-text" style="font-size: 0.7rem; margin-top: 2px;">Debe incluir guion y dígito verificador.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Nombre Completo <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" id="in-nombre" class="form-control form-control-sm" minlength="5" pattern="^[a-zA-ZÁÉÍÓÚáéíóúÑñ\s]+$" required>
                                <div class="invalid-feedback" id="feedback-nombre" style="font-size: 0.75rem;">Solo se permiten letras y espacios para este rol.</div>
                            </div>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="small text-muted fw-bold">Fecha Nac. <span class="text-danger">*</span></label>
                                    <input type="date" name="fecha_nacimiento" id="in-fnac" class="form-control form-control-sm" min="1945-01-01" max="2025-12-31" required>
                                    <div class="form-text" style="font-size: 0.7rem; margin-top: 2px;">Rango: 1945 a 2025.</div>
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted fw-bold">Sexo <span class="text-danger">*</span></label>
                                    <select name="sexo" id="in-sexo" class="form-select form-select-sm" required>
                                        <option value="m">Masculino</option>
                                        <option value="f">Femenino</option>
                                        <option value="o">Otro</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">E-mail <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="in-email" class="form-control form-control-sm" placeholder="ejemplo@correo.cl" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Teléfono Móvil <span class="text-danger">*</span></label>
                                <input type="tel" name="telefono" id="in-fono" class="form-control form-control-sm" placeholder="Ej: 912345678" pattern="^9\d{8}$" required>
                                <div class="form-text" style="font-size: 0.7rem; margin-top: 2px;">Obligatorio: 9 dígitos, comenzando por 9.</div>
                            </div>

                            <hr>
                            
                            <div class="mb-3">
                                <label class="small text-dark fw-bold">Rol en el Sistema <span class="text-danger">*</span></label>
                                <select name="rol" class="form-select form-select-sm border-success" id="selectRolAdmin" required>
                                    <option value="usuario" selected>Usuario Normal</option>
                                    <option value="propietario">Propietario</option>
                                    <option value="gestor">Gestor Inmobiliario Free</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>

                            <div id="campoCrudPropietario" class="mb-3 p-2 bg-light border rounded d-none">
                                <label class="small text-primary fw-bold">N° de la propiedad</label>
                                <input type="text" name="n_propiedad" id="in-prop" class="form-control form-control-sm" placeholder="Ej: 1234-56">
                            </div>

                            <div id="campoCrudGestor" class="mb-3 p-2 bg-light border rounded d-none">
                                <label class="small text-primary fw-bold">Certificado de Antecedentes</label>
                                <input type="file" name="certificado_antecedentes" class="form-control form-control-sm" accept=".pdf">
                            </div>

                            <div class="mb-3">
                                <label class="small text-muted fw-bold">Estado de Cuenta <span class="text-danger">*</span></label>
                                <select name="estado" id="in-estado" class="form-select form-select-sm" required>
                                    <option value="activa">Activa</option>
                                    <option value="pendiente">Pendiente de Aprobación</option>
                                    <option value="suspendida">Suspendida</option>
                                </select>
                            </div>

                            <div class="d-flex flex-column flex-sm-row gap-2 mt-3">
                                <button type="submit" id="btnSubmitUsr" class="btn btn-success btn-sm flex-grow-1 fw-bold py-2 shadow-sm">Guardar</button>
                                <button type="button" id="btnResetUsr" class="btn btn-outline-secondary btn-sm py-2">Limpiar / Nuevo</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('formCrudUsuario');
            const selectRol = document.getElementById('selectRolAdmin');
            const inputEmail = document.getElementById('in-email');
            const selectEstado = document.getElementById('in-estado');
            const inputRut = document.getElementById('in-rut');
            
            // Elementos nuevos para la validación dinámica
            const inputNombre = document.getElementById('in-nombre');
            const feedbackNombre = document.getElementById('feedback-nombre');
            
            const divProp = document.getElementById('campoCrudPropietario');
            const divGest = document.getElementById('campoCrudGestor');
            const btnSubmit = document.getElementById('btnSubmitUsr');
            const btnReset = document.getElementById('btnResetUsr');
            const formTitulo = document.getElementById('form-titulo');

            // FUNCIÓN: Validar RUT Matemáticamente
            const validarRutChileno = (rutCompleto) => {
                if (!/^[0-9]+[-|‐]{1}[0-9kK]{1}$/.test(rutCompleto)) return false;
                const [rut, dv] = rutCompleto.split('-');
                if (/^([0-9])\1+$/.test(rut)) return false;
                let M = 0, S = 1;
                for (let T = parseInt(rut, 10); T; T = Math.floor(T / 10)) {
                    S = (S + T % 10 * (9 - M++ % 6)) % 11;
                }
                let dvCalculado = S ? (S - 1).toString() : 'k';
                return dvCalculado === dv.toLowerCase();
            };

            const manejarCamposPorRol = (rol) => {
                divProp.classList.add('d-none');
                divGest.classList.add('d-none');
                if (rol === 'propietario') divProp.classList.remove('d-none');
                else if (rol === 'gestor') divGest.classList.remove('d-none');
                
                // LÓGICA DINÁMICA DE VALIDACIÓN DE NOMBRE
                if (rol === 'usuario' || rol === 'admin') {
                    inputNombre.setAttribute('pattern', '^[a-zA-ZÁÉÍÓÚáéíóúÑñ\\s]+$');
                    feedbackNombre.innerText = 'Solo se permiten letras y espacios para este rol.';
                } else {
                    inputNombre.setAttribute('pattern', '^[a-zA-Z0-9ÁÉÍÓÚáéíóúÑñ\\s\\.\\&]+$');
                    feedbackNombre.innerText = 'Ingrese un nombre válido (permite números y símbolos como . y &).';
                }
            };

            selectRol.addEventListener('change', (e) => manejarCamposPorRol(e.target.value));

            document.querySelectorAll('.btn-editar-usr').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    document.querySelectorAll('tr').forEach(tr => tr.classList.remove('fila-editando'));
                    const fila = document.getElementById('fila-user-' + id);
                    fila.classList.add('fila-editando');

                    document.getElementById('id_usuario').value = id;
                    document.getElementById('in-rut').value = fila.querySelector('.p-rut').innerText.trim();
                    document.getElementById('in-nombre').value = fila.querySelector('.p-nombre').innerText;
                    
                    const correo = fila.querySelector('.p-email').innerText.trim();
                    inputEmail.value = correo;
                    document.getElementById('in-fnac').value = fila.querySelector('.u-fnac').value;
                    document.getElementById('in-sexo').value = fila.querySelector('.u-sexo').value;
                    document.getElementById('in-fono').value = fila.querySelector('.u-fono').value;
                    
                    const rol = fila.querySelector('.u-rol').value;
                    selectRol.value = rol;
                    manejarCamposPorRol(rol);
                    selectEstado.value = fila.querySelector('.u-estado').value;

                    // Reglas de seguridad
                    if (correo === 'admin@mail.cl') {
                        inputEmail.readOnly = true; inputEmail.classList.add('bg-light', 'text-muted');
                        selectRol.disabled = true; selectEstado.value = 'activa'; selectEstado.disabled = true;
                    } else if (rol === 'admin') {
                        inputEmail.readOnly = true; inputEmail.classList.add('bg-light', 'text-muted');
                        selectRol.disabled = true; selectEstado.disabled = false; 
                    } else {
                        inputEmail.readOnly = false; inputEmail.classList.remove('bg-light', 'text-muted');
                        selectRol.disabled = false; selectEstado.disabled = false;
                    }

                    document.getElementById('in-prop').value = fila.querySelector('.u-prop').value;

                    formTitulo.innerText = "Actualizar Usuario";
                    btnSubmit.innerText = "Actualizar Cambios";
                    btnSubmit.classList.replace('btn-success', 'btn-warning');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });

            btnReset.addEventListener('click', () => {
                form.reset();
                form.classList.remove('was-validated');
                document.getElementById('id_usuario').value = "";
                manejarCamposPorRol('usuario'); // Resetea la validación dinámica al estado inicial
                
                inputEmail.readOnly = false; inputEmail.classList.remove('bg-light', 'text-muted');
                selectRol.disabled = false; selectEstado.disabled = false;
                
                formTitulo.innerText = "Formulario de Usuario";
                btnSubmit.innerText = "Guardar";
                btnSubmit.classList.replace('btn-warning', 'btn-success');
                document.querySelectorAll('tr').forEach(tr => tr.classList.remove('fila-editando'));
            });

            // ==========================================
            // ENVÍO AL BACKEND (Crear o Editar Usuario)
            // ==========================================
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                // 1. Validación Visual de Bootstrap
                if (!form.checkValidity()) {
                    e.stopPropagation();
                    form.classList.add('was-validated');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Faltan datos',
                        text: 'Por favor, completa correctamente los campos marcados en rojo.',
                        confirmButtonColor: '#0d6efd'
                    });
                    return;
                }
                
                // 2. VALIDACIÓN FRONTEND DE RUT
                if (!validarRutChileno(inputRut.value)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'RUT Inválido',
                        text: 'El RUT ingresado no es válido. Verifica el dígito verificador y el guion.',
                        confirmButtonColor: '#dc3545'
                    }).then(() => {
                        inputRut.focus();
                    });
                    return;
                }

                // Truco para enviar inputs deshabilitados por las reglas de seguridad
                const rolDeshabilitado = selectRol.disabled;
                const estadoDeshabilitado = selectEstado.disabled;
                
                if (rolDeshabilitado) selectRol.disabled = false;
                if (estadoDeshabilitado) selectEstado.disabled = false;
                
                const formData = new FormData(form);
                
                if (rolDeshabilitado) selectRol.disabled = true;
                if (estadoDeshabilitado) selectEstado.disabled = true;

                // 3. SweetAlert de estado "Cargando"
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Guardando información del usuario.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                try {
                    const resp = await fetch('backend/procesar_registro.php', { method: 'POST', body: formData });
                    const res = await resp.json();
                    
                    if(res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: res.message,
                            timer: 2500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al procesar',
                            text: res.message,
                            confirmButtonColor: '#dc3545'
                        });
                    }
                } catch (err) { 
                    Swal.fire('Error de conexión', 'Fallo al comunicarse con el servidor.', 'error');
                }
            });

            // ==========================================
            // BORRADO DE USUARIOS CON SWEETALERT2
            // ==========================================
            document.querySelectorAll('.btn-borrar-usr').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idBorrar = this.getAttribute('data-id');
                    
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "Estás a punto de eliminar al usuario #" + idBorrar + " de forma permanente.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then(async (result) => {
                        if (result.isConfirmed) {
                            try {
                                const resp = await fetch('backend/eliminar_usuario.php', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json'},
                                    body: JSON.stringify({id: idBorrar})
                                });
                                const res = await resp.json();
                                
                                if(res.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '¡Eliminado!',
                                        text: res.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    }).then(() => location.reload());
                                } else {
                                    Swal.fire('Error', res.message, 'error');
                                }
                            } catch(err) {
                                Swal.fire('Error', 'Fallo al conectar con el servidor AWS.', 'error');
                            }
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>