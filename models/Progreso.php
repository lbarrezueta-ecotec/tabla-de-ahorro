<?php
class Progreso
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function registrar(int $salaId, int $usuarioId, float $valor, int $casillas, ?string $indices = null, ?string $valores = null): bool
    {
        // Verifica si ya existe
        $check = $this->pdo->prepare("SELECT id FROM progreso WHERE sala_id = ? AND usuario_id = ?");
        $check->execute([$salaId, $usuarioId]);
        if ($check->fetch()) {
            $stmt = $this->pdo->prepare(
                "UPDATE progreso SET valor_ahorrado = ?, casillas_marcadas = ?, casillas_indices = ?, valores = ? WHERE sala_id = ? AND usuario_id = ?"
            );
            return $stmt->execute([$valor, $casillas, $indices, $valores, $salaId, $usuarioId]);
        }
        $stmt = $this->pdo->prepare(
            "INSERT INTO progreso (sala_id, usuario_id, valor_ahorrado, casillas_marcadas, casillas_indices, valores) VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$salaId, $usuarioId, $valor, $casillas, $indices, $valores]);
    }

    public function obtenerPorUsuario(int $salaId, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM progreso WHERE sala_id = ? AND usuario_id = ?"
        );
        $stmt->execute([$salaId, $usuarioId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listarPorSala(int $salaId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id AS usuario_id, u.nombre, p.valor_ahorrado, p.casillas_marcadas
             FROM usuarios u
             LEFT JOIN progreso p ON p.usuario_id = u.id
             WHERE u.sala_id = ?
             ORDER BY p.valor_ahorrado DESC"
        );
        $stmt->execute([$salaId]);
        return $stmt->fetchAll();
    }
}
