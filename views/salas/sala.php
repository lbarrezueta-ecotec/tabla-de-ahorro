<?php
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/Sala.php';

$salaModel = new Sala($pdo);

// Obtener sala por código desde query param
$codigo = trim($_GET['codigo'] ?? '');
$sala = $codigo ? $salaModel->obtenerPorCodigo($codigo) : null;

// Si no existe, redirigir
if (!$sala) {
    header('Location: ../../index.php?error=sala_no_encontrada');
    exit;
}

$error = $_GET['error'] ?? null;
$registroOk = isset($_GET['registro']);
$loginFallido = $error === 'login_fallido';
$errorMsg = null;
if ($error && !$loginFallido) {
    $map = [
        'datos_invalidos' => ' Datos inválidos. Revisá los campos.',
        'nombre_invalido' => ' El nombre debe tener entre 3 y 50 caracteres.',
        'contrasena_corta' => ' La contraseña debe tener al menos 4 caracteres.',
        'registro_fallido' => ' Ese nombre de usuario ya existe',
        'campos_vacios' => ' Completá todos los campos.',
        'codigo_vacio' => ' Código de sala vacío.',
        'sala_no_encontrada' => ' Sala no encontrada.',
    ];
    $errorMsg = $map[$error] ?? (' Error: ' . htmlspecialchars($error));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala <?= htmlspecialchars($sala['codigo']) ?> - Tabla de Ahorro</title>
    <link rel="stylesheet" href="../../css/estilos.css">
</head>
<body class="sala-page">
    <div class="sala-container">
        <div class="sala-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px">
            <div>
                <h1> Sala: <?= htmlspecialchars($sala['codigo']) ?></h1>
                <p class="description" style="margin:5px 0 0">
                    Compartí el código <strong><?= htmlspecialchars($sala['codigo']) ?></strong> con quienes quieras invitar.
                </p>
            </div>
            <a href="../../index.php" class="logout-btn" style="background:#3498db;text-decoration:none">Menú</a>
        </div>

        <?php if ($registroOk): ?>
            <div class="alert alert-success"> Usuario registrado. Ahora iniciá sesión.</div>
        <?php endif; ?>

        <?php if ($loginFallido): ?>
            <div class="alert alert-error"> Usuario o contraseña incorrectos.</div>
        <?php endif; ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><?= $errorMsg ?></div>
        <?php endif; ?>

        <div class="sala-users-section">
            <div class="user-card">
                <h2>¿Ya tenés cuenta?</h2>
                <p>Iniciá sesión en esta sala.</p>
                <form method="POST" action="../../index.php" onsubmit="return validarLoginUsuario(this)" class="form-box">
                    <input type="hidden" name="target" value="usuario">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="sala_id" value="<?= $sala['id'] ?>">
                    <label for="login-nombre">Usuario</label>
                    <input type="text" id="login-nombre" name="nombre" placeholder="Tu nombre de usuario" required>
                    <label for="login-contrasena">Contraseña</label>
                    <input type="password" id="login-contrasena" name="contrasena" placeholder="Tu contraseña" required>
                    <button type="submit" class="btn-primary">Iniciar Sesión</button>
                </form>
            </div>

            <div class="user-card">
                <h2>¿Sos nuevo?</h2>
                <p>Creá tu usuario para esta sala.</p>
                <form method="POST" action="../../index.php" onsubmit="return validarRegistroUsuario(this)" class="form-box">
                    <input type="hidden" name="target" value="usuario">
                    <input type="hidden" name="action" value="registrar">
                    <input type="hidden" name="sala_id" value="<?= $sala['id'] ?>">
                    <label for="reg-nombre">Nombre de usuario</label>
                    <input type="text" id="reg-nombre" name="nombre" placeholder="Mínimo 3 caracteres" required>
                    <label for="reg-contrasena">Contraseña</label>
                    <input type="password" id="reg-contrasena" name="contrasena" placeholder="Mínimo 4 caracteres" required>
                    <button type="submit" class="btn-secondary">Crear Usuario</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../../js/validaciones.js"></script>
</body>
</html>
