<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Inmobiliario - InmobiliariaLibre</title>
    <link rel="icon" href="img/1Logopng.PNG">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f8f9fa; }
        .registro-container { max-width: 550px; margin: 40px auto; }
    </style>
</head>

<body>

    <div class="container registro-container">
        <div class="text-center mb-4">
            <img src="img/1Logopng.PNG" alt="Logo" style="width: 70px; margin-bottom: 10px;">
            <h2 class="fw-bold text-primary">Portal para Publicadores</h2>
            <p class="text-muted">Únete como Propietario o Gestor Inmobiliario.</p>
        </div>

        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-top: 5px solid #0d6efd !important;">
            <div class="card-body p-4">
                <form id="formRegistroInmobiliario" class="needs-validation" novalidate enctype="multipart/form-data">
                    
                    <div class="mb-4 p-3 bg-light rounded border border-primary border-opacity-25">
                        <label class="form-label small fw-bold text-primary">¿Cómo deseas registrarte? <span class="text-danger">*</span></label>
                        <select name="rol" id="selectRol" class="form-select border-primary" required>
                            <option value="">Selecciona tu perfil...</option>
                            <option value="propietario">Dueño / Propietario directo</option>
                            <option value="gestor">Gestor Inmobiliario (Corredor)</option>
                        </select>
                        <div class="invalid-feedback" style="font-size: 0.75rem;">Seleccione un rol.</div>
                    </div>

                    <div id="campoPropietario" class="mb-4 p-3 bg-light rounded border border-primary border-opacity-25 d-none">
                        <label class="form-label small fw-bold text-primary">N° de la Propiedad</label>
                        <input type="text" name="n_propiedad" id="in-prop" class="form-control" placeholder="Ej: 1234-56">
                    </div>

                    <div id="campoGestor" class="mb-4 p-3 bg-light rounded border border-primary border-opacity-25 d-none">
                        <label class="form-label small fw-bold text-primary">Certificado de Antecedentes <span class="text-danger">*</span></label>
                        <input type="file" name="certificado_antecedentes" id="in-cert" class="form-control" accept=".pdf">
                        <div class="form-text" style="font-size: 0.75rem;">Debe adjuntar su certificado en formato PDF.</div>
                        <div class="invalid-feedback" style="font-size: 0.75rem;">El certificado es obligatorio para gestores.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">RUT <span class="text-danger">*</span></label>
                        <input type="text" name="rut" id="reg-rut-inmo" class="form-control" placeholder="Ej: 11222333-4" pattern="^[0-9]+-[0-9kK]{1}$" required>
                        <div class="form-text" style="font-size: 0.75rem;">Requerido para la validación de identidad.</div>
                        <div class="invalid-feedback" style="font-size: 0.75rem;">Ingrese un RUT en formato válido.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre Completo / Razón Social <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" class="form-control" minlength="5" placeholder="Tu nombre o el de tu agencia" pattern="^[a-zA-Z0-9ÁÉÍÓÚáéíóúÑñ\s\.\&]+$" required>
                        <div class="invalid-feedback" style="font-size: 0.75rem;">Requerido. Ingrese un nombre válido.</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Fecha Nac. / Creación <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_nacimiento" class="form-control" min="1945-01-01" max="2025-12-31" required>
                            <div class="invalid-feedback" style="font-size: 0.75rem;">Ingrese una fecha válida.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Sexo / Tipo <span class="text-danger">*</span></label>
                            <select name="sexo" class="form-select" required>
                                <option value="">Seleccione...</option>
                                <option value="m">Masculino</option>
                                <option value="f">Femenino</option>
                                <option value="o">Otro / Empresa</option>
                            </select>
                            <div class="invalid-feedback" style="font-size: 0.75rem;">Requerido.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Teléfono de Contacto <span class="text-danger">*</span></label>
                        <input type="tel" name="telefono" class="form-control" placeholder="Ej: 912345678" pattern="^9\d{8}$" required>
                        <div class="form-text" style="font-size: 0.75rem;">Los clientes verán este número en tus publicaciones.</div>
                        <div class="invalid-feedback" style="font-size: 0.75rem;">Debe tener 9 dígitos y comenzar con 9.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="contacto@agencia.cl" required>
                        <div class="invalid-feedback" style="font-size: 0.75rem;">Ingrese un correo válido.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Contraseña de acceso <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" minlength="6" placeholder="Mínimo 6 caracteres" required>
                        <div class="invalid-feedback" style="font-size: 0.75rem;">La contraseña debe tener al menos 6 caracteres.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mb-3" style="border-radius: 8px;">Enviar Solicitud de Cuenta</button>
                    
                    <div class="text-center">
                        <a href="index.php" class="text-decoration-none text-muted small">← Cancelar y volver al inicio</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('formRegistroInmobiliario');
            
            const selectRol = document.getElementById('selectRol');
            const divProp = document.getElementById('campoPropietario');
            const divGest = document.getElementById('campoGestor');
            const inputCert = document.getElementById('in-cert');

            selectRol.addEventListener('change', (e) => {
                const rol = e.target.value;
                
                divProp.classList.add('d-none');
                divGest.classList.add('d-none');
                inputCert.removeAttribute('required');

                if (rol === 'propietario') {
                    divProp.classList.remove('d-none');
                } else if (rol === 'gestor') {
                    divGest.classList.remove('d-none');
                    inputCert.setAttribute('required', 'required'); 
                }
            });

            const validarRutChileno = (rutCompleto) => {
                if (!/^[0-9]+[-|‐]{1}[0-9kK]{1}$/.test(rutCompleto)) return false;
                const [rut, dv] = rutCompleto.split('-');
                if (/^([0-9])\1+$/.test(rut)) return false;
                let M = 0, S = 1;
                for (let T = parseInt(rut, 10); T; T = Math.floor(T / 10)) {
                    S = (S + T % 10 * (9 - M++ % 6)) % 11;
                }
                const dvCalculado = S ? (S - 1).toString() : 'k';
                return dvCalculado === dv.toLowerCase();
            };

            form.addEventListener('submit', async (e) => {
                e.preventDefault(); 

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

                const inputRut = document.getElementById('reg-rut-inmo').value;
                if (!validarRutChileno(inputRut)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'RUT Inválido',
                        text: 'El RUT ingresado no es válido. Revisa el formato y el dígito verificador.',
                        confirmButtonColor: '#dc3545'
                    }).then(() => {
                        document.getElementById('reg-rut-inmo').focus();
                    });
                    return;
                }

                const formData = new FormData(form);

                Swal.fire({
                    title: 'Procesando...',
                    text: 'Enviando tu solicitud al servidor.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                try {
                    const resp = await fetch('backend/procesar_registro_publico.php', { 
                        method: 'POST', 
                        body: formData 
                    });
                    
                    const res = await resp.json();

                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Solicitud Enviada!',
                            text: res.message,
                            confirmButtonColor: '#198754'
                        }).then(() => {
                            window.location.href = 'index.php';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de validación',
                            text: res.message, 
                            confirmButtonColor: '#dc3545'
                        });
                    }
                } catch (err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de red',
                        text: 'No se pudo conectar con el servidor AWS.',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        });
    </script>
</body>
</html>