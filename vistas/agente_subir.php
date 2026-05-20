<?php
session_start();
// ¡OJO AQUÍ! Asegúrate de que 2 sea tu rol de Agente (o cámbialo a 3 si el 2 es cliente)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 2) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Propiedad - Panel Agente</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map { z-index: 1; }
    </style>
</head>
<body class="bg-gray-50 font-sans flex h-screen overflow-hidden">

    <aside class="w-64 bg-[#1e1e2f] text-gray-400 flex flex-col shadow-2xl h-full z-20">
        <div class="p-6 border-b border-gray-800">
            <h1 class="font-bold text-2xl text-white flex items-center gap-2">
                Panel <span class="text-[#10b981]">Agente</span>
            </h1>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="agente_subir.php" class="flex items-center gap-3 bg-[#10b981] text-white px-4 py-3 rounded-xl transition shadow-lg shadow-emerald-500/30">
                + Subir Propiedad
            </a>
            <a href="agente_dashboard.php" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition">
                Mi Inventario
            </a>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <a href="../apis/api_logout.php" class="flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition text-sm font-semibold w-full">Cerrar Sesión</a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-y-auto">
        <header class="bg-white border-b border-gray-200 py-4 px-8 flex justify-between items-center z-10">
            <h2 class="text-2xl font-bold text-gray-800">Registrar Nueva Propiedad</h2>
            <span class="text-sm text-gray-500 font-bold">Agente: <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
        </header>

        <div class="p-8 max-w-5xl mx-auto w-full">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                <form action="../apis/api_crear_propiedad_agente.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="border-b pb-4 mb-4">
                        <h3 class="text-xl font-bold text-gray-800">Subir Nueva Propiedad para Revisión</h3>
                        <p class="text-sm text-gray-500">Llena los datos. Un administrador revisará la publicación antes de hacerla pública.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Título del Anuncio</label>
                            <input type="text" name="titulo" required placeholder="Ej: Casa Moderna en Zapopan" class="w-full border rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                            <textarea name="descripcion" rows="3" placeholder="Detalles de la propiedad..." class="w-full border rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-emerald-500 outline-none"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Precio ($ MXN)</label>
                            <input type="number" name="precio" step="0.01" required class="w-full border rounded-xl px-4 py-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Área (m²)</label>
                            <input type="number" name="area_m2" step="0.1" required class="w-full border rounded-xl px-4 py-2.5">
                        </div>

                        <div class="col-span-2 bg-emerald-50 p-4 rounded-xl border border-emerald-100">
                            <label class="block text-sm font-bold text-emerald-900 mb-2">📸 Foto de la Propiedad</label>
                            <input type="file" name="foto" id="foto" accept="image/*" onchange="previewImagen(event)" required 
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 cursor-pointer">
                            
                            <div id="preview-container" class="hidden mt-4">
                                <img id="img-preview" src="#" class="h-40 w-full object-cover rounded-lg border-2 border-white shadow-md">
                            </div>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-2">📍 Ubicación aproximada</label>
                            <div id="map" class="h-64 w-full rounded-xl z-10 border border-gray-300"></div>
                        </div>

                        <div class="col-span-2 grid grid-cols-2 gap-4 bg-gray-100 p-3 rounded-xl">
                            <div>
                                <label class="text-[10px] uppercase font-bold text-gray-500">Latitud</label>
                                <input type="text" name="latitud" id="input_lat" readonly class="w-full bg-transparent font-mono text-sm outline-none">
                            </div>
                            <div>
                                <label class="text-[10px] uppercase font-bold text-gray-500">Longitud</label>
                                <input type="text" name="longitud" id="input_lon" readonly class="w-full bg-transparent font-mono text-sm outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t">
                        <button type="submit" class="px-8 py-2.5 bg-[#10b981] text-white rounded-xl font-bold shadow-lg shadow-emerald-200 hover:bg-[#059669] transition-all">
                            Enviar a Revisión
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // 1. Script para previsualizar la imagen antes de subirla
        function previewImagen(event) {
            const reader = new FileReader();
            reader.onload = function(){
                const output = document.getElementById('img-preview');
                output.src = reader.result;
                document.getElementById('preview-container').classList.remove('hidden');
            };
            if(event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            }
        }

        // 2. Script para cargar el mapa interactivo
        const lonLatInicial = [20.671956, -103.348821]; // Coordenadas iniciales (Zapopan/Guadalajara)
        
        const map = L.map('map').setView(lonLatInicial, 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        const marker = L.marker(lonLatInicial, { draggable: true }).addTo(map);

        function actCoordenadas(lat, lng) {
            document.getElementById("input_lat").value = lat.toFixed(6);
            document.getElementById("input_lon").value = lng.toFixed(6);
        }

        // Fijar coordenadas de inicio
        actCoordenadas(lonLatInicial[0], lonLatInicial[1]);

        // Actualizar coordenadas al arrastrar el marcador
        marker.on('dragend', function() { 
            actCoordenadas(marker.getLatLng().lat, marker.getLatLng().lng); 
        });
        
        // Mover marcador al hacer click en el mapa
        map.on('click', function(e) { 
            marker.setLatLng(e.latlng); 
            actCoordenadas(e.latlng.lat, e.latlng.lng); 
        });
    </script>
</body>
</html>