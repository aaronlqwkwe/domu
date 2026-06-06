<?php
session_start();
// Validar que solo el Administrador (rol_id = 1) pueda entrar
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: login.php");
    exit;
}

require_once '../conexion.php';

try {
    // 1. Obtener todas las citas PENDIENTES
    // Hacemos JOIN con usuarios para saber el nombre del cliente y el nombre de la propiedad
    $stmt_citas = $conn->prepare("
        SELECT c.id, c.fecha, c.hora, u_cliente.nombre AS cliente_nombre, u_prop.nombre AS propiedad_nombre
        FROM citas c
        INNER JOIN usuarios u_cliente ON c.cliente_id = u_cliente.id
        INNER JOIN usuarios u_prop ON c.propiedad_id = u_prop.id
        WHERE c.estado = 'pendiente'
        ORDER BY c.fecha ASC, c.hora ASC
    ");
    $stmt_citas->execute();
    $citas_pendientes = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener la lista de agentes activos para poder asignarlos
    $stmt_agentes = $conn->prepare("SELECT id, nombre FROM usuarios WHERE rol_id = 2 AND estado_cuenta = 'aprobado'");
    $stmt_agentes->execute();
    $agentes = $stmt_agentes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar el panel: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es" class="bg-slate-50 h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Citas - Domu Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans h-full antialiased p-6 md:p-10">

    <div class="max-w-6xl mx-auto">
        
        <div class="flex items-center justify-between border-b border-slate-200 pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-slate-900">Bandeja de Citas</h1>
                <p class="text-sm text-slate-500 mt-1">Aprueba las solicitudes de visita y asigna un asesor.</p>
            </div>
            <a href="admin_dashboard.php" class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-bold shadow-sm hover:bg-slate-50 transition">
                Volver al Dashboard
            </a>
        </div>

        <?php if (count($citas_pendientes) === 0): ?>
            <div class="bg-white border border-slate-200 rounded-3xl p-12 text-center shadow-sm">
                <div class="text-5xl mb-4">☕</div>
                <h3 class="text-lg font-black text-slate-800">Todo al día</h3>
                <p class="text-sm text-slate-500 mt-2">No hay citas pendientes por aprobar en este momento.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($citas_pendientes as $cita): ?>
                    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                        
                        <div class="absolute top-0 left-0 w-1 h-full bg-amber-400"></div>

                        <div class="mb-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-600 border border-amber-200 px-2.5 py-1 rounded-md">
                                Pendiente
                            </span>
                        </div>

                        <h3 class="text-lg font-black text-slate-800 truncate" title="<?php echo htmlspecialchars($cita['propiedad_nombre']); ?>">
                            <?php echo htmlspecialchars($cita['propiedad_nombre']); ?>
                        </h3>
                        <p class="text-sm text-slate-500 mt-1 font-medium">👤 Cliente: <?php echo htmlspecialchars($cita['cliente_nombre']); ?></p>
                        
                        <div class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-100 flex justify-between items-center">
                            <div>
                                <p class="text-[10px] uppercase font-bold text-slate-400">Fecha</p>
                                <p class="text-sm font-black text-slate-700"><?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] uppercase font-bold text-slate-400">Hora</p>
                                <p class="text-sm font-black text-indigo-600"><?php echo $cita['hora']; ?></p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Asignar Asesor:</label>
                            <select id="agente_<?php echo $cita['id']; ?>" class="w-full text-sm p-3 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-500 transition">
                                <option value="" disabled selected>Selecciona un asesor...</option>
                                <?php foreach ($agentes as $agente): ?>
                                    <option value="<?php echo $agente['id']; ?>"><?php echo htmlspecialchars($agente['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <button onclick="confirmarCita(<?php echo $cita['id']; ?>)" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black text-sm py-3 rounded-xl transition shadow-md shadow-indigo-600/20">
                                Confirmar y Asignar
                            </button>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <script>
        async function confirmarCita(citaId) {
            const selectAgente = document.getElementById(`agente_${citaId}`);
            const agenteId = selectAgente.value;

            if (!agenteId) {
                alert("Por favor, selecciona un asesor antes de confirmar la cita.");
                return;
            }

            if (!confirm("¿Estás seguro de confirmar esta cita y asignarle este asesor?")) return;

            const datos = new FormData();
            datos.append('cita_id', citaId);
            datos.append('agente_id', agenteId);

            try {
                // Apuntamos a la API saliendo de la carpeta vistas/
                const respuesta = await fetch('../apis/api_confirmar_cita.php', {
                    method: 'POST',
                    body: datos
                });

                const resultado = await respuesta.json();
                
                alert(resultado.message);

                if (resultado.success) {
                    location.reload(); // Recargar la página para que la tarjeta desaparezca
                }
            } catch (error) {
                console.error("Error:", error);
                alert("Ocurrió un error de comunicación con el servidor.");
            }
        }
    </script>
</body>
</html>