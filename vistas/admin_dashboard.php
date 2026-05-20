<?php
// 1. Encendemos el reporte de errores para seguridad
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../index.php");
    exit;
}
$nombre_admin = $_SESSION['nombre'];
require_once '../conexion.php'; 

// 1. Consulta de Usuarios
$stmt = $conn->prepare("SELECT id, nombre, email, rol_id, estado_cuenta FROM usuarios ORDER BY id DESC");
$stmt->execute();
$lista_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_usuarios = count($lista_usuarios);

// 2. Consulta de Propiedades (Lista completa para la tabla)
$stmt_prop_list = $conn->prepare("SELECT * FROM propiedad ORDER BY id DESC");
$stmt_prop_list->execute();
$lista_propiedades = $stmt_prop_list->fetchAll(PDO::FETCH_ASSOC);
$total_propiedades = count($lista_propiedades);

// 3. Obtener el histórico de apartados y su avance legal
try {
    $stmt_apartados = $conn->prepare("
        SELECT 
            a.id AS apartado_id, 
            a.fecha_apartado, 
            a.paso_legal,
            p.id AS propiedad_id,
            p.titulo AS casa_titulo, 
            p.precio AS casa_precio,
            u.nombre AS cliente_nombre,
            u.email AS cliente_correo
        FROM const_apartados a
        JOIN propiedad p ON a.propiedad_id = p.id
        JOIN usuarios u ON a.usuario_id = u.id
        ORDER BY a.id DESC
    ");
    $stmt_apartados->execute();
    $lista_apartados = $stmt_apartados->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista_apartados = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domu - Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map, #mapEditar { height: 300px; width: 100%; border-radius: 12px; z-index: 1; }
        .modal-scroll::-webkit-scrollbar { width: 6px; }
        .modal-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
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
            <a href="admin_dashboard.php" class="flex items-center gap-3 bg-[#6366f1] text-white px-4 py-3 rounded-xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard
            </a>
            <button onclick="abrirModalPropiedad()" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition w-full text-left">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nueva Propiedad
            </button>
            <a href="admin_usuarios.php" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Usuarios
            </a>
            <a href="admin_usuarios_pendientes.php" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition">
                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Usuarios por Aceptar
            </a>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <a href="../apis/api_logout.php" class="flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition text-sm font-semibold w-full">Cerrar Sesión</a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white border-b border-gray-200 py-4 px-8 flex justify-between items-center z-10">
            <h2 class="text-2xl font-bold text-gray-800">Panel de Control</h2>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($nombre_admin); ?></p>
                    <p class="text-xs text-green-500 font-medium italic">Administrador</p>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nombre_admin); ?>&background=111118&color=fff" class="h-10 w-10 rounded-full border">
            </div>
        </header>

        <div class="p-8 flex-1 overflow-y-auto">
            
            <?php if(isset($_GET['mensaje'])): ?>
                <?php if($_GET['mensaje'] == 'propiedad_guardada'): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-6 flex justify-between items-center">
                        <span class="font-bold">¡Éxito! Propiedad guardada correctamente.</span>
                        <button onclick="this.parentElement.remove()" class="text-green-700 font-bold">&times;</button>
                    </div>
                <?php elseif($_GET['mensaje'] == 'propiedad_editada'): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-xl mb-6 flex justify-between items-center">
                        <span class="font-bold">¡Éxito! La propiedad ha sido actualizada correctamente.</span>
                        <button onclick="this.parentElement.remove()" class="text-blue-700 font-bold">&times;</button>
                    </div>
                <?php elseif($_GET['mensaje'] == 'aprobada'): ?>
                    <div class="bg-indigo-100 border border-indigo-400 text-indigo-700 px-4 py-3 rounded-xl mb-6 flex justify-between items-center">
                        <span class="font-bold">¡Listo! La propiedad ha sido aprobada y ya es pública.</span>
                        <button onclick="this.parentElement.remove()" class="text-indigo-700 font-bold">&times;</button>
                    </div>
                <?php elseif($_GET['mensaje'] == 'rechazada'): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-6 flex justify-between items-center">
                        <span class="font-bold">Propiedad rechazada. El estado se ha actualizado.</span>
                        <button onclick="this.parentElement.remove()" class="text-red-700 font-bold">&times;</button>
                    </div>
                <?php elseif($_GET['mensaje'] == 'actualizado_correcto'): ?>
                    <div class="bg-emerald-100 border border-emerald-400 text-emerald-800 px-4 py-3 rounded-xl mb-6 flex justify-between items-center">
                        <span class="font-bold">¡Flujo Actualizado! El paso legal y el estado del inmueble se sincronizaron con éxito.</span>
                        <button onclick="this.parentElement.remove()" class="text-emerald-800 font-bold">&times;</button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div><p class="text-sm font-medium text-gray-500 mb-1">Propiedades</p><p class="text-3xl font-bold text-gray-900"><?php echo $total_propiedades; ?></p></div>
                    <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center text-[#6366f1]"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg></div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div><p class="text-sm font-medium text-gray-500 mb-1">Usuarios Totales</p><p class="text-3xl font-bold text-gray-900"><?php echo $total_usuarios; ?></p></div>
                    <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-500"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg></div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">Gestión de Propiedades</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-100">
                                <th class="p-4 font-bold">Imagen</th>
                                <th class="p-4 font-bold">Título</th>
                                <th class="p-4 font-bold">Precio</th>
                                <th class="p-4 font-bold">Estado</th>
                                <th class="p-4 font-bold text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            <?php foreach($lista_propiedades as $prop): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4">
                                    <img src="../uploads/<?php echo htmlspecialchars($prop['imagen']); ?>" class="h-12 w-16 object-cover rounded-lg border shadow-sm" onerror="this.src='https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=150&q=80'">
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-gray-900"><?php echo htmlspecialchars($prop['titulo']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo $prop['area_m2']; ?> m²</p>
                                </td>
                                <td class="p-4 font-medium text-gray-700">$<?php echo number_format($prop['precio'], 2); ?></td>
                                <td class="p-4">
                                    <?php 
                                        $clase_estado = 'bg-gray-100 text-gray-600';
                                        if (strtolower($prop['estado']) == 'disponible') $clase_estado = 'bg-green-100 text-green-700';
                                        if (strtolower($prop['estado']) == 'pendiente') $clase_estado = 'bg-yellow-100 text-yellow-700';
                                        if (strtolower($prop['estado']) == 'rechazado') $clase_estado = 'bg-red-100 text-red-700';
                                        if (strtolower($prop['estado']) == 'apartada') $clase_estado = 'bg-indigo-100 text-indigo-700';
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase <?php echo $clase_estado; ?>">
                                        <?php echo htmlspecialchars($prop['estado']); ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <?php if(strtolower($prop['estado']) == 'pendiente'): ?>
                                            <a href="../apis/api_aprobar_propiedad.php?id=<?php echo $prop['id']; ?>" class="flex items-center gap-1 bg-green-50 text-green-700 hover:bg-green-500 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all border border-green-200 shadow-sm">Aprobar</a>
                                            <a href="../apis/api_rechazar_propiedad.php?id=<?php echo $prop['id']; ?>" onclick="return confirm('¿Estás seguro de rechazar esta propiedad?')" class="flex items-center gap-1 bg-red-50 text-red-700 hover:bg-red-500 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all border border-red-200 shadow-sm">Rechazar</a>
                                            <div class="w-px h-6 bg-gray-200 mx-1"></div>
                                        <?php endif; ?>

                                        <button onclick="abrirModalEditar(<?php echo htmlspecialchars(json_encode($prop), ENT_QUOTES, 'UTF-8'); ?>)" class="text-indigo-600 hover:text-indigo-900 font-bold text-sm px-2 transition-colors">Editar</button>
                                        <a href="../apis/api_eliminar_propiedad.php?id=<?php echo $prop['id']; ?>" onclick="return confirm('¿Seguro que quieres borrar esta propiedad?')" class="text-red-500 hover:text-red-700 font-bold">Borrar</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Monitoreo de Apartados y Procesos Legales</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Avanza las fases del trámite, sube expedientes y genera contratos digitales.</p>
                    </div>
                    <span class="bg-indigo-50 text-indigo-700 text-xs font-extrabold px-3 py-1 rounded-full border border-indigo-100">
                        <?php echo count($lista_apartados); ?> Operaciones
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/30 text-gray-500 text-xs uppercase border-b border-gray-100">
                                <th class="p-4 font-bold">Inmueble</th>
                                <th class="p-4 font-bold">Cliente Comprador</th>
                                <th class="p-4 font-bold">Fecha Solicitud</th>
                                <th class="p-4 font-bold">Paso Actual</th>
                                <th class="p-4 font-bold text-center">Control de Trámite / Documentación</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            <?php if (count($lista_apartados) > 0): ?>
                                <?php foreach($lista_apartados as $reg): 
                                    $paso = intval($reg['paso_legal']);
                                ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4">
                                        <p class="font-bold text-gray-900"><?php echo htmlspecialchars($reg['casa_titulo']); ?></p>
                                        <p class="text-xs text-indigo-600 font-semibold">$<?php echo number_format($reg['casa_precio'], 2); ?> MXN</p>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($reg['cliente_nombre']); ?></p>
                                        <p class="text-xs text-gray-400"><?php echo htmlspecialchars($reg['cliente_correo']); ?></p>
                                    </td>
                                    <td class="p-4 text-xs text-gray-500">
                                        <?php echo date("d/m/Y g:i a", strtotime($reg['fecha_apartado'])); ?>
                                    </td>
                                    <td class="p-4">
                                        <?php if ($paso === 0): ?>
                                            <span class="px-2.5 py-1 bg-red-100 text-red-800 text-[10px] font-bold rounded-full uppercase">🔴 Rechazado</span>
                                        <?php elseif ($paso === 1): ?>
                                            <span class="px-2.5 py-1 bg-indigo-100 text-indigo-800 text-[10px] font-bold rounded-full uppercase">🟣 1. Apartado</span>
                                        <?php elseif ($paso === 2): ?>
                                            <span class="px-2.5 py-1 bg-blue-100 text-blue-800 text-[10px] font-bold rounded-full uppercase">🔵 2. Validación</span>
                                        <?php elseif ($paso === 3): ?>
                                            <span class="px-2.5 py-1 bg-amber-100 text-amber-800 text-[10px] font-bold rounded-full uppercase">🟡 3. Notaría</span>
                                        <?php elseif ($paso === 4): ?>
                                            <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded-full uppercase">🟢 4. Entregado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-col gap-3 max-w-xs mx-auto bg-gray-50 p-3 rounded-xl border border-gray-200 shadow-sm">
                                            <form action="../apis/api_cambiar_paso.php" method="POST" class="flex gap-2 w-full">
                                                <input type="hidden" name="apartado_id" value="<?php echo $reg['apartado_id']; ?>">
                                                <input type="hidden" name="propiedad_id" value="<?php echo $reg['propiedad_id']; ?>">
                                                
                                                <select name="nuevo_paso" class="bg-white border border-gray-200 text-gray-900 text-xs rounded-lg p-1.5 outline-none focus:ring-2 focus:ring-indigo-500 flex-1">
                                                    <option value="1" <?php echo $paso === 1 ? 'selected' : ''; ?>>Paso 1: Apartado Seguro</option>
                                                    <option value="2" <?php echo $paso === 2 ? 'selected' : ''; ?>>Paso 2: Validación Legal</option>
                                                    <option value="3" <?php echo $paso === 3 ? 'selected' : ''; ?>>Paso 3: Proceso Notarial</option>
                                                    <option value="4" <?php echo $paso === 4 ? 'selected' : ''; ?>>Paso 4: Llaves en Mano</option>
                                                    <option value="0" <?php echo $paso === 0 ? 'selected' : ''; ?>>❌ Cancelar Operación</option>
                                                </select>
                                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-2.5 py-1.5 rounded-lg transition shrink-0">Actualizar</button>
                                            </form>

                                            <form action="../apis/api_subir_documento_notaria.php" method="POST" enctype="multipart/form-data" class="border-t border-gray-200 pt-2">
                                                <input type="hidden" name="propiedad_id" value="<?php echo $reg['propiedad_id']; ?>">
                                                <input type="hidden" name="apartado_id" value="<?php echo $reg['apartado_id']; ?>">
                                                <div class="flex items-center gap-2">
                                                    <input type="file" name="documento_notarial" accept=".pdf" required class="block w-full text-[11px] text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                                    <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white text-[10px] font-bold px-2 py-1.5 rounded transition shrink-0">Subir</button>
                                                </div>
                                            </form>

                                            <a href="../apis/api_generar_contrato.php?apartado_id=<?php echo $reg['apartado_id']; ?>" target="_blank" class="w-full inline-flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold py-1.5 px-2 rounded-lg transition shadow-sm">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                                Generar Contrato Digital
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center p-8 text-gray-400 font-medium">No se registran solicitudes de apartado en el sistema por el momento.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">Gestión General de Usuarios</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-100">
                                <th class="p-4 font-bold">Nombre</th>
                                <th class="p-4 font-bold">Email</th>
                                <th class="p-4 font-bold text-center">Estado de Cuenta</th>
                                <th class="p-4 font-bold text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            <?php foreach($lista_usuarios as $user): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-medium text-gray-900"><?php echo htmlspecialchars($user['nombre']); ?></td>
                                <td class="p-4 text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="p-4 text-center">
                                    <?php 
                                        $color_estado = 'bg-gray-100 text-gray-600';
                                        if(isset($user['estado_cuenta'])) {
                                            if($user['estado_cuenta'] == 'aprobado') $color_estado = 'bg-green-100 text-green-700';
                                            if($user['estado_cuenta'] == 'pendiente') $color_estado = 'bg-yellow-100 text-yellow-700';
                                            if($user['estado_cuenta'] == 'baneado') $color_estado = 'bg-red-100 text-red-700';
                                        }
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase <?php echo $color_estado; ?>">
                                        <?php echo isset($user['estado_cuenta']) ? htmlspecialchars($user['estado_cuenta']) : 'N/A'; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if(isset($user['estado_cuenta']) && $user['estado_cuenta'] != 'baneado'): ?>
                                        <a href="../apis/api_estado_usuario.php?id=<?php echo $user['id']; ?>&estado=baneado" onclick="return confirm('¿Estás seguro de Banear a este usuario?')" class="text-orange-500 hover:text-orange-700 font-bold mr-3">Banear</a>
                                    <?php endif; ?>
                                    <?php if(isset($user['estado_cuenta']) && $user['estado_cuenta'] == 'baneado'): ?>
                                        <a href="../apis/api_estado_usuario.php?id=<?php echo $user['id']; ?>&estado=aprobado" onclick="return confirm('¿Quitar baneo?')" class="text-green-600 hover:text-green-700 font-bold mr-3">Desbanear</a>
                                    <?php endif; ?>
                                    <a href="../apis/api_eliminar_usuario.php?id=<?php echo $user['id']; ?>" onclick="return confirm('¿Eliminar definitivamente?')" class="text-red-500 hover:text-red-700 font-bold">Borrar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="modalPropiedad" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-6 border-b flex justify-between items-center bg-gray-50">
                <h3 class="text-xl font-bold text-gray-800">Publicar Nueva Propiedad</h3>
                <button onclick="cerrarModalPropiedad()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <form action="../apis/api_crear_propiedad.php" method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto modal-scroll space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2"><label class="block text-sm font-bold text-gray-700 mb-1">Título del Anuncio</label><input type="text" name="titulo" required class="w-full border rounded-xl px-4 py-2.5 outline-none"></div>
                    <div class="col-span-2"><label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label><textarea name="descripcion" rows="3" class="w-full border rounded-xl px-4 py-2.5 outline-none"></textarea></div>
                    <div><label class="block text-sm font-bold text-gray-700 mb-1">Precio ($ MXN)</label><input type="number" name="precio" step="0.01" required class="w-full border rounded-xl px-4 py-2.5"></div>
                    <div><label class="block text-sm font-bold text-gray-700 mb-1">Área (m²)</label><input type="number" name="area_m2" step="0.1" required class="w-full border rounded-xl px-4 py-2.5"></div>
                    <div class="col-span-2 bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                        <label class="block text-sm font-bold text-indigo-900 mb-2">📸 Foto de la Propiedad</label>
                        <input type="file" name="foto" accept="image/*" onchange="previewImagen(event, 'img-preview', 'preview-container')" required class="block w-full text-sm text-gray-500 cursor-pointer">
                        <div id="preview-container" class="hidden mt-4"><img id="img-preview" src="#" class="h-40 w-full object-cover rounded-lg border-2 border-white shadow-md"></div>
                    </div>
                    <div class="col-span-2"><label class="block text-sm font-bold text-gray-700 mb-2">📍 Ubicación aproximada</label><div id="map"></div></div>
                    <div class="col-span-2 grid grid-cols-2 gap-4 bg-gray-100 p-3 rounded-xl">
                        <div><label class="text-[10px] uppercase font-bold text-gray-500">Latitud</label><input type="text" name="latitud" id="input_lat" readonly class="w-full bg-transparent font-mono text-sm"></div>
                        <div><label class="text-[10px] uppercase font-bold text-gray-500">Longitud</label><input type="text" name="longitud" id="input_lon" readonly class="w-full bg-transparent font-mono text-sm"></div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="cerrarModalPropiedad()" class="px-6 py-2.5 text-gray-500 font-bold">Cancelar</button>
                    <button type="submit" class="px-8 py-2.5 bg-[#6366f1] text-white rounded-xl font-bold shadow-lg hover:bg-[#4f46e5]">Publicar Propiedad</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEditarPropiedad" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-6 border-b flex justify-between items-center bg-gray-50">
                <h3 class="text-xl font-bold text-gray-800">Editar Propiedad</h3>
                <button onclick="cerrarModalEditar()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <form action="../apis/api_editar_propiedad.php" method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto modal-scroll space-y-6">
                <input type="hidden" name="propiedad_id" id="edit_id">
                <input type="hidden" name="foto_actual" id="edit_foto_actual">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Título del Anuncio</label>
                        <input type="text" name="titulo" id="edit_titulo" required class="w-full border rounded-xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                        <textarea name="descripcion" id="edit_descripcion" rows="3" class="w-full border rounded-xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                    <div><label class="block text-sm font-bold text-gray-700 mb-1">Precio ($ MXN)</label><input type="number" name="precio" id="edit_precio" step="0.01" required class="w-full border rounded-xl px-4 py-2.5"></div>
                    <div><label class="block text-sm font-bold text-gray-700 mb-1">Área (m²)</label><input type="number" name="area_m2" id="edit_area" step="0.1" required class="w-full border rounded-xl px-4 py-2.5"></div>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Estado de Disponibilidad</label>
                        <select name="estado" id="edit_estado" class="w-full border rounded-xl px-4 py-2.5 bg-white outline-none">
                            <option value="disponible">Disponible</option>
                            <option value="apartada">Apartada</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="rechazado">Rechado</option>
                        </select>
                    </div>

                    <div class="col-span-2 bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                        <label class="block text-sm font-bold text-indigo-900 mb-2">📸 Cambiar Foto (Opcional)</label>
                        <input type="file" name="foto" accept="image/*" onchange="previewImagen(event, 'img-preview-edit', 'preview-container-edit')" class="block w-full text-sm text-gray-500 cursor-pointer">
                        <div id="preview-container-edit" class="mt-4"><img id="img-preview-edit" src="#" class="h-40 w-full object-cover rounded-lg border-2 border-white shadow-md"></div>
                    </div>
                    <div class="col-span-2"><label class="block text-sm font-bold text-gray-700 mb-2">📍 Modificar Ubicación en el mapa</label><div id="mapEditar"></div></div>
                    <div class="col-span-2 grid grid-cols-2 gap-4 bg-gray-100 p-3 rounded-xl">
                        <div><label class="text-[10px] uppercase font-bold text-gray-500">Latitud</label><input type="text" name="latitud" id="edit_lat" readonly class="w-full bg-transparent font-mono text-sm"></div>
                        <div><label class="text-[10px] uppercase font-bold text-gray-500">Longitud</label><input type="text" name="longitud" id="edit_lon" readonly class="w-full bg-transparent font-mono text-sm"></div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="cerrarModalEditar()" class="px-6 py-2.5 text-gray-500 font-bold">Cancelar</button>
                    <button type="submit" class="px-8 py-2.5 bg-indigo-600 text-white rounded-xl font-bold shadow-lg hover:bg-indigo-700">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map, marker;
        let mapEditar, markerEditar;

        function previewImagen(event, imgId, containerId) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById(imgId);
                output.src = reader.result;
                document.getElementById(containerId).classList.remove('hidden');
            };
            if(event.target.files[0]) reader.readAsDataURL(event.target.files[0]);
        }

        function abrirModalPropiedad() { 
            document.getElementById('modalPropiedad').classList.remove('hidden');
            setTimeout(initLeaflet, 300);
        }
        function cerrarModalPropiedad() { document.getElementById('modalPropiedad').classList.add('hidden'); }

        function abrirModalEditar(propiedad) {
            document.getElementById('modalEditarPropiedad').classList.remove('hidden');
            
            document.getElementById('edit_id').value = propiedad.id;
            document.getElementById('edit_foto_actual').value = propiedad.imagen;
            document.getElementById('edit_titulo').value = propiedad.titulo;
            document.getElementById('edit_descripcion').value = propiedad.descripcion;
            document.getElementById('edit_precio').value = propiedad.precio;
            document.getElementById('edit_area').value = propiedad.area_m2;
            document.getElementById('edit_estado').value = propiedad.estado.toLowerCase();
            document.getElementById('edit_lat').value = propiedad.latitud;
            document.getElementById('edit_lon').value = propiedad.longitud;

            document.getElementById('img-preview-edit').src = '../uploads/' + propiedad.imagen;
            document.getElementById('preview-container-edit').classList.remove('hidden');

            setTimeout(() => {
                initLeafletEditar(parseFloat(propiedad.latitud), parseFloat(propiedad.longitud));
            }, 300);
        }

        function cerrarModalEditar() { 
            document.getElementById('modalEditarPropiedad').classList.add('hidden'); 
        }

        function initLeaflet() {
            const lonLatInicial = [20.671956, -103.348821];
            if (!map) {
                map = L.map('map').setView(lonLatInicial, 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                marker = L.marker(lonLatInicial, { draggable: true }).addTo(map);
                
                function act(lat, lng) {
                    document.getElementById("input_lat").value = lat.toFixed(6);
                    document.getElementById("input_lon").value = lng.toFixed(6);
                }
                act(lonLatInicial[0], lonLatInicial[1]);
                marker.on('dragend', () => act(marker.getLatLng().lat, marker.getLatLng().lng));
                map.on('click', (e) => { marker.setLatLng(e.latlng); act(e.latlng.lat, e.latlng.lng); });
            } else { map.invalidateSize(); }
        }

        function initLeafletEditar(lat, lng) {
            const coords = [lat ? lat : 20.671956, lng ? lng : -103.348821];
            
            if (!mapEditar) {
                mapEditar = L.map('mapEditar').setView(coords, 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapEditar);
                markerEditar = L.marker(coords, { draggable: true }).addTo(mapEditar);

                function actEditar(lt, lg) {
                    document.getElementById("edit_lat").value = lt.toFixed(6);
                    document.getElementById("edit_lon").value = lg.toFixed(6);
                }

                markerEditar.on('dragend', () => actEditar(markerEditar.getLatLng().lat, markerEditar.getLatLng().lng));
                mapEditar.on('click', (e) => { markerEditar.setLatLng(e.latlng); actEditar(e.latlng.lat, e.latlng.lng); });
            } else {
                mapEditar.setView(coords, 14);
                markerEditar.setLatLng(coords);
                mapEditar.invalidateSize();
            }
        }
    </script>
</body>
</html>