<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'inmo_db';
$username = 'root';
$password = ''; // Vacío en XAMPP por defecto

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>