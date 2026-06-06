<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../index.php");
    exit;
}
$nombre_admin = $_SESSION['nombre'];
require_once '../conexion.php'; 

// Consultamos SOLO a los usuarios que están en estado "pendiente" (Incluyendo el rol_id)
$stmt = $conn->prepare("SELECT id, nombre, email, curp, ine_frente, ine_reverso, estado_cuenta, rol_id FROM usuarios WHERE estado_cuenta = 'pendiente' ORDER BY id DESC");
$stmt->execute();
$usuarios_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Pendientes - Domu Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal-scroll::-webkit-scrollbar { width: 8px; }
        .modal-scroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 8px; }
        .modal-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
        .modal-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .fade-in { animation: fadeIn 0.25s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.98); } to { opacity: 1; transform: scale(1); } }
    </style>
</head>
<body class="bg-slate-50 font-sans flex h-screen overflow-hidden antialiased text-slate-900">

    <aside class="w-72 bg-[#0f111a] text-slate-400 flex flex-col shadow-2xl h-full z-30 shrink-0">
        <div class="p-6 border-b border-slate-800/60 flex items-center justify-between">
            <h1 class="font-black text-2xl text-white flex items-center gap-3 tracking-tight">
                <div class="w-8 h-8 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                </div>
                <span>Domu<span class="text-indigo-500 font-medium">Admin</span></span>
            </h1>
            <span class="text-[10px] bg-slate-800 text-slate-400 px-2 py-0.5 rounded font-mono border border-slate-700">v2.5</span>
        </div>
        
        <nav class="flex-1 p-5 space-y-2 overflow-y-auto modal-scroll">
            <p class="px-4 text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2">Sistemas Core</p>
            
            <a href="admin_dashboard.php" class="flex items-center gap-3.5 hover:bg-slate-800/60 hover:text-white px-4 py-3 rounded-xl font-medium transition text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path></svg>
                Control Central
            </a>

            <div class="pt-4 my-2 border-t border-slate-800/50"></div>
            <p class="px-4 text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2">Ecosistema de Usuarios</p>

            <a href="admin_usuarios.php" class="flex items-center gap-3.5 hover:bg-slate-800/60 hover:text-white px-4 py-3 rounded-xl font-medium transition text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Directorio Usuarios
            </a>

            <a href="admin_usuarios_pendientes.php" class="flex items-center gap-3.5 bg-indigo-600 text-white px-4 py-3 rounded-xl font-semibold shadow-xl shadow-indigo-600/10 transition">
                <div class="relative">
                    <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-amber-400 ring-2 ring-indigo-650"></span>
                </div>
                Validaciones Pendientes
            </a>
        </nav>

        <div class="p-5 border-t border-slate-800/60 bg-[#0a0b11]">
            <a href="../apis/api_logout.php" class="flex items-center justify-center gap-2 bg-rose-600 hover:bg-rose-700 text-white px-4 py-3 rounded-xl font-bold transition text-sm shadow-lg shadow-rose-600/10 w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Desconectarse del Panel
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full bg-slate-50 overflow-hidden">
        <header class="bg-white border-b border-slate-200 py-4 px-10 flex justify-between items-center z-20 shrink-0 shadow-sm">
            <div>
                <h2 class="text-2xl font-black text-slate-800 tracking-tight">Verificación de Identidad Perimetral</h2>
                <p class="text-xs text-slate-400 font-medium">Filtro de seguridad: Valida la documentación oficial antes de otorgar credenciales de acceso.</p>
            </div>
            
            <div class="flex items-center gap-5 bg-slate-50 p-1.5 pr-5 rounded-full border border-slate-200">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nombre_admin); ?>&background=4f46e5&color=fff&bold=true" class="h-9 w-9 rounded-full border border-white shadow-sm">
                <div class="text-left">
                    <p class="text-xs font-black text-slate-800 leading-3"><?php echo htmlspecialchars($nombre_admin); ?></p>
                    <p class="text-[10px] text-indigo-600 font-bold tracking-wider uppercase mt-0.5">Super Administrador</p>
                </div>
            </div>
        </header>

        <div class="p-10 flex-1 overflow-y-auto modal-scroll space-y-6">
            
            <div class="flex justify-between items-center bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm">
                <div>
                    <h3 class="text-lg font-black text-slate-800 tracking-tight">Solicitudes en Fila de Espera (<?php echo count($usuarios_pendientes); ?>)</h3>
                    <p class="text-xs text-slate-400 font-medium">Los usuarios no podrán iniciar sesión ni ver inventario hasta que confirmes sus datos.</p>
                </div>
            </div>

            <?php if(empty($usuarios_pendientes)): ?>
                <div class="bg-white rounded-3xl border border-slate-200/60 p-16 text-center shadow-sm fade-in">
                    <div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-emerald-100 shadow-sm">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h4 class="text-xl font-black text-slate-800 tracking-tight">¡Bandeja de entrada limpia!</h4>
                    <p class="text-sm text-slate-400 mt-1">No existen expedientes o identidades pendientes por validar en este momento.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 fade-in">
                    
                    <?php foreach($usuarios_pendientes as $user): ?>
                    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200/60 flex flex-col justify-between hover:border-slate-300 transition duration-150">
                        <div>
                            <div class="flex justify-between items-start mb-4 gap-2">
                                <div class="truncate">
                                    <h4 class="font-black text-base text-slate-900 truncate tracking-tight"><?php echo htmlspecialchars($user['nombre']); ?></h4>
                                    <p class="text-xs font-mono text-slate-400 truncate mt-0.5"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                                <span class="bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider shrink-0 shadow-sm animate-pulse">Pendiente</span>
                            </div>

                            <div class="flex flex-wrap gap-1.5 mb-5">
                                <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2.5 py-1 rounded-xl border border-slate-200 font-mono shadow-sm">
                                    CURP: <?php echo htmlspecialchars($user['curp'] ?? 'N/A'); ?>
                                </span>
                                
                                <span class="text-[10px] font-extrabold px-2.5 py-1 rounded-xl border shadow-sm <?php 
                                    if ($user['rol_id'] == 1) echo 'bg-slate-100 text-slate-700 border-slate-300';
                                    elseif ($user['rol_id'] == 2) echo 'bg-amber-50 text-amber-700 border-amber-200';
                                    elseif ($user['rol_id'] == 3) echo 'bg-indigo-50 text-indigo-700 border-indigo-200';
                                    else echo 'bg-rose-50 text-rose-700 border-rose-200';
                                ?>">
                                    <?php 
                                        if ($user['rol_id'] == 1) echo '⚙️ Admin (ID: 1)';
                                        elseif ($user['rol_id'] == 2) echo '💼 Agente (ID: 2)';
                                        elseif ($user['rol_id'] == 3) echo '👤 Cliente (ID: 3)';
                                        else echo '❓ Rol Desconocido (ID: ' . $user['rol_id'] . ')';
                                    ?>
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3 mb-6">
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-wider">Identificación Frente</p>
                                    <div class="relative group overflow-hidden rounded-xl border border-slate-200 shadow-sm bg-slate-50">
                                        <img src="../uploads/ine/<?php echo htmlspecialchars($user['ine_frente']); ?>" 
                                             class="w-full h-28 object-cover cursor-pointer group-hover:scale-105 transition duration-200"
                                             onclick="window.open(this.src, '_blank')"
                                             title="Ver en pestaña completa"
                                             onerror="this.src='https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=150&q=80'">
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 mb-1.5 uppercase tracking-wider">Identificación Reverso</p>
                                    <div class="relative group overflow-hidden rounded-xl border border-slate-200 shadow-sm bg-slate-50">
                                        <img src="../uploads/ine/<?php echo htmlspecialchars($user['ine_reverso']); ?>" 
                                             class="w-full h-28 object-cover cursor-pointer group-hover:scale-105 transition duration-200"
                                             onclick="window.open(this.src, '_blank')"
                                             title="Ver en pestaña completa"
                                             onerror="this.src='https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=150&q=80'">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-4 border-t border-slate-100">
                            <button onclick="cambiarEstado(<?php echo $user['id']; ?>, 'aprobado')" 
                               class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white text-center py-2.5 rounded-xl font-black transition text-xs shadow-md shadow-emerald-600/10">
                                Aprobar Acceso
                            </button>
                            
                            <a href="../apis/api_eliminar_usuario.php?id=<?php echo $user['id']; ?>" 
                               onclick="return confirm('¿Rechazar a este usuario de forma permanente? Se eliminarán sus llaves de acceso e imágenes.')" 
                               class="flex-1 bg-rose-50 hover:bg-rose-100 text-rose-600 text-center py-2.5 rounded-xl font-bold transition text-xs border border-rose-100">
                                Rechazar Registro
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                </div>
            <?php endif; ?>

        </div>
    </main>

    <script>
        async function cambiarEstado(idUsuario, estado) {
            const formData = new FormData();
            formData.append('id_usuario', idUsuario);
            formData.append('estado', estado);

            try {
                const respuesta = await fetch('../apis/api_estado_usuario.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await respuesta.json();
                alert(data.message);
                
                if(data.success) {
                    location.reload(); 
                }
                
            } catch (error) {
                console.error("Error global en el ecosistema fetch:", error);
                alert("Ocurrió un error de comunicación al intentar cambiar el estado de la cuenta.");
            }
        }
    </script>
</body>
</html>