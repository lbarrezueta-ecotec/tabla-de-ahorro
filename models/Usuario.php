<?php
class Usuario
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function crear(int $salaId, string $nombre, string $contrasena): bool
    {
        // Evitar duplicados por sala
        $chk = $this->pdo->prepare("SELECT id FROM usuarios WHERE sala_id = ? AND nombre = ?");
        $chk->execute([$salaId, $nombre]);
        if ($chk->fetch()) return false;
        // Evitar duplicados globales: nombre ya usado por otro usuario con distinta contraseña
        $chkGlobal = $this->pdo->prepare("SELECT contrasena FROM usuarios WHERE nombre = ? LIMIT 1");
        $chkGlobal->execute([$nombre]);
        $existing = $chkGlobal->fetch();
        if ($existing) {
            // Si ya existe el nombre globalmente, solo permitir si la contraseña coincide (mismo usuario)
            if (!password_verify($contrasena, $existing['contrasena'])) {
                return false; // Nombre ya tomado por otro usuario
            }
            // Si coincide, es el mismo usuario en otra sala, permitir (reusar hash existente)
            $hash = $existing['contrasena'];
            try {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO usuarios (sala_id, nombre, contrasena) VALUES (?, ?, ?)"
                );
                return $stmt->execute([$salaId, $nombre, $hash]);
            } catch (\PDOException $e) {
                return false;
            }
        }
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO usuarios (sala_id, nombre, contrasena) VALUES (?, ?, ?)"
            );
            return $stmt->execute([$salaId, $nombre, $hash]);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function existeNombreGlobal(string $nombre): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE nombre = ? LIMIT 1");
        $stmt->execute([$nombre]);
        return (bool)$stmt->fetch();
    }

    public function verificar(int $salaId, string $nombre, string $contrasena): ?int
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, contrasena FROM usuarios WHERE sala_id = ? AND nombre = ?"
        );
        $stmt->execute([$salaId, $nombre]);
        $row = $stmt->fetch();
        if ($row && password_verify($contrasena, $row['contrasena'])) {
            return (int) $row['id'];
        }
        return null;
    }

    public function verificarGlobal(string $nombre, string $contrasena): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, sala_id, contrasena FROM usuarios WHERE nombre = ? ORDER BY id DESC");
        $stmt->execute([$nombre]);
        while ($row = $stmt->fetch()) {
            if (password_verify($contrasena, $row['contrasena'])) {
                return ['id' => (int)$row['id'], 'sala_id' => (int)$row['sala_id']];
            }
        }
        return null;
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listarPorSala(int $salaId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, nombre FROM usuarios WHERE sala_id = ? ORDER BY nombre"
        );
        $stmt->execute([$salaId]);
        return $stmt->fetchAll();
    }
}
