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

$idMesa = (int) ($dados['id_mesa'] ?? 0);
$observacao = trim($dados['observacao'] ?? '');
$itens = $dados['itens'] ?? [];

if ($idMesa <= 0 || !is_array($itens) || count($itens) === 0) {
    http_response_code(400);
    die(json_encode(['erro' => 'Selecione uma mesa e ao menos um item.']));
}

$pdo = getConexao();

$stmt = $pdo->prepare('SELECT status FROM Mesas WHERE id_mesa = ?');
$stmt->execute([$idMesa]);
$mesa = $stmt->fetch();

if (!$mesa) {
    http_response_code(404);
    die(json_encode(['erro' => 'Mesa não encontrada.']));
}
if ($mesa['status'] === 'Reservada') {
    http_response_code(409);
    die(json_encode(['erro' => 'Mesa reservada, selecione outra mesa.']));
}

try {
    $pdo->beginTransaction();

    $pdo->prepare(
        "INSERT INTO Pedidos (id_cliente, id_funcionario, id_mesa, status, data_criacao, observacao)
         VALUES (?, NULL, ?, 'Aberto', NOW(), ?)"
    )->execute([$_SESSION['id_cliente'], $idMesa, $observacao]);
    $idPedido = (int) $pdo->lastInsertId();

    foreach ($itens as $item) {
        $idProduto = (int) ($item['id_produto'] ?? 0);
        $quantidade = (int) ($item['quantidade'] ?? 0);
        $observacaoItem = trim($item['observacao'] ?? '');

        if ($idProduto <= 0 || $quantidade <= 0) {
            throw new RuntimeException('Item de pedido inválido.');
        }

        $stmt = $pdo->prepare('SELECT nome, preco, disponivel FROM Produtos WHERE id_produto = ? FOR UPDATE');
        $stmt->execute([$idProduto]);
        $produto = $stmt->fetch();

        if (!$produto || !$produto['disponivel']) {
            throw new RuntimeException('Produto indisponível: ' . ($produto['nome'] ?? "#$idProduto") . '.');
        }

        $pdo->prepare(
            "INSERT INTO ItensPedido (id_pedido, id_produto, quantidade, preco_unitario, observacao, status)
             VALUES (?, ?, ?, ?, ?, 'Pendente')"
        )->execute([$idPedido, $idProduto, $quantidade, $produto['preco'], $observacaoItem]);

        baixarEstoqueItem($pdo, $idProduto, $quantidade);
    }

    $pdo->prepare("UPDATE Mesas SET status = 'Ocupada' WHERE id_mesa = ? AND status = 'Livre'")->execute([$idMesa]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(409);
    die(json_encode(['erro' => $e->getMessage()]));
}

http_response_code(201);
echo json_encode(['sucesso' => true, 'id_pedido' => $idPedido]);
