<?php
session_start();

// Activar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar que sea Administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: login.php"); 
    exit;
}

// Conexión subiendo un nivel desde la carpeta vistas/
require_once '../conexion.php';

try {
    // 🛠️ CORRECCIÓN: Quitamos 'correo' de la consulta para evitar el error de columna inexistente
    $stmt = $conn->prepare("SELECT id, nombre, trust_factor FROM usuarios WHERE rol_id = 3 ORDER BY nombre ASC");
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error interno en la base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#0f111a]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Trust Factor - Admin Domu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0f111a] text-slate-200 font-sans min-h-full flex items-center justify-center p-4 md:p-10 antialiased" style="background-image: url('../path_a_tu_imagen.jpg'); background-size: cover; background-position: center; background-blend-mode: overlay; background-color: rgba(15, 17, 26, 0.85);">

    <div class="max-w-4xl w-full bg-[#161925]/90 backdrop-blur-md rounded-3xl p-6 md:p-10 shadow-2xl border border-slate-800/80 space-y-8">
        
        <div class="border-b border-slate-800 pb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-widest bg-indigo-500/10 text-indigo-400 px-2.5 py-1 rounded-md border border-indigo-500/20">Panel de Control</span>
                <h2 class="text-2xl font-black mt-2 tracking-tight text-white">Moderar Trust Factor</h2>
                <p class="text-xs text-slate-400 mt-1">Configura la reputación de los clientes para aplicar advertencias o restricciones de agenda.</p>
            </div>
            <a href="javascript:history.back()" class="self-start sm:self-center px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-xs font-bold rounded-xl text-slate-300 hover:text-white transition">
                Volver
            </a>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-800/60 bg-[#0f111a]/50">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 bg-[#121420] text-[10px] uppercase tracking-wider text-slate-400 font-bold">
                        <th class="p-4">Cliente</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4 text-center">Puntaje</th>
                        <th class="p-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/40 text-xs">
                    <?php if (empty($clientes)): ?>
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500 font-medium">No hay usuarios con rol de cliente registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $cliente): ?>
                            <?php 
                                $tf = intval($cliente['trust_factor']);
                                if ($tf < 0) {
                                    $badge = '<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">⛔ Bloqueado</span>';
                                } elseif ($tf >= 0 && $tf <= 50) {
                                    $badge = '<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">⚠️ Riesgo</span>';
                                } else {
                                    $badge = '<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">👍 Excelente</span>';
                                }
                            ?>
                            <tr class="hover:bg-slate-900/30 transition-colors">
                                <td class="p-4">
                                    <p class="font-bold text-white"><?php echo htmlspecialchars($cliente['nombre']); ?></p>
                                    <p class="text-[10px] text-slate-500">ID Usuario: #<?php echo $cliente['id']; ?></p>
                                </td>
                                <td class="p-4"><?php echo $badge; ?></td>
                                <td class="p-4 text-center">
                                    <input type="number" 
                                           id="tf-<?php echo $cliente['id']; ?>" 
                                           value="<?php echo $tf; ?>" 
                                           class="w-20 p-2 bg-[#0f111a] border border-slate-800 rounded-lg text-center font-mono text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition">
                                </td>
                                <td class="p-4 text-center">
                                    <button onclick="actualizarTrustFactor(<?php echo $cliente['id']; ?>)" 
                                            class="px-3 py-2 bg-indigo-600/10 hover:bg-indigo-600 border border-indigo-500/20 text-indigo-400 hover:text-white rounded-xl font-bold transition text-[11px]">
                                        Guardar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        async function actualizarTrustFactor(usuarioId) {
            const inputTf = document.getElementById(`tf-${usuarioId}`);
            const nuevoValor = inputTf.value;

            if (nuevoValor === '') {
                alert('Por favor ingresa un número válido.');
                return;
            }

            const datos = new FormData();
            datos.append('usuario_id', usuarioId);
            datos.append('trust_factor', nuevoValor);

            try {
                // Sube correctamente un nivel desde vistas/ hacia apis/
                const respuesta = await fetch('../apis/api_actualizar_trust_factor.php', {
                    method: 'POST',
                    body: datos
                });

                if (!respuesta.ok) {
                    throw new Error(`Error del servidor: ${respuesta.status}`);
                }

                const resultado = await respuesta.json();
                alert(resultado.message);
                
                if (resultado.success) {
                    location.reload(); 
                }
            } catch (error) {
                console.error("Error:", error);
                alert("Ocurrió un problema en la solicitud. Abre la consola para revisar.");
            }
        }
    </script>
</body>
</html>