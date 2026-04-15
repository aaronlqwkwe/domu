<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header("Location: ../index.php");
    exit;
}
$nombre_admin = $_SESSION['nombre'];
require_once '../conexion.php'; 

// 1. Consulta de Usuarios
$stmt = $conn->prepare("SELECT id, nombre, email, rol_id FROM usuarios ORDER BY id DESC");
$stmt->execute();
$lista_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_usuarios = count($lista_usuarios);

// 2. Consulta de Propiedades (Lista completa para la tabla)
$stmt_prop_list = $conn->prepare("SELECT * FROM propiedad ORDER BY id DESC");
$stmt_prop_list->execute();
$lista_propiedades = $stmt_prop_list->fetchAll(PDO::FETCH_ASSOC);
$total_propiedades = count($lista_propiedades);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InmoPro - Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        #map { height: 300px; width: 100%; border-radius: 12px; z-index: 1; }
        .modal-scroll::-webkit-scrollbar { width: 6px; }
        .modal-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="bg-gray-50 font-sans flex h-screen overflow-hidden">

    <aside class="w-64 bg-[#111118] text-gray-400 flex flex-col shadow-2xl h-full z-20">
        <div class="p-6 border-b border-gray-800">
            <h1 class="font-bold text-2xl text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-[#6366f1]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Inmo<span class="text-[#6366f1]">Admin</span>
            </h1>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="admin_dashboard.php" class="flex items-center gap-3 bg-[#6366f1] text-white px-4 py-3 rounded-xl transition">Dashboard</a>
            <button onclick="abrirModalPropiedad()" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition w-full text-left">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nueva Propiedad
            </button>
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
            
            <?php if(isset($_GET['mensaje']) && $_GET['mensaje'] == 'propiedad_guardada'): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-6 flex justify-between items-center">
                    <span class="font-bold">¡Éxito! Propiedad guardada correctamente.</span>
                    <button onclick="this.parentElement.remove()" class="text-green-700 font-bold">&times;</button>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div><p class="text-sm font-medium text-gray-500 mb-1">Propiedades</p><p class="text-3xl font-bold text-gray-900"><?php echo $total_propiedades; ?></p></div>
                    <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center text-[#6366f1]"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg></div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div><p class="text-sm font-medium text-gray-500 mb-1">Usuarios</p><p class="text-3xl font-bold text-gray-900"><?php echo $total_usuarios; ?></p></div>
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
                                    <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase <?php echo ($prop['estado'] == 'Disponible') ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-orange-600'; ?>">
                                        <?php echo htmlspecialchars($prop['estado']); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <button class="text-indigo-600 hover:text-indigo-900 font-bold mr-3">Editar</button>
                                    <a href="../apis/api_eliminar_propiedad.php?id=<?php echo $prop['id']; ?>" onclick="return confirm('¿Seguro que quieres borrar esta propiedad?')" class="text-red-500 hover:text-red-700 font-bold">Borrar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($lista_propiedades)): ?>
                            <tr><td colspan="5" class="p-4 text-center text-gray-500">No hay propiedades registradas aún.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">Usuarios Registrados</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase border-b border-gray-100">
                                <th class="p-4 font-bold">Nombre</th>
                                <th class="p-4 font-bold">Email</th>
                                <th class="p-4 font-bold text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            <?php foreach($lista_usuarios as $user): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 font-medium text-gray-900"><?php echo htmlspecialchars($user['nombre']); ?></td>
                                <td class="p-4 text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="p-4 text-center">
                                    <button class="text-indigo-600 font-bold mr-3">Editar</button>
                                    <a href="../apis/api_eliminar_usuario.php?id=<?php echo $user['id']; ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este usuario definitivamente?')" class="text-red-500 hover:text-red-700 font-bold">Borrar</a>
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
                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Título del Anuncio</label>
                        <input type="text" name="titulo" required placeholder="Ej: Casa Moderna en Zapopan" class="w-full border rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label>
                        <textarea name="descripcion" rows="3" placeholder="Detalles de la propiedad..." class="w-full border rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Precio ($ MXN)</label>
                        <input type="number" name="precio" step="0.01" required class="w-full border rounded-xl px-4 py-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Área (m²)</label>
                        <input type="number" name="area_m2" step="0.1" required class="w-full border rounded-xl px-4 py-2.5">
                    </div>

                    <div class="col-span-2 bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                        <label class="block text-sm font-bold text-indigo-900 mb-2">📸 Foto de la Propiedad</label>
                        <input type="file" name="foto" id="foto" accept="image/*" onchange="previewImagen(event)" required 
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer">
                        
                        <div id="preview-container" class="hidden mt-4">
                            <p class="text-xs text-gray-500 mb-1">Vista previa:</p>
                            <img id="img-preview" src="#" class="h-40 w-full object-cover rounded-lg border-2 border-white shadow-md">
                        </div>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">📍 Ubicación aproximada</label>
                        <div id="map"></div>
                    </div>

                    <div class="col-span-2 grid grid-cols-2 gap-4 bg-gray-100 p-3 rounded-xl">
                        <div>
                            <label class="text-[10px] uppercase font-bold text-gray-500">Latitud</label>
                            <input type="text" name="latitud" id="input_lat" readonly class="w-full bg-transparent font-mono text-sm">
                        </div>
                        <div>
                            <label class="text-[10px] uppercase font-bold text-gray-500">Longitud</label>
                            <input type="text" name="longitud" id="input_lon" readonly class="w-full bg-transparent font-mono text-sm">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="cerrarModalPropiedad()" class="px-6 py-2.5 text-gray-500 font-bold">Cancelar</button>
                    <button type="submit" class="px-8 py-2.5 bg-[#6366f1] text-white rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-[#4f46e5] transition-all">
                        Publicar Propiedad
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        let map, marker;

        // Vista previa de imagen
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

        function abrirModalPropiedad() { 
            document.getElementById('modalPropiedad').classList.remove('hidden');
            setTimeout(initLeaflet, 300); // Darle tiempo al modal de renderizarse
        }

        function cerrarModalPropiedad() { 
            document.getElementById('modalPropiedad').classList.add('hidden'); 
        }

        // Lógica del mapa gratuita y a prueba de errores
        function initLeaflet() {
            const lonLatInicial = [20.671956, -103.348821]; // Coordenadas en Guadalajara/Zapopan

            if (!map) {
                map = L.map('map').setView(lonLatInicial, 13);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                marker = L.marker(lonLatInicial, { draggable: true }).addTo(map);

                function actCoordenadas(lat, lng) {
                    document.getElementById("input_lat").value = lat.toFixed(6);
                    document.getElementById("input_lon").value = lng.toFixed(6);
                }

                actCoordenadas(lonLatInicial[0], lonLatInicial[1]);

                marker.on('dragend', function() { 
                    actCoordenadas(marker.getLatLng().lat, marker.getLatLng().lng); 
                });
                
                map.on('click', function(e) { 
                    marker.setLatLng(e.latlng); 
                    actCoordenadas(e.latlng.lat, e.latlng.lng); 
                });
            } else {
                map.invalidateSize();
            }
        }
    </script>
</body>
</html>