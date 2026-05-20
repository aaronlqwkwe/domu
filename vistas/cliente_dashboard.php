<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$nombre_cliente = $_SESSION['nombre'];
$usuario_id = intval($_SESSION['usuario_id']);
require_once '../conexion.php';

// Consultar los apartados del cliente incluyendo 'paso_legal' y buscando si hay algún documento en 'multimedia'
try {
    // 🌟 SE AGREGÓ: m.ruta_archivo mediante un LEFT JOIN filtrando por tipo = 'documento'
    $sql = "SELECT p.*, a.id AS apartado_id, a.fecha_apartado, a.paso_legal, m.ruta_archivo AS documento_notarial
            FROM const_apartados a 
            JOIN propiedad p ON a.propiedad_id = p.id 
            LEFT JOIN multimedia m ON p.id = m.propiedad_id AND m.tipo = 'documento'
            WHERE a.usuario_id = :usuario_id 
            ORDER BY a.id DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':usuario_id' => $usuario_id]);
    $mis_apartados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mis_apartados = [];
}

// Definición de los 4 pasos estándar del proceso legal de Domu
$pasos_proceso = [
    1 => ['titulo' => 'Apartado Seguro', 'desc' => 'Inmueble congelado en el catálogo.'],
    2 => ['titulo' => 'Validación Legal', 'desc' => 'Revisión de escrituras e identidad.'],
    3 => ['titulo' => 'Proceso Notarial', 'desc' => 'Preparando contratos finales de compra.'],
    4 => ['titulo' => 'Llaves en Mano', 'desc' => '¡Felicidades, propiedad entregada!']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domu - Mi Panel de Cliente</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">

    <header class="bg-[#111827] text-white py-4 px-8 flex justify-between items-center shadow-md">
        <div class="font-bold text-2xl flex items-center gap-2 cursor-pointer" onclick="window.location.href='../index.php'">
            Domu<span class="text-indigo-400">Cliente</span>
        </div>
        <div class="flex items-center gap-4">
            <a href="../index.php" class="text-sm font-medium text-gray-300 hover:text-white transition">Ver Catálogo</a>
            <a href="../apis/api_logout.php" class="bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-2 px-4 rounded-lg transition">Cerrar Sesión</a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto w-full p-6 md:py-10 flex-grow">
        
        <div class="bg-white p-8 rounded-2xl border shadow-sm mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="text-3xl font-extrabold text-gray-900">¡Hola, <?php echo htmlspecialchars($nombre_cliente); ?>!</h2>
                <p class="text-gray-500 mt-1">Monitorea en tiempo real el avance legal de tu adquisición.</p>
            </div>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nombre_cliente); ?>&background=6366f1&color=fff" class="h-14 w-14 rounded-full shadow-md">
        </div>

        <h3 class="text-xl font-bold text-gray-800 mb-6">Mis Procesos de Adquisición</h3>

        <?php if(count($mis_apartados) > 0): ?>
            <div class="space-y-8">
                <?php foreach($mis_apartados as $prop): 
                    $paso_actual = isset($prop['paso_legal']) ? intval($prop['paso_legal']) : 1;
                    $es_rechazada = ($paso_actual === 0);
                ?>
                    <div class="bg-white rounded-2xl border shadow-sm p-6 <?php echo $es_rechazada ? 'border-red-200 bg-red-[2px]' : ''; ?>">
                        
                        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-center">
                            <div class="flex items-center gap-4 lg:col-span-1 border-b lg:border-b-0 lg:border-r pb-4 lg:pb-0 pr-4">
                                <?php $foto = !empty($prop['imagen']) ? '../uploads/' . $prop['imagen'] : 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=300&q=80'; ?>
                                <div class="relative">
                                    <img src="<?php echo $foto; ?>" class="w-20 h-20 object-cover rounded-xl shadow-inner <?php echo $es_rechazada ? 'grayscale' : ''; ?>">
                                    <?php if($es_rechazada): ?>
                                        <span class="absolute inset-0 bg-red-900/10 rounded-xl"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="truncate">
                                    <h4 class="font-bold text-gray-950 truncate"><?php echo htmlspecialchars($prop['titulo']); ?></h4>
                                    <p class="<?php echo $es_rechazada ? 'text-gray-400 line-through' : 'text-indigo-600'; ?> font-extrabold text-sm">$<?php echo number_format($prop['precio'], 2); ?> MXN</p>
                                    <span class="text-[10px] text-gray-400 block mt-1">Apartada el: <?php echo date("d/m/Y", strtotime($prop['fecha_apartado'])); ?></span>
                                </div>
                            </div>

                            <div class="lg:col-span-3 w-full px-2">
                                <?php if($es_rechazada): ?>
                                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center text-lg font-bold shadow-sm shrink-0">✕</div>
                                        <div>
                                            <h5 class="text-sm font-bold text-red-900">Apartado Rechazado / Cancelado</h5>
                                            <p class="text-xs text-red-700 mt-0.5">Esta solicitud no pudo proceder debido a inconsistencias en la validación o duplicidad. Tu asesor se pondrá en contacto contigo.</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="flex flex-col md:flex-row justify-between relative gap-6 md:gap-0">
                                        <?php for($i = 1; $i <= 4; $i++): 
                                            $paso_completado = ($i <= $paso_actual);
                                            $es_ultimo = ($i === 4);
                                        ?>
                                            <div class="flex-1 flex flex-row md:flex-col items-center text-left md:text-center relative">
                                                
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs transition z-10 
                                                    <?php echo $paso_completado ? 'bg-indigo-600 text-white ring-4 ring-indigo-100' : 'bg-gray-200 text-gray-400'; ?>">
                                                    <?php if ($i < $paso_actual): ?> ✓ <?php else: ?> <?php echo $i; ?> <?php endif; ?>
                                                </div>

                                                <div class="ml-4 md:ml-0 md:mt-2">
                                                    <h5 class="text-xs font-bold <?php echo $paso_completado ? 'text-gray-900' : 'text-gray-400'; ?>">
                                                        <?php echo $pasos_proceso[$i]['titulo']; ?>
                                                    </h5>
                                                    <p class="text-[10px] text-gray-400 max-w-[150px] md:mx-auto md:block hidden mt-0.5 leading-tight">
                                                        <?php echo $pasos_proceso[$i]['desc']; ?>
                                                    </p>
                                                </div>

                                                <?php if(!$es_ultimo): ?>
                                                    <div class="hidden md:block absolute top-4 left-[50%] right-[-50%] h-0.5 z-0 
                                                        <?php echo ($i < $paso_actual) ? 'bg-indigo-600' : 'bg-gray-200'; ?>">
                                                    </div>
                                                <?php endif; ?>

                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($paso_actual === 3): ?>
                            <div class="mt-6 pt-6 border-t border-gray-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-indigo-50/50 p-4 rounded-xl">
                                <div class="flex items-start gap-3">
                                    <div class="p-2 bg-indigo-100 text-indigo-700 rounded-lg shrink-0 mt-0.5">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900">Documentación en Notaría Activa</h4>
                                        <p class="text-xs text-gray-500 mt-0.5">La notaría asignada está estructurando el borrador del contrato de compraventa final y validando los avalúos.</p>
                                    </div>
                                </div>
                                <div class="w-full md:w-auto text-right shrink-0">
                                    <?php if (!empty($prop['documento_notarial'])): ?>
                                        <a href="<?php echo htmlspecialchars($prop['documento_notarial']); ?>" target="_blank" 
                                           class="w-full md:w-auto inline-flex justify-center items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition shadow-md shadow-indigo-500/20">
                                            📥 Descargar Borrador Contractual (PDF)
                                        </a>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-3 py-2 rounded-xl">
                                            ⏱️ Esperando carga de archivos por tu asesor...
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16 bg-white rounded-2xl border border-dashed">
                <span class="text-4xl">🏡</span>
                <p class="text-gray-500 font-medium mt-4">Aún no has apartado ninguna propiedad.</p>
                <a href="../index.php" class="inline-block mt-4 bg-indigo-600 text-white text-xs font-bold py-2.5 px-5 rounded-xl">Explorar Catálogo</a>
            </div>
        <?php endif; ?>

    </main>

    <footer class="bg-gray-900 text-gray-500 py-4 text-center text-xs mt-auto">
        © 2026 Domu. Todos los derechos reservados.
    </footer>
</body>
</html>