<?php
// ==========================================
// CONTROLADOR DE VERIFICACIÓN POR ENLACE (CLICK)
// ==========================================
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../conexion.php'; 

// Validar que el token venga en la URL
if (!isset($_GET['token']) || empty(trim($_GET['token']))) {
    die("❌ Enlace de verificación inválido o corrupto.");
}

$token = trim($_GET['token']);

try {
    // 1. Buscar si existe un usuario con ese token. 
    // Evaluamos de forma segura buscando que la columna NO esté vacía y coincida con el token
    $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE codigo_verificacion = :token AND codigo_verificacion IS NOT NULL AND codigo_verificacion != ''");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // 2. Encontrado: 
        // 🛠️ CORRECCIÓN CLAVE: Dejamos el estado_cuenta en 'PENDIENTE' (para que el admin lo dé de alta)
        // y LIMPIAMOS por completo el 'codigo_verificacion' asignándole NULL para superar la Fase 1.
        $update = $conn->prepare("UPDATE usuarios SET estado_cuenta = 'PENDIENTE', codigo_verificacion = NULL WHERE id = :id");
        $update->bindParam(':id', $usuario['id']);
        
        if ($update->execute()) {
            // Renderizamos una respuesta visual limpia y profesional
            echo "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Correo Verificado - Domu</title>
                <script src='https://cdn.tailwindcss.com'></script>
            </head>
            <body class='bg-slate-900 text-white font-sans min-h-screen flex items-center justify-center p-4'>
                <div class='bg-slate-800 p-8 rounded-3xl max-w-md w-full text-center border border-slate-700 shadow-2xl'>
                    <div class='w-16 h-16 bg-emerald-500/10 text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-500/30'>
                        <svg class='w-8 h-8' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M5 13l4 4L19 7'></path>
                        </svg>
                    </div>
                    <h2 class='text-2xl font-black mb-2'>¡Correo Confirmado, " . htmlspecialchars($usuario['nombre']) . "!</h2>
                    <p class='text-sm text-slate-400 mb-6'>Tu dirección de correo electrónico ha sido validada con éxito en nuestro sistema.</p>
                    
                    <div class='bg-slate-800/50 border border-slate-700 p-4 rounded-2xl text-xs text-left text-slate-400 mb-6 space-y-2'>
                        <p class='font-bold text-amber-400 uppercase tracking-wider'>⚠️ Siguiente paso:</p>
                        <p>El Administrador de Domu ha sido notificado. Procederá a revisar tus fotografías de la INE y CURP para garantizar la seguridad de la plataforma.</p>
                        <p>Recibirás acceso total en cuanto tu documentación sea aprobada.</p>
                    </div>

                    <a href='../vistas/login.php' class='block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-xl transition text-sm shadow-lg shadow-indigo-600/20'>
                        Ir al Inicio de Sesión
                    </a>
                </div>
            </body>
            </html>";
            exit;
        } else {
            echo "❌ Ocurrió un error interno al intentar actualizar tu estado de cuenta.";
        }
    } else {
        // Si no se encuentra, es porque el token ya se eliminó (ya se verificó) o es inválido
        echo "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Enlace Inválido - Domu</title>
            <script src='https://cdn.tailwindcss.com'></script>
        </head>
        <body class='bg-slate-900 text-white font-sans min-h-screen flex items-center justify-center p-4'>
            <div class='bg-slate-800 p-8 rounded-3xl max-w-md w-full text-center border border-slate-700 shadow-2xl'>
                <div class='w-16 h-16 bg-amber-500/10 text-amber-400 rounded-full flex items-center justify-center mx-auto mb-4 border border-amber-500/30'>
                    <svg class='w-8 h-8' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'></path>
                    </svg>
                </div>
                <h2 class='text-xl font-bold mb-2'>Enlace ya utilizado o no válido</h2>
                <p class='text-sm text-slate-400 mb-6'>Este enlace ya expiró, fue procesado con anterioridad o tu cuenta ya se encuentra verificada en espera de alta.</p>
                <a href='../vistas/login.php' class='text-indigo-400 text-sm font-bold hover:underline'>Regresar al Login</a>
            </div>
        </body>
        </html>";
    }

} catch (PDOException $e) {
    die("❌ Error en el servidor de datos: " . $e->getMessage());
}
?>