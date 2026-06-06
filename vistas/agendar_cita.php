<?php
session_start();
// 1. CORRECCIÓN DE ZONA HORARIA PARA EVITAR QUE SE CIERREN HORARIOS ANTES DE TIEMPO
date_default_timezone_set('America/Mexico_City');

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 3) {
    header("Location: login.php"); 
    exit;
}

// 🛠️ Conexión a la base de datos
require_once '../conexion.php';

$id_propiedad = isset($_GET['id']) ? intval($_GET['id']) : 0;
$fecha_seleccionada = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$usuario_id = $_SESSION['usuario_id'];

if ($id_propiedad === 0) {
    die("Error: Propiedad no válida.");
}

try {
    // 1. OBTENER EL TRUST FACTOR DEL USUARIO
    $stmt_user = $conn->prepare("SELECT trust_factor FROM usuarios WHERE id = ?");
    $stmt_user->execute([$usuario_id]);
    $resultado_tf = $stmt_user->fetchColumn();
    
    $trust_factor = ($resultado_tf !== false && $resultado_tf !== null) ? intval($resultado_tf) : 100; 
    $es_bloqueado = ($trust_factor < 0);
    $es_riesgo = ($trust_factor >= 0 && $trust_factor <= 50);

    // 2. Traer información de la propiedad
    $stmt_prop = $conn->prepare("SELECT id, nombre AS titulo FROM usuarios WHERE id = ?"); 
    $stmt_prop->execute([$id_propiedad]);
    $propiedad = $stmt_prop->fetch(PDO::FETCH_ASSOC) ?: ['titulo' => 'Propiedad Domu #' . $id_propiedad];

    // 3. OBTENER TODOS LOS AGENTES DISPONIBLES (Nombres e IDs)
    $stmt_agentes = $conn->prepare("SELECT id, nombre FROM usuarios WHERE rol_id = 2 AND estado_cuenta = 'aprobado'");
    $stmt_agentes->execute();
    $todos_los_agentes = $stmt_agentes->fetchAll(PDO::FETCH_ASSOC);

    // 4. OBTENER AGENTES OCUPADOS EN EL DÍA SELECCIONADO
    // Agrupamos qué agentes ya tienen cita en cada hora para no empalmarlos
    $stmt_citas = $conn->prepare("
        SELECT hora, agente_id 
        FROM citas 
        WHERE fecha = ? AND estado != 'rechazada' AND estado != 'cancelada'
    ");
    $stmt_citas->execute([$fecha_seleccionada]);
    $citas_del_dia = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);

    $agentes_ocupados_por_hora = [];
    foreach ($citas_del_dia as $cita) {
        // Agrupamos en un arreglo los IDs de los agentes ocupados por cada bloque horario
        $agentes_ocupados_por_hora[$cita['hora']][] = $cita['agente_id'];
    }

    $bloques_horarios = ["7:00 PM", "8:30 PM", "9:00 PM", "10:30 PM", "08:0`0 PM", "09:30 PM"];

} catch (PDOException $e) {
    die("Error en el motor de reservas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-[#0f111a]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Recorrido - Domu</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0f111a] text-slate-200 font-sans min-h-full flex items-center justify-center p-4 md:p-10 antialiased" style="background-image: url('../path_a_tu_imagen.jpg'); background-size: cover; background-position: center; background-blend-mode: overlay; background-color: rgba(15, 17, 26, 0.85);">
    
    <div class="max-w-xl w-full bg-[#161925]/90 backdrop-blur-md rounded-3xl p-6 md:p-10 shadow-2xl border border-slate-800/80 space-y-8">
        
        <div class="border-b border-slate-800 pb-5">
            <div class="flex items-center gap-3">
                <a href="javascript:history.back()" class="p-2 bg-slate-800/60 hover:bg-slate-800 rounded-xl text-slate-400 hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <span class="text-[10px] font-bold uppercase tracking-widest bg-indigo-500/10 text-indigo-400 px-2.5 py-1 rounded-md border border-indigo-500/20">Reserva de Espacio</span>
            </div>
            <h2 class="text-2xl font-black mt-3 tracking-tight text-white">Programa tu Visita</h2>
            <p class="text-xs text-slate-400 mt-1">Propiedad: <span class="text-indigo-400 font-bold"><?php echo htmlspecialchars($propiedad['titulo']); ?></span></p>
        </div>

        <?php if ($es_bloqueado): ?>
            <div class="bg-rose-950/30 border border-rose-500/50 rounded-2xl p-8 text-center shadow-inner">
                <div class="w-16 h-16 bg-rose-500/10 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-rose-500/20">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8V7z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-black text-rose-400">Cuenta Restringida</h3>
                <p class="text-sm text-slate-300 mt-3 leading-relaxed">
                    Tu <strong class="text-white">Trust Factor es negativo (<?php echo $trust_factor; ?>)</strong>. Has acumulado demasiadas inasistencias o cancelaciones fuera de tiempo. 
                    <br><br>
                    Por seguridad, la programación de nuevas visitas ha sido bloqueada. Por favor, contacta a tu asesor para solicitar una revisión de tu cuenta.
                </p>
                <button onclick="history.back()" class="mt-6 w-full bg-slate-800 hover:bg-slate-700 text-white font-bold py-3 px-6 rounded-xl transition">Volver al Catálogo</button>
            </div>

        <?php else: ?>
            <?php if ($es_riesgo): ?>
                <div class="bg-amber-950/30 border border-amber-500/50 rounded-2xl p-4 flex items-start gap-4 shadow-inner">
                    <span class="text-amber-500 text-2xl leading-none mt-1">⚠️</span>
                    <div>
                        <h4 class="text-sm font-black text-amber-400">Advertencia de Trust Factor</h4>
                        <p class="text-[11px] text-amber-200/80 mt-1 leading-relaxed">
                            Tu nivel de confianza está en riesgo (<strong><?php echo $trust_factor; ?> pts</strong>). Si agendas esta cita y no asistes, tu cuenta será <strong>bloqueada automáticamente</strong> y no podrás programar más visitas.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="GET" class="space-y-2">
                <input type="hidden" name="id" value="<?php echo $id_propiedad; ?>">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">1. Elige el día</label>
                <input type="date" name="fecha" value="<?php echo $fecha_seleccionada; ?>" onchange="this.form.submit()" min="<?php echo date('Y-m-d'); ?>" class="w-full p-3.5 bg-[#0f111a] border border-slate-800 rounded-xl text-xs font-semibold focus:outline-none focus:border-indigo-500 text-white text-center transition">
            </form>

            <div class="space-y-4">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">2. Horarios y asesores libres para el <?php echo date('d/m/Y', strtotime($fecha_seleccionada)); ?></label>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <?php foreach($bloques_horarios as $hora): ?>
                        <?php 
                            $timestamp_bloque = strtotime($fecha_seleccionada . ' ' . $hora);
                            $ya_paso = ($timestamp_bloque < time());
                            
                            // Agentes ocupados específicamente en esta hora
                            $ocupados_ahora = isset($agentes_ocupados_por_hora[$hora]) ? $agentes_ocupados_por_hora[$hora] : [];
                            
                            // Filtramos a los agentes que NO están en la lista de ocupados
                            $agentes_libres = [];
                            foreach ($todos_los_agentes as $agente) {
                                if (!in_array($agente['id'], $ocupados_ahora)) {
                                    $agentes_libres[] = $agente;
                                }
                            }
                            
                            // Si el tiempo ya pasó, forzamos cupos a 0. Si no, los cupos son la cantidad de agentes libres.
                            $cupos_restantes = $ya_paso ? 0 : count($agentes_libres);
                        ?>
                        
                        <?php if($cupos_restantes <= 0): ?>
                            <div class="p-4 bg-slate-900/40 border border-slate-800/30 rounded-2xl flex items-center justify-between opacity-40 cursor-not-allowed">
                                <div>
                                    <p class="font-mono text-xs font-bold text-slate-500"><?php echo $hora; ?></p>
                                    <p class="text-[10px] text-rose-500 font-medium mt-0.5">
                                        <?php echo $ya_paso ? '● Horario expirado' : '● Horario agotado'; ?>
                                    </p>
                                </div>
                                <span class="text-[10px] bg-rose-500/10 text-rose-400 px-2 py-0.5 rounded-md border border-rose-500/20 font-bold">
                                    <?php echo $ya_paso ? 'Cerrado' : '0 cupos'; ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <?php 
                                // Tomamos al primer agente libre para asignarlo en el botón
                                $id_agente_asignado = $agentes_libres[0]['id']; 
                                
                                // Extraemos solo el primer nombre de todos los agentes libres para mostrarlos en la UI sin saturar
                                $nombres_agentes = array_column($agentes_libres, 'nombre');
                                $primeros_nombres = array_map(function($nombre) { return explode(' ', trim($nombre))[0]; }, $nombres_agentes);
                                $texto_agentes = implode(', ', $primeros_nombres);
                            ?>
                            <button onclick="enviarSolicitudCita('<?php echo $hora; ?>', <?php echo $id_agente_asignado; ?>)" class="p-4 bg-[#0f111a] hover:bg-[#121420] border border-slate-800 hover:border-indigo-500/50 rounded-2xl flex items-center justify-between transition-all group text-left">
                                <div class="max-w-[100px]">
                                    <p class="font-mono text-xs font-black text-slate-200 group-hover:text-indigo-400 transition"><?php echo $hora; ?></p>
                                    <p class="text-[9px] text-slate-500 font-medium mt-0.5 truncate border-t border-slate-800 mt-1 pt-1">
                                        Con: <?php echo $texto_agentes; ?>
                                    </p>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] <?php echo ($cupos_restantes == 1) ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'; ?> px-2 py-0.5 rounded-md border font-black transition">
                                        <?php echo $cupos_restantes; ?> <?php echo ($cupos_restantes == 1) ? 'asesor' : 'cupos'; ?>
                                    </span>
                                    <div class="w-7 h-7 bg-indigo-600/10 group-hover:bg-indigo-600 rounded-lg flex items-center justify-center text-indigo-400 group-hover:text-white transition-all shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                                    </div>
                                </div>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <p class="text-[10px] text-slate-500 text-center tracking-wide">
                🔒 El sistema asigna automáticamente al primer asesor comercial libre para garantizar tu espacio sin empalmes.
            </p>
        <?php endif; ?>
    </div>

    <?php if (!$es_bloqueado): ?>
    <script>
        // RECIBIMOS EL ID DEL AGENTE ASIGNADO DESDE EL BOTÓN
        async function enviarSolicitudCita(hora, agente_id) {
            if (!confirm(`¿Confirmas tu solicitud de visita guiada para las ${hora}?`)) return;
            
            const datos = new FormData();
            datos.append('propiedad_id', '<?php echo $id_propiedad; ?>');
            datos.append('fecha', '<?php echo $fecha_seleccionada; ?>');
            datos.append('hora', hora);
            // ENVIAMOS EL ID DEL AGENTE A LA API DE GUARDADO
            datos.append('agente_id', agente_id); 

            try {
                const respuesta = await fetch('/domu_oficial/apis/api_guardar_cita.php', { 
                    method: 'POST', 
                    body: datos 
                });
                
                if (!respuesta.ok) {
                    throw new Error(`Servidor respondió con código: ${respuesta.status}`);
                }
                
                const resultado = await respuesta.json();
                alert(resultado.message);
                if (resultado.success) {
                    location.reload(); 
                }
            } catch (e) {
                console.error("Detalle del error:", e);
                alert("Ocurrió un problema al procesar la cita en el servidor. Abre la consola con F12 para ver los detalles.");
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>