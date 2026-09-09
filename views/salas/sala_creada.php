<?php
session_start();
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/Sala.php';

$codigo = trim($_GET['codigo'] ?? '');
$salaModel = new Sala($pdo);
$sala = $codigo ? $salaModel->obtenerPorCodigo($codigo) : null;
if (!$sala) {
    header('Location: ../../index.php?error=sala_no_encontrada');
    exit;
}
if (!isset($_SESSION['usuario_id']) || (int)$_SESSION['sala_id'] !== (int)$sala['id']) {
    // Permitir si es admin o si tiene sesión
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala <?= htmlspecialchars($sala['nombre'] ?? $sala['codigo']) ?> creada - Tabla de Ahorro</title>
    <link rel="stylesheet" href="../../css/estilos.css">
</head>
<body class="sala-page">
    <div class="sala-container">
        <div class="sala-header">
            <h1> Sala creada: <?= htmlspecialchars($sala['nombre'] ?? $sala['codigo']) ?></h1>
            <p class="description">Compartí el código <strong><?= htmlspecialchars($sala['codigo']) ?></strong> con quienes quieras invitar.</p>
        </div>

        <div class="user-card" style="max-width:600px;margin:0 auto;text-align:center">
            <h2><?= htmlspecialchars($sala['nombre'] ?? 'Sala ' . $sala['codigo']) ?></h2>
            <div style="font-size:36px;font-weight:bold;letter-spacing:6px;background:#ecf0f1;padding:15px;border-radius:10px;margin:15px 0"><?= htmlspecialchars($sala['codigo']) ?></div>
            <p><strong><?= htmlspecialchars($sala['nombre'] ?? $sala['codigo']) ?></strong> — Meta: <strong>$<?= htmlspecialchars($sala['meta']) ?></strong> · Modo: <strong><?= htmlspecialchars($sala['modo']) ?></strong> · Personas: <strong><?= (int)$sala['num_personas'] ?></strong> · Tipo: <strong><?= htmlspecialchars($sala['tipo_suma']) ?></strong></p>
            <?php if ($sala['modo'] === 'grupal'): ?>
                <p>En modo grupal la suma de todos <?= $sala['tipo_suma'] === 'conjunta' ? 'debe dar la meta conjunta' : 'es individual por persona' ?>.</p>
            <?php endif; ?>
            <a href="../progreso/tablero.php" class="btn-primary" style="display:block;text-align:center;text-decoration:none;padding:14px;margin-top:20px">Continuar a tu tabla →</a>
            <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($sala['codigo']) ?>'); alert('Código copiado: <?= htmlspecialchars($sala['codigo']) ?>')" class="btn-secondary" style="width:100%;margin-top:10px">Copiar código</button>
            <a href="../../index.php" style="display:block;margin-top:15px;color:#7f8c8d">Volver al inicio</a>
        </div>
    </div>
</body>
</html>
