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

$sql = "SELECT ped.id_pedido, ped.status, ped.data_criacao, ped.observacao, m.numero AS numero_mesa
        FROM Pedidos ped
        LEFT JOIN Mesas m ON m.id_mesa = ped.id_mesa
        WHERE ped.id_cliente = ?";
$parametros = [$_SESSION['id_cliente']];

if (!empty($_GET['status'])) {
    $sql .= ' AND ped.status = ?';
    $parametros[] = $_GET['status'];
}
$sql .= ' ORDER BY ped.data_criacao DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$pedidos = $stmt->fetchAll();

if ($pedidos) {
    $ids = array_column($pedidos, 'id_pedido');
    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $stmtItens = $pdo->prepare(
        "SELECT ip.id_item, ip.id_pedido, ip.id_produto, p.nome AS nome_produto, ip.quantidade, ip.preco_unitario, ip.observacao, ip.status
         FROM ItensPedido ip
         JOIN Produtos p ON p.id_produto = ip.id_produto
         WHERE ip.id_pedido IN ($marcadores)"
    );
    $stmtItens->execute($ids);
    $itensPorPedido = [];
    foreach ($stmtItens->fetchAll() as $item) {
        $itensPorPedido[$item['id_pedido']][] = $item;
    }

    foreach ($pedidos as &$pedido) {
        $pedido['itens'] = $itensPorPedido[$pedido['id_pedido']] ?? [];
        $pedido['total'] = array_reduce(
            $pedido['itens'],
            fn ($soma, $item) => $soma + $item['quantidade'] * $item['preco_unitario'],
            0
        );
    }
}

echo json_encode(['sucesso' => true, 'pedidos' => $pedidos]);
