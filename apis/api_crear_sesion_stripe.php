<?php
// 1. Forzar visualización interna de errores para atrapar el fallo exacto en la consola
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// 2. Ruta manual exacta basada en tu captura de carpetas
// Como "apis" y "stripe-php" están al mismo nivel, retrocedemos un paso con ../
$ruta_stripe = __DIR__ . '/../stripe-php/init.php';

if (!file_exists($ruta_stripe)) {
    echo json_encode([
        'error' => 'Error de configuración: No se encontró el archivo init.php en la ruta especificada: ' . $ruta_stripe
    ]);
    exit;
}

require_once $ruta_stripe;

// 3. Establecer la Clave Secreta de Stripe
// IMPORTANTE: Si en tu Javascript usas clave "pk_live_...", aquí DEBES usar tu clave secreta de producción "sk_live_..."
// Si estás haciendo pruebas en localhost, se recomienda usar "sk_test_..." en el servidor y "pk_test_..." en el JS.
$clave_secreta = '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'; 

\Stripe\Stripe::setApiKey($clave_secreta);

// 4. Leer los datos enviados desde cliente_dashboard.php
$input_raw = file_get_contents('php://input');
$input = json_decode($input_raw, true);

if (!$input || !isset($input['precio']) || !isset($input['titulo'])) {
    echo json_encode(['error' => 'Parámetros inválidos. No se recibieron los datos de la propiedad en el backend.']);
    exit;
}

try {
    // Convertir el precio a centavos (Stripe lo requiere así. Ejemplo: $1,500.00 MXN -> 150000)
    $monto_centavos = intval(round(floatval($input['precio']) * 100));

    if ($monto_centavos <= 0) {
        echo json_encode(['error' => 'El monto de la propiedad debe ser mayor a cero.']);
        exit;
    }

    // 5. Crear la sesión de Checkout
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'mxn',
                'product_data' => [
                    'name' => 'Enganche Legal: ' . $input['titulo'],
                ],
                'unit_amount' => $monto_centavos,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        // urls de retorno dinámicas para evitar problemas si cambia el nombre de la carpeta raíz
        'success_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/domu_oficial/vistas/cliente_dashboard.php?mensaje=pago_exitoso&apartado=' . intval($input['apartado_id']),
        'cancel_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/domu_oficial/vistas/cliente_dashboard.php?mensaje=pago_cancelado',
    ]);

    // Devolver el ID de sesión exitoso
    echo json_encode(['id' => $session->id]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    echo json_encode(['error' => 'Error directo desde la API de Stripe: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error interno en el servidor PHP: ' . $e->getMessage()]);
}
?>