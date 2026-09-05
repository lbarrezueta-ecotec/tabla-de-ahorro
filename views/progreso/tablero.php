<?php
session_start();
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/Sala.php';
require_once __DIR__ . '/../../models/Progreso.php';
require_once __DIR__ . '/../../models/Usuario.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['sala_id'])) {
    header('Location: ../../index.php?error=sin_sesion');
    exit;
}

$salaModel = new Sala($pdo);
$progresoModel = new Progreso($pdo);
$usuarioModel = new Usuario($pdo);

$sala = $salaModel->obtenerPorId((int) $_SESSION['sala_id']);
$miProgreso = $progresoModel->obtenerPorUsuario((int) $_SESSION['sala_id'], (int) $_SESSION['usuario_id']);
$todos = $progresoModel->listarPorSala((int) $_SESSION['sala_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabla de Ahorro - <?= htmlspecialchars($sala['codigo']) ?></title>
    <link rel="stylesheet" href="../../css/estilos.css">
</head>
<body>
    <div class="savings-container">
        <div class="header">
            <div class="user-info">
                <span class="current-user">👤 <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                <span class="sala-code">Sala: <?= htmlspecialchars($sala['codigo']) ?></span>
            </div>
            <div class="header-actions">
                <button id="editMetaBtn" class="btn-secondary">✏️ Editar meta</button>
                <a href="../../controllers/UsuarioController.php?action=logout" class="logout-btn">Cerrar Sesión</a>
            </div>
        </div>

        <h1>Tabla de Ahorro</h1>
        <p class="description">
            Modo: <strong id="modoTexto"><?= htmlspecialchars($sala['modo']) ?></strong> ·
            Meta: $<span id="metaValor"><?= htmlspecialchars($sala['meta']) ?></span>
        </p>

        <?php if ($sala['modo'] === 'grupal'): ?>
            <div class="sala-progress-summary" id="salaProgressSummary">
                <h3>Progreso por usuario</h3>
                <div id="listaUsuarios" class="usuarios-grid">
                    <?php foreach ($todos as $u): ?>
                        <div class="usuario-bar" data-usuario="<?= (int) $u['usuario_id'] ?>">
                            <span class="usuario-nombre"><?= htmlspecialchars($u['nombre']) ?></span>
                            <div class="usuario-barra">
                                <div class="usuario-fill" style="width: <?= min(($u['valor_ahorrado'] / $sala['meta']) * 100, 100) ?>%"></div>
                            </div>
                            <span class="usuario-valor">$<?= number_format((float) $u['valor_ahorrado'], 0) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="progress-text">
                <span>Ahorrado: $<span id="savedAmount"><?= (float) ($miProgreso['valor_ahorrado'] ?? 0) ?></span></span>
                <span>Meta: $<span id="metaText"><?= htmlspecialchars($sala['meta']) ?></span></span>
            </div>
        </div>

        <div class="table-container" id="savingsTable"></div>

        <div class="summary">
            <h3>Resumen</h3>
            <div class="summary-stats">
                <div class="stat">
                    <div class="stat-value" id="totalCells"><?= (int) ($miProgreso['casillas_marcadas'] ?? 0) ?></div>
                    <div class="stat-label">Casillas completadas</div>
                </div>
                <div class="stat">
                    <div class="stat-value" id="remainingAmount">0</div>
                    <div class="stat-label">Faltante para la meta</div>
                </div>
                <div class="stat">
                    <div class="stat-value" id="percentComplete">0%</div>
                    <div class="stat-label">Progreso total</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal editar meta -->
    <div class="password-container" id="metaModal" style="display: none;">
        <div class="password-form">
            <div class="password-title">Editar meta de la sala</div>
            <label for="inputMeta">Monto (USD)</label>
            <input type="number" id="inputMeta" min="10" max="100000" step="5" value="<?= htmlspecialchars($sala['meta']) ?>" class="meta-input">
            <label for="selectModo">Modo de ahorro</label>
            <select id="selectModo" class="meta-input">
                <option value="individual" <?= $sala['modo'] === 'individual' ? 'selected' : '' ?>>Individual</option>
                <option value="grupal" <?= $sala['modo'] === 'grupal' ? 'selected' : '' ?>>Grupal</option>
            </select>
            <div class="password-buttons">
                <button class="password-btn confirm" id="guardarMetaBtn">Guardar</button>
                <button class="password-btn cancel" id="cancelarMetaBtn">Cancelar</button>
            </div>
        </div>
    </div>

    <audio id="checkSound" preload="auto">
        <source src="https://luisbarrezuetagarin.github.io/sonidos-ahorro/mixkit-coins-handling-1939.wav" type="audio/wav">
    </audio>
    <audio id="goalSound" preload="auto">
        <source src="https://luisbarrezuetagarin.github.io/sonidos-ahorro/mixkit-achievement-bell-600.wav" type="audio/wav">
    </audio>

    <script>
        // Variables expuestas al script.js
        window.SALA_ID = <?= (int) $sala['id'] ?>;
        window.USUARIO_ID = <?= (int) $_SESSION['usuario_id'] ?>;
        window.META_INICIAL = <?= (float) $sala['meta'] ?>;
        window.MODO_INICIAL = <?= json_encode($sala['modo']) ?>;
        window.PROGRESO_INICIAL = <?= json_encode($miProgreso) ?>;
    </script>
    <script src="../../js/script.js"></script>
</body>
</html>
