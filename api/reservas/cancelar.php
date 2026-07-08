<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
exigirPerfil(['cliente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];
$idReserva = (int) ($dados['id_reserva'] ?? 0);

if ($idReserva <= 0) {
    http_response_code(400);
    die(json_encode(['erro' => 'Informe a reserva a cancelar.']));
}

$pdo = getConexao();

$stmt = $pdo->prepare('SELECT id_cliente, data_reserva, hora_reserva, status FROM Reservas WHERE id_reserva = ?');
$stmt->execute([$idReserva]);
$reserva = $stmt->fetch();

if (!$reserva) {
    http_response_code(404);
    die(json_encode(['erro' => 'Reserva não encontrada.']));
}
if ((int) $reserva['id_cliente'] !== (int) $_SESSION['id_cliente']) {
    http_response_code(403);
    die(json_encode(['erro' => 'Você não pode cancelar uma reserva que não é sua.']));
}
if ($reserva['status'] !== 'Confirmada') {
    http_response_code(409);
    die(json_encode(['erro' => 'Esta reserva já foi cancelada ou não está mais ativa.']));
}

$horasRestantes = (strtotime($reserva['data_reserva'] . ' ' . $reserva['hora_reserva']) - time()) / 3600;
if ($horasRestantes < RESERVA_CANCELAMENTO_GRATUITO_HORAS) {
    http_response_code(409);
    die(json_encode(['erro' => 'Cancelamento gratuito só é permitido até ' . RESERVA_CANCELAMENTO_GRATUITO_HORAS . ' hora(s) antes da reserva. Entre em contato com o restaurante.']));
}

$pdo->prepare("UPDATE Reservas SET status = 'Cancelada' WHERE id_reserva = ?")->execute([$idReserva]);

echo json_encode(['sucesso' => true, 'mensagem' => 'Reserva cancelada com sucesso.']);
