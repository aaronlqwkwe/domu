<?php
session_start();
require_once 'conexion.php'; // Asegúrate de que este archivo esté en la misma carpeta

// 1. Verificamos si hay una sesión activa
$logeado = isset($_SESSION['usuario_id']);
$nombre_usuario = $logeado ? $_SESSION['nombre'] : '';
$rol_usuario = $logeado ? $_SESSION['rol_id'] : null;

// 2. Consultar las propiedades reales de la base de datos
try {
    $stmt = $conn->prepare("SELECT * FROM propiedad ORDER BY id DESC");
    $stmt->execute();
    $propiedades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $propiedades = []; // En caso de error, lista vacía para no romper la página
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InmoPro - Encuentra tu hogar ideal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #f3f4f6; } 
        .hero-bg {
            background-image: linear-gradient(rgba(17, 24, 39, 0.75), rgba(17, 24, 39, 0.75)), url('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80');
            background-size: cover;
            background-position: center;
        }
        .property-card { transition: transform 0.2s, box-shadow 0.2s; background-color: white; }
        .property-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body class="font-sans text-gray-800 flex flex-col min-h-screen">

    <header class="bg-[#111827] text-white py-4 px-6 md:px-10 flex justify-between items-center shadow-lg sticky top-0 z-50">
        <div class="font-bold text-2xl text-white flex items-center gap-2">
            <svg class="w-8 h-8 text-[#6366f1]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            InmoPro
        </div>
        
        <div class="flex items-center gap-4">
            <?php if($logeado): ?>
                <span class="text-sm font-medium text-gray-300 hidden md:block">Hola, <?php echo htmlspecialchars($nombre_usuario); ?></span>
                
                <?php if($rol_usuario == 1): ?>
                    <a href="vistas/admin_dashboard.php" class="bg-gray-800 hover:bg-gray-700 text-white text-sm font-semibold py-2 px-4 rounded-lg transition">Panel Admin</a>
                <?php endif; ?>

                <a href="apis/api_logout.php" class="bg-red-500 hover:bg-red-600 text-white text-sm font-semibold py-2 px-4 rounded-lg transition">Salir</a>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nombre_usuario); ?>&background=6366f1&color=fff" class="h-10 w-10 rounded-full border-2 border-[#6366f1]" alt="Avatar">
            <?php else: ?>
                <a href="vistas/login.php" class="text-gray-300 hover:text-white font-medium text-sm transition">Iniciar Sesión</a>
                <a href="vistas/registro.php" class="bg-[#6366f1] hover:bg-[#4f46e5] text-white text-sm font-semibold py-2 px-5 rounded-lg transition shadow-lg shadow-indigo-500/30">Regístrate</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="hero-bg text-white min-h-[50vh] flex flex-col items-center justify-center px-6 text-center shadow-inner">
        <h1 class="text-5xl md:text-6xl font-extrabold mb-6 tracking-tight">Encuentra el lugar de tus sueños</h1>
        <p class="text-xl md:text-2xl text-gray-300 max-w-2xl mx-auto">Explora nuestro catálogo de propiedades exclusivas y da el siguiente paso hacia tu nuevo hogar.</p>
    </div>

    <main class="max-w-7xl mx-auto w-full p-6 md:py-16 md:px-10 flex-grow">
        
        <div class="flex justify-between items-end mb-10 border-b border-gray-300 pb-4">
            <h2 class="text-3xl font-bold text-gray-900">Propiedades Destacadas</h2>
            <span class="text-[#6366f1] font-semibold cursor-pointer hover:text-[#4f46e5] transition">Ver todas &rarr;</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <?php if (count($propiedades) > 0): ?>
                <?php foreach($propiedades as $p): ?>
                
                <div class="property-card rounded-2xl overflow-hidden border border-gray-200 block shadow-sm flex flex-col">
                    <div class="relative h-64 bg-gray-200">
                        <?php 
                            // Buscamos la foto en la carpeta uploads, si no hay, ponemos una por defecto
                            $foto_url = (!empty($p['imagen'])) ? 'uploads/' . $p['imagen'] : 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=800&q=80';
                        ?>
                        <img src="<?php echo $foto_url; ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($p['titulo']); ?>">
                        
                        <div class="absolute top-4 left-4 bg-white px-3 py-1 rounded-full text-xs font-bold text-gray-900 shadow capitalize">
                            <?php echo htmlspecialchars($p['estado']); ?>
                        </div>
                        
                        <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black/80 to-transparent p-4">
                            <div class="text-white font-bold text-2xl">$<?php echo number_format($p['precio'], 2); ?> <span class="text-xs font-normal">MXN</span></div>
                        </div>
                    </div>
                    
                    <div class="p-6 flex flex-col flex-grow">
                        <h3 class="text-xl font-bold text-gray-900 mb-2 truncate"><?php echo htmlspecialchars($p['titulo']); ?></h3>
                        
                        <p class="text-gray-500 text-sm mb-4 line-clamp-2">
                            <?php echo htmlspecialchars($p['descripcion']); ?>
                        </p>

                        <div class="mt-auto">
                            <div class="flex justify-between items-center border-t border-gray-100 pt-4 text-sm text-gray-600">
                                <div class="flex items-center gap-1 font-semibold text-gray-900">
                                    📐 <?php echo $p['area_m2']; ?> m²
                                </div>
                                
                                <?php if($p['latitud'] && $p['longitud']): ?>
                                <a href="https://www.google.com/maps?q=<?php echo $p['latitud']; ?>,<?php echo $p['longitud']; ?>" target="_blank" class="text-[#6366f1] hover:underline flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                                    Ver Mapa
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <a href="detalles.php?id=<?php echo $p['id']; ?>" class="block w-full text-center mt-4 bg-gray-900 text-white py-2 rounded-lg font-bold hover:bg-gray-800 transition">
                                Ver Detalles
                            </a>
                        </div>
                    </div>
                </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-20">
                    <p class="text-gray-400 text-xl">No hay propiedades publicadas todavía.</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-6 text-center text-sm mt-auto">
        <p>&copy; 2026 InmoPro. Todos los derechos reservados.</p>
    </footer>

</body>
</html>