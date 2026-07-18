<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/funcoes_estoque.php';
require_once __DIR__ . '/../../includes/log.php';

header('Content-Type: application/json');
exigirPerfil(['garcom', 'administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];
$idItem = (int) ($dados['id_item'] ?? 0);
$quantidadeCancelar = isset($dados['quantidade_cancelar']) ? (int) $dados['quantidade_cancelar'] : 1;

if ($idItem <= 0 || $quantidadeCancelar <= 0) {
    http_response_code(400);
    die(json_encode(['erro' => 'Informe o item e uma quantidade positiva a cancelar.']));
}

$pdo = getConexao();

$stmt = $pdo->prepare(
    "SELECT ip.id_item, ip.id_pedido, ip.id_produto, ip.quantidade, ip.status AS status_item,
            p.nome AS nome_produto, ped.status AS status_pedido, ped.id_mesa
     FROM ItensPedido ip
     JOIN Pedidos ped ON ped.id_pedido = ip.id_pedido
     JOIN Produtos p ON p.id_produto = ip.id_produto
     WHERE ip.id_item = ?"
);
$stmt->execute([$idItem]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    die(json_encode(['erro' => 'Item não encontrado.']));
}
if (in_array($item['status_pedido'], ['Finalizado', 'Cancelado'], true)) {
    http_response_code(409);
    die(json_encode(['erro' => "Pedido \"{$item['status_pedido']}\" é definitivo, não é possível alterar itens."]));
}
if (!in_array($item['status_item'], ['Pendente', 'Recebido'], true)) {
    http_response_code(409);
    die(json_encode(['erro' => 'Item já está em preparo ou cancelado, não é possível cancelar.']));
}
if ($quantidadeCancelar > (int) $item['quantidade']) {
    http_response_code(400);
    die(json_encode(['erro' => 'Quantidade a cancelar maior que a quantidade pedida.']));
}

$novaQuantidade = (int) $item['quantidade'] - $quantidadeCancelar;

try {
    $pdo->beginTransaction();

    estornarEstoqueItem($pdo, (int) $item['id_produto'], $quantidadeCancelar);

    if ($novaQuantidade <= 0) {
        $pdo->prepare("UPDATE ItensPedido SET quantidade = 0, status = 'Cancelado' WHERE id_item = ?")
            ->execute([$idItem]);
    } else {
        $pdo->prepare('UPDATE ItensPedido SET quantidade = ? WHERE id_item = ?')
            ->execute([$novaQuantidade, $idItem]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    die(json_encode(['erro' => 'Erro ao cancelar o item.']));
}

registrarLog(
    $pdo,
    $_SESSION['id_funcionario'] ?? null,
    null,
    'cancelar_item_pedido',
    "Item #$idItem (\"{$item['nome_produto']}\") do pedido #{$item['id_pedido']} (mesa #{$item['id_mesa']}): -$quantidadeCancelar"
);

echo json_encode([
    'sucesso' => true,
    'id_item' => $idItem,
    'quantidade' => $novaQuantidade,
    'status' => $novaQuantidade <= 0 ? 'Cancelado' : $item['status_item'],
]);
