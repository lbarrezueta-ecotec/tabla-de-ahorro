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
            $ok = $progresoModel->registrar($salaId, $usuarioId, $valor, $casillas);
            echo json_encode(['ok' => $ok]);
            break;

        case 'actualizar_meta':
            $salaId = (int) ($input['sala_id'] ?? 0);
            $meta = (float) ($input['meta'] ?? 1500);
            $ok = $salaModel->actualizarMeta($salaId, $meta);
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
