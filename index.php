<?php
// Punto de entrada único
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$target = $_GET['target'] ?? $_POST['target'] ?? null;

// Rutas simples sin front-controller
if ($target === 'sala' && $action === 'crear') {
    require_once __DIR__ . '/controllers/SalaController.php';
    $ctrl = new SalaController($pdo ?? null);
    $ctrl->crear();
    exit;
}

if ($target === 'sala' && $action === 'entrar') {
    require_once __DIR__ . '/controllers/SalaController.php';
    $ctrl = new SalaController($pdo ?? null);
    $ctrl->entrar();
    exit;
}

if ($target === 'usuario' && $action === 'registrar') {
    require_once __DIR__ . '/controllers/UsuarioController.php';
    $ctrl = new UsuarioController($pdo ?? null);
    $ctrl->registrar();
    exit;
}

if ($target === 'usuario' && $action === 'login') {
    require_once __DIR__ . '/controllers/UsuarioController.php';
    $ctrl = new UsuarioController($pdo ?? null);
    $ctrl->login();
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabla de Ahorro - Inicio</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="landing">
    <div class="landing-container">
        <h1>💰 Tabla de Ahorro</h1>
        <p class="description">Crea una sala para ti o tu grupo, o entra con un código compartido.</p>

        <div class="landing-options">
            <div class="landing-card">
                <h2>Crear sala nueva</h2>
                <p>Genera una URL única y un código para compartir.</p>
                <form method="POST" action="index.php" onsubmit="return validarCrearSala(this)">
                    <input type="hidden" name="target" value="sala">
                    <input type="hidden" name="action" value="crear">
                    <button type="submit" class="btn-primary">Crear sala</button>
                </form>
            </div>

            <div class="landing-card">
                <h2>Entrar a una sala</h2>
                <p>Ingresa el código que te compartieron.</p>
                <form method="POST" action="index.php" onsubmit="return validarEntrarSala(this)">
                    <input type="hidden" name="target" value="sala">
                    <input type="hidden" name="action" value="entrar">
                    <label for="codigo">Código de sala</label>
                    <input type="text" id="codigo" name="codigo" maxlength="10" placeholder="Ej. ABC123" required>
                    <button type="submit" class="btn-secondary">Entrar</button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/validaciones.js"></script>
</body>
</html>
