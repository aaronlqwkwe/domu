<?php
session_start();

// 1. SEGURIDAD: Verificar si el usuario está logeado y si su rol es 1 (Admin)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    // Si no cumple las condiciones, lo mandamos al index
    header("Location: index.php");
    exit;
}

$nombre_admin = $_SESSION['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InmoPro - Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 font-sans flex h-screen overflow-hidden">

    <aside class="w-64 bg-[#111118] text-gray-400 flex flex-col shadow-2xl h-full z-20">
        <div class="p-6 border-b border-gray-800">
            <h1 class="font-bold text-2xl text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-[#6366f1]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Inmo<span class="text-[#6366f1]">Admin</span>
            </h1>
        </div>
        
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="#" class="flex items-center gap-3 bg-[#6366f1] text-white px-4 py-3 rounded-xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>
            <a href="#" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Usuarios y Agentes
            </a>
            <a href="#" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Propiedades
            </a>
            <a href="#" class="flex items-center gap-3 hover:bg-gray-800 hover:text-white px-4 py-3 rounded-xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Configuración
            </a>
        </nav>
        
        <div class="p-4 border-t border-gray-800">
            <a href="apis/api_logout.php" class="flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition text-sm font-semibold w-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Cerrar Sesión
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-full bg-gray-50 overflow-hidden">
        
        <header class="bg-white border-b border-gray-200 py-4 px-8 flex justify-between items-center z-10">
            <h2 class="text-2xl font-bold text-gray-800">Resumen General</h2>
            <div class="flex items-center gap-4">
                <a href="index.php" class="text-sm text-[#6366f1] hover:underline font-medium">Ver sitio público</a>
                <div class="h-8 w-px bg-gray-300"></div>
                <div class="flex items-center gap-3">
                    <div class="text-right hidden md:block">
                        <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($nombre_admin); ?></p>
                        <p class="text-xs text-gray-500">Administrador</p>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nombre_admin); ?>&background=111118&color=fff" class="h-10 w-10 rounded-full border border-gray-300">
                </div>
            </div>
        </header>

        <div class="p-8 flex-1 overflow-y-auto">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Propiedades</p>
                        <p class="text-3xl font-bold text-gray-900">124</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center text-[#6366f1]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Usuarios Registrados</p>
                        <p class="text-3xl font-bold text-gray-900">856</p>
                    </div>
                    <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Agentes Activos</p>
                        <p class="text-3xl font-bold text-gray-900">12</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-50 rounded-full flex items-center justify-center text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Citas Pendientes</p>
                        <p class="text-3xl font-bold text-gray-900">28</p>
                    </div>
                    <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center text-red-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 min-h-[400px] flex items-center justify-center text-gray-400">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <p class="text-lg font-medium">Aquí iría una tabla con las últimas propiedades registradas.</p>
                    <p class="text-sm mt-2">Pronto la conectaremos a la base de datos.</p>
                </div>
            </div>

        </div>
    </main>

</body>
</html>
