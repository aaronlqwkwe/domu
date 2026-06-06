<?php
session_start();
// Si ya hay sesión activa, redirigir al index directamente
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
    <title>Domu Real Estate - Registro de Clientes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-login-signup { 
            background-image: linear-gradient(rgba(10, 10, 10, 0.85), rgba(10, 10, 10, 0.85)), url('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80'); 
            background-size: cover; 
            background-position: center; 
        }
        .bg-form-card { 
            background-color: #0f111a; 
            padding: 2.5rem; 
            border-radius: 2rem; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
            width: 100%; 
            max-width: 30rem; 
            border: 1px solid #1e293b; 
            color: white; 
        }
        .input-dark { 
            width: 100%; 
            background-color: #161925; 
            border: 1px solid #26293b; 
            border-radius: 0.85rem; 
            padding: 0.875rem 1rem; 
            color: white; 
            outline: none; 
            transition: all 0.2s;
        }
        .input-dark:focus { 
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15); 
        }
        .btn-purple { 
            width: 100%; 
            background-color: #6366f1; 
            font-weight: 700; 
            padding: 1rem; 
            border-radius: 0.85rem; 
            color: white; 
            box-shadow: 0 10px 20px -3px rgba(99, 102, 241, 0.3); 
            transition: all 0.2s; 
        }
        .btn-purple:hover:not(:disabled) { 
            background-color: #4f46e5; 
            transform: translateY(-1px);
        }
        .btn-purple:disabled {
            background-color: #3b3d54;
            color: #94a3b8;
            cursor: not-allowed;
        }
        input[type="file"]::file-selector-button { 
            background-color: #272a3d; 
            color: white; 
            border: 1px solid #3b3f5c; 
            padding: 0.4rem 0.8rem; 
            border-radius: 0.5rem; 
            margin-right: 1rem; 
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        input[type="file"]::file-selector-button:hover { 
            background-color: #3b3f5c; 
        }
    </style>
</head>
<body class="font-sans antialiased text-slate-200 bg-login-signup min-h-screen flex items-center justify-center p-4">

    <div class="bg-form-card">
        <div class="text-center mb-6">
            <h2 class="text-3xl font-black tracking-tight text-white mb-1">Crear Cuenta</h2>
            <p class="text-xs text-slate-400 font-medium">Regístrate como cliente para acceder al inventario y gestionar tus apartados legales.</p>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nombre Completo</label>
                <input type="text" id="reg-nombre" placeholder="Nombre y apellidos" class="input-dark">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Correo Electrónico</label>
                <input type="email" id="reg-email" placeholder="correo@ejemplo.com" class="input-dark">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Contraseña</label>
                <input type="password" id="reg-password" placeholder="Mínimo 8 caracteres" class="input-dark">
            </div>

            <div class="border-t border-slate-800/60 my-4 pt-4">
                <p class="text-[11px] font-bold text-indigo-400 uppercase tracking-widest mb-3">📍 Validación Obligatoria de Identidad (KYC)</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">CURP</label>
                <input type="text" id="reg-curp" placeholder="18 caracteres reglamentarios" maxlength="18" class="input-dark uppercase font-mono tracking-wider">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Identificación oficial (Frente)</label>
                <input type="file" id="reg-ine-frente" accept=".jpg, .jpeg, .png" class="input-dark" style="padding: 0.5rem 0.6rem;">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Identificación oficial (Reverso)</label>
                <input type="file" id="reg-ine-reverso" accept=".jpg, .jpeg, .png" class="input-dark" style="padding: 0.5rem 0.6rem;">
            </div>
            
            <button onclick="registrarUsuario()" class="btn-purple mt-4">Registrar Cuenta de Cliente</button>
        </div>
        
        <p class="text-center text-sm text-slate-400 mt-6">¿Ya tienes una cuenta? <a href="login.php" class="text-indigo-400 font-bold hover:underline">Inicia Sesión</a></p>
    </div>

    <script>
        async function registrarUsuario() {
            const nombre = document.getElementById('reg-nombre').value.trim();
            const email = document.getElementById('reg-email').value.trim();
            const password = document.getElementById('reg-password').value;
            const curp = document.getElementById('reg-curp').value.trim().toUpperCase();
            
            const ineFrente = document.getElementById('reg-ine-frente').files[0];
            const ineReverso = document.getElementById('reg-ine-reverso').files[0];

            if(!nombre || !email || !password || !curp || !ineFrente || !ineReverso) {
                alert("Por favor, rellena todos los campos y sube ambos lados de tu documento de identidad.");
                return;
            }

            if(curp.length !== 18) {
                alert("El identificador CURP introducido es inválido. Debe contener exactamente 18 caracteres.");
                return;
            }

            const formData = new FormData();
            formData.append('nombre', nombre);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('rol_id', 2); // ID Interno forzado: Cliente corporativo
            formData.append('curp', curp);
            formData.append('ine_frente', ineFrente);
            formData.append('ine_reverso', ineReverso);

            const btn = document.querySelector('.btn-purple');
            try {
                btn.innerText = "Procesando expediente digital...";
                btn.disabled = true;

                const respuesta = await fetch('../apis/api_registro.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await respuesta.json(); 

                if(data.success) {
    alert("¡Registro exitoso! Correo enviado. Verifica tu buzón de entrada.");

    window.location.href = 'login.php?mensaje=revisa_correo';
} else {
                    alert("Error: " + data.message);
                    btn.innerText = "Registrar Cuenta de Cliente";
                    btn.disabled = false;
                }
                
            } catch (error) {
                console.error("Error de pasarela:", error);
                alert("No se pudo establecer conexión con el clúster de autenticación.");
                btn.innerText = "Registrar Cuenta de Cliente";
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>