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

// 2. Consulta de Propiedades para el contador
$stmt_prop = $conn->prepare("SELECT COUNT(*) as total FROM propiedad");
$stmt_prop->execute();
$total_propiedades = $stmt_prop->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InmoPro - Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/esri-leaflet-geocoder@3.1.4/dist/esri-leaflet-geocoder.css">

    <style>
        #map { height: 350px; width: 100%; border-radius: 12px; z-index: 1; }
        /* Ajuste para que el buscador no se esconda */
        .geocoder-control-input { border-radius: 8px !important; border: 1px solid #ddd !important; }
    </style>
</head>
<body class="bg-gray-50 font-sans flex h-screen overflow-hidden">

    <aside class="w-64 bg-[#111118] text-gray-400 flex flex-col shadow-2xl h-full z-20">
        <div class="p-6 border-b border-gray-800">
            <h1 class="font-bold text-2xl text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-[#6366f1]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Inmo<span class="text-[#6366f1]">Admin</span>
            </h1>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="admin_dashboard.php" class="flex items-center gap-3 bg-[#6366f1] text-white px-4 py-3 rounded-xl transition">Dashboard</a>
            <button onclick="abrirModalPropiedad()" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition w-full text-left">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"></path></svg>
                Nueva Propiedad
            </button>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <a href="../apis/api_logout.php" class="flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition text-sm font-semibold w-full">Cerrar Sesión</a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        <header class="bg-white border-b border-gray-200 py-4 px-8 flex justify-between items-center z-10">
            <h2 class="text-2xl font-bold text-gray-800">Resumen General</h2>
            <div class="flex items-center gap-4">
                <div class="text-right hidden md:block">
                    <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($nombre_admin); ?></p>
                    <p class="text-xs text-gray-500">Administrador</p>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nombre_admin); ?>&background=111118&color=fff" class="h-10 w-10 rounded-full border border-gray-300">
            </div>
        </header>

        <div class="p-8 flex-1 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div><p class="text-sm font-medium text-gray-500 mb-1">Total Propiedades</p><p class="text-3xl font-bold text-gray-900"><?php echo $total_propiedades; ?></p></div>
                    <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center text-[#6366f1]"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg></div>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div><p class="text-sm font-medium text-gray-500 mb-1">Usuarios Registrados</p><p class="text-3xl font-bold text-gray-900"><?php echo $total_usuarios; ?></p></div>
                    <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-500"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg></div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Usuarios Registrados</h3>
                    <button onclick="abrirModalUsuario()" class="text-sm bg-[#6366f1] text-white px-4 py-2 rounded-lg hover:bg-[#4f46e5] transition shadow-md">+ Nuevo Usuario</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-sm border-b border-gray-100">
                                <th class="p-4 font-medium">ID</th>
                                <th class="p-4 font-medium">Nombre</th>
                                <th class="p-4 font-medium">Email</th>
                                <th class="p-4 font-medium text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100">
                            <?php foreach($lista_usuarios as $user): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 text-gray-500">#<?php echo $user['id']; ?></td>
                                <td class="p-4 font-medium text-gray-900"><?php echo htmlspecialchars($user['nombre']); ?></td>
                                <td class="p-4 text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="p-4 text-center">
                                    <button onclick="abrirEditarUsuario('<?php echo $user['id']; ?>', '<?php echo addslashes($user['nombre']); ?>', '<?php echo $user['email']; ?>', '<?php echo $user['rol_id']; ?>')" class="text-[#6366f1] font-medium mr-2">Editar</button>
                                    <a href="../apis/api_eliminar_usuario.php?id=<?php echo $user['id']; ?>" class="text-red-500 font-medium">Borrar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="modalNuevoUsuario" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
            <h3 id="modalTitulo" class="text-xl font-bold text-gray-800 mb-4">Usuario</h3>
            <form id="formUsuario" action="../apis/api_crear_usuario_admin.php" method="POST" class="space-y-4">
                <input type="hidden" name="id" id="edit_id">
                <input type="text" name="nombre" id="edit_nombre" placeholder="Nombre" required class="w-full border rounded-lg px-4 py-2">
                <input type="email" name="email" id="edit_email" placeholder="Email" required class="w-full border rounded-lg px-4 py-2">
                <input type="password" name="password" placeholder="Contraseña" class="w-full border rounded-lg px-4 py-2">
                <select name="rol_id" id="edit_rol" class="w-full border rounded-lg px-4 py-2 bg-white">
                    <option value="3">Cliente</option>
                    <option value="2">Agente</option>
                    <option value="1">Administrador</option>
                </select>
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="cerrarModalUsuario()" class="px-4 py-2 text-gray-600">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-[#6366f1] text-white rounded-lg">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalPropiedad" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl w-full max-w-4xl p-6 shadow-xl overflow-y-auto max-h-[95vh]">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-xl font-bold text-gray-800">Nueva Propiedad</h3>
                <button onclick="cerrarModalPropiedad()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>

            <form action="../apis/api_crear_propiedad.php" method="POST" class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="text-sm font-bold">Título</label>
                    <input type="text" name="titulo" required class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="col-span-2">
                    <label class="text-sm font-bold">Descripción</label>
                    <textarea name="descripcion" rows="2" class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                <div>
                    <label class="text-sm font-bold">Precio ($)</label>
                    <input type="number" name="precio" step="0.01" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="text-sm font-bold">Área (m²)</label>
                    <input type="number" name="area_m2" step="0.1" required class="w-full border rounded-lg px-3 py-2">
                </div>
                
                <div class="col-span-2 border-t pt-4">
                    <label class="text-sm font-bold mb-2 block text-indigo-600">📍 Ubicación en el Mapa (Gratis)</label>
                    <div id="map"></div>
                </div>

                <div class="bg-gray-100 p-3 rounded-lg flex gap-4 col-span-2">
                    <div class="flex-1">
                        <label class="text-[10px] uppercase font-bold text-gray-500">Latitud</label>
                        <input type="text" name="latitud" id="input_lat" readonly class="w-full bg-transparent font-mono text-sm outline-none">
                    </div>
                    <div class="flex-1">
                        <label class="text-[10px] uppercase font-bold text-gray-500">Longitud</label>
                        <input type="text" name="longitud" id="input_lon" readonly class="w-full bg-transparent font-mono text-sm outline-none">
                    </div>
                </div>

                <div class="col-span-2 flex justify-end gap-3 pt-4">
                    <button type="button" onclick="cerrarModalPropiedad()" class="px-6 py-2 text-gray-500 font-semibold">Cancelar</button>
                    <button type="submit" class="px-6 py-2 bg-[#6366f1] text-white rounded-lg shadow-lg font-semibold hover:bg-[#4f46e5]">Publicar Propiedad</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/esri-leaflet@3.0.10/dist/esri-leaflet.js"></script>
    <script src="https://unpkg.com/esri-leaflet-geocoder@3.1.4/dist/esri-leaflet-geocoder.js"></script>

    <script>
        // Scripts Usuarios
        function abrirModalUsuario() {
            document.getElementById('modalTitulo').innerText = "Nuevo Usuario";
            document.getElementById('formUsuario').action = "../apis/api_crear_usuario_admin.php";
            document.getElementById('modalNuevoUsuario').classList.remove('hidden');
        }
        function abrirEditarUsuario(id, nombre, email, rol) {
            document.getElementById('modalTitulo').innerText = "Editar Usuario";
            document.getElementById('formUsuario').action = "../apis/api_editar_usuario.php";
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_rol').value = rol;
            document.getElementById('modalNuevoUsuario').classList.remove('hidden');
        }
        function cerrarModalUsuario() { document.getElementById('modalNuevoUsuario').classList.add('hidden'); }

        // Scripts Propiedades y Mapa Leaflet
        let map, marker;

        function abrirModalPropiedad() { 
            document.getElementById('modalPropiedad').classList.remove('hidden');
            // Inicializar el mapa después de mostrar el modal
            setTimeout(initMapGratis, 300);
        }

        function cerrarModalPropiedad() { 
            document.getElementById('modalPropiedad').classList.add('hidden'); 
        }

        function initMapGratis() {
            const initialPos = [20.659698, -103.349609]; // Guadalajara

            if (!map) {
                map = L.map('map').setView(initialPos, 13);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                marker = L.marker(initialPos, { draggable: true }).addTo(map);

                // Actualizar inputs al mover marcador
                marker.on('dragend', function() {
                    const pos = marker.getLatLng();
                    document.getElementById("input_lat").value = pos.lat.toFixed(6);
                    document.getElementById("input_lon").value = pos.lng.toFixed(6);
                });

                // Clic en mapa mueve el marcador
                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    document.getElementById("input_lat").value = e.latlng.lat.toFixed(6);
                    document.getElementById("input_lon").value = e.latlng.lng.toFixed(6);
                });

                // Añadir Buscador
                const searchControl = L.esri.Geocoding.geosearch({
                    placeholder: 'Buscar dirección...',
                    useMapBounds: false
                }).addTo(map);

                searchControl.on('results', function(data) {
                    if (data.results.length > 0) {
                        const res = data.results[0];
                        marker.setLatLng(res.latlng);
                        map.setView(res.latlng, 16);
                        document.getElementById("input_lat").value = res.latlng.lat.toFixed(6);
                        document.getElementById("input_lon").value = res.latlng.lng.toFixed(6);
                    }
                });
            } else {
                // Re-ajustar tamaño si ya existe (evita mapa gris)
                map.invalidateSize();
            }
        }
    </script>
</body>
</html>