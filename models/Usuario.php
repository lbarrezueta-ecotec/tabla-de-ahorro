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
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            "INSERT INTO usuarios (sala_id, nombre, contrasena) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$salaId, $nombre, $hash]);
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

    public function listarPorSala(int $salaId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, nombre FROM usuarios WHERE sala_id = ? ORDER BY nombre"
        );
        $stmt->execute([$salaId]);
        return $stmt->fetchAll();
    }
}
