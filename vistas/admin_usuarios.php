<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../index.php");
    exit;
}
$nombre_admin = $_SESSION['nombre'];
require_once '../conexion.php'; 

// Consultamos a TODOS los usuarios (excepto al admin actual para que no se borre a sí mismo)
$stmt = $conn->prepare("SELECT id, nombre, email, curp, rol_id, estado_cuenta FROM usuarios WHERE id != :mi_id ORDER BY id DESC");
$stmt->bindParam(':mi_id', $_SESSION['usuario_id']);
$stmt->execute();
$todos_los_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - DomuAdmin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans flex h-screen overflow-hidden">

    <aside class="w-64 bg-[#111118] text-gray-400 flex flex-col shadow-2xl h-full z-20">
        <div class="p-6 border-b border-gray-800">
            <h1 class="font-bold text-2xl text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-[#6366f1]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Domu<span class="text-[#6366f1]">Admin</span>
            </h1>
        </div>
        
        <nav class="flex-1 p-4 space-y-2">
            <a href="admin_dashboard.php" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition">
                Dashboard
            </a>
            <a href="admin_usuarios.php" class="flex items-center gap-3 bg-[#6366f1] text-white px-4 py-3 rounded-xl transition shadow-lg shadow-indigo-500/30">
                Gestión de Usuarios
            </a>
            <a href="admin_usuarios_pendientes.php" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition">
                Usuarios por Aceptar
            </a>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <a href="../apis/api_logout.php" class="flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition text-sm font-semibold w-full">Cerrar Sesión</a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white border-b border-gray-200 py-4 px-8 flex justify-between items-center z-10">
            <h2 class="text-2xl font-bold text-gray-800">Directorio de Usuarios</h2>
        </header>

        <div class="p-8 flex-1 overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                            <th class="p-4 font-semibold">Nombre / Email</th>
                            <th class="p-4 font-semibold">CURP</th>
                            <th class="p-4 font-semibold">Estado</th>
                            <th class="p-4 font-semibold">Rol (Privilegios)</th>
                            <th class="p-4 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-gray-700">
                        <?php foreach($todos_los_usuarios as $user): ?>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="p-4">
                                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($user['nombre']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                            </td>
                            <td class="p-4 font-mono text-xs"><?php echo htmlspecialchars($user['curp'] ?? 'N/A'); ?></td>
                            <td class="p-4">
                                <?php if($user['estado_cuenta'] == 'aprobado'): ?>
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold uppercase">Aprobado</span>
                                <?php elseif($user['estado_cuenta'] == 'pendiente'): ?>
                                    <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs font-bold uppercase">Pendiente</span>
                                <?php else: ?>
                                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold uppercase">Baneado</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 font-medium text-gray-600">
                                <?php 
                                    if($user['rol_id'] == 1) echo "Administrador";
                                    elseif($user['rol_id'] == 3) echo "Cliente";
                                    elseif($user['rol_id'] == 2) echo "Agente Inmobiliario";
                                ?>
                            </td>
                            <td class="p-4 text-right space-x-2">
                                <button onclick="abrirModalEditar(<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>)" class="text-indigo-600 hover:text-indigo-900 font-bold">Editar</button>
                                <button onclick="eliminarUsuario(<?php echo $user['id']; ?>)" class="text-red-500 hover:text-red-700 font-medium">Borrar</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="modalEditar" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-8 w-full max-w-md shadow-2xl relative mx-4">
            <button onclick="cerrarModalEditar()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h3 class="text-2xl font-bold mb-6 text-gray-800">Editar Perfil</h3>
            <form onsubmit="guardarEdicion(event)" class="space-y-4">
                <input type="hidden" name="id_usuario" id="edit_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                    <input type="text" name="nombre" id="edit_nombre" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                    <input type="email" name="email" id="edit_email" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cambiar Rol</label>
                    <select name="rol_id" id="edit_rol" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-indigo-500 focus:border-indigo-500 outline-none font-semibold text-indigo-700 bg-indigo-50">
                        <option value="1">Administrador</option>
                        <option value="2">Agente Inmobiliario</option>
                        <option value="3">Cliente</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition mt-4 shadow-md shadow-indigo-500/20">Actualizar Datos</button>
            </form>
        </div>
    </div>

    <script>
        function abrirModalEditar(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_nombre').value = user.nombre;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_rol').value = user.rol_id;
            
            document.getElementById('modalEditar').classList.remove('hidden');
            document.getElementById('modalEditar').classList.add('flex');
        }
        
        function cerrarModalEditar() {
            document.getElementById('modalEditar').classList.add('hidden');
            document.getElementById('modalEditar').classList.remove('flex');
        }

        async function guardarEdicion(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            try {
                const resp = await fetch('../apis/api_editar_usuario.php', { 
                    method: 'POST', 
                    body: formData 
                });
                const data = await resp.json();
                
                alert(data.message);
                if(data.success) {
                    location.reload();
                }
            } catch (err) { 
                alert("Error de conexión al procesar la solicitud."); 
            }
        }

        function eliminarUsuario(id) {
            if(confirm("¿Estás seguro de borrar este usuario permanentemente?")) {
                window.location.href = `../apis/api_eliminar_usuario.php?id=${id}`;
            }
        }
    </script>
</body>
</html>