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
    </style>
</head>
<body class="font-sans antialiased text-gray-800 bg-login-signup min-h-screen flex items-center justify-center p-4">

    <div class="bg-form-card">
        <h2 class="text-3xl font-bold text-center mb-10 tracking-tight">Signup</h2>
        <div class="mb-5">
            <label class="block text-sm text-gray-400 mb-2">Full Name</label>
            <input type="text" id="reg-nombre" placeholder="Nombre completo" class="input-dark">
        </div>
        <div class="mb-5">
            <label class="block text-sm text-gray-400 mb-2">Email Address</label>
            <input type="email" id="reg-email" placeholder="correo@ejemplo.com" class="input-dark">
        </div>
        <div class="mb-6 relative">
            <label class="block text-sm text-gray-400 mb-2">Password</label>
            <input type="password" id="reg-password" placeholder="Min. 8 characters" class="input-dark">
        </div>
        
        <button onclick="registrarUsuario()" class="btn-purple mt-4">Create Account</button>
        
        <p class="text-center text-sm text-gray-400 mt-8">Already have an account? <a href="login.php" class="text-[#6366f1] font-medium hover:underline">Login</a></p>
    </div>

    <script>
        async function registrarUsuario() {
            const nombre = document.getElementById('reg-nombre').value;
            const email = document.getElementById('reg-email').value;
            const password = document.getElementById('reg-password').value;

            if(!nombre || !email || !password) {
                alert("Por favor llena todos los campos");
                return;
            }

            try {
                // Salimos de 'vistas' y entramos a 'apis'
                const respuesta = await fetch('../apis/api_registro.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nombre, email, password })
                });

                const data = await respuesta.json();
                
                if(data.success) {
                    alert(data.message);
                    // Como login.php está en la misma carpeta, no necesitamos el ../
                    window.location.href = 'login.php';
                } else {
                    alert("Error: " + data.message);
                }
            } catch (error) {
                console.error("Error:", error);
                alert("Error de conexión con el servidor.");
            }
        }
    </script>
</body>
</html>