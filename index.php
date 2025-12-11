<?php
// 1. CARGAR CONFIGURACIÓN
$fileConfig = 'config.json';
$config = file_exists($fileConfig) ? json_decode(file_get_contents($fileConfig), true) : ['margen_usd' => 200, 'margen_brl' => 200];
$margen_usd = $config['margen_usd'];
$margen_brl = $config['margen_brl'];

// 2. GESTIÓN DE MONEDA Y API
$cacheFile = 'tasa.json';
$currentTime = time();
$cacheTime = 12 * 60 * 60; // 12 horas

if (!file_exists($cacheFile) || ($currentTime - filemtime($cacheFile)) > $cacheTime) {
    $apiUrl = "https://open.er-api.com/v6/latest/COP";
    $response = @file_get_contents($apiUrl);
    if($response) file_put_contents($cacheFile, $response);
}

// 3. CÁLCULO DE TASAS
$rates = json_decode(file_get_contents($cacheFile), true);
$tasa_oficial_usd = 1 / $rates['rates']['USD']; 
$tasa_oficial_brl = 1 / $rates['rates']['BRL'];
$tasa_tuya_usd = $tasa_oficial_usd - $margen_usd;
$tasa_tuya_brl = $tasa_oficial_brl - $margen_brl;

// --- FUNCIÓN DE REDONDEO INTELIGENTE (0.5 ARRIBA) ---
function precio_inteligente($valor) {
    // Multiplicamos por 2, redondeamos hacia arriba, y dividimos por 2
    // Ejemplo: 50.20 -> 100.4 -> 101 -> 50.5
    // Ejemplo: 50.51 -> 101.02 -> 102 -> 51.0
    $redondeado = ceil($valor * 2) / 2;
    
    // Formateo visual: Si es entero, no mostrar decimales. Si es .5, mostrar 1 decimal.
    // number_format fuerza decimales, así que usamos un truco simple
    return (float)$redondeado; 
}

// 4. CARGAR TOURS Y DETECTAR RUTA
$tours = file_exists('data.json') ? json_decode(file_get_contents('data.json'), true) : [];

// Detectar slug de la URL
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = dirname($_SERVER['SCRIPT_NAME']);
if($base_path == '/') $base_path = '';
$slug_solicitado = trim(str_replace($base_path, '', $request_uri), '/');

// Verificamos si existe ese slug
$singleTour = null;
if (!empty($slug_solicitado) && isset($tours[$slug_solicitado])) {
    $singleTour = $tours[$slug_solicitado];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $singleTour ? $singleTour['nombre'] : 'Lista de Precios' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .card-price { 
            border: 0; 
            border-radius: 12px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.08); 
            transition: transform 0.2s, box-shadow 0.2s; 
            text-decoration: none; 
            color: inherit; 
            display: block; 
        }
        .card-price:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            color: inherit;
        }

        .price-usd { color: #198754; font-weight: 700; font-size: 1.3rem; }
        .price-brl { color: #0d6efd; font-weight: 700; font-size: 1.3rem; }
        .badge-tasa { font-size: 0.75rem; font-weight: normal; background: #e9ecef; color: #495057; padding: 5px 10px; border-radius: 20px; }
    </style>
</head>
<body class="container py-5">
    
    <div class="text-center mb-5">
        <h1 class="fw-bold text-dark">Descubre Cartagena 🌴</h1>
        <div class="d-inline-flex gap-3 mt-2">
            <span class="badge-tasa">
                🇺🇸 Tasa cálculo: $<?= number_format($tasa_tuya_usd, 0) ?> COP 
            </span>
            <span class="badge-tasa">
                🇧🇷 Tasa cálculo: $<?= number_format($tasa_tuya_brl, 0) ?> COP
            </span>
        </div>
    </div>

    <?php if ($singleTour): ?>
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card card-price p-4 text-center cursor-default" style="cursor: default;">
                    <h3 class="fw-bold mb-3"><?= htmlspecialchars($singleTour['nombre']) ?></h3>
                    <div class="bg-light p-3 rounded mb-3">
                        <small class="text-uppercase text-muted fw-bold">Precio Base</small><br>
                        <span class="fs-4">$<?= number_format($singleTour['precio_cop']) ?> COP</span>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <small>🇺🇸 Dólares</small><br>
                                <span class="price-usd">$<?= precio_inteligente($singleTour['precio_cop'] / $tasa_tuya_usd) ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-2">
                                <small>🇧🇷 Reales</small><br>
                                <span class="price-brl">R$ <?= precio_inteligente($singleTour['precio_cop'] / $tasa_tuya_brl) ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <a href="./" class="btn btn-dark w-100 mt-4">Ver todos los tours</a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($tours as $slug => $tour): ?>
            <div class="col-md-6 col-lg-4">
                <a href="./<?= $slug ?>" class="card card-price h-100 p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <h5 class="card-title fw-bold mb-0"><?= htmlspecialchars($tour['nombre']) ?></h5>
                        <span class="badge bg-light text-dark border">$<?= number_format($tour['precio_cop']) ?> COP</span>
                    </div>
                    <hr class="my-3 opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="price-usd">🇺🇸 $<?= precio_inteligente($tour['precio_cop'] / $tasa_tuya_usd) ?></div>
                            <div class="price-brl">🇧🇷 R$ <?= precio_inteligente($tour['precio_cop'] / $tasa_tuya_brl) ?></div>
                        </div>
                        <div class="text-muted opacity-50">
                            ➝
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>