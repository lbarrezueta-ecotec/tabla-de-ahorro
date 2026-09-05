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

        // Validaciones
        if (empty($nombre) || empty($contrasena) || $salaId <= 0) {
            header('Location: ../views/salas/?error=datos_invalidos');
            exit;
        }
        if (strlen($nombre) < 3 || strlen($nombre) > 50) {
            header('Location: ../views/salas/?error=nombre_invalido');
            exit;
        }
        if (strlen($contrasena) < 4) {
            header('Location: ../views/salas/?error=contrasena_corta');
            exit;
        }

        if ($this->usuarioModel->crear($salaId, $nombre, $contrasena)) {
            header('Location: ../views/salas/?registro=ok&sala_id=' . $salaId);
            exit;
        }

        header('Location: ../views/salas/?error=registro_fallido');
        exit;
    }

    // Login de usuario
    public function login(): void
    {
        $nombre = trim($_POST['nombre'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        $salaId = (int) ($_POST['sala_id'] ?? 0);

        if (empty($nombre) || empty($contrasena) || $salaId <= 0) {
            header('Location: ../views/salas/?error=campos_vacios');
            exit;
        }

        $usuarioId = $this->usuarioModel->verificar($salaId, $nombre, $contrasena);
        if ($usuarioId) {
            session_start();
            $_SESSION['usuario_id'] = $usuarioId;
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['sala_id'] = $salaId;
            header('Location: ../views/progreso/tablero.php');
            exit;
        }

        header('Location: ../views/salas/?error=login_fallido&sala_id=' . $salaId);
        exit;
    }

    // Logout
    public function logout(): void
    {
        session_start();
        session_destroy();
        header('Location: ../');
        exit;
    }
}
