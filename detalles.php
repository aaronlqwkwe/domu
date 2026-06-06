<?php
session_start();
require_once 'conexion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$propiedad_id = intval($_GET['id']);
$logeado = isset($_SESSION['usuario_id']);

try {
    $stmt = $conn->prepare("SELECT * FROM propiedad WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $propiedad_id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$p) {
        die("Propiedad no encontrada.");
    }
} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($p['titulo']); ?> - Domu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>#map { height: 350px; width: 100%; border-radius: 16px; z-index: 1; }</style>
</head>
<body class="bg-gray-50 font-sans flex flex-col min-h-screen">

    <header class="bg-[#111827] text-white py-4 px-6 flex justify-between items-center shadow-lg">
        <div class="font-bold text-2xl cursor-pointer" onclick="window.location.href='index.php'">Domu</div>
        <a href="index.php" class="text-sm bg-gray-800 hover:bg-gray-700 py-2 px-4 rounded-lg transition">← Volver al catálogo</a>
    </header>

    <main class="max-w-6xl mx-auto w-full p-6 md:py-12 flex-grow grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-2xl overflow-hidden shadow-md bg-white border">
                <?php $foto = !empty($p['imagen']) ? 'uploads/' . $p['imagen'] : 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=1200&q=80'; ?>
                <img src="<?php echo $foto; ?>" class="w-full h-[450px] object-cover">
            </div>

            <div class="bg-white p-8 rounded-2xl shadow-sm border space-y-4">
                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold uppercase"><?php echo htmlspecialchars($p['estado']); ?></span>
                <h1 class="text-3xl font-extrabold text-gray-900"><?php echo htmlspecialchars($p['titulo']); ?></h1>
                <p class="text-2xl font-bold text-indigo-600">$<?php echo number_format($p['precio'], 2); ?> MXN</p>
                <div class="border-t border-b py-3 my-4 flex gap-6 text-gray-600 font-semibold text-sm">
                    <span>📐 Área: <?php echo $p['area_m2']; ?> m²</span>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Descripción de la propiedad</h3>
                <p class="text-gray-600 leading-relaxed mb-6"><?php echo nl2br(htmlspecialchars($p['descripcion'])); ?></p>

                <?php if (!empty($p['latitud']) && !empty($p['longitud'])): ?>
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-3">📍 Ubicación de la propiedad</h3>
                        <div id="map"></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="space-y-6">
            
            <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 sticky top-6 space-y-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 mb-1">Proceso de Adquisición</h3>
                    <p class="text-xs text-gray-400">Adquiere este inmueble de forma segura en 2 fases financieras.</p>
                </div>
                
                <?php if(isset($_GET['error']) && $_GET['error'] == 'no_disponible'): ?>
                    <div class="bg-red-50 text-red-700 p-3 rounded-lg text-xs font-semibold">
                        ⚠️ Esta propiedad ya fue apartada por otro usuario.
                    </div>
                <?php endif; ?>

                <div class="p-4 rounded-xl bg-indigo-50/50 border border-indigo-100 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold uppercase text-indigo-600 tracking-wider">Fase 1: Apartar Inmueble</span>
                        <span class="text-[10px] bg-indigo-600 text-white font-semibold px-2 py-0.5 rounded-full">Inmediato</span>
                    </div>
                    <p class="text-xs text-gray-600 leading-normal">
                        Congela la propiedad en tiempo real para que nadie más pueda ofertar por ella mientras integras tus documentos legales.
                    </p>
                    <div class="flex justify-between items-baseline border-b border-indigo-100/50 pb-2">
                        <span class="text-xs text-gray-500 font-medium">Monto del Apartado:</span>
                        <span class="text-lg font-bold text-gray-900">$10,000.00 <span class="text-xs font-normal text-gray-500">MXN</span></span>
                    </div>
                    
                    <div class="flex items-center justify-between pt-1">
                        <span class="text-[10px] font-semibold text-gray-400 uppercase">Medio de cobro seguro:</span>
                        <div class="flex gap-1.5 opacity-80">
                            <span class="px-1.5 py-0.5 bg-white border text-[9px] font-bold text-blue-800 rounded shadow-sm">VISA</span>
                            <span class="px-1.5 py-0.5 bg-white border text-[9px] font-bold text-red-600 rounded shadow-sm">MC</span>
                            <span class="px-1.5 py-0.5 bg-white border text-[9px] font-bold text-blue-500 rounded shadow-sm">AMEX</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <?php if(strtolower($p['estado']) == 'disponible'): ?>
                            <?php if($logeado): ?>
                                <form id="payment-form" action="apis/api_apartar_interno.php" method="POST">
                                    <input type="hidden" name="propiedad_id" value="<?php echo $p['id']; ?>">
                                    <button id="pay-button" type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl transition-all text-center flex items-center justify-center gap-2 text-sm shadow-md shadow-indigo-100">
                                        <span id="button-icon">💳</span> 
                                        <span id="button-text">Apartar con Tarjeta (Stripe)</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="vistas/login.php" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-bold py-2.5 px-4 rounded-xl transition text-center block text-sm shadow-md">
                                    Inicia sesión para apartar
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button disabled class="w-full bg-gray-200 text-gray-400 font-bold py-2.5 px-4 rounded-xl cursor-not-allowed text-sm">
                                ❌ No Disponible (<?php echo ucfirst($p['estado']); ?>)
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-gray-50 border border-gray-200 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold uppercase text-gray-500 tracking-wider">Fase 2: Liquidar Saldo</span>
                        <span class="text-[10px] bg-gray-500 text-white font-semibold px-2 py-0.5 rounded-full">Notaría</span>
                    </div>
                    <p class="text-xs text-gray-600 leading-normal">
                        El monto restante se transfiere una vez que el Mediador y el Notario Público validen los expedientes de identidad.
                    </p>
                    <div class="flex justify-between items-baseline border-b border-gray-200/60 pb-2">
                        <span class="text-xs text-gray-500 font-medium">Saldo Restante estimado:</span>
                        <span class="text-base font-bold text-gray-700">$<?php echo number_format(($p['precio'] - 10000), 2); ?> <span class="text-xs font-normal text-gray-400">MXN</span></span>
                    </div>
                    
                    <div class="flex items-center justify-between pt-1">
                        <span class="text-[10px] font-semibold text-gray-400 uppercase">Medio de Pago requerido:</span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 text-[10px] font-bold text-emerald-700 border border-emerald-200">
                            🏦 Transferencia SPEI / Bancaria
                        </span>
                    </div>
                </div>

                <?php if($logeado): ?>
                    <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4 mt-6">
                        <div class="flex items-start gap-3">
                            <div class="text-xl mt-0.5">📅</div>
                            <div>
                                <h4 class="text-base font-black text-slate-800 tracking-tight">Agendar una Visita</h4>
                                <p class="text-xs text-slate-500 mt-1 leading-relaxed">Elige un día para conocer la propiedad en tiempo real guiado por un asesor experto.</p>
                            </div>
                        </div>

                        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 flex items-center justify-between">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Agenda Abierta</p>
                            <span class="flex h-2.5 w-2.5 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </span>
                        </div>

                        <a href="vistas/agendar_cita.php?id=<?php echo $propiedad_id; ?>" class="block w-full bg-[#1e2230] hover:bg-indigo-600 text-white text-center py-3.5 rounded-xl font-black text-[13px] transition-all duration-300 shadow-md shadow-slate-900/5">
                            Consultar Horarios Disponibles
                        </a>
                    </div>
                <?php else: ?>
                    <div class="bg-amber-50 p-4 rounded-xl border border-amber-200 text-center space-y-2">
                        <p class="text-xs font-bold text-amber-800">📅 ¿Quieres conocer esta casa en persona?</p>
                        <p class="text-[11px] text-amber-700 leading-normal">Inicia sesión de forma rápida para proponer un día y hora de recorrido.</p>
                        <a href="vistas/login.php" class="inline-block bg-amber-600 hover:bg-amber-700 text-white font-bold py-1.5 px-4 rounded-lg text-xs transition shadow-sm">
                            Ingresar a mi cuenta
                        </a>
                    </div>
                <?php endif; ?>

                <div class="flex items-center justify-center gap-2 text-center text-[10px] text-gray-400 pt-2 border-t border-dashed">
                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    Conexión SSL Cifrada · Protección PCI-DSS por Stripe
                </div>

            </div>
        </div>
        
    </main>

    <footer class="bg-gray-900 text-gray-400 py-4 text-center text-sm">© 2026 Domu.</footer>

    <script>
        // 1. Control del estado de carga del Botón de Pago Seguro
        const paymentForm = document.getElementById('payment-form');
        const payButton = document.getElementById('pay-button');
        const buttonText = document.getElementById('button-text');
        const buttonIcon = document.getElementById('button-icon');

        if (paymentForm) {
            paymentForm.addEventListener('submit', function() {
                // Deshabilitar botón para evitar multi-clics financieros
                payButton.disabled = true;
                payButton.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
                payButton.classList.add('bg-indigo-400', 'cursor-not-allowed');
                
                // Cambiar textos e ícono a cargando
                buttonIcon.innerHTML = `<svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
                buttonText.innerText = "Conectando con Stripe seguro...";
            });
        }
    </script>

    <?php if (!empty($p['latitud']) && !empty($p['longitud'])): ?>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            const lat = <?php echo floatval($p['latitud']); ?>;
            const lng = <?php echo floatval($p['longitud']); ?>;

            const map = L.map('map').setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            L.marker([lat, lng]).addTo(map)
                .bindPopup('<div class="font-sans font-bold text-gray-800"><?php echo htmlspecialchars($p['titulo']); ?></div><div class="text-xs text-gray-500">Zona aproximada</div>')
                .openPopup();
        </script>
    <?php endif; ?>

</body>
</html>