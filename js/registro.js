document.addEventListener('DOMContentLoaded', () => {
    // Detectamos cuál de los dos formularios de registro está presente
    const form = document.getElementById('formRegistro') || document.getElementById('formRegistroUsuario');

    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Procesando...';
            btn.disabled = true;

            const formData = new FormData(form);
            
            // Si el formulario es el de usuario común, forzamos el rol
            if (form.id === 'formRegistroUsuario') {
                formData.append('rol', 'usuario');
            } else {
                // En registro.html, el rol depende del botón presionado (Propietario/Gestor)
                // Esto asume que tienes un input hidden llamado 'rol' que cambia con tus botones
                const rolActivo = document.getElementById('campoPropietario').classList.contains('d-none') ? 'gestor' : 'propietario';
                formData.append('rol', rolActivo);
            }

            try {
                const response = await fetch('backend/procesar_registro.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.status === 'success') {
                    form.classList.add('d-none');
                    const successMsg = document.getElementById('mensajeExitoPropietario') || document.getElementById('mensajeExitoUsuario');
                    successMsg.classList.remove('d-none');
                    window.scrollTo(0, 0);
                } else {
                    alert(result.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                alert("Error de conexión con el servidor.");
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    }
});