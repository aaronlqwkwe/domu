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
    <title>InmoPro - Iniciar Sesión</title>
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
        <h2 class="text-3xl font-bold text-center mb-10 tracking-tight">Login</h2>
        <div class="mb-5">
            <label class="block text-sm text-gray-400 mb-2">Email Address</label>
            <input type="email" id="log-email" placeholder="correo@ejemplo.com" class="input-dark">
        </div>
        <div class="mb-6 relative">
            <label class="block text-sm text-gray-400 mb-2">Password</label>
            <input type="password" id="log-password" placeholder="••••••••" class="input-dark">
        </div>
        
        <button onclick="hacerLogin()" class="btn-purple mt-4">Login</button>
        
        <p class="text-center text-sm text-gray-400 mt-8">Don't have an account? <a href="registro.php" class="text-[#6366f1] font-medium hover:underline">Signup</a></p>
    </div>

    <script>
        async function hacerLogin() {
            const email = document.getElementById('log-email').value;
            const password = document.getElementById('log-password').value;

            if(!email || !password) {
                alert("Por favor ingresa tu correo y contraseña");
                return;
            }

            try {
                // Salimos de 'vistas' y entramos a 'apis'
                const respuesta = await fetch('../apis/api_login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await respuesta.json();

                if(data.success) {
                    // Si el login es correcto, salimos de 'vistas' hacia index.php
                    window.location.href = '../index.php'; 
                } else {
                    alert("Error: " + data.message);
                }
            } catch (error) {
                console.error("Error:", error);
                alert("Hubo un error de conexión con el servidor.");
            }
        }
    </script>
</body>
</html>