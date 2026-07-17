<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/funcoes_estoque.php';
require_once __DIR__ . '/../../includes/log.php';

header('Content-Type: application/json');
exigirPerfil(['cozinheiro', 'administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];
$idInsumo = (int) ($dados['id_insumo'] ?? 0);
$quantidadeAdicionar = (float) ($dados['quantidade_adicionar'] ?? 0);

if ($idInsumo <= 0 || $quantidadeAdicionar <= 0) {
    http_response_code(400);
    die(json_encode(['erro' => 'Informe o insumo e uma quantidade positiva a registrar.']));
}

$pdo = getConexao();

$stmt = $pdo->prepare('SELECT nome FROM Insumos WHERE id_insumo = ?');
$stmt->execute([$idInsumo]);
$insumo = $stmt->fetch();

if (!$insumo) {
    http_response_code(404);
    die(json_encode(['erro' => 'Insumo não encontrado.']));
}

$pdo->beginTransaction();
$pdo->prepare('UPDATE Insumos SET quantidade = quantidade + ?, ultima_atualizacao = NOW() WHERE id_insumo = ?')
    ->execute([$quantidadeAdicionar, $idInsumo]);
reavaliarProdutosPorInsumo($pdo, $idInsumo);
$pdo->commit();

registrarLog(
    $pdo,
    $_SESSION['id_funcionario'] ?? null,
    null,
    'registrar_compra_insumo',
    "Insumo \"{$insumo['nome']}\" (#$idInsumo): +$quantidadeAdicionar"
);

$stmt = $pdo->prepare('SELECT quantidade FROM Insumos WHERE id_insumo = ?');
$stmt->execute([$idInsumo]);

echo json_encode(['sucesso' => true, 'nova_quantidade' => (float) $stmt->fetchColumn()]);
