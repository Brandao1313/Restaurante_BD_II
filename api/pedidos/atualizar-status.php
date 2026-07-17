<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/log.php';

header('Content-Type: application/json');
exigirPerfil(['cozinheiro', 'garcom', 'administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

// Transições permitidas: chave = status atual, valor = próximos status possíveis.
$transicoesPermitidas = [
    'Aberto' => ['Em Preparo'],
    'Em Preparo' => ['Pronto'],
    'Pronto' => ['Finalizado'],
];

$dados = json_decode(file_get_contents('php://input'), true) ?? [];
$idPedido = (int) ($dados['id_pedido'] ?? 0);
$novoStatus = $dados['novo_status'] ?? '';

if ($idPedido <= 0 || $novoStatus === '') {
    http_response_code(400);
    die(json_encode(['erro' => 'Informe o pedido e o novo status.']));
}

$pdo = getConexao();

$stmt = $pdo->prepare('SELECT status FROM Pedidos WHERE id_pedido = ?');
$stmt->execute([$idPedido]);
$pedido = $stmt->fetch();

if (!$pedido) {
    http_response_code(404);
    die(json_encode(['erro' => 'Pedido não encontrado.']));
}

$statusAtual = $pedido['status'];
$proximosValidos = $transicoesPermitidas[$statusAtual] ?? [];

if (!in_array($novoStatus, $proximosValidos, true)) {
    http_response_code(409);
    die(json_encode(['erro' => "Não é possível mudar de \"$statusAtual\" para \"$novoStatus\"."]));
}

$pdo->prepare('UPDATE Pedidos SET status = ? WHERE id_pedido = ?')->execute([$novoStatus, $idPedido]);

registrarLog(
    $pdo,
    $_SESSION['id_funcionario'] ?? null,
    null,
    'atualizar_status_pedido',
    "Pedido #$idPedido: \"$statusAtual\" -> \"$novoStatus\""
);

echo json_encode(['sucesso' => true, 'id_pedido' => $idPedido, 'status' => $novoStatus]);
