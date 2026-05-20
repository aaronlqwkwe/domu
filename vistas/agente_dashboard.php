<?php
session_start();

// Validamos que sea el Rol 2 (Agente)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 2) {
    header("Location: ../index.php");
    exit;
}

$nombre_agente = $_SESSION['nombre'] ?? 'Agente';
$agente_id = $_SESSION['usuario_id'];

require_once '../conexion.php'; 

// Consulta de Propiedades (SOLO LAS DEL AGENTE) con manejo de errores
try {
    $stmt_prop_list = $conn->prepare("SELECT * FROM propiedad WHERE agente_id = :agente_id ORDER BY id DESC");
    $stmt_prop_list->bindParam(':agente_id', $agente_id);
    $stmt_prop_list->execute();
    $lista_propiedades = $stmt_prop_list->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}

$total_propiedades = count($lista_propiedades);
$propiedades_aprobadas = 0;
$propiedades_pendientes = 0;

foreach($lista_propiedades as $prop) {
    $estado = strtolower($prop['estado'] ?? '');
    if($estado == 'aprobado' || $estado == 'disponible') {
        $propiedades_aprobadas++;
    } elseif($estado == 'pendiente') {
        $propiedades_pendientes++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InmoPro - Panel de Agente</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans flex h-screen overflow-hidden">

    <aside class="w-64 bg-[#111118] text-gray-400 flex flex-col shadow-2xl h-full z-20">
        <div class="p-6 border-b border-gray-800">
            <h1 class="font-bold text-2xl text-white flex items-center gap-2">
                Panel <span class="text-[#10b981]">Agente</span>
            </h1>
        </div>
        
        <nav class="flex-1 p-4 space-y-2">
            <a href="agente_dashboard.php" class="flex items-center gap-3 bg-[#10b981] text-white px-4 py-3 rounded-xl transition shadow-lg shadow-emerald-500/30">
                Mi Dashboard
            </a>
            <a href="agente_subir.php" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition w-full text-left">
                Subir Propiedad
            </a>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <a href="../apis/api_logout.php" class="flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition text-sm font-semibold w-full">Cerrar Sesión</a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white border-b border-gray-200 py-4 px-8 flex justify-between items-center z-10">
            <h2 class="text-2xl font-bold text-gray-800">Mi Inventario</h2>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($nombre_agente); ?></p>
                    <p class="text-xs text-[#10b981] font-medium italic">Agente Inmobiliario</p>
                </div>
            </div>
        </header>

        <div class="p-8 flex-1 overflow-y-auto">
            
            <?php if(isset($_GET['mensaje']) && $_GET['mensaje'] == 'propiedad_guardada'): ?>
                <div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-xl mb-6 flex justify-between items-center">
                    <span class="font-bold">¡Éxito! Propiedad enviada a revisión.</span>
                    <button onclick="this.parentElement.remove()" class="text-emerald-700 font-bold">&times;</button>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-2xl p-6 shadow-sm border-l-4 border-l-blue-500">
                    <p class="text-sm font-medium text-gray-500 mb-1">Total Subidas</p><p class="text-3xl font-bold text-gray-900"><?php echo $total_propiedades; ?></p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border-l-4 border-l-emerald-500">
                    <p class="text-sm font-medium text-gray-500 mb-1">Aprobadas</p><p class="text-3xl font-bold text-gray-900"><?php echo $propiedades_aprobadas; ?></p>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border-l-4 border-l-yellow-500">
                    <p class="text-sm font-medium text-gray-500 mb-1">En Revisión</p><p class="text-3xl font-bold text-gray-900"><?php echo $propiedades_pendientes; ?></p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">Mis Propiedades Registradas</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-100">
                                <th class="p-4 font-bold">Título</th>
                                <th class="p-4 font-bold">Precio</th>
                                <th class="p-4 font-bold">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            <?php foreach($lista_propiedades as $prop): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4 font-bold text-gray-900"><?php echo htmlspecialchars($prop['titulo']); ?></td>
                                <td class="p-4 font-medium text-gray-700">$<?php echo number_format($prop['precio'], 2); ?></td>
                                <td class="p-4">
                                    <?php if(strtolower($prop['estado']) == 'pendiente'): ?>
                                        <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-600 font-bold text-xs uppercase">Pendiente</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded bg-green-100 text-green-600 font-bold text-xs uppercase"><?php echo htmlspecialchars($prop['estado']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($lista_propiedades)): ?>
                            <tr><td colspan="3" class="p-8 text-center text-gray-500">Aún no has subido ninguna propiedad.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>