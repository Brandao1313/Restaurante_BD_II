<?php
/**
 * Regras de estoque (RN-3): baixa e estorno de insumos ligados a um produto,
 * e atualização automática de Produtos.disponivel.
 * Deve ser chamado dentro de uma transação PDO já aberta pelo chamador.
 */

function baixarEstoqueItem(PDO $pdo, int $idProduto, int $quantidade): void
{
    $stmt = $pdo->prepare('SELECT id_insumo, quantidade_necessaria FROM ProdutoInsumo WHERE id_produto = ?');
    $stmt->execute([$idProduto]);
    $insumos = $stmt->fetchAll();

    foreach ($insumos as $insumo) {
        $necessario = $insumo['quantidade_necessaria'] * $quantidade;

        $stmtAtual = $pdo->prepare('SELECT quantidade FROM Insumos WHERE id_insumo = ? FOR UPDATE');
        $stmtAtual->execute([$insumo['id_insumo']]);
        $atual = (float) $stmtAtual->fetchColumn();

        if ($atual < $necessario) {
            throw new RuntimeException("Estoque insuficiente para atender o produto solicitado.");
        }

        $pdo->prepare('UPDATE Insumos SET quantidade = quantidade - ? WHERE id_insumo = ?')
            ->execute([$necessario, $insumo['id_insumo']]);

        if (($atual - $necessario) <= 0) {
            marcarProdutosDependentesIndisponiveis($pdo, (int) $insumo['id_insumo']);
        }
    }
}

function estornarEstoqueItem(PDO $pdo, int $idProduto, int $quantidade): void
{
    $stmt = $pdo->prepare('SELECT id_insumo, quantidade_necessaria FROM ProdutoInsumo WHERE id_produto = ?');
    $stmt->execute([$idProduto]);
    $insumos = $stmt->fetchAll();

    foreach ($insumos as $insumo) {
        $necessario = $insumo['quantidade_necessaria'] * $quantidade;

        $pdo->prepare('UPDATE Insumos SET quantidade = quantidade + ? WHERE id_insumo = ?')
            ->execute([$necessario, $insumo['id_insumo']]);
    }

    reavaliarDisponibilidadeProduto($pdo, $idProduto);
}

function marcarProdutosDependentesIndisponiveis(PDO $pdo, int $idInsumo): void
{
    $pdo->prepare(
        'UPDATE Produtos SET disponivel = 0 WHERE id_produto IN
         (SELECT id_produto FROM ProdutoInsumo WHERE id_insumo = ?)'
    )->execute([$idInsumo]);
}

function reavaliarDisponibilidadeProduto(PDO $pdo, int $idProduto): void
{
    $stmt = $pdo->prepare(
        'SELECT MIN(i.quantidade - pi.quantidade_necessaria) AS folga
         FROM ProdutoInsumo pi JOIN Insumos i ON i.id_insumo = pi.id_insumo
         WHERE pi.id_produto = ?'
    );
    $stmt->execute([$idProduto]);
    $folga = $stmt->fetchColumn();

    if ($folga === null || $folga >= 0) {
        $pdo->prepare('UPDATE Produtos SET disponivel = 1 WHERE id_produto = ?')->execute([$idProduto]);
    }
}

/**
 * Reavalia a disponibilidade de todos os produtos que dependem de um insumo
 * (usado após reposição de estoque, ex.: cozinheiro registrando uma compra).
 */
function reavaliarProdutosPorInsumo(PDO $pdo, int $idInsumo): void
{
    $stmt = $pdo->prepare('SELECT DISTINCT id_produto FROM ProdutoInsumo WHERE id_insumo = ?');
    $stmt->execute([$idInsumo]);

    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $idProduto) {
        reavaliarDisponibilidadeProduto($pdo, (int) $idProduto);
    }
}
