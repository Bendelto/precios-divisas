<?php
session_start();
// --- SEGURIDAD ---
if (isset($_POST['login']) && $_POST['pass'] == 'Dc@6691400') { // <--- PON TU CONTRASEÑA AQUÍ
    $_SESSION['admin'] = true;
}
if (!isset($_SESSION['admin'])) {
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
          <div class="d-flex justify-content-center align-items-center vh-100 bg-light">
            <form method="post" class="card p-4 shadow">
                <h4 class="mb-3">Acceso Admin</h4>
                <input type="password" name="pass" class="form-control mb-2" placeholder="Contraseña">
                <button name="login" type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>
          </div>';
    exit;
}

// --- ARCHIVOS DE DATOS ---
$fileTours = 'data.json';
$fileConfig = 'config.json';

// Cargar datos existentes
$tours = file_exists($fileTours) ? json_decode(file_get_contents($fileTours), true) : [];
$config = file_exists($fileConfig) ? json_decode(file_get_contents($fileConfig), true) : ['margen_usd' => 200, 'margen_brl' => 200];

// --- GUARDAR CONFIGURACIÓN DE TASAS ---
if (isset($_POST['save_config'])) {
    $config['margen_usd'] = floatval($_POST['margen_usd']);
    $config['margen_brl'] = floatval($_POST['margen_brl']);
    file_put_contents($fileConfig, json_encode($config));
    header("Location: admin.php");
    exit;
}

// --- AGREGAR O EDITAR TOUR ---
if (isset($_POST['add'])) {
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $rango_adulto = !empty($_POST['rango_adulto']) ? $_POST['rango_adulto'] : ''; // NUEVO CAMPO ADULTO
    
    // DATOS DE NIÑOS
    $precio_nino = !empty($_POST['precio_nino']) ? $_POST['precio_nino'] : 0;
    $rango_nino = !empty($_POST['rango_nino']) ? $_POST['rango_nino'] : '';
    
    // 1. Crear SLUG
    $slugInput = !empty($_POST['slug']) ? $_POST['slug'] : $nombre;
    
    // 2. Limpieza PROFUNDA del slug
    $cleanSlug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $slugInput)));
    $cleanSlug = trim($cleanSlug, '-');
    
    // Guardamos usando el SLUG limpio como CLAVE
    $tours[$cleanSlug] = [
        'nombre' => $nombre, 
        'precio_cop' => $precio,
        'rango_adulto' => $rango_adulto, // Guardar rango adulto
        'precio_nino' => $precio_nino,
        'rango_nino' => $rango_nino
    ];
    
    file_put_contents($fileTours, json_encode($tours));
    header("Location: admin.php");
    exit;
}

// --- BORRAR TOUR ---
if (isset($_GET['delete'])) {
    $slugToDelete = $_GET['delete'];
    if(isset($tours[$slugToDelete])) {
        unset($tours[$slugToDelete]);
        file_put_contents($fileTours, json_encode($tours));
    }
    header("Location: admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Tours & Tasas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Panel de Control</h2>
        <a href="index.php" target="_blank" class="btn btn-outline-success">Ver Página Pública</a>
    </div>

    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning text-dark fw-bold">📉 Ajuste de Cambio (Protección)</div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-5">
                    <label>Margen a restar al Dólar (COP)</label>
                    <div class="input-group">
                        <span class="input-group-text">-$</span>
                        <input type="number" name="margen_usd" class="form-control" value="<?= $config['margen_usd'] ?>" required>
                    </div>
                </div>
                <div class="col-md-5">
                    <label>Margen a restar al Real (COP)</label>
                    <div class="input-group">
                        <span class="input-group-text">-$</span>
                        <input type="number" name="margen_brl" class="form-control" value="<?= $config['margen_brl'] ?>" required>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="save_config" class="btn btn-dark w-100">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">Nuevo / Editar Tour</div>
        <div class="card-body">
            <form method="post" class="row g-3" id="tourForm">
                
                <div class="col-12"><h6 class="text-muted border-bottom pb-2">Datos Principales</h6></div>
                
                <div class="col-md-6">
                    <label class="form-label">Nombre del Tour</label>
                    <input type="text" name="nombre" id="inputNombre" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">URL Amigable (Auto)</label>
                    <input type="text" name="slug" id="inputSlug" class="form-control bg-light" placeholder="se-genera-automatico">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Precio Adulto (COP)</label>
                    <input type="number" name="precio" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Rango Edad Adulto</label>
                    <input type="text" name="rango_adulto" class="form-control" placeholder="Ej: 10+ años">
                </div>

                <div class="col-12 mt-4"><h6 class="text-muted border-bottom pb-2">Datos Niños (Opcional)</h6></div>
                
                <div class="col-md-6">
                    <label class="form-label">Precio Niño (COP)</label>
                    <input type="number" name="precio_nino" class="form-control" placeholder="0 si no aplica">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Rango de Edad Niño</label>
                    <input type="text" name="rango_nino" class="form-control" placeholder="Ej: 4 a 9 años">
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" name="add" class="btn btn-primary w-100 btn-lg">Guardar Tour</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-4 shadow-sm">
        <div class="card-body">
            <table class="table table-hover">
                <thead><tr><th>Tour</th><th>Adulto</th><th>Niño</th><th>Acción</th></tr></thead>
                <tbody>
                    <?php foreach ($tours as $slug => $tour): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($tour['nombre']) ?></strong><br>
                            <small class="text-muted">/<?= $slug ?></small>
                        </td>
                        <td>
                            $<?= number_format($tour['precio_cop']) ?><br>
                            <small class="text-muted"><?= !empty($tour['rango_adulto']) ? $tour['rango_adulto'] : '' ?></small>
                        </td>
                        <td>
                            <?php if(!empty($tour['precio_nino'])): ?>
                                $<?= number_format($tour['precio_nino']) ?><br>
                                <small class="text-muted"><?= $tour['rango_nino'] ?></small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="?delete=<?= $slug ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Borrar?');">Borrar</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const inputNombre = document.getElementById('inputNombre');
    const inputSlug = document.getElementById('inputSlug');

    inputNombre.addEventListener('input', function() {
        let text = this.value;
        let slug = text.toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "") 
            .replace(/[^a-z0-9]+/g, '-') 
            .replace(/^-+|-+$/g, ''); 
            
        inputSlug.value = slug;
    });
</script>

</body>
</html>