<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
exigirPerfil(['cliente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];

$data = $dados['data_reserva'] ?? '';
$horario = $dados['hora_reserva'] ?? '';
$pessoas = (int) ($dados['quantidade_pessoas'] ?? 0);
$idMesa = (int) ($dados['id_mesa'] ?? 0);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $horario) || $pessoas < 1 || $idMesa <= 0) {
    http_response_code(400);
    die(json_encode(['erro' => 'Dados de reserva inválidos.']));
}

$horarioCompleto = strlen($horario) === 5 ? $horario . ':00' : $horario;
$turno = turnoDoHorario($horarioCompleto);

if ($turno === null) {
    http_response_code(400);
    die(json_encode(['erro' => 'Horário fora dos turnos de funcionamento.']));
}

$inicioTurno = $turno === 'Almoço' ? TURNO_ALMOCO_INICIO : TURNO_JANTAR_INICIO;
$fimTurno = $turno === 'Almoço' ? TURNO_ALMOCO_FIM : TURNO_JANTAR_FIM;

$dataHoraTurno = new DateTime("$data $inicioTurno");
$horasAntecedencia = ($dataHoraTurno->getTimestamp() - time()) / 3600;

if ($horasAntecedencia < RESERVA_ANTECEDENCIA_MINIMA_HORAS) {
    http_response_code(400);
    die(json_encode(['erro' => 'Reservas exigem no mínimo ' . RESERVA_ANTECEDENCIA_MINIMA_HORAS . ' horas de antecedência do início do turno.']));
}

function gerarCodigoConfirmacao(string $data): string
{
    return 'RES-' . str_replace('-', '', $data) . '-' . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
}

$pdo = getConexao();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT capacidade FROM Mesas WHERE id_mesa = ? FOR UPDATE');
    $stmt->execute([$idMesa]);
    $mesa = $stmt->fetch();

    if (!$mesa) {
        throw new RuntimeException('Mesa não encontrada.');
    }
    if ($mesa['capacidade'] < $pessoas) {
        throw new RuntimeException('Capacidade da mesa insuficiente para essa quantidade de pessoas.');
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM Reservas WHERE id_mesa = ? AND data_reserva = ? AND status = 'Confirmada'
         AND hora_reserva BETWEEN ? AND ?"
    );
    $stmt->execute([$idMesa, $data, $inicioTurno, $fimTurno]);
    if ((int) $stmt->fetchColumn() > 0) {
        throw new RuntimeException('Esta mesa já está reservada para o turno selecionado.');
    }

    $codigo = null;
    for ($tentativa = 0; $tentativa < 5; $tentativa++) {
        $candidato = gerarCodigoConfirmacao($data);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM Reservas WHERE codigo_confirmacao = ?');
        $stmt->execute([$candidato]);
        if ((int) $stmt->fetchColumn() === 0) {
            $codigo = $candidato;
            break;
        }
    }
    if ($codigo === null) {
        throw new RuntimeException('Não foi possível gerar um código de confirmação. Tente novamente.');
    }

    $pdo->prepare(
        "INSERT INTO Reservas (id_cliente, id_mesa, data_reserva, hora_reserva, quantidade_pessoas, status, codigo_confirmacao, data_criacao)
         VALUES (?, ?, ?, ?, ?, 'Confirmada', ?, NOW())"
    )->execute([$_SESSION['id_cliente'], $idMesa, $data, $horarioCompleto, $pessoas, $codigo]);
    $idReserva = (int) $pdo->lastInsertId();

    $pdo->commit();
} catch (RuntimeException $e) {
    $pdo->rollBack();
    http_response_code(409);
    die(json_encode(['erro' => $e->getMessage()]));
}

http_response_code(201);
echo json_encode([
    'sucesso' => true,
    'reserva' => [
        'id_reserva' => $idReserva,
        'codigo_confirmacao' => $codigo,
        'turno' => $turno,
        'numero_mesa' => null,
    ],
]);
