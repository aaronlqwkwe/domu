<?php
// 1. Iniciamos/Reanudamos la sesión actual para poder manipularla
session_start();

// 2. Vaciamos todas las variables que guardamos (usuario_id, nombre, rol_id)
session_unset();

// 3. Destruimos la sesión por completo en el servidor
session_destroy();

// 4. Redirigimos al usuario de vuelta al index.php (subimos un nivel con ../)
header("Location: ../index.php");
exit;
?>