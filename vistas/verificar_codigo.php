<?php
session_start();
// Si no hay un correo en sesión esperando validarse, lo regresamos al registro
if (!isset($_SESSION['email_verificar'])) {
    header("Location: registro.php"); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Recibido - Domu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-login-signup { 
            background-image: linear-gradient(rgba(10, 10, 10, 0.85), rgba(10, 10, 10, 0.85)), url('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80'); 
            background-size: cover; 
            background-position: center; 
        }
        .bg-form-card { 
            background-color: #101115; 
            padding: 3rem 2.5rem; 
            border-radius: 2rem; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
            width: 100%; 
            max-width: 28rem; 
            border: 1px solid #27272a; 
            color: white; 
        }
        .btn-purple { 
            width: 100%; 
            background-color: #6366f1; 
            font-weight: 600; 
            padding: 0.875rem; 
            border-radius: 0.75rem; 
            color: white; 
            transition: all 0.2s; 
            display: inline-block;
            text-align: center;
        }
        .btn-purple:hover { background-color: #4f46e5; }
    </style>
</head>
<body class="font-sans antialiased bg-login-signup min-h-screen flex items-center justify-center p-4">

    <div class="bg-form-card text-center space-y-6">
        
        <div class="inline-flex p-4 bg-indigo-500/10 rounded-full text-indigo-400 mb-2 animate-pulse text-4xl">
            ⏳
        </div>

        <h2 class="text-3xl font-bold tracking-tight text-white">¡Registro Recibido!</h2>
        
        <div class="text-gray-300 text-sm space-y-4 text-left bg-zinc-900/50 p-4 rounded-xl border border-zinc-800">
            <p>Hemos recibido tus documentos correctamente. Para continuar, sigue estos dos pasos:</p>
            <ol class="list-decimal list-inside space-y-2 text-xs text-gray-400">
                <li><span class="text-indigo-400 font-semibold">Verifica tu buzón:</span> Hemos enviado un enlace de confirmación a <strong class="text-gray-200"><?php echo htmlspecialchars($_SESSION['email_verificar']); ?></strong>. Haz clic en el botón dentro del correo para validar tu bandeja.</li>
                <li><span class="text-indigo-400 font-semibold">Revisión de INE:</span> El administrador validará tus fotografías adjuntas.</li>
            </ol>
        </div>

        <p class="text-xs text-gray-500">
            Una vez completada la revisión regulatoria de tu documentación, recibirás un correo de alta y podrás ingresar a la plataforma.
        </p>

        <div class="pt-2">
            <a href="login.php" class="btn-purple">Entendido, ir al Inicio</a>
        </div>
    </div>

</body>
</html>