<?php
session_start();
require_once __DIR__ . '/config/conexion.php';

// Punto de entrada único
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$target = $_GET['target'] ?? $_POST['target'] ?? null;

// Nuevo flujo: paso 1 - cuenta / login antes de sala
if ($target === 'landing' && $action === 'pre_registro') {
    $nombre = trim($_POST['nombre'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    if (empty($nombre) || strlen($nombre) < 3 || strlen($nombre) > 50) {
        header('Location: index.php?error=nombre_invalido');
        exit;
    }
    if (empty($contrasena) || strlen($contrasena) < 4) {
        header('Location: index.php?error=contrasena_corta');
        exit;
    }
    require_once __DIR__ . '/models/Usuario.php';
    $uCheck = new Usuario($pdo ?? null);
    // Crear cuenta solo crea cuentas nuevas: si el nombre ya existe (con cualquier contraseña), es duplicado
    if ($uCheck->existeNombreGlobal($nombre)) {
        header('Location: index.php?error=registro_fallido');
        exit;
    }
    $_SESSION['temp_nombre'] = $nombre;
    $_SESSION['temp_contrasena'] = $contrasena;
    $_SESSION['auth_mode'] = 'registro';
    header('Location: index.php?step=sala');
    exit;
}
if ($target === 'landing' && $action === 'pre_login') {
    $nombre = trim($_POST['nombre'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    if (empty($nombre) || empty($contrasena)) {
        header('Location: index.php?error=campos_vacios');
        exit;
    }
    require_once __DIR__ . '/models/Usuario.php';
    $uModel = new Usuario($pdo ?? null);
    $found = $uModel->verificarGlobal($nombre, $contrasena);
    if ($found) {
        $_SESSION['usuario_id'] = $found['id'];
        $_SESSION['usuario_nombre'] = $nombre;
        $_SESSION['sala_id'] = $found['sala_id'];
        $_SESSION['temp_contrasena'] = $contrasena;
        header('Location: index.php?step=sala');
        exit;
    } else {
        header('Location: index.php?error=login_fallido');
        exit;
    }
}
if ($target === 'landing' && $action === 'logout_temp') {
    unset($_SESSION['temp_nombre'], $_SESSION['temp_contrasena'], $_SESSION['auth_mode']);
    // Si estaba logueado, también hacer logout completo
    if (isset($_SESSION['usuario_id'])) {
        session_destroy();
        session_start();
    }
    header('Location: index.php');
    exit;
}

// Rutas simples sin front-controller
if ($target === 'sala' && $action === 'crear') {
    require_once __DIR__ . '/controllers/SalaController.php';
    $ctrl = new SalaController($pdo ?? null);
    $ctrl->crear();
    exit;
}

if ($target === 'sala' && $action === 'crear_configurado') {
    require_once __DIR__ . '/controllers/SalaController.php';
    $ctrl = new SalaController($pdo ?? null);
    $ctrl->crearConfigurado();
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

if ($target === 'usuario' && $action === 'logout') {
    require_once __DIR__ . '/controllers/UsuarioController.php';
    $ctrl = new UsuarioController($pdo ?? null);
    $ctrl->logout();
    exit;
}
?>
<?php
  $errorMsg = null;
  if (isset($_GET['error'])) {
      $map = [
          'sala_no_encontrada' => ' Sala no encontrada. Verificá el código.',
          'codigo_vacio' => ' Ingresá un código de sala.',
          'datos_invalidos' => ' Datos inválidos.',
          'sin_sesion' => ' Iniciá sesión para acceder al tablero.',
          'nombre_invalido' => ' Nombre inválido.',
          'contrasena_corta' => ' Contraseña demasiado corta.',
          'registro_fallido' => ' Ese nombre de usuario ya existe',
          'login_fallido' => ' Usuario o contraseña incorrectos.',
          'campos_vacios' => ' Completá todos los campos.',
      ];
      $errorMsg = $map[$_GET['error']] ?? (' Error: ' . htmlspecialchars($_GET['error']));
  }
  $okMsg = isset($_GET['registro']) ? ' Usuario registrado.' : null;
  $step = $_GET['step'] ?? null;
  $isLogged = isset($_SESSION['usuario_id']);
  $hasTemp = isset($_SESSION['temp_nombre']);
  $showSalaStep = $isLogged || $hasTemp || $step === 'sala';
  $displayName = $isLogged ? $_SESSION['usuario_nombre'] : ($hasTemp ? $_SESSION['temp_nombre'] : null);
  // Para Ver mis salas: buscar todas las salas donde el usuario participa
  $misSalas = [];
  if ($showSalaStep && $displayName) {
      try {
          $stmt = $pdo->prepare("SELECT DISTINCT s.codigo, s.nombre, s.modo, s.meta, s.tipo_suma FROM salas s JOIN usuarios u ON u.sala_id = s.id WHERE u.nombre = ? ORDER BY s.creada_en DESC");
          $stmt->execute([$displayName]);
          $misSalas = $stmt->fetchAll();
      } catch (Exception $e) { $misSalas = []; }
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
        <h1> Tabla de Ahorro</h1>
        <p class="description">Crea una sala para ti o tu grupo, o entra con un código compartido.</p>
        <?php if ($errorMsg): ?>
            <div class="alert alert-error"><?= $errorMsg ?></div>
        <?php endif; ?>
        <?php if ($okMsg): ?>
            <div class="alert alert-success"><?= $okMsg ?></div>
        <?php endif; ?>
        <?php if ($displayName): ?>
            <div class="alert alert-success">Sesión: <strong><?= htmlspecialchars($displayName) ?></strong> — <a href="index.php?target=landing&action=logout_temp" style="color:inherit;text-decoration:underline">Cerrar sesion</a></div>
        <?php endif; ?>

        <?php if (!$showSalaStep): ?>
        <!-- PASO 1: Solo login + botón pequeño para registrarse -->
        <div style="max-width:450px;margin:0 auto">
            <div class="landing-card">
                <h2>Iniciar sesión</h2>
                <p>Ingresá para luego crear o unirte a una sala.</p>
                <form method="POST" action="index.php" onsubmit="return validarLoginUsuario(this)" class="form-box">
                    <input type="hidden" name="target" value="landing">
                    <input type="hidden" name="action" value="pre_login">
                    <label for="pre-login-nombre">Usuario</label>
                    <input type="text" id="pre-login-nombre" name="nombre" placeholder="Tu nombre" required>
                    <label for="pre-login-contrasena">Contraseña</label>
                    <input type="password" id="pre-login-contrasena" name="contrasena" placeholder="Tu contraseña" required>
                    <button type="submit" class="btn-primary">Iniciar sesión</button>
                </form>
                <div style="text-align:center;margin-top:15px">
                    <small style="color:#7f8c8d">¿No tienes cuenta?</small><br>
                    <button onclick="document.getElementById('registroModal').style.display='flex'" style="margin-top:8px;background:none;border:none;color:#3498db;cursor:pointer;font-size:14px;text-decoration:underline">Registrarse</button>
                </div>
            </div>
        </div>
        <p class="description" style="margin-top:20px"><small>Después de iniciar sesión o crear cuenta, podrás crear una sala o unirte a una existente.</small></p>
        <?php else: ?>
        <!-- PASO 2: Sala -->
        <div class="landing-options">
            <div class="landing-card">
                <h2>Crear sala nueva</h2>
                <p>Elegí si tu ahorro será individual o grupal.</p>
                <a href="views/salas/crear_wizard.php" class="btn-primary" style="display:block;text-align:center;text-decoration:none;padding:14px">Crear sala (grupal)</a>
                <form method="POST" action="index.php" onsubmit="return validarCrearSalaIndividual(this)" style="margin-top:15px;border-top:1px solid #eee;padding-top:15px">
                    <input type="hidden" name="target" value="sala">
                    <input type="hidden" name="action" value="crear">
                    <label for="nombre-sala-ind">Nombre para sala individual</label>
                    <input type="text" id="nombre-sala-ind" name="nombre_sala" maxlength="100" placeholder="Ej. Mi ahorro personal" required>
                    <button type="submit" class="btn-secondary" style="width:100%">Crear sala (individual)</button>
                </form>
            </div>
            <div class="landing-card">
                <h2>Unirse a una sala</h2>
                <p>Ingresa el código que te compartieron.</p>
                <form method="POST" action="index.php" onsubmit="return validarEntrarSala(this)">
                    <input type="hidden" name="target" value="sala">
                    <input type="hidden" name="action" value="entrar">
                    <label for="codigo">Código de sala</label>
                    <input type="text" id="codigo" name="codigo" maxlength="10" placeholder="Ej. ABC123" required>
                    <button type="submit" class="btn-secondary">Unirse</button>
                </form>
                <?php if ($displayName): ?>
                    <button onclick="document.getElementById('misSalasModal').style.display='flex'" class="btn-secondary" style="width:100%;margin-top:10px;background:#ecf0f1;color:#2c3e50;border:1px solid #ddd">Ver mis salas (<?= count($misSalas) ?>)</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Ventana flotante Registro -->
    <div id="registroModal" class="password-container" style="display:none">
        <div class="password-form">
            <div class="password-title">Crear cuenta</div>
            <p class="password-subtitle">Creá tu usuario para luego crear o unirte a una sala</p>
            <form method="POST" action="index.php" onsubmit="return validarRegistroUsuario(this)" class="form-box">
                <input type="hidden" name="target" value="landing">
                <input type="hidden" name="action" value="pre_registro">
                <label for="pre-reg-nombre">Nombre de usuario</label>
                <input type="text" id="pre-reg-nombre" name="nombre" placeholder="Mínimo 3 caracteres" required>
                <label for="pre-reg-contrasena">Contraseña</label>
                <input type="password" id="pre-reg-contrasena" name="contrasena" placeholder="Mínimo 4 caracteres" required>
                <div class="password-buttons">
                    <button type="button" onclick="document.getElementById('registroModal').style.display='none'" class="password-btn cancel">Cancelar</button>
                    <button type="submit" class="password-btn confirm">Crear cuenta</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ventana flotante Mis Salas -->
    <div id="misSalasModal" class="password-container" style="display:none">
        <div class="password-form" style="max-width:500px;max-height:80vh;overflow:auto">
            <div class="password-title">Mis salas (<?= htmlspecialchars($displayName ?? '') ?>)</div>
            <?php if (empty($misSalas)): ?>
                <p class="description">Aún no formas parte de ninguna sala. ¡Creá una o unite con un código!</p>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:1fr;gap:12px;margin:15px 0">
                    <?php foreach ($misSalas as $ms): ?>
                        <div id="sala-<?= htmlspecialchars($ms['codigo']) ?>" style="background:white;padding:16px;border-radius:10px;border:1px solid #e9ecef;box-shadow:0 2px 8px rgba(0,0,0,0.04)">
                            <div style="display:flex;justify-content:space-between;align-items:start;gap:12px;margin-bottom:10px">
                                <div>
                                    <div style="font-weight:600;color:#2c3e50;font-size:15px"><?= htmlspecialchars($ms['nombre'] ?? 'Sala ' . $ms['codigo']) ?></div>
                                    <div style="font-family:monospace;font-size:12px;color:#7f8c8d;letter-spacing:1px;margin-top:4px"><?= htmlspecialchars($ms['codigo']) ?></div>
                                </div>
                                <span class="sala-code" style="background:#f8f9fa"><?= htmlspecialchars($ms['modo']) ?></span>
                            </div>
                            <div style="display:flex;gap:8px;font-size:12px;color:#5a6c7d;margin-bottom:12px">
                                <span>Meta $<?= htmlspecialchars($ms['meta']) ?></span>
                                <span>·</span>
                                <span><?= htmlspecialchars($ms['tipo_suma']) ?></span>
                            </div>
                            <div style="display:flex;gap:8px">
                                <a href="index.php?target=sala&action=entrar&codigo=<?= urlencode($ms['codigo']) ?>" class="btn-primary" style="flex:1;padding:8px 12px;font-size:13px;text-align:center;text-decoration:none">Ir</a>
                                <button onclick="document.getElementById('sala-<?= htmlspecialchars($ms['codigo']) ?>').style.display='none'" class="btn-secondary" style="flex:0 0 auto;padding:8px 12px;font-size:13px;background:white;color:#e74c3c;border-color:#f5b7b1" title="Solo lo oculta aquí; si te vuelves a unir con el código, reaparecerá">Eliminar</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <button onclick="document.getElementById('misSalasModal').style.display='none'" class="password-btn cancel" style="margin-top:15px">Cerrar</button>
        </div>
    </div>

    <script src="js/validaciones.js"></script>
    <script>
        // Cerrar modal al hacer click fuera
        document.getElementById('misSalasModal')?.addEventListener('click', function(e) {
            if (e.target === this) this.style.display = 'none';
        });
    </script>
</body>
</html>
