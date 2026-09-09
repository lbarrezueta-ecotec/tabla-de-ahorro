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
if (!$sala) {
    header('Location: ../../index.php?error=sala_no_encontrada');
    exit;
}
$miProgreso = $progresoModel->obtenerPorUsuario((int) $_SESSION['sala_id'], (int) $_SESSION['usuario_id']);
$todos = $progresoModel->listarPorSala((int) $_SESSION['sala_id']);
$isAdmin = $salaModel->esAdmin((int) $sala['id'], (int) $_SESSION['usuario_id']);
// Calcular total grupal para tipo_suma conjunta
$totalGrupal = 0;
foreach ($todos as $t) { $totalGrupal += (float)($t['valor_ahorrado'] ?? 0); }
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
                <span class="current-user"> <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                <span class="sala-code">Sala: <?= htmlspecialchars($sala['codigo']) ?></span>
                <?php if ($isAdmin): ?><span class="sala-code" style="background:#2ecc71;color:white">Admin</span><?php endif; ?>
            </div>
            <div class="header-actions">
                <a href="../../index.php" class="logout-btn" style="background:#3498db;min-width:80px;text-align:center">Menú</a>
                <?php if ($isAdmin): ?>
                    <button id="editMetaBtn" class="logout-btn" style="background:#f39c12;min-width:80px">Editar</button>
                <?php else: ?>
                    <span class="sala-code" title="Solo el admin puede configurar el límite">Meta: $<?= htmlspecialchars($sala['meta']) ?></span>
                <?php endif; ?>
                <a href="../../index.php?target=usuario&action=logout" class="logout-btn" style="background:#e74c3c;min-width:90px;text-align:center">Cerrar sesión</a>
            </div>
        </div>

        <h1><?= htmlspecialchars($sala['nombre'] ?? 'Tabla de Ahorro') ?></h1>
        <p class="description">
            Código: <strong><?= htmlspecialchars($sala['codigo']) ?></strong> · Modo: <strong id="modoTexto"><?= htmlspecialchars($sala['modo']) ?></strong> ·
            Meta: $<span id="metaValor"><?= htmlspecialchars($sala['meta']) ?></span>
        </p>

        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="progress-text">
                <span>Ahorrado: $<span id="savedAmount"><?= (float) ($miProgreso['valor_ahorrado'] ?? 0) ?></span></span>
                <span>Meta: $<span id="metaText"><?= htmlspecialchars($sala['meta']) ?></span></span>
            </div>
            <div id="excedidoBox" style="display:none;margin-top:10px;padding:10px;background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;color:#155724;text-align:center;font-weight:600"></div>
        </div>

        <div class="tablero-layout <?= $sala['modo'] === 'grupal' ? 'grupal' : '' ?>">
            <div class="table-container" id="savingsTable"></div>
            <?php if ($sala['modo'] === 'grupal'): ?>
                <div class="sala-progress-summary" id="salaProgressSummary">
                    <h3>Progreso por usuario — ordenado por cercanía</h3>
                    <p class="description" style="margin-bottom:10px">Tipo: <strong><?= htmlspecialchars($sala['tipo_suma']) ?></strong> · Admin: <strong><?= htmlspecialchars($sala['admin_usuario_id'] ? ($usuarioModel->obtenerPorId((int)$sala['admin_usuario_id'])['nombre'] ?? '—') : '—') ?></strong></p>
                    <div id="listaUsuarios" class="usuarios-grid">
                        <?php
                        usort($todos, function($a,$b) use ($sala) {
                            $pa = $sala['meta'] > 0 ? ((float)($a['valor_ahorrado'] ?? 0) / (float)$sala['meta']) : 0;
                            $pb = $sala['meta'] > 0 ? ((float)($b['valor_ahorrado'] ?? 0) / (float)$sala['meta']) : 0;
                            return $pb <=> $pa;
                        });
                        foreach ($todos as $u):
                            $valor = (float)($u['valor_ahorrado'] ?? 0);
                            $pct = $sala['meta'] > 0 ? min(($valor / (float)$sala['meta']) * 100, 100) : 0;
                            $color = $pct >= 100 ? '#2ecc71' : ($pct >= 75 ? '#3498db' : ($pct >= 50 ? '#f39c12' : '#e74c3c'));
                            $excedido = $valor > (float)$sala['meta'] ? $valor - (float)$sala['meta'] : 0;
                        ?>
                            <div class="usuario-bar" data-usuario="<?= (int) $u['usuario_id'] ?>">
                                <span class="usuario-nombre"><?= htmlspecialchars($u['nombre']) ?></span>
                                <div class="usuario-barra">
                                    <div class="usuario-fill" style="width: <?= $pct ?>%; background: <?= $color ?>"></div>
                                </div>
                                <span class="usuario-valor">$<?= number_format($valor, 0) ?> (<?= round($pct) ?>%)</span>
                                <?php if ($excedido > 0): ?>
                                    <span class="sala-code" style="background:#d4edda;color:#155724;font-size:11px">+<?= number_format($excedido, 0) ?> excedido</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

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
                <button class="password-btn cancel" id="cancelarMetaBtn">Cancelar</button>
                <button class="password-btn confirm" id="guardarMetaBtn">Guardar</button>
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
        window.TIPO_SUMA = <?= json_encode($sala['tipo_suma']) ?>;
        window.TOTAL_GRUPAL = <?= (float)$totalGrupal ?>;
        window.SALA_VALORES = <?= $sala['valores'] ? $sala['valores'] : 'null' ?>;
        window.PROGRESO_INICIAL = <?= json_encode($miProgreso) ?>;
    </script>
    <script src="../../js/validaciones.js"></script>
    <script src="../../js/script.js"></script>
</body>
</html>
