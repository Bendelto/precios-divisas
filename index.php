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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #333; }
        
        /* Contenedor principal responsive */
        .main-container { max-width: 1200px; margin: 0 auto; }
        .calc-container { max-width: 600px; margin: 0 auto; } /* Solo para la calculadora */

        .card-price { 
            border: 0; 
            border-radius: 16px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
            text-decoration: none; 
            color: inherit; 
            display: block; 
            background: white;
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }
        .card-price:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            color: inherit;
        }
        
        /* Tipografía */
        h4, h6 { font-weight: 700; color: #2c3e50; }
        
        /* Colores de moneda */
        .price-usd { color: #198754; font-weight: 700; }
        .price-brl { color: #0d6efd; font-weight: 700; }
        .price-cop-highlight { color: #212529; font-weight: 800; font-size: 1.4rem; } /* COP Resaltado */
        
        /* Badges de Tasa (Sin emojis) */
        .badge-tasa { 
            font-size: 0.8rem; 
            background: #fff; 
            border: 1px solid #dee2e6; 
            color: #6c757d; 
            padding: 5px 10px; 
            border-radius: 50px; 
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Estilos Calculadora */
        .calc-box { background-color: #fff; border-radius: 12px; padding: 20px; border: 1px solid #edf2f7; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .form-control-qty { text-align: center; font-weight: bold; border: 1px solid #dee2e6; background: #f8f9fa; height: 50px; font-size: 1.3rem; color: #495057; }
        
        /* Caja de Total (Azul Suave) */
        .total-display { 
            background-color: #e7f1ff; 
            color: #0d6efd; 
            border: 1px solid #cce5ff;
            border-radius: 12px; 
            padding: 20px; 
            margin-top: 20px; 
        }
        
        /* Botones Sólidos */
        .btn-solid-blue {
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 20px;
            font-weight: 600;
            width: 100%;
            display: block;
            text-align: center;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-solid-blue:hover { background-color: #0b5ed7; color: white; }

        .btn-back {
            background-color: #e9ecef;
            color: #212529;
            width: 40px; height: 40px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn-back:hover { background-color: #dee2e6; color: black; }

        /* Iconos de moneda textuales */
        .currency-tag { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.7; }

    </style>
</head>
<body class="py-4">

    <div class="container main-container">
        
    <?php if ($singleTour): ?>
        <div class="calc-container">
            
            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="./" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>
                <h4 class="mb-0 lh-sm"><?= htmlspecialchars($singleTour['nombre']) ?></h4>
            </div>

            <div class="card card-price p-4 mb-4" style="cursor: default; transform: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <div class="row g-0 text-center mb-2">
                    <div class="col-6 border-end pe-2">
                        <span class="text-uppercase text-muted fw-bold" style="font-size:0.7rem;">Adulto</span>
                        <div class="price-cop-highlight my-1">$<?= number_format($singleTour['precio_cop']) ?></div>
                        <div class="d-flex flex-column gap-1">
                            <span class="price-usd small">USD $<?= precio_inteligente($singleTour['precio_cop'] / $tasa_tuya_usd) ?></span>
                            <span class="price-brl small">BRL R$<?= precio_inteligente($singleTour['precio_cop'] / $tasa_tuya_brl) ?></span>
                        </div>
                    </div>
                    <div class="col-6 ps-2">
                        <span class="text-uppercase text-muted fw-bold" style="font-size:0.7rem;">Niño <small class="fw-normal">(<?= !empty($singleTour['rango_nino']) ? $singleTour['rango_nino'] : '-' ?>)</small></span>
                        <?php if(!empty($singleTour['precio_nino']) && $singleTour['precio_nino'] > 0): ?>
                            <div class="price-cop-highlight my-1">$<?= number_format($singleTour['precio_nino']) ?></div>
                            <div class="d-flex flex-column gap-1">
                                <span class="price-usd small">USD $<?= precio_inteligente($singleTour['precio_nino'] / $tasa_tuya_usd) ?></span>
                                <span class="price-brl small">BRL R$<?= precio_inteligente($singleTour['precio_nino'] / $tasa_tuya_brl) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="text-muted mt-3 small">- No aplica -</div>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="my-4 opacity-10">

                <div class="calc-box">
                    <h6 class="fw-bold mb-4 text-center text-secondary"><i class="fa-solid fa-calculator me-2"></i>Calcular Total</h6>
                    <div class="row g-3 justify-content-center">
                        <div class="col-5">
                            <label class="small text-muted mb-2 d-block text-center fw-bold">ADULTOS</label>
                            <input type="number" id="qtyAdult" class="form-control form-control-qty shadow-sm" value="1" min="1" inputmode="numeric">
                        </div>
                        <div class="col-5">
                            <label class="small text-muted mb-2 d-block text-center fw-bold">NIÑOS</label>
                            <input type="number" id="qtyKid" class="form-control form-control-qty shadow-sm" value="0" min="0" inputmode="numeric" <?= (empty($singleTour['precio_nino']) || $singleTour['precio_nino'] == 0) ? 'disabled style="background-color:#eee"' : '' ?>>
                        </div>
                    </div>

                    <div class="total-display text-center">
                        <div class="small text-uppercase text-secondary mb-1 fw-bold">Total a Pagar</div>
                        <div class="fw-bold text-dark fs-1 lh-1 mb-3" id="totalCOP">$<?= number_format($singleTour['precio_cop']) ?></div>
                        
                        <div class="row pt-3 border-top border-primary-subtle">
                            <div class="col-6 border-end border-primary-subtle">
                                <div class="currency-tag text-success">Dollars (USD)</div>
                                <div class="fw-bold text-success fs-4" id="totalUSD">$<?= precio_inteligente($singleTour['precio_cop'] / $tasa_tuya_usd) ?></div>
                            </div>
                            <div class="col-6">
                                <div class="currency-tag text-primary">Reais (BRL)</div>
                                <div class="fw-bold text-primary fs-4" id="totalBRL">R$ <?= precio_inteligente($singleTour['precio_cop'] / $tasa_tuya_brl) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <a href="./" class="btn-solid-blue shadow mb-5">
                Ver todos los tours
            </a>
        </div>

        <script>
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

            function precioInteligente(valor) {
                let redondeado = Math.ceil(valor * 2) / 2;
                return redondeado;
            }

            function calculate() {
                let qA = parseInt(inputAdult.value) || 0;
                let qK = parseInt(inputKid.value) || 0;
                let totalCOP = (qA * priceAdult) + (qK * priceKid);
                
                displayCOP.innerText = formatMoney(totalCOP);
                displayUSD.innerText = '$' + precioInteligente(totalCOP / rateUsd);
                displayBRL.innerText = 'R$ ' + precioInteligente(totalCOP / rateBrl);
            }

            inputAdult.addEventListener('input', calculate);
            inputKid.addEventListener('input', calculate);
        </script>

    <?php else: ?>
        <div class="text-center mb-5 pt-3">
            <h3 class="fw-bold text-dark mb-2">Descubre Cartagena 🌴</h3>
            
            <div class="d-flex justify-content-center gap-3 mt-3 flex-wrap">
                <span class="badge-tasa">
                    <span class="fw-bold text-success">USD</span> $<?= number_format($tasa_tuya_usd, 0) ?>
                </span>
                <span class="badge-tasa">
                    <span class="fw-bold text-primary">BRL</span> $<?= number_format($tasa_tuya_brl, 0) ?>
                </span>
            </div>
            <small class="text-muted d-block mt-2" style="font-size: 0.7rem;">* Tasas calculadas con margen de cambio local</small>
        </div>

        <div class="row g-4">
            <?php foreach ($tours as $slug => $tour): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="./<?= $slug ?>" class="card card-price p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h6 class="fw-bold mb-0 text-dark lh-base pe-2"><?= htmlspecialchars($tour['nombre']) ?></h6>
                    </div>
                    
                    <div class="price-cop-highlight mb-3">
                        $<?= number_format($tour['precio_cop']) ?> <small class="fs-6 text-muted fw-normal">COP</small>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-end mt-auto pt-3 border-top">
                        <div class="d-flex flex-column gap-1">
                            <div class="price-usd"><span class="currency-tag">USD</span> $<?= precio_inteligente($tour['precio_cop'] / $tasa_tuya_usd) ?></div>
                            <div class="price-brl"><span class="currency-tag">BRL</span> R$ <?= precio_inteligente($tour['precio_cop'] / $tasa_tuya_brl) ?></div>
                        </div>
                        <div class="text-primary fs-5">
                            <i class="fa-solid fa-circle-arrow-right"></i>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    </div>
</body>
</html>