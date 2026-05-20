<?php
session_start();
require_once '../conexion.php';

$propiedad_id = $_GET['id'] ?? null;

if ($propiedad_id) {
    try {
        // Cambiamos el estado de la propiedad en tu base de datos
        $stmt = $conn->prepare("UPDATE propiedad SET estado = 'Apartada' WHERE id = :id");
        $stmt->execute([':id' => $propiedad_id]);
        
        // Aquí podrías agregar un INSERT INTO historial_pagos si tuvieras esa tabla
        
        // Redirigimos al index con un mensaje de éxito
        header("Location: ../index.php?mensaje=pago_completado");
        exit;
    } catch (PDOException $e) {
        die("Error al actualizar la propiedad: " . $e->getMessage());
    }
} else {
    header("Location: ../index.php");
    exit;
}