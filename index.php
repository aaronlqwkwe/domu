<?php
// 1. Encendemos el reporte de errores para que si algo falla, nos avise exactamente qué es
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'conexion.php'; // Asegúrate de que este archivo esté en la misma carpeta

// 2. Verificamos si hay una sesión activa
$logeado = isset($_SESSION['usuario_id']);
$nombre_usuario = $logeado ? $_SESSION['nombre'] : '';
$rol_usuario = $logeado ? $_SESSION['rol_id'] : null;

// 3. Consultar las propiedades reales de la base de datos con filtros dinámicos
try {
    $sql = "SELECT * FROM propiedad WHERE LOWER(estado) = 'disponible'";
    $params = [];

    // Filtro por palabra clave (Usamos parámetros distintos para evitar errores en PDO)
    if (isset($_GET['busqueda']) && !empty(trim($_GET['busqueda']))) {
        $sql .= " AND (titulo LIKE :busqueda1 OR descripcion LIKE :busqueda2)";
        $params[':busqueda1'] = '%' . trim($_GET['busqueda']) . '%';
        $params[':busqueda2'] = '%' . trim($_GET['busqueda']) . '%';
    }

    // Filtro por precio mínimo
    if (isset($_GET['precio_min']) && is_numeric($_GET['precio_min']) && $_GET['precio_min'] > 0) {
        $sql .= " AND precio >= :precio_min";
        $params[':precio_min'] = $_GET['precio_min'];
    }

    // Filtro por precio máximo
    if (isset($_GET['precio_max']) && is_numeric($_GET['precio_max']) && $_GET['precio_max'] > 0) {
        $sql .= " AND precio <= :precio_max";
        $params[':precio_max'] = $_GET['precio_max'];
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $propiedades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Si llega a fallar algo de la base de datos, te lo pintará en pantalla de forma clara
    echo "<div style='background:#f8d7da; color:#721c24; padding:15px; font-family:sans-serif;'><b>Error en consulta SQL:</b> " . htmlspecialchars($e->getMessage()) . "</div>";
    $propiedades = []; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domu - Encuentra tu hogar ideal</title>
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
        <div class="font-bold text-2xl text-white flex items-center gap-2 cursor-pointer" onclick="window.location.href='index.php'">
            <svg class="w-8 h-8 text-[#6366f1]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Domu
        </div>
        
        <div class="flex items-center gap-4">
            <?php if($logeado): ?>
                <span class="text-sm font-medium text-gray-300 hidden md:block">Hola, <?php echo htmlspecialchars($nombre_usuario); ?></span>
                
                <?php if($rol_usuario == 1): ?>
                    <a href="vistas/admin_dashboard.php" class="bg-gray-800 hover:bg-gray-700 text-white text-sm font-semibold py-2 px-4 rounded-lg transition">Panel Admin</a>
                <?php endif; ?>

                <?php if($rol_usuario == 2): ?>
                    <a href="vistas/agente_dashboard.php" class="bg-gray-800 hover:bg-gray-700 text-white text-sm font-semibold py-2 px-4 rounded-lg transition">Panel Agente</a>
                <?php endif; ?>

                <?php if($rol_usuario != 1 && $rol_usuario != 2): ?>
                    <a href="vistas/cliente_dashboard.php" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2 px-4 rounded-lg transition shadow-md shadow-indigo-500/20">Mi Cuenta</a>
                <?php endif; ?>

                <a href="apis/api_logout.php" class="bg-red-500 hover:bg-red-600 text-white text-sm font-semibold py-2 px-4 rounded-lg transition">Salir</a>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nombre_usuario); ?>&background=6366f1&color=fff" class="h-10 w-10 rounded-full border-2 border-[#6366f1]" alt="Avatar">
            <?php else: ?>
                <a href="vistas/login.php" class="text-gray-300 hover:text-white font-medium text-sm transition">Iniciar Sesión</a>
                <a href="vistas/registro.php" class="bg-[#6366f1] hover:bg-[#4f46e5] text-white text-sm font-semibold py-2 px-5 rounded-lg transition shadow-lg shadow-indigo-500/30">Regístrate</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if(isset($_GET['mensaje']) && $_GET['mensaje'] == 'pago_completado'): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl my-4 max-w-7xl mx-auto w-full">
        <span class="font-bold">¡Felicidades! 🎉 El pago se ha procesado con éxito y la propiedad ha sido apartada.</span>
    </div>
    <?php endif; ?>

    <?php if(isset($_GET['error']) && $_GET['error'] == 'pago_cancelado'): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl my-4 max-w-7xl mx-auto w-full">
        <span class="font-bold">El pago fue cancelado. No se realizó ningún cargo.</span>
    </div>
    <?php endif; ?>

    <div class="hero-bg text-white min-h-[50vh] flex flex-col items-center justify-center px-6 text-center shadow-inner">
        <h1 class="text-5xl md:text-6xl font-extrabold mb-6 tracking-tight">Encuentra el lugar de tus sueños</h1>
        <p class="text-xl md:text-2xl text-gray-300 max-w-2xl mx-auto">Explora nuestro catálogo de propiedades exclusivas y da el siguiente paso hacia tu nuevo hogar.</p>
    </div>

    <main class="max-w-7xl mx-auto w-full p-6 md:py-12 md:px-10 flex-grow">
        
        <div class="mb-12 -mt-16 relative z-10">
            <form action="index.php" method="GET" class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 flex flex-col md:flex-row gap-4 items-end">
                
                <div class="flex-1 w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Buscar propiedad</label>
                    <input type="text" name="busqueda" placeholder="Ej. Casa con alberca, departamento..." 
                           value="<?php echo isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : ''; ?>"
                           class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2 border outline-none text-sm">
                </div>

                <div class="w-full md:w-44">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio Mínimo ($)</label>
                    <input type="number" name="precio_min" placeholder="Min" 
                           value="<?php echo isset($_GET['precio_min']) ? htmlspecialchars($_GET['precio_min']) : ''; ?>"
                           class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2 border outline-none text-sm">
                </div>

                <div class="w-full md:w-44">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Precio Máximo ($)</label>
                    <input type="number" name="precio_max" placeholder="Max" 
                           value="<?php echo isset($_GET['precio_max']) ? htmlspecialchars($_GET['precio_max']) : ''; ?>"
                           class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-4 py-2 border outline-none text-sm">
                </div>

                <div class="w-full md:w-auto flex gap-2">
                    <button type="submit" class="flex-1 md:flex-none bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-xl transition text-sm">
                        Buscar
                    </button>
                    <?php if(!empty($_GET['busqueda']) || !empty($_GET['precio_min']) || !empty($_GET['precio_max'])): ?>
                        <a href="index.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2.5 px-4 rounded-xl transition text-sm text-center">
                            Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="flex justify-between items-end mb-10 border-b border-gray-300 pb-4">
            <h2 class="text-3xl font-bold text-gray-900">
                <?php echo (!empty($_GET['busqueda']) || !empty($_GET['precio_min']) || !empty($_GET['precio_max'])) ? 'Resultados encontrados' : 'Propiedades Destacadas'; ?>
            </h2>
            <span onclick="window.location.href='index.php'" class="text-[#6366f1] font-semibold cursor-pointer hover:text-[#4f46e5] transition">Ver todas →</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            
            <?php if (count($propiedades) > 0): ?>
                <?php foreach($propiedades as $p): ?>
                
                <div class="property-card rounded-2xl overflow-hidden border border-gray-200 block shadow-sm flex flex-col">
                    <div class="relative h-64 bg-gray-200">
                        <?php 
                            $foto_url = (!empty($p['imagen'])) ? 'uploads/' . $p['imagen'] : 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?auto=format&fit=crop&w=800&q=80';
                        ?>
                        <img src="<?php echo $foto_url; ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($p['titulo']); ?>">
                        
                        <div class="absolute top-4 left-4 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow uppercase">
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
                                
                                <?php if(!empty($p['latitud']) && !empty($p['longitud'])): ?>
                                <a href="https://maps.google.com/?q=<?php echo $p['latitud']; ?>,<?php echo $p['longitud']; ?>" target="_blank" class="text-[#6366f1] hover:underline flex items-center gap-1">
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
                <div class="col-span-full text-center py-20 bg-white rounded-2xl border border-gray-200">
                    <p class="text-gray-400 text-xl font-medium">No se encontraron propiedades con esos criterios. ¡Intenta con otra combinación!</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-6 text-center text-sm mt-auto">
        <p>© 2026 Domu. Todos los derechos reservados.</p>
    </footer>

    <?php if($logeado): ?>
    <script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    (function(){
    var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
    s1.async=true;
    s1.src='https://embed.tawk.to/6a03fcfe0554a21c38ac4bd2/1jofp8gf6';
    s1.charset='UTF-8';
    s1.setAttribute('crossorigin','*');
    s0.parentNode.insertBefore(s1,s0);
    })();
    </script>
    <?php endif; ?>

</body>
</html>