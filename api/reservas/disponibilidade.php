<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
exigirPerfil(['cliente']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$data = $_GET['data'] ?? '';
$horario = $_GET['horario'] ?? '';
$pessoas = (int) ($_GET['pessoas'] ?? 0);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    http_response_code(400);
    die(json_encode(['erro' => 'Data inválida.']));
}
if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $horario)) {
    http_response_code(400);
    die(json_encode(['erro' => 'Horário inválido.']));
}
if ($pessoas < 1) {
    http_response_code(400);
    die(json_encode(['erro' => 'Informe a quantidade de pessoas.']));
}

$horarioCompleto = strlen($horario) === 5 ? $horario . ':00' : $horario;
$turno = turnoDoHorario($horarioCompleto);

if ($turno === null) {
    http_response_code(400);
    die(json_encode(['erro' => 'Horário fora dos turnos de funcionamento (Almoço 11h-15h ou Jantar 18h-23h).']));
}

$dataHoraReserva = new DateTime("$data $horarioCompleto");
$agora = new DateTime();
$horasAntecedencia = ($dataHoraReserva->getTimestamp() - $agora->getTimestamp()) / 3600;

if ($horasAntecedencia < RESERVA_ANTECEDENCIA_MINIMA_HORAS) {
    http_response_code(400);
    die(json_encode(['erro' => 'Reservas exigem no mínimo ' . RESERVA_ANTECEDENCIA_MINIMA_HORAS . ' horas de antecedência do horário desejado.']));
}

$inicioTurno = $turno === 'Almoço' ? TURNO_ALMOCO_INICIO : TURNO_JANTAR_INICIO;

$fimTurno = $turno === 'Almoço' ? TURNO_ALMOCO_FIM : TURNO_JANTAR_FIM;

$pdo = getConexao();
$stmt = $pdo->prepare(
    "SELECT m.id_mesa, m.numero, m.capacidade
     FROM Mesas m
     WHERE m.capacidade >= ?
       AND m.id_mesa NOT IN (
           SELECT r.id_mesa FROM Reservas r
           WHERE r.data_reserva = ? AND r.status = 'Confirmada'
             AND r.hora_reserva BETWEEN ? AND ?
       )
     ORDER BY m.capacidade ASC, m.numero ASC"
);
$stmt->execute([$pessoas, $data, $inicioTurno, $fimTurno]);

echo json_encode(['sucesso' => true, 'turno' => $turno, 'mesas' => $stmt->fetchAll()]);
