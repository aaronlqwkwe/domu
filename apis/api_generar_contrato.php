<?php
// Evitar cualquier salida de texto previa para no corromper el PDF
ob_start();
session_start();

// Verificar autenticación de administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    die("Acceso denegado.");
}

if (!isset($_GET['apartado_id'])) {
    die("ID de operación no especificado.");
}

require_once '../conexion.php';
// Importamos la librería FPDF (Asegúrate de que la ruta sea correcta)
require_once '../libs/fpdf.php'; 

$apartado_id = intval($_GET['apartado_id']);

try {
    // Consultar todos los datos necesarios para las cláusulas del contrato
    $stmt = $conn->prepare("
        SELECT 
            a.id AS contrato_num,
            a.fecha_apartado,
            p.titulo AS inmueble_nombre,
            p.precio AS inmueble_precio,
            p.area_m2 AS inmueble_area,
            p.latitud,
            p.longitud,
            u.nombre AS cliente_nombre,
            u.email AS cliente_correo
        FROM const_apartados a
        JOIN propiedad p ON a.propiedad_id = p.id
        JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.id = :apartado_id
    ");
    $stmt->execute([':apartado_id' => $apartado_id]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$datos) {
        die("No se encontraron registros válidos para esta operación.");
    }

} catch (PDOException $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

// ==========================================
// CONFIGURACIÓN Y DISEÑO DEL CONTRATO EN PDF
// ==========================================

class PDF_Contrato extends FPDF {
    // Encabezado corporativo
    function Header() {
        $this->SetFont('Arial', 'B', 22);
        $this->SetTextColor(30, 30, 45); // Color oscuro elegante
        $this->Cell(0, 12, utf8_decode('DOMU BIENES RAÍCES'), 0, 1, 'C');
        
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor(99, 102, 241); // Color Indigo/Azul corporativo
        $this->Cell(0, 5, utf8_decode('Contrato Digital de Apartado y Promesa de Compraventa'), 0, 1, 'C');
        
        $this->Ln(5);
        // Línea divisoria elegante
        $this->SetDrawColor(99, 102, 241);
        $this->SetLineWidth(0.8);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(10);
    }

    // Pie de página con numeración
    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, utf8_decode('Este documento es una constancia digital emitida de forma automática por el sistema DomuAdmin.'), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('Página ').$this->PageNo().' de {nb}', 0, 0, 'C');
    }
}

// Crear instancia del PDF en tamaño Carta (Letter)
$pdf = new PDF_Contrato('P', 'mm', 'Letter');
$pdf->AliasNbPages();
$pdf->SetMargins(15, 20, 15);
$pdf->AddPage();

// Formatear valores
$fecha_formateada = date("d/m/Y", strtotime($datos['fecha_apartado']));
$precio_letras = number_format($datos['inmueble_precio'], 2);

// ---- BLOQUE INFO CONTRATO ----
$pdf->SetFillColor(245, 247, 255); // Fondo azul tenue
$pdf->SetDrawColor(220, 225, 245);
$pdf->Rect(15, $pdf->GetY(), 180, 22, 'DF');

$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(50, 50, 50);
$pdf->SetXY(20, $pdf->GetY() + 3);
$pdf->Cell(90, 5, utf8_decode("Folio de Operación: #DOMU-" . str_pad($datos['contrato_num'], 5, "0", STR_PAD_LEFT)), 0, 0);
$pdf->Cell(90, 5, utf8_decode("Fecha de Emisión: " . $fecha_formateada), 0, 1);

$pdf->SetFont('Arial', '', 10);
$pdf->SetX(20);
$pdf->Cell(90, 6, utf8_decode("Estado Jurídico: En Proceso de Validación Notarial"), 0, 0);
$pdf->Cell(90, 6, utf8_decode("Lugar: Guadalajara, Jalisco, México"), 0, 1);

$pdf->Ln(12);

// ---- CUERPO LEGAL (DECLARACIONES) ----
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(30, 30, 45);
$pdf->Cell(0, 6, utf8_decode("DECLARACIONES PRELIMINARES"), 0, 1, 'L');
$pdf->Ln(3);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(60, 60, 60);

$texto_declaraciones = "Por una parte, la plataforma inmobiliaria DOMU S.A. de C.V., en lo sucesivo denominado como 'EL MEDIADOR', y por la otra parte el C. " . strtoupper($datos['cliente_nombre']) . ", con correo electrónico institucional " . $datos['cliente_correo'] . ", a quien en lo sucesivo se le denominará como 'EL ADQUIRENTE'. Ambas partes manifiestan su expresa voluntad en someterse bajo las siguientes cláusulas aplicables a la separación del inmueble.";

$pdf->MultiCell(0, 6, utf8_decode($texto_declaraciones), 0, 'J');
$pdf->Ln(8);

// ---- CLÁUSULAS ----
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(30, 30, 45);
$pdf->Cell(0, 6, utf8_decode("CLÁUSULAS DEL TRÁMITE"), 0, 1, 'L');
$pdf->Ln(3);

$pdf->SetFont('Arial', '', 10);

// Cláusula 1
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, utf8_decode("PRIMERA. OBJETO DEL CONTRATO:"), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 5, utf8_decode("El Adquirente manifiesta su interés formal y aparta el inmueble denominado '" . $datos['inmueble_nombre'] . "', el cual cuenta con una superficie aproximada de " . $datos['inmueble_area'] . " m², ubicado geográficamente bajo las coordenadas de Latitud: " . $datos['latitud'] . " y Longitud: " . $datos['longitud'] . "."), 0, 'J');
$pdf->Ln(4);

// Cláusula 2
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, utf8_decode("SEGUNDA. PRECIO ACORDADO Y GASTOS:"), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 5, utf8_decode("El precio total de la operación de compraventa fijado por el inmueble es la cantidad de $" . $precio_letras . " MXN (Moneda Nacional). El costo final del trámite no incluye los honorarios de escrituración notariales ni impuestos federales correspondientes."), 0, 'J');
$pdf->Ln(4);

// Cláusula 3
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, utf8_decode("TERCERA. VIGENCIA DEL APARTADO SEGURO:"), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 5, utf8_decode("A partir de la emisión del presente documento, el inmueble cambiará su estado a 'Apartada' en el inventario global de DOMU, otorgando una ventana exclusiva de quince (15) días hábiles para la integración del expediente y presentación física ante la Notaría Pública asignada."), 0, 'J');
$pdf->Ln(15);

// ---- SECCIÓN DE FIRMAS ----
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 5, utf8_decode("CONFORMIDAD DE LAS PARTES"), 0, 1, 'C');
$pdf->Ln(20);

// Coordenadas para posicionar las líneas de firma en paralelo
$y_firmas = $pdf->GetY();

$pdf->SetDrawColor(150, 150, 150);
$pdf->SetLineWidth(0.4);

// Firma Representante Domu
$pdf->Line(25, $y_firmas, 85, $y_firmas);
$pdf->SetXY(25, $y_firmas + 2);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(60, 4, utf8_decode("Por DOMU Bienes Raíces"), 0, 0, 'C');

// Firma Cliente Comprador
$pdf->Line(125, $y_firmas, 185, $y_firmas);
$pdf->SetXY(125, $y_firmas + 2);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(60, 4, utf8_decode(strtoupper($datos['cliente_nombre'])), 0, 1, 'C');
$pdf->SetX(125);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(60, 4, utf8_decode("El Adquirente / Cliente"), 0, 1, 'C');

// Limpiar el búfer y enviar el archivo PDF al navegador
ob_end_clean();
$pdf->Output('I', 'Contrato_Apartado_' . $apartado_id . '.pdf');
exit;
?>