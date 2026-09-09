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

    // Landing: crear sala nueva (individual)
    public function crear(): void
    {
        $codigo = $this->generarCodigo(6);
        $urlUnica = $this->generarUrlUnica($codigo);
        $nombre = trim($_POST['nombre_sala'] ?? $_POST['nombre'] ?? '');
        if ($nombre === '') $nombre = 'Sala ' . $codigo;

        if ($this->salaModel->crear($codigo, $urlUnica, null, null, null, 1, 'conjunta', null, $nombre)) {
            $sala = $this->salaModel->obtenerPorCodigo($codigo);
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            // Si hay temp (nuevo flujo), crear usuario y hacerlo admin
            if (isset($_SESSION['temp_nombre'], $_SESSION['temp_contrasena'])) {
                $nombre = $_SESSION['temp_nombre'];
                $pass = $_SESSION['temp_contrasena'];
                if ($this->usuarioModel->crear($sala['id'], $nombre, $pass)) {
                    $uid = $this->usuarioModel->verificar($sala['id'], $nombre, $pass);
                    if ($uid) {
                        $this->salaModel->establecerAdmin($sala['id'], $uid);
                        $_SESSION['usuario_id'] = $uid;
                        $_SESSION['usuario_nombre'] = $nombre;
                        $_SESSION['sala_id'] = $sala['id'];
                        unset($_SESSION['temp_nombre'], $_SESSION['temp_contrasena'], $_SESSION['auth_mode']);
                        // Si es individual (por defecto), ir directo a tablero
                        header('Location: views/progreso/tablero.php');
                        exit;
                    }
                }
            } elseif (isset($_SESSION['usuario_id'])) {
                // Ya logueado, crear entrada para este usuario en nueva sala y hacerlo admin
                $existing = $this->usuarioModel->obtenerPorId((int)$_SESSION['usuario_id']);
                if ($existing) {
                    $nombre = $existing['nombre'];
                    // Reusar hash: crear con mismo nombre y contraseña dummy, luego copiar hash
                    // Simplificado: crear nuevo usuario con mismo nombre y pass del temp si existe, sino usar hash existente
                    $hash = $existing['contrasena'];
                    // Crear directamente con hash (evitar re-hash)
                    $stmt = $this->salaModel; // dummy to avoid unused
                    try {
                        $pdo = $this->usuarioModel; // dummy
                    } catch (\Exception $e) {}
                    // Crear usuario via SQL directo con hash
                    $ref = new \ReflectionClass($this->usuarioModel);
                    $prop = $ref->getProperty('pdo');
                    $prop->setAccessible(true);
                    $pdoObj = $prop->getValue($this->usuarioModel);
                    $stmt2 = $pdoObj->prepare("INSERT INTO usuarios (sala_id, nombre, contrasena) VALUES (?, ?, ?)");
                    try { $stmt2->execute([$sala['id'], $nombre, $hash]); $uid = (int)$pdoObj->lastInsertId(); } catch (\Exception $e) { $uid = null; }
                    if ($uid) {
                        $this->salaModel->establecerAdmin($sala['id'], $uid);
                        $_SESSION['usuario_id'] = $uid;
                        $_SESSION['sala_id'] = $sala['id'];
                        header('Location: views/progreso/tablero.php');
                        exit;
                    }
                }
            }
            $_SESSION['sala_id'] = $sala['id'];
            $_SESSION['codigo'] = $codigo;
            $_SESSION['url_unica'] = $urlUnica;
            header('Location: views/salas/sala.php?codigo=' . $codigo);
            exit;
        }

        echo "Error al crear la sala.";
    }

    // Nuevo flujo: crear sala con configuración completa (wizard grupal)
    public function crearConfigurado(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $nombre = trim($_POST['nombre_sala'] ?? $_POST['nombre'] ?? '');
        if ($nombre === '' || strlen($nombre) < 3) {
            header('Location: ../../views/salas/crear_wizard.php?error=nombre_invalido');
            exit;
        }
        $meta = isset($_POST['meta']) ? (float)$_POST['meta'] : 1500;
        $numPersonas = isset($_POST['num_personas']) ? (int)$_POST['num_personas'] : 2;
        $modo = $_POST['modo'] ?? 'grupal';
        $tipoSuma = $_POST['tipo_suma'] ?? 'conjunta';
        // Validar meta numérica
        if ($meta < 10 || $meta > 100000) $meta = 1500;
        if ($numPersonas < 1) $numPersonas = 1;
        if (!in_array($modo, ['individual','grupal'])) $modo = 'individual';
        if (!in_array($tipoSuma, ['conjunta','individual'])) $tipoSuma = 'conjunta';
        // Si es individual, forzar num_personas=1 y tipo_suma=individual
        if ($modo === 'individual') {
            $numPersonas = 1;
            $tipoSuma = 'individual';
        }

        $codigo = $this->generarCodigo(6);
        $urlUnica = $this->generarUrlUnica($codigo);

        if (!$this->salaModel->crear($codigo, $urlUnica, $meta, $modo, null, $numPersonas, $tipoSuma, null, $nombre)) {
            echo "Error al crear la sala configurada.";
            return;
        }
        $sala = $this->salaModel->obtenerPorCodigo($codigo);

        // Crear usuario admin si hay temp
        $isIndividual = ($modo === 'individual' && $numPersonas === 1);
        if (isset($_SESSION['temp_nombre'], $_SESSION['temp_contrasena'])) {
            $nombre = $_SESSION['temp_nombre'];
            $pass = $_SESSION['temp_contrasena'];
            if ($this->usuarioModel->crear($sala['id'], $nombre, $pass)) {
                $uid = $this->usuarioModel->verificar($sala['id'], $nombre, $pass);
                if ($uid) {
                    $this->salaModel->establecerAdmin($sala['id'], $uid);
                    $_SESSION['usuario_id'] = $uid;
                    $_SESSION['usuario_nombre'] = $nombre;
                    $_SESSION['sala_id'] = $sala['id'];
                    unset($_SESSION['temp_nombre'], $_SESSION['temp_contrasena'], $_SESSION['auth_mode']);
                    if ($isIndividual) {
                        header('Location: views/progreso/tablero.php');
                        exit;
                    } else {
                        // Grupal: mostrar código y botón continuar
                        header('Location: views/salas/sala_creada.php?codigo=' . $codigo);
                        exit;
                    }
                }
            }
        } elseif (isset($_SESSION['usuario_id'])) {
            // Ya logueado, clonar usuario a nueva sala
            $existing = $this->usuarioModel->obtenerPorId((int)$_SESSION['usuario_id']);
            if ($existing) {
                $hash = $existing['contrasena'];
                $nombre = $existing['nombre'];
                $ref = new \ReflectionClass($this->usuarioModel);
                $prop = $ref->getProperty('pdo');
                $prop->setAccessible(true);
                $pdoObj = $prop->getValue($this->usuarioModel);
                $stmt2 = $pdoObj->prepare("INSERT INTO usuarios (sala_id, nombre, contrasena) VALUES (?, ?, ?)");
                try { $stmt2->execute([$sala['id'], $nombre, $hash]); $uid = (int)$pdoObj->lastInsertId(); } catch (\Exception $e) { $uid = null; }
                if ($uid) {
                    $this->salaModel->establecerAdmin($sala['id'], $uid);
                    $_SESSION['usuario_id'] = $uid;
                    $_SESSION['sala_id'] = $sala['id'];
                    if ($isIndividual) {
                        header('Location: views/progreso/tablero.php');
                        exit;
                    } else {
                        header('Location: views/salas/sala_creada.php?codigo=' . $codigo);
                        exit;
                    }
                }
            }
        }
        // Fallback sin usuario: redirigir a sala
        $_SESSION['sala_id'] = $sala['id'];
        $_SESSION['codigo'] = $codigo;
        header('Location: views/salas/sala.php?codigo=' . $codigo);
        exit;
    }

    // Landing: entrar a sala existente
    public function entrar(): void
    {
        $codigo = trim($_POST['codigo'] ?? $_GET['codigo'] ?? '');
        if (empty($codigo)) {
            header('Location: index.php?error=codigo_vacio');
            exit;
        }

        $sala = $this->salaModel->obtenerPorCodigo($codigo);
        if (!$sala) {
            header('Location: index.php?error=sala_no_encontrada');
            exit;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        // Si viene del nuevo flujo con temp cuenta, crear usuario en esta sala y loguear directo a tablero
        if (isset($_SESSION['temp_nombre'], $_SESSION['temp_contrasena'])) {
            $nombre = $_SESSION['temp_nombre'];
            $pass = $_SESSION['temp_contrasena'];
            // Si es grupal con tipo_suma conjunta, verificar si ya existe usuario con ese nombre
            if (!$this->usuarioModel->verificar($sala['id'], $nombre, $pass)) {
                // Crear si no existe (evitar duplicado)
                $chk = $this->usuarioModel->verificar($sala['id'], $nombre, $pass);
                if (!$chk) {
                    $this->usuarioModel->crear($sala['id'], $nombre, $pass);
                }
            }
            $uid = $this->usuarioModel->verificar($sala['id'], $nombre, $pass);
            if ($uid) {
                $_SESSION['usuario_id'] = $uid;
                $_SESSION['usuario_nombre'] = $nombre;
                $_SESSION['sala_id'] = $sala['id'];
                unset($_SESSION['temp_nombre'], $_SESSION['temp_contrasena'], $_SESSION['auth_mode']);
                header('Location: views/progreso/tablero.php');
                exit;
            }
        }
        // Si ya está logueado, verificar/crear usuario en la nueva sala y actualizar sesión
        if (isset($_SESSION['usuario_id'])) {
            $currentUid = (int)$_SESSION['usuario_id'];
            $currentUser = $this->usuarioModel->obtenerPorId($currentUid);
            $nombre = $currentUser['nombre'] ?? $_SESSION['usuario_nombre'] ?? '';
            // Verificar si ya tiene usuario en la sala destino
            $existingInTarget = null;
            if ($nombre !== '') {
                // Buscar por nombre en la sala destino
                $stmt = $this->salaModel; // dummy
                $ref = new \ReflectionClass($this->usuarioModel);
                $prop = $ref->getProperty('pdo');
                $prop->setAccessible(true);
                $pdoObj = $prop->getValue($this->usuarioModel);
                $chkStmt = $pdoObj->prepare("SELECT id, contrasena FROM usuarios WHERE sala_id = ? AND nombre = ?");
                $chkStmt->execute([$sala['id'], $nombre]);
                $row = $chkStmt->fetch();
                if ($row) {
                    $existingInTarget = (int)$row['id'];
                } else {
                    // No existe, crear nuevo usuario en esta sala con mismo hash
                    $hash = $currentUser['contrasena'] ?? '';
                    if ($hash !== '') {
                        $ins = $pdoObj->prepare("INSERT INTO usuarios (sala_id, nombre, contrasena) VALUES (?, ?, ?)");
                        try { $ins->execute([$sala['id'], $nombre, $hash]); $existingInTarget = (int)$pdoObj->lastInsertId(); } catch (\Exception $e) { $existingInTarget = null; }
                    }
                }
            }
            if ($existingInTarget) {
                $_SESSION['usuario_id'] = $existingInTarget;
            }
            $_SESSION['sala_id'] = $sala['id'];
            $_SESSION['codigo'] = $sala['codigo'];
            $_SESSION['url_unica'] = $sala['url_unica'];
            header('Location: views/progreso/tablero.php');
            exit;
        }
        $_SESSION['sala_id'] = $sala['id'];
        $_SESSION['codigo'] = $sala['codigo'];
        $_SESSION['url_unica'] = $sala['url_unica'];
        header('Location: views/salas/sala.php?codigo=' . $sala['codigo']);
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

    private function generarUrlUnica(?string $codigo = null): string
    {
        $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'
            ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
            . dirname($_SERVER['SCRIPT_NAME']);
        // URL única basada en el código de sala, no en token aleatorio
        if ($codigo) {
            return rtrim($base, '/') . '/views/salas/sala.php?codigo=' . $codigo;
        }
        $token = bin2hex(random_bytes(8));
        return rtrim($base, '/') . '/views/salas/sala.php?codigo=' . $token;
    }
}
