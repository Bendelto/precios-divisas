<?php
session_start();
// SEGURIDAD SIMPLE
if (isset($_POST['login']) && $_POST['pass'] == 'TU_CONTRASEÑA') {
    $_SESSION['admin'] = true;
}
if (!isset($_SESSION['admin'])) {
    echo '<form method="post" style="text-align:center; margin-top:50px;">
            <input type="password" name="pass" placeholder="Contraseña">
            <button name="login" type="submit">Entrar</button>
          </form>';
    exit;
}

$file = 'data.json';
$tours = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

// AGREGAR TOUR
if (isset($_POST['add'])) {
    $tours[] = ['nombre' => $_POST['nombre'], 'precio_cop' => $_POST['precio']];
    file_put_contents($file, json_encode($tours));
    header("Location: admin.php");
}

// BORRAR TOUR
if (isset($_GET['delete'])) {
    unset($tours[$_GET['delete']]);
    file_put_contents($file, json_encode(array_values($tours)));
    header("Location: admin.php");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Tours</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
    <h2>Administrar Tours</h2>
    
    <form method="post" class="mb-4 p-3 bg-light rounded">
        <div class="row">
            <div class="col-md-6">
                <input type="text" name="nombre" class="form-control" placeholder="Nombre del Tour" required>
            </div>
            <div class="col-md-4">
                <input type="number" name="precio" class="form-control" placeholder="Precio COP" required>
            </div>
            <div class="col-md-2">
                <button type="submit" name="add" class="btn btn-primary w-100">Agregar</button>
            </div>
        </div>
    </form>

    <table class="table table-striped">
        <thead><tr><th>Tour</th><th>Precio COP</th><th>Acción</th></tr></thead>
        <tbody>
            <?php foreach ($tours as $id => $tour): ?>
            <tr>
                <td><?= $tour['nombre'] ?></td>
                <td>$<?= number_format($tour['precio_cop']) ?></td>
                <td><a href="?delete=<?= $id ?>" class="btn btn-danger btn-sm">Borrar</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="index.php" target="_blank" class="btn btn-success">Ver Página Pública</a>
</body>
</html>