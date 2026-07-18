<?php
require_once __DIR__ . '/helpers.php';

/**
 * Regras de ciclo de vida da mesa: uma mesa Ocupada só volta a ficar Livre
 * automaticamente quando não resta nenhum pedido ativo (Aberto/Em Preparo/
 * Pronto) vinculado a ela — ou seja, após pagamento confirmado ou
 * cancelamento de todos os pedidos em aberto. Deve ser chamada dentro de
 * uma transação PDO já aberta pelo chamador.
 *
 * Também inclui a busca da reserva ativa do cliente (usada para vincular
 * automaticamente os pedidos dele à mesa reservada).
 */

function mesaTemPedidoAtivo(PDO $pdo, int $idMesa): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM Pedidos WHERE id_mesa = ? AND status IN ('Aberto', 'Em Preparo', 'Pronto')"
    );
    $stmt->execute([$idMesa]);

    return (int) $stmt->fetchColumn() > 0;
}

function liberarMesaSeSemPedidosAtivos(PDO $pdo, ?int $idMesa): void
{
    if ($idMesa === null || mesaTemPedidoAtivo($pdo, $idMesa)) {
        return;
    }

    $pdo->prepare("UPDATE Mesas SET status = 'Livre' WHERE id_mesa = ? AND status = 'Ocupada'")->execute([$idMesa]);
}

/**
 * Localiza a reserva confirmada do cliente para o turno atual (hoje), se
 * houver. Usada para vincular automaticamente os pedidos do cliente à mesa
 * que ele reservou assim que ele chega no horário/turno da reserva.
 */
function buscarReservaAtivaHoje(PDO $pdo, int $idCliente): ?array
{
    $turnoAtual = turnoDoHorario(date('H:i:s'));
    if ($turnoAtual === null) {
        return null;
    }

    $inicioTurno = $turnoAtual === 'Almoço' ? TURNO_ALMOCO_INICIO : TURNO_JANTAR_INICIO;
    $fimTurno = $turnoAtual === 'Almoço' ? TURNO_ALMOCO_FIM : TURNO_JANTAR_FIM;

    $stmt = $pdo->prepare(
        "SELECT r.id_mesa, m.numero FROM Reservas r
         JOIN Mesas m ON m.id_mesa = r.id_mesa
         WHERE r.id_cliente = ? AND r.data_reserva = CURDATE() AND r.status = 'Confirmada'
           AND r.hora_reserva BETWEEN ? AND ?
         ORDER BY r.hora_reserva ASC LIMIT 1"
    );
    $stmt->execute([$idCliente, $inicioTurno, $fimTurno]);
    $linha = $stmt->fetch();

    return $linha ?: null;
}
