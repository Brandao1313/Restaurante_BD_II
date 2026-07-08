<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
exigirPerfil(['cliente']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$pdo = getConexao();

$sql = "SELECT r.id_reserva, r.data_reserva, r.hora_reserva, r.quantidade_pessoas, r.status,
               r.codigo_confirmacao, r.data_criacao, r.id_mesa, m.numero AS numero_mesa
        FROM Reservas r
        JOIN Mesas m ON m.id_mesa = r.id_mesa
        WHERE r.id_cliente = ?";
$parametros = [$_SESSION['id_cliente']];

if (!empty($_GET['status'])) {
    $sql .= ' AND r.status = ?';
    $parametros[] = $_GET['status'];
}
$sql .= ' ORDER BY r.data_reserva DESC, r.hora_reserva DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$reservas = $stmt->fetchAll();

$agora = time();
foreach ($reservas as &$reserva) {
    $timestampReserva = strtotime($reserva['data_reserva'] . ' ' . $reserva['hora_reserva']);
    $horasRestantes = ($timestampReserva - $agora) / 3600;
    $reserva['pode_cancelar'] = $reserva['status'] === 'Confirmada' && $horasRestantes >= RESERVA_CANCELAMENTO_GRATUITO_HORAS;
}

echo json_encode(['sucesso' => true, 'reservas' => $reservas]);
