<?php
session_start();
// Salimos de 'vistas' para ir al index
if(isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InmoPro - Crear Cuenta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-login-signup { background-image: linear-gradient(rgba(10, 10, 10, 0.8), rgba(10, 10, 10, 0.8)), url('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80'); background-size: cover; background-position: center; }
        .bg-form-card { background-color: #101115; padding: 2.5rem; border-radius: 2rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); width: 100%; max-width: 28rem; border: 1px solid #27272a; color: white; }
        .input-dark { width: 100%; background-color: #1c1d22; border: none; border-radius: 0.75rem; padding: 0.875rem 1rem; color: white; outline: none; }
        .input-dark:focus { box-shadow: 0 0 0 2px #6366f1; }
        .btn-purple { width: 100%; background-color: #6366f1; font-weight: 600; padding: 0.875rem; border-radius: 0.75rem; color: white; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); transition: all 0.2s; }
        .btn-purple:hover { background-color: #4f46e5; }
        /* Pequeño ajuste para que los inputs de tipo file se vean bien en tu diseño oscuro */
        input[type="file"]::file-selector-button { background-color: #3f3f46; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; margin-right: 1rem; cursor: pointer;}
        input[type="file"]::file-selector-button:hover { background-color: #52525b; }
    </style>
</head>
<body class="font-sans antialiased text-gray-800 bg-login-signup min-h-screen flex items-center justify-center p-4">

    <div class="bg-form-card">
        <h2 class="text-3xl font-bold text-center mb-6 tracking-tight">Signup</h2>
        
        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Nombre Completo</label>
            <input type="text" id="reg-nombre" placeholder="Nombre completo" class="input-dark">
        </div>
        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Correo Electrónico</label>
            <input type="email" id="reg-email" placeholder="correo@ejemplo.com" class="input-dark">
        </div>
        <div class="mb-4 relative">
            <label class="block text-sm text-gray-400 mb-1">Contraseña</label>
            <input type="password" id="reg-password" placeholder="Mín. 8 caracteres" class="input-dark">
        </div>

        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">CURP</label>
            <input type="text" id="reg-curp" placeholder="18 caracteres" maxlength="18" class="input-dark uppercase">
        </div>
        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Foto INE (Frente)</label>
            <input type="file" id="reg-ine-frente" accept=".jpg, .jpeg, .png" class="input-dark" style="padding: 0.5rem;">
        </div>
        <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-1">Foto INE (Reverso)</label>
            <input type="file" id="reg-ine-reverso" accept=".jpg, .jpeg, .png" class="input-dark" style="padding: 0.5rem;">
        </div>
        <button onclick="registrarUsuario()" class="btn-purple mt-2">Crear Cuenta</button>
        
        <p class="text-center text-sm text-gray-400 mt-6">¿Ya tienes una cuenta? <a href="login.php" class="text-[#6366f1] font-medium hover:underline">Inicia Sesión</a></p>
    </div>

    <script>
        async function registrarUsuario() {
            // 1. Recolectar textos
            const nombre = document.getElementById('reg-nombre').value;
            const email = document.getElementById('reg-email').value;
            const password = document.getElementById('reg-password').value;
            const curp = document.getElementById('reg-curp').value;
            
            // 2. Recolectar archivos (files[0] toma el primer archivo seleccionado)
            const ineFrente = document.getElementById('reg-ine-frente').files[0];
            const ineReverso = document.getElementById('reg-ine-reverso').files[0];

            // 3. Validación de caja blanca (Ruta de error)
            if(!nombre || !email || !password || !curp || !ineFrente || !ineReverso) {
                alert("Por favor llena todos los campos y sube ambas fotos de tu INE.");
                return;
            }

            if(curp.length !== 18) {
                alert("La CURP debe tener exactamente 18 caracteres.");
                return;
            }

            // 4. CREAR FORMDATA: El camión de carga para enviar texto + imágenes
            const formData = new FormData();
            formData.append('nombre', nombre);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('rol_id', 2); // Forzamos el rol_id a 2 (Cliente) por seguridad
            formData.append('curp', curp);
            formData.append('ine_frente', ineFrente);
            formData.append('ine_reverso', ineReverso);

            try {
                // Modifiqué el botón visualmente para que el usuario sepa que está cargando
                const btn = document.querySelector('.btn-purple');
                btn.innerText = "Subiendo documentos...";
                btn.disabled = true;

                const respuesta = await fetch('../apis/api_registro.php', {
                    method: 'POST',
                    // ¡OJO! No pongas headers: {'Content-Type': ...} aquí. 
                    // El navegador lo asigna automáticamente cuando ve un FormData.
                    body: formData
                });

                // Como nuestra API anterior hacía "echo" de texto puro, usamos .text() en lugar de .json()
               const data = await respuesta.json(); // Ahora leemos el JSON que manda PHP
alert(data.message); // Mostramos solo el mensaje del JSON

if(data.success) {
    // Solo redirigimos al login si todo salió bien
    window.location.href = 'login.php';
} else {
    // Si hay error (ej: correo repetido), restauramos el botón
    document.querySelector('.btn-purple').innerText = "Crear Cuenta";
    document.querySelector('.btn-purple').disabled = false;
}
                
            } catch (error) {
                console.error("Error:", error);
                alert("Error de conexión con el servidor.");
                document.querySelector('.btn-purple').innerText = "Crear Cuenta";
                document.querySelector('.btn-purple').disabled = false;
            }
        }
    </script>
</body>
</html>