<?php
// ==========================================
// 1. CONFIGURACIÓN DE ERRORES Y SEGURIDAD
// ==========================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Control de acceso estricto para el Rol de Administrador (ID: 1)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../index.php");
    exit;
}
$nombre_admin = $_SESSION['nombre'];

// Conexión obligatoria al motor de la base de datos
require_once '../conexion.php'; 

// ==========================================
// 2. CAPA DE CONSULTAS Y EXTRACCIÓN DE DATOS
// ==========================================

$stmt = $conn->prepare("
    SELECT 
        id, 
        nombre, 
        email, 
        rol_id, 
        estado_cuenta,
        codigo_verificacion,
        CASE 
            WHEN codigo_verificacion IS NULL OR trim(codigo_verificacion) = '' OR trim(codigo_verificacion) = '0' THEN 1 
            ELSE 0 
        END AS correo_verificado_interno
    FROM usuarios 
    ORDER BY id DESC
");
$stmt->execute();
$lista_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_usuarios = count($lista_usuarios);

$stmt_prop_list = $conn->prepare("SELECT * FROM propiedad ORDER BY id DESC");
$stmt_prop_list->execute();
$lista_propiedades = $stmt_prop_list->fetchAll(PDO::FETCH_ASSOC);
$total_propiedades = count($lista_propiedades);

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
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domu Real Estate - Panel Avanzado de Administración Global</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        #map, #mapEditar { 
            height: 340px; 
            width: 100%; 
            border-radius: 16px; 
            z-index: 10; 
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
        }
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
            
            <a href="admin_dashboard.php" class="flex items-center gap-3.5 bg-indigo-600 text-white px-4 py-3 rounded-xl font-semibold shadow-xl shadow-indigo-600/10 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z"></path>
                </svg>
                Control Central
            </a>

            <button onclick="abrirModalPropiedad()" class="flex items-center gap-3.5 hover:bg-slate-800/60 hover:text-white px-4 py-3 rounded-xl transition w-full text-left font-medium text-slate-400">
                <div class="w-5 h-5 bg-emerald-500/10 text-emerald-400 rounded flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                </div>
                Publicar Inmueble
            </button>

            <a href="admin_citas.php" class="flex items-center justify-between hover:bg-slate-800/60 hover:text-white px-4 py-3 rounded-xl transition w-full text-left font-medium text-slate-400 group">
                <div class="flex items-center gap-3.5">
                    <div class="w-5 h-5 bg-amber-500/10 text-amber-400 rounded flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <span>Bandeja de Citas</span>
                </div>
                <span class="text-[9px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2 py-0.5 rounded-md uppercase tracking-wider group-hover:bg-amber-500 group-hover:text-slate-950 transition-all duration-300">Revisar</span>
            </a>

            <div class="pt-4 my-2 border-t border-slate-800/50"></div>
            <p class="px-4 text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2">Ecosistema de Usuarios</p>

            <a href="admin_usuarios.php" class="flex items-center gap-3.5 hover:bg-slate-800/60 hover:text-white px-4 py-3 rounded-xl font-medium transition text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Directorio Usuarios
            </a>

            <a href="admin_usuarios_pendientes.php" class="flex items-center gap-3.5 hover:bg-slate-800/60 hover:text-white px-4 py-3 rounded-xl font-medium transition text-slate-400">
                <div class="relative">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-amber-500 ring-2 ring-[#0f111a]"></span>
                </div>
                Validaciones Pendientes
            </a>
        </nav>
        <a href="admin_trust_factor.php" class="flex items-center justify-between p-4 bg-[#161925]/80 hover:bg-[#1c2030] border border-slate-800 hover:border-indigo-500/40 rounded-2xl transition-all group max-w-xs m-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-indigo-600/10 group-hover:bg-indigo-600/20 text-indigo-400 rounded-xl flex items-center justify-center border border-indigo-500/10 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-black text-slate-200 group-hover:text-white transition">Trust Factor</p>
                    <p class="text-[10px] text-slate-500 font-medium">Moderar reputación</p>
                </div>
            </div>
            <div class="text-slate-600 group-hover:text-indigo-400 transition-colors pl-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
        </a>

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
                <h2 class="text-2xl font-black text-slate-800 tracking-tight">Consola de Control Integral</h2>
                <p class="text-xs text-slate-400 font-medium">Monitoreo global de operaciones, inventario de inmuebles y seguridad.</p>
            </div>
            
            <div class="flex items-center gap-5 bg-slate-50 p-1.5 pr-5 rounded-full border border-slate-200">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nombre_admin); ?>&background=4f46e5&color=fff&bold=true" class="h-9 w-9 rounded-full border border-white shadow-sm">
                <div class="text-left">
                    <p class="text-xs font-black text-slate-800 leading-3"><?php echo htmlspecialchars($nombre_admin); ?></p>
                    <p class="text-[10px] text-indigo-600 font-bold tracking-wider uppercase mt-0.5">Super Administrador</p>
                </div>
            </div>
        </header>

        <div class="p-10 flex-1 overflow-y-auto space-y-10 modal-scroll">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-200/60 flex items-center justify-between relative overflow-hidden group">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Inventario Registrado</p>
                        <p class="text-4xl font-black text-slate-900 tracking-tight"><?php echo $total_propiedades; ?> <span class="text-xs text-slate-400 font-normal">Propiedades</span></p>
                    </div>
                    <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center border border-indigo-100 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                </div>
                
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-200/60 flex items-center justify-between relative overflow-hidden group">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Cuentas Incorporadas</p>
                        <p class="text-4xl font-black text-slate-900 tracking-tight"><?php echo $total_usuarios; ?> <span class="text-xs text-slate-400 font-normal">Usuarios Globales</span></p>
                    </div>
                    <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center border border-emerald-100 shadow-sm">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/70 overflow-hidden">
                <div class="p-6 bg-slate-50/60 border-b border-slate-200">
                    <h3 class="text-lg font-black text-slate-800 tracking-tight">Catálogo de Propiedades Indexadas</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100/70 text-slate-500 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                                <th class="p-5">Inmueble</th>
                                <th class="p-5">Información Detallada</th>
                                <th class="p-5">Precio</th>
                                <th class="p-5">Estatus</th>
                                <th class="p-5 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-100">
                            <?php foreach($lista_propiedades as $prop): ?>
                            <tr class="hover:bg-slate-50/80 transition font-medium text-slate-800">
                                <td class="p-5">
                                    <div class="flex items-center gap-4">
                                        <img src="../uploads/<?php echo htmlspecialchars($prop['imagen']); ?>" class="h-14 w-20 object-cover rounded-xl border border-slate-200 shadow-sm" onerror="this.src='https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=150&q=80'">
                                        <p class="font-black text-slate-900"><?php echo htmlspecialchars($prop['titulo']); ?></p>
                                    </div>
                                </td>
                                <td class="p-5"><span class="text-[11px] font-bold text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-md"><?php echo $prop['area_m2']; ?> m²</span></td>
                                <td class="p-5 font-mono font-bold">$<?php echo number_format($prop['precio'], 2); ?></td>
                                <td class="p-5">
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border shadow-sm">
                                        <?php echo htmlspecialchars($prop['estado']); ?>
                                    </span>
                                </td>
                                <td class="p-5 text-center">
                                    <button onclick="abrirModalEditar(<?php echo htmlspecialchars(json_encode($prop), ENT_QUOTES, 'UTF-8'); ?>)" class="text-indigo-600 font-bold text-xs mx-2 hover:underline">Editar</button>
                                    <a href="../apis/api_eliminar_propiedad.php?id=<?php echo $prop['id']; ?>" onclick="return confirm('¿Seguro que deseas eliminar esta propiedad?')" class="text-rose-600 font-bold text-xs hover:underline">Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/70 overflow-hidden">
                <div class="p-6 bg-slate-50/60 border-b border-slate-200">
                    <h3 class="text-lg font-black text-slate-800 tracking-tight">Monitoreo Legal y Gestión de Expedientes</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100/70 text-slate-500 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                                <th class="p-5">Propiedad</th>
                                <th class="p-5">Comprador</th>
                                <th class="p-5">Fase</th>
                                <th class="p-5 text-center">Control de Trámite</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-100">
                            <?php foreach($lista_apartados as $reg): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="p-5 font-bold"><?php echo htmlspecialchars($reg['casa_titulo']); ?></td>
                                <td class="p-5"><?php echo htmlspecialchars($reg['cliente_nombre']); ?></td>
                                <td class="p-5"><span class="px-3 py-1 bg-indigo-50 text-indigo-700 text-[10px] font-extrabold rounded-full">Paso <?php echo $reg['paso_legal']; ?></span></td>
                                <td class="p-5">
                                    <div class="flex flex-col gap-2 max-w-xs mx-auto">
                                        <a href="../apis/api_generar_contrato.php?apartado_id=<?php echo $reg['apartado_id']; ?>" target="_blank" class="bg-emerald-600 text-white text-[11px] font-black text-center py-1.5 px-3 rounded-xl">Contrato Digital</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200/70 overflow-hidden">
                <div class="p-6 bg-slate-50/60 border-b border-slate-200">
                    <h3 class="text-lg font-black text-slate-800 tracking-tight">Estatus de Seguridad de Usuarios (Clientes)</h3>
                    <p class="text-xs text-slate-400">Verificación perimetral: Detecta quién ya validó su correo y espera tu acceso completo.</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100/70 text-slate-500 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                                <th class="p-5">Nombre Completo</th>
                                <th class="p-5">Email</th>
                                <th class="p-5">Rol Asignado</th>
                                <th class="p-5 text-center">Correo Verificado</th>
                                <th class="p-5 text-center">Estatus Cuenta</th>
                                <th class="p-5 text-center">Acciones de Acceso</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-100">
                            <?php foreach($lista_usuarios as $user): ?>
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="p-5 font-bold text-slate-900"><?php echo htmlspecialchars($user['nombre']); ?></td>
                                <td class="p-5 text-slate-600 font-mono text-xs"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="p-5">
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded bg-slate-100 text-slate-700 border">
                                        <?php echo ($user['rol_id'] == 3) ? '👤 Cliente' : (($user['rol_id'] == 1) ? '⚙️ Admin' : '💼 Agente'); ?>
                                    </span>
                                </td>
                                
                                <td class="p-5 text-center">
                                    <?php if (intval($user['correo_verificado_interno']) === 1): ?>
                                        <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold border border-emerald-200 shadow-sm">
                                            ✓ Verificado
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 px-3 py-1 rounded-full text-xs font-bold border border-amber-200 shadow-sm">
                                            ⚠ Sin Verificar
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-5 text-center">
                                    <?php 
                                        $estado_db = trim(strtoupper($user['estado_cuenta']));
                                        $color_estado = 'bg-slate-100 text-slate-600 border-slate-200';
                                        $texto_estado = $estado_db;
                                        
                                        if($estado_db === 'APROBADO') {
                                            $color_estado = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                            $texto_estado = 'Acceso Total';
                                        } elseif(intval($user['correo_verificado_interno']) === 1 && $estado_db === 'PENDIENTE') {
                                            $color_estado = 'bg-sky-50 text-sky-700 border-sky-200 animate-pulse';
                                            $texto_estado = 'Espera de Acceso';
                                        } elseif($estado_db === 'PENDIENTE') {
                                            $color_estado = 'bg-amber-50 text-amber-600 border-amber-200';
                                            $texto_estado = 'Falta Validar Mail';
                                        } elseif($estado_db === 'BANEADO') {
                                            $color_estado = 'bg-rose-50 text-rose-700 border-rose-200';
                                            $texto_estado = 'SUSPENDIDO';
                                        }
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border shadow-sm tracking-wider <?php echo $color_estado; ?>">
                                        <?php echo $texto_estado; ?>
                                    </span>
                                </td>
                                <td class="p-5 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <?php if(intval($user['correo_verificado_interno']) === 1 && $estado_db !== 'APROBADO'): ?>
                                            <button onclick="ejecutarCambioEstado(<?php echo $user['id']; ?>, 'APROBADO')" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-xl font-black text-xs transition shadow-sm">Dar Acceso Completo</button>
                                        <?php endif; ?>

                                        <?php if($estado_db === 'APROBADO'): ?>
                                            <button onclick="if(confirm('¿Bloquear acceso?')) ejecutarCambioEstado(<?php echo $user['id']; ?>, 'BANEADO')" class="text-amber-600 text-xs font-bold px-2 py-1">Banear</button>
                                        <?php endif; ?>
                                        
                                        <?php if($estado_db === 'BANEADO'): ?>
                                            <button onclick="ejecutarCambioEstado(<?php echo $user['id']; ?>, 'APROBADO')" class="text-emerald-600 text-xs font-bold px-2 py-1">Reactivar</button>
                                        <?php endif; ?>
                                        
                                        <a href="../apis/api_eliminar_usuario.php?id=<?php echo $user['id']; ?>" onclick="return confirm('¿Purgar permanentemente?')" class="text-rose-600 text-xs font-bold px-2 py-1">Eliminar</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="modalEditar" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 fade-in">
        <div class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden border border-slate-100">
            <header class="p-6 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-black text-slate-800">Actualizar Datos de Inmueble</h3>
                    <p class="text-xs text-slate-400">Modifica los detalles técnicos y la geolocalización.</p>
                </div>
                <button onclick="cerrarModalEditar()" class="text-slate-400 hover:text-slate-600 text-2xl font-bold">&times;</button>
            </header>

            <form action="../apis/api_editar_propiedad.php" method="POST" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-8 space-y-6 modal-scroll">
                <input type="hidden" name="id" id="edit_id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Título de la Propiedad</label>
                        <input type="text" name="titulo" id="edit_titulo" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Precio (MXN)</label>
                        <input type="number" step="0.01" name="precio" id="edit_precio" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Área Terreno (m²)</label>
                        <input type="number" name="area_m2" id="edit_area" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Estado del Estatus</label>
                        <select name="estado" id="edit_estado" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-bold">
                            <option value="Disponible">Disponible</option>
                            <option value="Apartado">Apartado</option>
                            <option value="Vendido">Vendido</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Descripción Completa</label>
                    <textarea name="descripcion" id="edit_descripcion" rows="3" required class="w-full bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 leading-relaxed"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Latitud</label>
                        <input type="text" name="latitud" id="edit_latitud" readonly class="w-full bg-slate-100 border border-slate-200 text-slate-500 rounded-xl px-4 py-2.5 text-sm font-mono cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Longitud</label>
                        <input type="text" name="longitud" id="edit_longitud" readonly class="w-full bg-slate-100 border border-slate-200 text-slate-500 rounded-xl px-4 py-2.5 text-sm font-mono cursor-not-allowed">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Reubicar Pin en el Mapa (Haz click o arrastra el marcador)</label>
                    <div id="mapEditar"></div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Fotografía (Dejar vacío para conservar la actual)</label>
                    <input type="file" name="imagen" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>

                <footer class="pt-4 border-t flex justify-end gap-3">
                    <button type="button" onclick="cerrarModalEditar()" class="bg-gray-100 hover:bg-gray-200 text-slate-600 font-bold px-5 py-2.5 rounded-xl text-sm transition">Cancelar</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition shadow-lg shadow-indigo-600/20">Guardar Cambios</button>
                </footer>
            </form>
        </div>
    </div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // ============================================================
        // 🛠️ INSTANCIAS GLOBALES PARA MAPAS (Evita bugs al reabrir)
        // ============================================================
        let mapEditarInstance = null;
        let markerEditarInstance = null;

        let mapPublicarInstance = null;
        let markerPublicarInstance = null;

        // Coordenadas base por defecto (Guadalajara, MX)
        const LAT_BASE = 20.674392;
        const LNG_BASE = -103.387415;

        // ============================================================
        // 🗺️ MODAL 1: PUBLICAR NUEVO INMUEBLE
        // ============================================================
        function abrirModalPropiedad() {
            // Mostrar modal de publicación
            document.getElementById('modalPropiedad').classList.remove('hidden');

            // Dejar valores por defecto iniciales en los campos de latitud/longitud
            document.getElementById('add_latitud').value = LAT_BASE.toFixed(6);
            document.getElementById('add_longitud').value = LNG_BASE.toFixed(6);

            // Esperar a que el modal se renderice en pantalla
            setTimeout(() => {
                if (mapPublicarInstance) {
                    mapPublicarInstance.remove(); // Limpiar mapa previo si existía
                }

                // Cargar mapa en el contenedor id="map"
                mapPublicarInstance = L.map('map').setView([LAT_BASE, LNG_BASE], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(mapPublicarInstance);

                // Crear marcador arrastrable inicial
                markerPublicarInstance = L.marker([LAT_BASE, LNG_BASE], { draggable: true }).addTo(mapPublicarInstance);

                // Sincronizar coordenadas al arrastrar el pin
                markerPublicarInstance.on('dragend', function (e) {
                    const position = markerPublicarInstance.getLatLng();
                    document.getElementById('add_latitud').value = position.lat.toFixed(6);
                    document.getElementById('add_longitud').value = position.lng.toFixed(6);
                });

                // Sincronizar coordenadas al hacer clic en el mapa
                mapPublicarInstance.on('click', function(e) {
                    markerPublicarInstance.setLatLng(e.latlng);
                    document.getElementById('add_latitud').value = e.latlng.lat.toFixed(6);
                    document.getElementById('add_longitud').value = e.latlng.lng.toFixed(6);
                });

            }, 200);
        }

        function cerrarModalPropiedad() {
            document.getElementById('modalPropiedad').classList.add('hidden');
        }

        // ============================================================
        // 🗺️ MODAL 2: EDITAR INMUEBLE EXISTENTE
        // ============================================================
        function abrirModalEditar(prop) {
            document.getElementById('modalEditar').classList.remove('hidden');

            // Inyectar datos en los inputs de edición
            document.getElementById('edit_id').value = prop.id;
            document.getElementById('edit_titulo').value = prop.titulo;
            document.getElementById('edit_precio').value = prop.precio;
            document.getElementById('edit_area').value = prop.area_m2;
            document.getElementById('edit_estado').value = prop.estado;
            document.getElementById('edit_descripcion').value = prop.descripcion;
            document.getElementById('edit_latitud').value = prop.latitud || LAT_BASE;
            document.getElementById('edit_longitud').value = prop.longitud || LNG_BASE;

            const lat = parseFloat(prop.latitud) || LAT_BASE;
            const lng = parseFloat(prop.longitud) || LNG_BASE;

            setTimeout(() => {
                if (mapEditarInstance) {
                    mapEditarInstance.remove();
                }

                // Cargar mapa en el contenedor id="mapEditar"
                mapEditarInstance = L.map('mapEditar').setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(mapEditarInstance);

                markerEditarInstance = L.marker([lat, lng], { draggable: true }).addTo(mapEditarInstance);

                markerEditarInstance.on('dragend', function (e) {
                    const position = markerEditarInstance.getLatLng();
                    document.getElementById('edit_latitud').value = position.lat.toFixed(6);
                    document.getElementById('edit_longitud').value = position.lng.toFixed(6);
                });

                mapEditarInstance.on('click', function(e) {
                    markerEditarInstance.setLatLng(e.latlng);
                    document.getElementById('edit_latitud').value = e.latlng.lat.toFixed(6);
                    document.getElementById('edit_longitud').value = e.latlng.lng.toFixed(6);
                });

            }, 200);
        }

        function cerrarModalEditar() {
            document.getElementById('modalEditar').classList.add('hidden');
        }

        // ============================================================
        // 👥 CONTROL DE ACCESO / SEGURIDAD DE USUARIOS
        // ============================================================
        function ejecutarCambioEstado(usuarioId, stringEstado) {
            fetch('../apis/api_estado_usuario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario_id: parseInt(usuarioId), estado: stringEstado })
            })
            .then(response => {
                if (!response.ok) throw new Error('Respuesta de red no okey');
                return response.json();
            })
            .then(data => {
                if(data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert("Error desde la API: " + data.message);
                }
            })
            .catch(error => {
                console.error("Error en la petición Fetch:", error);
                alert("Hubo un problema al procesar la solicitud.");
            });
        }
    </script>
</body>
</html>