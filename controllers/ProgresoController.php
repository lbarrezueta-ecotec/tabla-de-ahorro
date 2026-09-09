<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/Sala.php';
require_once __DIR__ . '/../models/Progreso.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? '';

try {
    $salaModel = new Sala($pdo);
    $progresoModel = new Progreso($pdo);

    switch ($action) {
        case 'obtener':
            $salaId = (int) ($input['sala_id'] ?? 0);
            $usuarioId = (int) ($input['usuario_id'] ?? 0);
            $sala = $salaModel->obtenerPorId($salaId);
            $progreso = $progresoModel->obtenerPorUsuario($salaId, $usuarioId);
            echo json_encode([
                'sala' => $sala,
                'progreso' => $progreso,
                'todos' => $progresoModel->listarPorSala($salaId)
            ]);
            break;

        case 'guardar':
            $salaId = (int) ($input['sala_id'] ?? 0);
            $usuarioId = (int) ($input['usuario_id'] ?? 0);
            $valor = (float) ($input['valor'] ?? 0);
            $casillas = (int) ($input['casillas'] ?? 0);
            // Nuevos campos para persistencia correcta de celdas marcadas
            $indices = null;
            if (isset($input['indices'])) {
                $indices = is_array($input['indices']) ? implode(',', $input['indices']) : (string)$input['indices'];
            } elseif (isset($input['casillas_indices'])) {
                $indices = (string)$input['casillas_indices'];
            }
            $valores = null;
            if (isset($input['valores'])) {
                $valores = is_array($input['valores']) ? json_encode($input['valores']) : (string)$input['valores'];
            } elseif (isset($input['values'])) {
                $valores = is_array($input['values']) ? json_encode($input['values']) : (string)$input['values'];
            }
            $ok = $progresoModel->registrar($salaId, $usuarioId, $valor, $casillas, $indices, $valores);
            echo json_encode(['ok' => $ok]);
            break;

        case 'actualizar_meta':
            $salaId = (int) ($input['sala_id'] ?? 0);
            $meta = (float) ($input['meta'] ?? 1500);
            $ok = $salaModel->actualizarMeta($salaId, $meta);
            // Ajustar valores de la tabla para que la suma sea la nueva meta, preservando las casillas ya marcadas
            if ($ok) {
                $sala = $salaModel->obtenerPorId($salaId);
                if ($sala && !empty($sala['valores'])) {
                    $valores = json_decode($sala['valores'], true);
                    if (is_array($valores)) {
                        // Obtener todos los índices marcados en esta sala (unión de todos los usuarios)
                        $stmtIdx = $pdo->prepare("SELECT casillas_indices FROM progreso WHERE sala_id = ? AND casillas_indices IS NOT NULL AND casillas_indices != ''");
                        $stmtIdx->execute([$salaId]);
                        $marcados = [];
                        while ($row = $stmtIdx->fetch()) {
                            $parts = explode(',', $row['casillas_indices']);
                            foreach ($parts as $p) {
                                $p = trim($p);
                                if ($p !== '' && is_numeric($p)) $marcados[(int)$p] = true;
                            }
                        }
                        $sumMarcados = 0;
                        foreach (array_keys($marcados) as $idx) {
                            if (isset($valores[$idx])) $sumMarcados += (int)$valores[$idx];
                        }
                        $totalActual = array_sum($valores);
                        // Si ya hay marcados, ajustar solo las no marcadas
                        if (!empty($marcados)) {
                            $numNoMarcadas = count($valores) - count($marcados);
                            $targetNoMarcadas = (int)$meta - $sumMarcados;
                            if ($numNoMarcadas > 0) {
                                if ($targetNoMarcadas < $numNoMarcadas * 5) $targetNoMarcadas = $numNoMarcadas * 5;
                                if ($targetNoMarcadas > $numNoMarcadas * 150) $targetNoMarcadas = $numNoMarcadas * 150;
                                $noMarcadasIdx = [];
                                foreach (array_keys($valores) as $i) if (!isset($marcados[$i])) $noMarcadasIdx[] = $i;
                                // Distribuir de forma proporcional manteniendo variedad
                                $baseVals = [];
                                foreach ($noMarcadasIdx as $idx) $baseVals[$idx] = $valores[$idx];
                                $baseSum = array_sum($baseVals);
                                if ($baseSum === 0) $baseSum = 1;
                                $newVals = [];
                                $rem = $targetNoMarcadas;
                                $count = count($noMarcadasIdx);
                                foreach ($noMarcadasIdx as $k => $idx) {
                                    if ($k === $count - 1) {
                                        $v = $rem;
                                    } else {
                                        $v = (int)(round(($valores[$idx] / $baseSum) * $targetNoMarcadas / 5) * 5);
                                        $v = max(5, min(150, $v));
                                        if ($v % 10 === 0 && $v < 100) $v += 5;
                                    }
                                    $v = max(5, min(150, $v));
                                    $newVals[$idx] = $v;
                                    $rem -= $v;
                                }
                                if ($rem !== 0) {
                                    $last = end($noMarcadasIdx);
                                    $newVals[$last] = max(5, $newVals[$last] + $rem);
                                    if ($newVals[$last] < 5) $newVals[$last] = 5;
                                    if ($newVals[$last] > 150) $newVals[$last] = 150;
                                    $newSum = array_sum($newVals);
                                    $diff = $targetNoMarcadas - $newSum;
                                    $attempts = 0;
                                    while ($diff !== 0 && $attempts < 100) {
                                        foreach ($noMarcadasIdx as $idx) {
                                            if ($diff === 0) break;
                                            if ($diff > 0 && $newVals[$idx] < 150) { $newVals[$idx] += 5; $diff -= 5; }
                                            elseif ($diff < 0 && $newVals[$idx] > 5) { $newVals[$idx] -= 5; $diff += 5; }
                                        }
                                        $attempts++;
                                    }
                                }
                                foreach ($noMarcadasIdx as $idx) $valores[$idx] = $newVals[$idx];
                            } else {
                                // Todas marcadas y meta aumentó: añadir nuevas casillas para llegar a la meta
                                $faltante = (int)$meta - $totalActual;
                                if ($faltante > 0) {
                                    $nuevas = [];
                                    $numNuevas = (int)ceil($faltante / 30); // promedio 30 por casilla
                                    if ($numNuevas < 1) $numNuevas = 1;
                                    if ($numNuevas > 20) $numNuevas = 20;
                                    $rem = $faltante;
                                    for ($i=0; $i<$numNuevas; $i++) {
                                        $v = ($i === $numNuevas-1) ? $rem : max(5, min(100, (int)(round(($rem/($numNuevas-$i))/5)*5)));
                                        if ($v < 5) $v = 5;
                                        if ($v > 100) $v = 100;
                                        $nuevas[] = $v;
                                        $rem -= $v;
                                    }
                                    if ($rem !== 0) $nuevas[count($nuevas)-1] += $rem;
                                    foreach ($nuevas as $v) $valores[] = $v;
                                } elseif ($faltante < 0) {
                                    // Meta disminuyó y todo está marcado: no se puede reducir sin desmarcar, dejar como excedido
                                    // No hacer nada, quedará excedido (mostrará recuadro)
                                }
                            }
                        } else {
                            // No hay marcadas, regenerar todo para la nueva meta
                            $ref = new ReflectionClass($salaModel);
                            $method = $ref->getMethod('generarValores');
                            $method->setAccessible(true);
                            $valores = $method->invoke($salaModel, $meta);
                        }
                        $stmtUpd = $pdo->prepare("UPDATE salas SET valores = ? WHERE id = ?");
                        $stmtUpd->execute([json_encode($valores), $salaId]);
                    }
                }
            }
            echo json_encode(['ok' => $ok]);
            break;

        case 'establecer_modo':
            $salaId = (int) ($input['sala_id'] ?? 0);
            $modo = $input['modo'] ?? 'individual';
            $ok = $salaModel->establecerModo($salaId, $modo);
            echo json_encode(['ok' => $ok]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no reconocida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
