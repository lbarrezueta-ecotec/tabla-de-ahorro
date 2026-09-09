<?php
class Sala
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function crear(string $codigo, string $urlUnica, ?float $meta = null, ?string $modo = null, ?int $adminId = null, int $numPersonas = 1, string $tipoSuma = 'conjunta', ?array $valores = null, ?string $nombre = null): bool
    {
        // Generar valores si no se proveen, usando la meta para que la suma sea correcta
        if ($valores === null) {
            $valores = $this->generarValores($meta);
        }
        $valoresJson = json_encode($valores);
        $nombre = trim($nombre ?? '');
        if ($nombre === '') $nombre = 'Sala ' . $codigo;
        if (strlen($nombre) > 100) $nombre = substr($nombre, 0, 100);
        if ($meta !== null || $modo !== null || $adminId !== null || $valores !== null || $nombre !== null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO salas (codigo, nombre, url_unica, meta, modo, admin_usuario_id, num_personas, tipo_suma, valores) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            return $stmt->execute([$codigo, $nombre, $urlUnica, $meta ?? 1500, $modo ?? 'individual', $adminId, $numPersonas, $tipoSuma, $valoresJson]);
        }
        $stmt = $this->pdo->prepare(
            "INSERT INTO salas (codigo, nombre, url_unica, valores) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$codigo, $nombre, $urlUnica, $valoresJson]);
    }

    private function generarValores(?float $meta = null): array
    {
        $meta = $meta ?? 1500;
        $values = [5,10,15,20,25,30,35,40,45,50];
        $result = [];
        foreach ($values as $v) for ($i=0; $i<5; $i++) $result[] = $v;
        foreach ([25,25,25,25,25] as $v) $result[] = $v;
        // Ajustar para que la suma sea exactamente la meta, con variedad y sin límite 50 para metas grandes
        $sum = array_sum($result); // 1500
        if ($sum !== (int)$meta) {
            $scale = $meta / $sum;
            // Para metas grandes, permitir valores más altos (hasta 200), para pequeñas mantener 5-50
            $maxVal = $meta > 2000 ? 100 : ($meta > 3000 ? 150 : 50);
            if ($meta > 2500) $maxVal = 100;
            if ($meta > 4000) $maxVal = 150;
            // Usar variedad: no solo escalar, sino generar con distribución proporcional
            $newResult = [];
            $newSum = 0;
            foreach ($result as $v) {
                $nv = (int)(round($v * $scale / 5) * 5);
                if ($nv < 5) $nv = 5;
                if ($nv > $maxVal) $nv = $maxVal;
                // Asegurar variedad: si todos quedan en 50 para 2990, distribuir
                $newResult[] = $nv;
                $newSum += $nv;
            }
            $diff = (int)$meta - $newSum;
            // Ajustar para que la suma sea exacta, con variedad
            $idx = 0;
            while ($diff !== 0 && $idx < 200) {
                for ($i=0; $i<count($newResult) && $diff !==0; $i++) {
                    if ($diff > 0) {
                        if ($newResult[$i] < $maxVal) { $newResult[$i] += 5; $diff -=5; }
                    } else {
                        if ($newResult[$i] > 5) { $newResult[$i] -=5; $diff +=5; }
                    }
                }
                $idx++;
                // Si no se puede ajustar por límites, aumentar maxVal
                if ($idx > 50 && $diff !== 0) $maxVal += 10;
            }
            $result = $newResult;
        }
        // Shuffle
        for ($i=count($result)-1; $i>0; $i--) {
            $j = random_int(0, $i);
            [$result[$i], $result[$j]] = [$result[$j], $result[$i]];
        }
        return $result;
    }

    public function obtenerValores(int $salaId): ?array
    {
        $sala = $this->obtenerPorId($salaId);
        if (!$sala || empty($sala['valores'])) return null;
        $decoded = json_decode($sala['valores'], true);
        return is_array($decoded) ? $decoded : null;
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

    public function actualizarConfig(int $id, ?float $meta = null, ?string $modo = null, ?int $numPersonas = null, ?string $tipoSuma = null): bool
    {
        $fields = [];
        $params = [];
        if ($meta !== null) { $fields[] = "meta = ?"; $params[] = $meta; }
        if ($modo !== null) { $fields[] = "modo = ?"; $params[] = $modo; }
        if ($numPersonas !== null) { $fields[] = "num_personas = ?"; $params[] = $numPersonas; }
        if ($tipoSuma !== null) { $fields[] = "tipo_suma = ?"; $params[] = $tipoSuma; }
        if (!$fields) return false;
        $params[] = $id;
        $stmt = $this->pdo->prepare("UPDATE salas SET " . implode(", ", $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    public function establecerAdmin(int $salaId, int $usuarioId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE salas SET admin_usuario_id = ? WHERE id = ?");
        return $stmt->execute([$usuarioId, $salaId]);
    }

    public function esAdmin(int $salaId, int $usuarioId): bool
    {
        $sala = $this->obtenerPorId($salaId);
        return $sala && (int)($sala['admin_usuario_id'] ?? 0) === $usuarioId;
    }
}
