<?php
session_start();
require_once '../conexion.php';

// 1. Verificar sesión de usuario y envío de datos
if (!isset($_SESSION['usuario_id']) || !isset($_POST['propiedad_id'])) {
    header("Location: ../index.php?error=acceso_invalido");
    exit;
}

$usuario_id = intval($_SESSION['usuario_id']);
$propiedad_id = intval($_POST['propiedad_id']);

try {
    // 2. CREACIÓN DE LA TABLA (Sin transacciones para evitar conflictos DDL)
    // Nota: Hemos eliminado los FOREIGN KEY temporales por si tus tablas se llaman diferente en plural/singular
    $conn->exec("CREATE TABLE IF NOT EXISTS const_apartados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        propiedad_id INT NOT NULL,
        fecha_apartado TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Comprobar si la propiedad existe y está disponible
    $stmtCheck = $conn->prepare("SELECT estado FROM propiedad WHERE id = :id LIMIT 1");
    $stmtCheck->execute([':id' => $propiedad_id]);
    $prop = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$prop) {
        header("Location: ../index.php?error=propiedad_no_existe");
        exit;
    }

    if (strtolower($prop['estado']) == 'disponible') {
        
        // Iniciamos la transacción de forma segura solo para los cambios de datos
        $conn->beginTransaction();

        // 4. Cambiar el estado de la propiedad a 'apartada'
        $stmtUpdate = $conn->prepare("UPDATE propiedad SET estado = 'apartada' WHERE id = :id");
        $stmtUpdate->execute([':id' => $propiedad_id]);

        // 5. Registrar el apartado a nombre de este cliente
        $stmtInsert = $conn->prepare("INSERT INTO const_apartados (usuario_id, propiedad_id) VALUES (:user_id, :prop_id)");
        $stmtInsert->execute([
            ':user_id' => $usuario_id,
            ':prop_id' => $propiedad_id
        ]);

        // Si todo sale bien, guardamos
        $conn->commit();
        
        header("Location: ../vistas/cliente_dashboard.php?mensaje=apartado_exitoso");
        exit;
    } else {
        header("Location: ../detalles.php?id=" . $propiedad_id . "&error=no_disponible");
        exit;
    }

} catch (PDOException $e) {
    // 6. Validar si la transacción sigue activa antes de intentar el rollback
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Mostramos el verdadero error que originó el fallo para saber qué línea de la Base de Datos chilló
    die("Error real en la Base de Datos: " . $e->getMessage());
}