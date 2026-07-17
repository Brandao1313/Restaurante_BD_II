<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
exigirPerfil(['cozinheiro', 'administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$pdo = getConexao();

$sql = "SELECT ped.id_pedido, ped.status, ped.data_criacao, ped.observacao, m.numero AS numero_mesa, c.nome AS nome_cliente
        FROM Pedidos ped
        LEFT JOIN Mesas m ON m.id_mesa = ped.id_mesa
        LEFT JOIN Clientes c ON c.id_cliente = ped.id_cliente
        WHERE ped.status IN ('Aberto', 'Em Preparo', 'Pronto')
        ORDER BY ped.data_criacao ASC";
$pedidos = $pdo->query($sql)->fetchAll();

if ($pedidos) {
    $ids = array_column($pedidos, 'id_pedido');
    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $stmtItens = $pdo->prepare(
        "SELECT ip.id_pedido, p.nome AS nome_produto, ip.quantidade, ip.observacao
         FROM ItensPedido ip
         JOIN Produtos p ON p.id_produto = ip.id_produto
         WHERE ip.id_pedido IN ($marcadores) AND ip.status != 'Cancelado'"
    );
    $stmtItens->execute($ids);
    $itensPorPedido = [];
    foreach ($stmtItens->fetchAll() as $item) {
        $itensPorPedido[$item['id_pedido']][] = $item;
    }

    foreach ($pedidos as &$pedido) {
        $pedido['itens'] = $itensPorPedido[$pedido['id_pedido']] ?? [];
    }
}

echo json_encode(['sucesso' => true, 'pedidos' => $pedidos]);
