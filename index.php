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

// FUNCIÓN DE REDONDEO INTELIGENTE (0.5 ARRIBA)
function precio_inteligente($valor) {
    $redondeado = ceil($valor * 2) / 2;
    return (float)$redondeado; 
}

// 4. CARGAR TOURS Y DETECTAR RUTA
$tours = file_exists('data.json') ? json_decode(file_get_contents('data.json'), true) : [];

// Detectar slug
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = dirname($_SERVER['SCRIPT_NAME']);
if($base_path == '/') $base_path = '';
$slug_solicitado = trim(str_replace($base_path, '', $request_uri), '/');

$singleTour = null;
if (!empty($slug_solicitado) && isset($tours[$slug_solicitado])) {
    $singleTour = $tours[$slug_solicitado];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $singleTour ? $singleTour['nombre'] : 'Lista de Precios' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        
        /* Optimización Móvil */
        .container { max-width: 600px; } /* Limitar ancho en desktop para simular app */
        .card-price { 
            border: 0; 
            border-radius: 12px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            text-decoration: none; 
            color: inherit; 
            display: block; 
            background: white;
        }
        
        /* Tipografía compacta */
        h1, h3 { font-weight: 700; letter-spacing: -0.5px; }
        .price-usd { color: #198754; font-weight: 700; }
        .price-brl { color: #0d6efd; font-weight: 700; }
        
        /* Badges y Etiquetas */
        .badge-tasa { font-size: 0.7rem; background: #e9ecef; color: #6c757d; padding: 4px 8px; border-radius: 6px; }
        .lbl-type { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; color: #adb5bd; font-weight: bold; }

        /* Estilos Calculadora */
        .calc-box { background-color: #eef2f7; border-radius: 12px; padding: 15px; }
        .form-control-qty { text-align: center; font-weight: bold; border: none; background: #fff; height: 45px; font-size: 1.2rem; }
        .total-display { background: #212529; color: white; border-radius: 10px; padding: 15px; margin-top: 15px; }
        
        /* Botón atrás flotante o simple */
        .btn-back { font-size: 1.2rem; text-decoration: none; color: #333; }
    </style>
</head>
<body class="container py-3">

    <?php if ($singleTour): ?>
        <div class="d-flex align-items-center mb-3">
            <a href="./" class="btn-back me-3">←</a>
            <h5 class="mb-0 fw-bold text-truncate"><?= htmlspecialchars($singleTour['nombre']) ?></h5>
        </div>

        <div class="card card-price p-3 mb-3">
            <div class="row g-2 text-center mb-3">
                <div class="col-6 border-end">
                    <span class="lbl-type">Adulto</span>
                    <div class="fw-bold text-dark">$<?= number_format($singleTour['precio_cop']) ?></div>
                    <div class="d-flex justify-content-center gap-2" style="font-size: 0.8rem;">
                        <span class="price-usd">$<?= precio_inteligente($singleTour['precio_cop'] / $tasa_tuya_usd) ?></span>
                        <span class="price-brl">R$<?= precio_inteligente($singleTour['precio_cop'] / $tasa_tuya_brl) ?></span>
                    </div>
                </div>
                <div class="col-6">
                    <span class="lbl-type">Niño <small>(<?= !empty($singleTour['rango_nino']) ? $singleTour['rango_nino'] : '-' ?>)</small></span>
                    <?php if(!empty($singleTour['precio_nino']) && $singleTour['precio_nino'] > 0): ?>
                        <div class="fw-bold text-dark">$<?= number_format($singleTour['precio_nino']) ?></div>
                        <div class="d-flex justify-content-center gap-2" style="font-size: 0.8rem;">
                            <span class="price-usd">$<?= precio_inteligente($singleTour['precio_nino'] / $tasa_tuya_usd) ?></span>
                            <span class="price-brl">R$<?= precio_inteligente($singleTour['precio_nino'] / $tasa_tuya_brl) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="text-muted mt-1">- No aplica -</div>
                    <?php endif; ?>
                </div>
            </div>

            <hr class="opacity-25 my-2">

            <div class="calc-box">
                <h6 class="fw-bold mb-3 text-center">🔢 Calcular Total</h6>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="small text-muted mb-1 d-block text-center">Adultos</label>
                        <input type="number" id="qtyAdult" class="form-control form-control-qty shadow-sm" value="1" min="1" inputmode="numeric">
                    </div>
                    <div class="col-6">
                        <label class="small text-muted mb-1 d-block text-center">Niños</label>
                        <input type="number" id="qtyKid" class="form-control form-control-qty shadow-sm" value="0" min="0" inputmode="numeric" <?= (empty($singleTour['precio_nino']) || $singleTour['precio_nino'] == 0) ? 'disabled style="opacity:0.5"' : '' ?>>
                    </div>
                </div>

                <div class="total-display text-center">
                    <div class="small text-white-50 text-uppercase mb-1">Total Estimado</div>
                    <h2 class="mb-0 fw-bold" id="totalCOP">$<?= number_format($singleTour['precio_cop']) ?></h2>
                    <div class="row mt-2 pt-2 border-top border-secondary">
                        <div class="col-6 border-end border-secondary">
                            <div class="small text-white-50">USD 🇺🇸</div>
                            <div class="fw-bold text-success fs-5" id="totalUSD">$<?= precio_inteligente($singleTour['precio_cop'] / $tasa_tuya_usd) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="small text-white-50">Reales 🇧🇷</div>
                            <div class="fw-bold text-primary fs-5" id="totalBRL">R$ <?= precio_inteligente($singleTour['precio_cop'] / $tasa_tuya_brl) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Pasamos variables de PHP a JS
            const priceAdult = <?= $singleTour['precio_cop'] ?>;
            const priceKid = <?= !empty($singleTour['precio_nino']) ? $singleTour['precio_nino'] : 0 ?>;
            const rateUsd = <?= $tasa_tuya_usd ?>;
            const rateBrl = <?= $tasa_tuya_brl ?>;

            const inputAdult = document.getElementById('qtyAdult');
            const inputKid = document.getElementById('qtyKid');
            
            const displayCOP = document.getElementById('totalCOP');
            const displayUSD = document.getElementById('totalUSD');
            const displayBRL = document.getElementById('totalBRL');

            function formatMoney(amount) {
                return '$' + new Intl.NumberFormat('es-CO').format(amount);
            }

            // Función idéntica a PHP "precio_inteligente" (ceil * 2 / 2)
            function precioInteligente(valor) {
                let redondeado = Math.ceil(valor * 2) / 2;
                return redondeado;
            }

            function calculate() {
                let qA = parseInt(inputAdult.value) || 0;
                let qK = parseInt(inputKid.value) || 0;

                let totalCOP = (qA * priceAdult) + (qK * priceKid);
                
                // Actualizar COP
                displayCOP.innerText = formatMoney(totalCOP);

                // Actualizar USD
                let totalUSD = precioInteligente(totalCOP / rateUsd);
                displayUSD.innerText = '$' + totalUSD;

                // Actualizar BRL
                let totalBRL = precioInteligente(totalCOP / rateBrl);
                displayBRL.innerText = 'R$ ' + totalBRL;
            }

            // Listeners
            inputAdult.addEventListener('input', calculate);
            inputKid.addEventListener('input', calculate);
        </script>

    <?php else: ?>
        <div class="text-center mb-4">
            <h4 class="fw-bold text-dark mb-1">Descubre Cartagena 🌴</h4>
            
            <div class="d-flex justify-content-center gap-2 mt-2">
                <span class="badge-tasa">🇺🇸 $<?= number_format($tasa_tuya_usd, 0) ?></span>
                <span class="badge-tasa">🇧🇷 $<?= number_format($tasa_tuya_brl, 0) ?></span>
            </div>
            <small class="text-muted" style="font-size: 0.65rem;">* Ref. Casas de cambio</small>
        </div>

        <div class="row g-3">
            <?php foreach ($tours as $slug => $tour): ?>
            <div class="col-12 col-md-6">
                <a href="./<?= $slug ?>" class="card card-price p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-truncate" style="max-width: 70%;"><?= htmlspecialchars($tour['nombre']) ?></h6>
                        <span class="badge bg-light text-dark border">$<?= number_format($tour['precio_cop']) ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-3">
                            <div class="price-usd small">🇺🇸 $<?= precio_inteligente($tour['precio_cop'] / $tasa_tuya_usd) ?></div>
                            <div class="price-brl small">🇧🇷 R$ <?= precio_inteligente($tour['precio_cop'] / $tasa_tuya_brl) ?></div>
                        </div>
                        <div class="text-muted opacity-50 small">➝</div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>