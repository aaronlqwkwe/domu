<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../index.php");
    exit;
}
$nombre_admin = $_SESSION['nombre'];
require_once '../conexion.php'; 

// Consultamos SOLO a los usuarios que están en estado "pendiente"
$stmt = $conn->prepare("SELECT id, nombre, email, curp, ine_frente, ine_reverso, estado_cuenta FROM usuarios WHERE estado_cuenta = 'pendiente' ORDER BY id DESC");
$stmt->execute();
$usuarios_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Pendientes - InmoAdmin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans flex h-screen overflow-hidden">

    <aside class="w-64 bg-[#111118] text-gray-400 flex flex-col shadow-2xl h-full z-20">
        <div class="p-6 border-b border-gray-800">
            <h1 class="font-bold text-2xl text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-[#6366f1]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Inmo<span class="text-[#6366f1]">Admin</span>
            </h1>
        </div>
        
        <nav class="flex-1 p-4 space-y-2">
            <a href="admin_dashboard.php" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard
            </a>

            <a href="admin_usuarios_pendientes.php" class="flex items-center gap-3 bg-[#6366f1] text-white px-4 py-3 rounded-xl transition">
                <svg class="w-5 h-5 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Usuarios por Aceptar
            </a>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <a href="../apis/api_logout.php" class="flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition text-sm font-semibold w-full">Cerrar Sesión</a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white border-b border-gray-200 py-4 px-8 flex justify-between items-center z-10">
            <h2 class="text-2xl font-bold text-gray-800">Verificación de Identidad</h2>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($nombre_admin); ?></p>
                    <p class="text-xs text-green-500 font-medium italic">Administrador</p>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nombre_admin); ?>&background=111118&color=fff" class="h-10 w-10 rounded-full border">
            </div>
        </header>

        <div class="p-8 flex-1 overflow-y-auto">
            
            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-800">Solicitudes Pendientes (<?php echo count($usuarios_pendientes); ?>)</h3>
                <p class="text-sm text-gray-500">Revisa la documentación de los usuarios antes de darles acceso al sistema.</p>
            </div>

            <?php if(empty($usuarios_pendientes)): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h4 class="text-lg font-bold text-gray-700">¡Todo al día!</h4>
                    <p class="text-gray-500">No hay usuarios pendientes de aprobación en este momento.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                    
                    <?php foreach($usuarios_pendientes as $user): ?>
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h4 class="font-bold text-lg text-gray-900"><?php echo htmlspecialchars($user['nombre']); ?></h4>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                                <p class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-1 rounded mt-2 inline-block">CURP: <?php echo htmlspecialchars($user['curp'] ?? 'N/A'); ?></p>
                            </div>
                            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold uppercase">Pendiente</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 mb-6 flex-1">
                            <div>
                                <p class="text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-wide">INE Frente</p>
                                <img src="../uploads/ine/<?php echo htmlspecialchars($user['ine_frente']); ?>" 
                                     alt="INE Frente" 
                                     class="w-full h-28 object-cover rounded-lg border border-gray-200 shadow-sm cursor-pointer hover:opacity-80 transition"
                                     onclick="window.open(this.src, '_blank')"
                                     title="Haz clic para ver en grande">
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-500 mb-1 uppercase tracking-wide">INE Reverso</p>
                                <img src="../uploads/ine/<?php echo htmlspecialchars($user['ine_reverso']); ?>" 
                                     alt="INE Reverso" 
                                     class="w-full h-28 object-cover rounded-lg border border-gray-200 shadow-sm cursor-pointer hover:opacity-80 transition"
                                     onclick="window.open(this.src, '_blank')"
                                     title="Haz clic para ver en grande">
                            </div>
                        </div>

                        <div class="flex gap-3 mt-auto pt-4 border-t border-gray-100">
                            <a href="../apis/api_estado_usuario.php?id=<?php echo $user['id']; ?>&estado=aprobado" 
                               class="flex-1 bg-green-500 hover:bg-green-600 text-white text-center py-2.5 rounded-xl font-bold transition text-sm shadow-md shadow-green-200">
                                Aprobar
                            </a>
                            <a href="../apis/api_eliminar_usuario.php?id=<?php echo $user['id']; ?>" 
                               onclick="return confirm('¿Rechazar a este usuario? Sus datos se borrarán del sistema.')" 
                               class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 text-center py-2.5 rounded-xl font-bold transition text-sm">
                                Rechazar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                </div>
            <?php endif; ?>

        </div>
    </main>

</body>
</html>