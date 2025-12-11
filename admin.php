<?php
session_start();
// --- SEGURIDAD ---
if (isset($_POST['login']) && $_POST['pass'] == 'TU_CONTRASEÑA') { // <--- PON TU CONTRASEÑA AQUÍ
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

// --- ORDENAR ALFABÉTICAMENTE ---
uasort($tours, function($a, $b) {
    return strcasecmp($a['nombre'], $b['nombre']);
});

// --- GUARDAR CONFIGURACIÓN DE TASAS ---
if (isset($_POST['save_config'])) {
    $config['margen_usd'] = floatval($_POST['margen_usd']);
    $config['margen_brl'] = floatval($_POST['margen_brl']);
    file_put_contents($fileConfig, json_encode($config));
    header("Location: admin.php");
    exit;
}

// --- LOGICA DE GUARDADO (CREAR O EDITAR) ---
if (isset($_POST['add'])) {
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $rango_adulto = !empty($_POST['rango_adulto']) ? $_POST['rango_adulto'] : ''; 
    
    // DATOS DE NIÑOS
    $precio_nino = !empty($_POST['precio_nino']) ? $_POST['precio_nino'] : 0;
    $rango_nino = !empty($_POST['rango_nino']) ? $_POST['rango_nino'] : '';
    
    // 1. Crear SLUG
    $slugInput = !empty($_POST['slug']) ? $_POST['slug'] : $nombre;
    
    // 2. Limpieza PROFUNDA del slug
    $cleanSlug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $slugInput)));
    $cleanSlug = trim($cleanSlug, '-');
    
    // 3. DETECTAR SI ES UNA EDICIÓN DE SLUG (RENOMBRAR)
    // Si venimos de editar y el slug original es diferente al nuevo, borramos el viejo
    if (!empty($_POST['original_slug']) && $_POST['original_slug'] != $cleanSlug) {
        if(isset($tours[$_POST['original_slug']])) {
            unset($tours[$_POST['original_slug']]);
        }
    }

    // 4. GUARDAR / ACTUALIZAR
    $tours[$cleanSlug] = [
        'nombre' => $nombre, 
        'precio_cop' => $precio,
        'rango_adulto' => $rango_adulto,
        'precio_nino' => $precio_nino,
        'rango_nino' => $rango_nino
    ];
    
    file_put_contents($fileTours, json_encode($tours));
    header("Location: admin.php"); // Limpiar formulario
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

// --- CARGAR DATOS PARA EDITAR ---
$tourToEdit = null;
$editingSlug = '';
if (isset($_GET['edit']) && isset($tours[$_GET['edit']])) {
    $tourToEdit = $tours[$_GET['edit']];
    $editingSlug = $_GET['edit'];
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

    <div class="card shadow-sm border-primary">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span><?= $tourToEdit ? '✏️ Editando: ' . htmlspecialchars($tourToEdit['nombre']) : '➕ Nuevo Tour' ?></span>
            <?php if($tourToEdit): ?>
                <a href="admin.php" class="btn btn-sm btn-light text-primary">Cancelar Edición</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3" id="tourForm">
                <input type="hidden" name="original_slug" value="<?= $editingSlug ?>">

                <div class="col-12"><h6 class="text-muted border-bottom pb-2">Datos Principales</h6></div>
                
                <div class="col-md-6">
                    <label class="form-label">Nombre del Tour</label>
                    <input type="text" name="nombre" id="inputNombre" class="form-control" required value="<?= $tourToEdit ? htmlspecialchars($tourToEdit['nombre']) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">URL Amigable (Auto)</label>
                    <input type="text" name="slug" id="inputSlug" class="form-control bg-light" placeholder="se-genera-automatico" value="<?= $editingSlug ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Precio Adulto (COP)</label>
                    <input type="number" name="precio" class="form-control" required value="<?= $tourToEdit ? $tourToEdit['precio_cop'] : '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Rango Edad Adulto</label>
                    <input type="text" name="rango_adulto" class="form-control" placeholder="Ej: 10+ años" value="<?= $tourToEdit && isset($tourToEdit['rango_adulto']) ? htmlspecialchars($tourToEdit['rango_adulto']) : '' ?>">
                </div>

                <div class="col-12 mt-4"><h6 class="text-muted border-bottom pb-2">Datos Niños (Opcional)</h6></div>
                
                <div class="col-md-6">
                    <label class="form-label">Precio Niño (COP)</label>
                    <input type="number" name="precio_nino" class="form-control" placeholder="0 si no aplica" value="<?= $tourToEdit && !empty($tourToEdit['precio_nino']) ? $tourToEdit['precio_nino'] : '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Rango de Edad Niño</label>
                    <input type="text" name="rango_nino" class="form-control" placeholder="Ej: 4 a 9 años" value="<?= $tourToEdit && isset($tourToEdit['rango_nino']) ? htmlspecialchars($tourToEdit['rango_nino']) : '' ?>">
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" name="add" class="btn btn-primary w-100 btn-lg"><?= $tourToEdit ? 'Actualizar Tour' : 'Guardar Tour' ?></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-4 shadow-sm">
        <div class="card-body">
            <table class="table table-hover align-middle">
                <thead><tr><th>Tour</th><th>Adulto</th><th>Niño</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($tours as $slug => $tour): ?>
                    <tr class="<?= $slug == $editingSlug ? 'table-warning' : '' ?>">
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
                        <td>
                            <div class="btn-group" role="group">
                                <a href="?edit=<?= $slug ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="?delete=<?= $slug ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Borrar este tour?');">Borrar</a>
                            </div>
                        </td>
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

    // Solo auto-generar slug si NO estamos editando (o si el usuario borra el slug)
    // Para simplificar, lo haremos siempre al escribir, pero el usuario puede ver que cambia.
    inputNombre.addEventListener('input', function() {
        // Solo generamos si el campo slug está vacio o si el usuario lo desea. 
        // Si estamos editando, tal vez queramos mantener el slug viejo aunque cambiemos una letra del nombre.
        // Pero para simplificar tu uso, haremos que sugiera.
        
        // Comportamiento: Si escribes nombre, sugiere slug.
        let text = this.value;
        let slug = text.toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "") 
            .replace(/[^a-z0-9]+/g, '-') 
            .replace(/^-+|-+$/g, ''); 
            
        // Si ya hay un valor en slug y estamos editando, quizas no quieras sobrescribirlo agresivamente
        // Pero como es un app sencilla, sobrescribirlo ayuda a mantener URLs limpias.
        inputSlug.value = slug;
    });
</script>

</body>
</html>