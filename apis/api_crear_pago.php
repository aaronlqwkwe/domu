

<?php
session_start();
require_once '../conexion.php'; 

// 1. Validar que el usuario esté logeado y que venga el ID de la propiedad por POST
if (!isset($_SESSION['usuario_id']) || !isset($_POST['propiedad_id'])) {
    header("Location: ../index.php?error=acceso_invalido");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$propiedad_id = intval($_POST['propiedad_id']);

// 2. Definir el costo fijo del APARTADO 
// Modifica este valor por la cantidad en pesos que quieras cobrar por reservar (Ej: 5000, 10000, etc.)
$monto_apartado_fijo = 10000.00; 
$precio_centavos = intval($monto_apartado_fijo * 100);

// 3. Obtener los datos reales de la propiedad desde la base de datos
try {
    $stmt = $conn->prepare("SELECT titulo, precio FROM propiedad WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $propiedad_id]);
    $propiedad = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$propiedad) {
        header("Location: ../index.php?error=propiedad_no_encontrada");
        exit;
    }

    $titulo_casa = $propiedad['titulo'];
    $precio_total_casa = $propiedad['precio'];

} catch (PDOException $e) {
    die("Error al consultar la propiedad en la base de datos: " . $e->getMessage());
}

// 4. TU CLAVE SECRETA DE STRIPE 
$stripe_secret_key = "key aqui"; 

// 5. Ajustamos los textos para que Stripe muestre que es un "Apartado" y no una compra completa
$concepto_pago = 'Apartado de Propiedad: ' . $titulo_casa;
$descripcion_pago = 'Pago de garantía para reservar la propiedad. Valor total del inmueble: $' . number_format($precio_total_casa, 2) . ' MXN.';

$data = [
    'success_url' => 'http://localhost/domu_oficial/apis/api_pago_exitoso.php?id=' . $propiedad_id,
    'cancel_url'  => 'http://localhost/domu_oficial/detalles.php?id=' . $propiedad_id . '&error=pago_cancelado',
    'mode'        => 'payment',
    'line_items'  => [
        [
            'price_data' => [
                'currency'     => 'mxn',
                'unit_amount'  => $precio_centavos, // Cobrará solo los $10,000 pesos de apartado
                'product_data' => [
                    'name'        => $concepto_pago,       // En Stripe aparecerá: "Apartado de Propiedad: Casa en Zapopan"
                    'description' => $descripcion_pago,  // Aparecerá el recordatorio del valor total abajo en chiquito
                ],
            ],
            'quantity' => 1,
        ]
    ]
];

// 6. Enviar la solicitud a la API de Stripe mediante cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); 
curl_setopt($ch, CURLOPT_USERPWD, $stripe_secret_key . ':');

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die('Error de conexión con el servidor de Stripe: ' . curl_error($ch));
}
curl_close($ch);

$resultado = json_decode($response, true);

// 7. Redirigir al cliente al checkout oficial de Stripe si todo es correcto
if (isset($resultado['url'])) {
    header("Location: " . $resultado['url']);
    exit;
} else {
    echo "<div style='background:#f8d7da; color:#721c24; padding:20px; font-family:sans-serif; border-radius:10px; max-width:600px; margin:20px auto;'>";
    echo "<h3 style='margin-top:0;'>❌ Error al conectar con la pasarela de pago:</h3>";
    echo "<pre>" . print_r($resultado, true) . "</pre>";
    echo "<a href='../index.php' style='display:inline-block; background:#721c24; color:white; padding:8px 15px; text-decoration:none; border-radius:5px; font-weight:bold; margin-top:10px;'>Volver al inicio</a>";
    echo "</div>";
    exit;
}

