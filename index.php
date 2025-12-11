<?php
// 1. GESTIÓN DE MONEDA Y API
$cacheFile = 'tasa.json';
$currentTime = time();
$cacheTime = 12 * 60 * 60; // 12 horas

if (!file_exists($cacheFile) || ($currentTime - filemtime($cacheFile)) > $cacheTime) {
    // Consultar API (Base COP)
    $apiUrl = "https://open.er-api.com/v6/latest/COP";
    $response = file_get_contents($apiUrl);
    file_put_contents($cacheFile, $response);
}

$rates = json_decode(file_get_contents($cacheFile), true);
$usd_rate = $rates['rates']['USD']; // 1 COP a USD
$brl_rate = $rates['rates']['BRL']; // 1 COP a BRL

// 2. CARGAR TOURS
$tours = file_exists('data.json') ? json_decode(file_get_contents('data.json'), true) : [];

// 3. FILTRO PARA LINK INDIVIDUAL (?id=0)
$singleTour = isset($_GET['id']) && isset($tours[$_GET['id']]) ? $tours[$_GET['id']] : null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Precios Tours - Descubre Cartagena</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-price { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .flag { height: 20px; margin-right: 5px; }
        .price-usd { color: #198754; font-weight: bold; font-size: 1.2em; }
        .price-brl { color: #0d6efd; font-weight: bold; font-size: 1.2em; }
    </style>
</head>
<body class="container py-5">
    
    <div class="text-center mb-5">
        <h1>Descubre Cartagena 🌴</h1>
        <p class="text-muted">Precios actualizados automáticamente</p>
        <small>Tasa hoy: 1 USD = <?= number_format(1/$usd_rate, 0) ?> COP | 1 BRL = <?= number_format(1/$brl_rate, 0) ?> COP</small>
    </div>

    <?php if ($singleTour): ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card card-price p-4 text-center">
                    <h3><?= $singleTour['nombre'] ?></h3>
                    <hr>
                    <div class="mb-3">
                        <span class="text-muted">Pesos Colombianos:</span><br>
                        <strong>$<?= number_format($singleTour['precio_cop']) ?> COP</strong>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted">Dólares (USD):</span><br>
                        <span class="price-usd">$<?= number_format($singleTour['precio_cop'] * $usd_rate, 2) ?> USD</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted">Reales (BRL):</span><br>
                        <span class="price-brl">R$ <?= number_format($singleTour['precio_cop'] * $brl_rate, 2) ?> BRL</span>
                    </div>
                    <a href="index.php" class="btn btn-outline-dark mt-3">Ver todos los tours</a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="row">
            <?php foreach ($tours as $id => $tour): ?>
            <div class="col-md-4">
                <div class="card card-price p-3">
                    <h5 class="card-title"><?= $tour['nombre'] ?></h5>
                    <ul class="list-unstyled mt-3">
                        <li class="mb-2">🇨🇴 $<?= number_format($tour['precio_cop']) ?> COP</li>
                        <li class="mb-2 price-usd">🇺🇸 $<?= number_format($tour['precio_cop'] * $usd_rate, 2) ?> USD</li>
                        <li class="mb-2 price-brl">🇧🇷 R$ <?= number_format($tour['precio_cop'] * $brl_rate, 2) ?> BRL</li>
                    </ul>
                    <input type="text" value="https://tusitio.com/precios/?id=<?= $id ?>" class="form-control form-control-sm mt-2" onclick="this.select()" readonly style="font-size:10px; color:gray;">
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>