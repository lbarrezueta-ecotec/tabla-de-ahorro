<?php
class Sala
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function crear(string $codigo, string $urlUnica): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO salas (codigo, url_unica) VALUES (?, ?)"
        );
        return $stmt->execute([$codigo, $urlUnica]);
    }

    public function obtenerPorCodigo(string $codigo): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM salas WHERE codigo = ?"
        );
        $stmt->execute([$codigo]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM salas WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function actualizarMeta(int $id, float $meta): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE salas SET meta = ? WHERE id = ?"
        );
        return $stmt->execute([$meta, $id]);
    }

    public function establecerModo(int $id, string $modo): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE salas SET modo = ? WHERE id = ?"
        );
        return $stmt->execute([$modo, $id]);
    }
}
