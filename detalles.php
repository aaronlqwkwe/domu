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
                    <span>📐 Area: <?php echo $p['area_m2']; ?> m²</span>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Descripción de la propiedad</h3>
                <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($p['descripcion'])); ?></p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 sticky top-24">
                <h3 class="text-xl font-bold text-gray-900 mb-2">¿Te interesa esta propiedad?</h3>
                <p class="text-sm text-gray-500 mb-6">Apártala directamente para congelar el inmueble. Un asesor se comunicará contigo de inmediato para continuar el proceso.</p>
                
                <?php if(isset($_GET['error']) && $_GET['error'] == 'no_disponible'): ?>
                    <div class="bg-red-50 text-red-700 p-3 rounded-lg text-xs mb-4 font-semibold">
                        ⚠️ Esta propiedad ya fue apartada por otro usuario.
                    </div>
                <?php endif; ?>

                <?php if(strtolower($p['estado']) == 'disponible'): ?>
                    <?php if($logeado): ?>
                        <form action="apis/api_apartar_interno.php" method="POST">
                            <input type="hidden" name="propiedad_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-xl transition text-center block shadow-lg shadow-indigo-200">
                                🤝 Apartar esta Propiedad
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="vistas/login.php" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-bold py-3 px-4 rounded-xl transition text-center block shadow-md">
                            Inicia sesión para apartar
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <button disabled class="w-full bg-gray-200 text-gray-400 font-bold py-3 px-4 rounded-xl cursor-not-allowed">
                        ❌ No Disponible (<?php echo ucfirst($p['estado']); ?>)
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-4 text-center text-sm">© 2026 Domu.</footer>
</body>
</html>