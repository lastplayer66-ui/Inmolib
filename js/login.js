document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Evita que la página se recargue

            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const btnSubmit = loginForm.querySelector('button[type="submit"]');

            const email = emailInput.value.trim();
            const password = passwordInput.value.trim();

            if (!email || !password) {
                alert("Por favor, completa ambos campos.");
                return;
            }

            // Cambiar el botón a estado de carga
            const textoOriginal = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btnSubmit.disabled = true;

            try {
                // CORRECCIÓN: Ruta apuntando a la carpeta backend/
                const respuesta = await fetch('backend/procesar_login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email: email, password: password })
                });

                // Convertir la respuesta del servidor a JSON
                const resultado = await respuesta.json();

                if (resultado.status === 'success') {
                    // Redirigir según el rol del usuario
                    if (resultado.rol === 'admin' || resultado.rol === 'gestor' || resultado.rol === 'propietario') {
                        window.location.href = "index.php";
                    } else {
                        window.location.href = "index.php"; 
                    }
                } else {
                    alert(resultado.message);
                    btnSubmit.innerHTML = textoOriginal;
                    btnSubmit.disabled = false;
                    passwordInput.value = '';
                    passwordInput.focus();
                }

            } catch (error) {
                console.error("Error en la petición AJAX:", error);
                alert("Error de conexión con el servidor.");
                btnSubmit.innerHTML = textoOriginal;
                btnSubmit.disabled = false;
            }
        });
    }
});