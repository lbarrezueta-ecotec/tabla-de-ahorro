<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Sala.php';

class UsuarioController
{
    private Usuario $usuarioModel;
    private Sala $salaModel;

    public function __construct($pdo)
    {
        $this->usuarioModel = new Usuario($pdo);
        $this->salaModel = new Sala($pdo);
    }

    // Registrar nuevo usuario en una sala
    public function registrar(): void
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $salaId = (int) ($_POST['sala_id'] ?? 0);

        // Obtener código de sala para redirección correcta
        $sala = $salaId ? $this->salaModel->obtenerPorId($salaId) : null;
        if (!$sala) {
            header('Location: ../../index.php?error=datos_invalidos');
            exit;
        }
        $codigo = $sala['codigo'];
        $baseRedirect = '../views/salas/sala.php?codigo=' . urlencode($codigo);

        // Validaciones
        if (empty($nombre) || empty($contrasena)) {
            header('Location: ' . $baseRedirect . '&error=datos_invalidos');
            exit;
        }
        if (strlen($nombre) < 3 || strlen($nombre) > 50) {
            header('Location: ' . $baseRedirect . '&error=nombre_invalido');
            exit;
        }
        if (strlen($contrasena) < 4) {
            header('Location: ' . $baseRedirect . '&error=contrasena_corta');
            exit;
        }

        if ($this->usuarioModel->crear($salaId, $nombre, $contrasena)) {
            header('Location: ' . $baseRedirect . '&registro=ok');
            exit;
        }

        header('Location: ' . $baseRedirect . '&error=registro_fallido');
        exit;
    }

    // Login de usuario
    public function login(): void
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $salaId = (int) ($_POST['sala_id'] ?? 0);

        $sala = $salaId ? $this->salaModel->obtenerPorId($salaId) : null;
        if (!$sala) {
            header('Location: ../../index.php?error=datos_invalidos');
            exit;
        }
        $codigo = $sala['codigo'];
        $baseRedirect = '../views/salas/sala.php?codigo=' . urlencode($codigo);

        if (empty($nombre) || empty($contrasena)) {
            header('Location: ' . $baseRedirect . '&error=campos_vacios');
            exit;
        }

        $usuarioId = $this->usuarioModel->verificar($salaId, $nombre, $contrasena);
        if ($usuarioId) {
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $_SESSION['usuario_id'] = $usuarioId;
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['sala_id'] = $salaId;
            header('Location: ../views/progreso/tablero.php');
            exit;
        }

        header('Location: ' . $baseRedirect . '&error=login_fallido');
        exit;
    }

    // Logout
    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        session_destroy();
        // Redirige a la landing, funciona tanto vía index.php como vía directa
        if (strpos($_SERVER['SCRIPT_NAME'] ?? '', 'index.php') !== false) {
            header('Location: index.php');
        } else {
            header('Location: ../../index.php');
        }
        exit;
    }
}

// Soporte para acceso directo al controlador (compatibilidad con vistas que usan controllers/UsuarioController.php)
if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'UsuarioController.php' && isset($_GET['action'])) {
    require_once __DIR__ . '/../config/conexion.php';
    $ctrl = new UsuarioController($pdo ?? null);
    $action = $_GET['action'] ?? '';
    if ($action === 'registrar') $ctrl->registrar();
    if ($action === 'login') $ctrl->login();
    if ($action === 'logout') $ctrl->logout();
}
