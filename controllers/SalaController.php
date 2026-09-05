<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/Sala.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Progreso.php';

class SalaController
{
    private Sala $salaModel;
    private Usuario $usuarioModel;
    private Progreso $progresoModel;

    public function __construct($pdo)
    {
        $this->salaModel = new Sala($pdo);
        $this->usuarioModel = new Usuario($pdo);
        $this->progresoModel = new Progreso($pdo);
    }

    // Landing: crear sala nueva
    public function crear(): void
    {
        $codigo = $this->generarCodigo(6);
        $urlUnica = $this->generarUrlUnica();

        if ($this->salaModel->crear($codigo, $urlUnica)) {
            $sala = $this->salaModel->obtenerPorCodigo($codigo);
            session_start();
            $_SESSION['sala_id'] = $sala['id'];
            $_SESSION['codigo'] = $codigo;
            $_SESSION['url_unica'] = $urlUnica;
            header('Location: ' . $urlUnica);
            exit;
        }

        echo "Error al crear la sala.";
    }

    // Landing: entrar a sala existente
    public function entrar(): void
    {
        $codigo = trim($_POST['codigo'] ?? '');
        if (empty($codigo)) {
            header('Location: ../views/salas/?error=codigo_vacio');
            exit;
        }

        $sala = $this->salaModel->obtenerPorCodigo($codigo);
        if (!$sala) {
            header('Location: ../views/salas/?error=sala_no_encontrada');
            exit;
        }

        session_start();
        $_SESSION['sala_id'] = $sala['id'];
        $_SESSION['codigo'] = $sala['codigo'];
        $_SESSION['url_unica'] = $sala['url_unica'];
        header('Location: ' . $sala['url_unica']);
        exit;
    }

    // Listar salas (vista admin)
    public function listar(): void
    {
        $stmt = $pdo ?? null;
        // Placeholder — se llena cuando se implemente la vista de listado
    }

    public function obtenerPorId(int $id): ?array
    {
        return $this->salaModel->obtenerPorId($id);
    }

    public function actualizarMeta(int $salaId, float $meta): bool
    {
        return $this->salaModel->actualizarMeta($salaId, $meta);
    }

    public function establecerModo(int $salaId, string $modo): bool
    {
        return $this->salaModel->establecerModo($salaId, $modo);
    }

    public function listarUsuarios(int $salaId): array
    {
        return $this->usuarioModel->listarPorSala($salaId);
    }

    public function listarProgresos(int $salaId): array
    {
        return $this->progresoModel->listarPorSala($salaId);
    }

    public function obtenerProgresoUsuario(int $salaId, int $usuarioId): ?array
    {
        return $this->progresoModel->obtenerPorUsuario($salaId, $usuarioId);
    }

    public function registrarProgreso(int $salaId, int $usuarioId, float $valor, int $casillas): bool
    {
        return $this->progresoModel->registrar($salaId, $usuarioId, $valor, $casillas);
    }

    private function generarCodigo(int $longitud = 6): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $codigo = '';
        for ($i = 0; $i < $longitud; $i++) {
            $codigo .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $codigo;
    }

    private function generarUrlUnica(): string
    {
        $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'
            ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
            . dirname($_SERVER['SCRIPT_NAME']);
        $token = bin2hex(random_bytes(8));
        return $base . '/sala.php?id=' . $token;
    }
}
