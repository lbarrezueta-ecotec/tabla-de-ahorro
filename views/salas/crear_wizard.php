<?php
session_start();
require_once __DIR__ . '/../../config/conexion.php';

// Debe haber pasado por paso 1 (temp o logueado)
$hasTemp = isset($_SESSION['temp_nombre']);
$isLogged = isset($_SESSION['usuario_id']);
if (!$hasTemp && !$isLogged) {
    header('Location: ../../index.php');
    exit;
}
$displayName = $isLogged ? $_SESSION['usuario_nombre'] : $_SESSION['temp_nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar sala - Tabla de Ahorro</title>
    <link rel="stylesheet" href="../../css/estilos.css">
</head>
<body class="sala-page">
    <div class="sala-container">
        <div class="sala-header">
            <h1> Configurar nueva sala</h1>
            <p class="description">Hola <strong><?= htmlspecialchars($displayName) ?></strong>, configurá tu sala antes de ver la tabla.</p>
        </div>

        <div class="user-card" style="max-width:600px;margin:0 auto">
            <h2>Configurar nueva sala grupal</h2>
            <p>Definí los detalles de tu sala grupal.</p>
            <form method="POST" action="../../index.php" onsubmit="return validarWizard(this)" class="form-box" id="wizardForm">
                <input type="hidden" name="target" value="sala">
                <input type="hidden" name="action" value="crear_configurado">

                <label for="nombre_sala">Nombre de la sala</label>
                <input type="text" id="nombre_sala" name="nombre_sala" maxlength="100" placeholder="Ej. Ahorro vacaciones" required>

                <label for="meta">Meta (USD)</label>
                <input type="number" id="meta" name="meta" min="10" max="100000" step="5" value="1500" required>

                <p style="background:#eaf2f8;padding:10px;border-radius:8px;color:#2c3e50;text-align:center">Estás creando una sala <strong>grupal</strong> — para individual usá el botón "Crear sala (individual)" en el inicio.</p>

                <label>¿Cuántas personas serán en la sala?</label>
                <div style="display:flex;align-items:center;gap:8px;justify-content:center;margin:10px auto;max-width:220px">
                    <button type="button" id="btnMinus" class="password-btn cancel" style="flex:0 0 48px;height:48px;font-size:20px;padding:0">−</button>
                    <input type="number" id="num_personas" name="num_personas" value="2" min="2" max="20" readonly style="width:80px;text-align:center;font-size:22px;font-weight:bold;padding:10px">
                    <button type="button" id="btnPlus" class="password-btn confirm" style="flex:0 0 48px;height:48px;font-size:20px;padding:0">+</button>
                </div>
                <p style="text-align:center;color:#7f8c8d;font-size:13px">Mínimo 2 personas para grupal</p>
                <input type="hidden" name="modo" value="grupal">

                <div id="tipoSumaGroup">
                    <label for="tipo_suma">¿Cómo suma la meta?</label>
                    <select id="tipo_suma" name="tipo_suma" class="meta-input">
                        <option value="conjunta" selected>Conjunta — la suma de todos debe dar el límite (ej. 1500 entre todos)</option>
                        <option value="individual">Individual — cada uno ahorra el límite completo (ej. cada uno 1500)</option>
                    </select>
                </div>

                <div class="password-buttons">
                    <a href="../../index.php?step=sala" class="password-btn cancel" style="text-align:center;text-decoration:none;padding:11px 16px">Cancelar</a>
                    <button type="submit" class="password-btn confirm">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../../js/validaciones.js"></script>
    <script>
        function validarWizard(form) {
            const nombre = form.nombre_sala.value.trim();
            if (!nombre || nombre.length < 3 || nombre.length > 100) { alert('El nombre de la sala debe tener entre 3 y 100 caracteres'); return false; }
            const meta = form.meta.value.trim();
            if (!validarMeta(meta)) return false;
            const num = parseInt(form.num_personas.value);
            if (isNaN(num) || num < 2) { alert('Elegí al menos 2 personas para grupal'); return false; }
            return true;
        }
        document.getElementById('btnMinus').addEventListener('click', function() {
            const input = document.getElementById('num_personas');
            let v = parseInt(input.value) || 2;
            if (v > 2) input.value = v - 1;
        });
        document.getElementById('btnPlus').addEventListener('click', function() {
            const input = document.getElementById('num_personas');
            let v = parseInt(input.value) || 2;
            if (v < 20) input.value = v + 1;
        });
    </script>
</body>
</html>
