<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/funcoes_estoque.php';

header('Content-Type: application/json');
exigirPerfil(['cliente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];
$idPedido = (int) ($dados['id_pedido'] ?? 0);

if ($idPedido <= 0) {
    http_response_code(400);
    die(json_encode(['erro' => 'Informe o pedido a cancelar.']));
}

$pdo = getConexao();

$stmt = $pdo->prepare('SELECT id_cliente FROM Pedidos WHERE id_pedido = ?');
$stmt->execute([$idPedido]);
$pedido = $stmt->fetch();

if (!$pedido) {
    http_response_code(404);
    die(json_encode(['erro' => 'Pedido não encontrado.']));
}
if ((int) $pedido['id_cliente'] !== (int) $_SESSION['id_cliente']) {
    http_response_code(403);
    die(json_encode(['erro' => 'Você não pode cancelar um pedido que não é seu.']));
}

$stmt = $pdo->prepare("SELECT id_item, id_produto, quantidade, status FROM ItensPedido WHERE id_pedido = ?");
$stmt->execute([$idPedido]);
$itens = $stmt->fetchAll();

$naoCancelaveis = array_filter($itens, fn ($item) => !in_array($item['status'], ['Pendente', 'Recebido'], true));
if ($naoCancelaveis) {
    http_response_code(409);
    die(json_encode(['erro' => 'Não é possível cancelar: um ou mais itens já entraram em preparo.']));
}

try {
    $pdo->beginTransaction();

    foreach ($itens as $item) {
        estornarEstoqueItem($pdo, (int) $item['id_produto'], (int) $item['quantidade']);
    }

    $pdo->prepare("UPDATE ItensPedido SET status = 'Cancelado' WHERE id_pedido = ?")->execute([$idPedido]);
    $pdo->prepare("UPDATE Pedidos SET status = 'Cancelado' WHERE id_pedido = ?")->execute([$idPedido]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    die(json_encode(['erro' => 'Erro ao cancelar o pedido.']));
}

echo json_encode(['sucesso' => true, 'mensagem' => 'Pedido cancelado com sucesso.']);
