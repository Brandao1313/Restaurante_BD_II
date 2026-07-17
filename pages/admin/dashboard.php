<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
exigirPerfilPagina(['administrador']);

$pdo = getConexao();
$rankingPratos = $pdo->query(
    "SELECT p.nome, SUM(ip.quantidade) AS total_qtd, SUM(ip.quantidade * ip.preco_unitario) AS total_valor
     FROM ItensPedido ip
     JOIN Pedidos ped ON ped.id_pedido = ip.id_pedido
     JOIN Produtos p ON p.id_produto = ip.id_produto
     WHERE ped.status = 'Finalizado'
     GROUP BY p.id_produto, p.nome
     ORDER BY total_qtd DESC
     LIMIT 10"
)->fetchAll();

$tituloPagina = 'Painel Administrativo - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Painel Administrativo</h1>
<div class="grid grid-cards">
    <a class="card" href="<?= BASE_URL ?>/pages/admin/funcionarios.php">
        <h3><?= icone('funcionarios') ?> Funcionários</h3>
        <p>Gerencie a equipe do restaurante.</p>
    </a>
    <a class="card" href="<?= BASE_URL ?>/pages/admin/produtos.php">
        <h3><?= icone('cardapio') ?> Produtos</h3>
        <p>Gerencie o cardápio.</p>
    </a>
    <a class="card" href="<?= BASE_URL ?>/pages/admin/insumos.php">
        <h3><?= icone('insumos') ?> Insumos</h3>
        <p>Controle o estoque de ingredientes.</p>
    </a>
    <a class="card" href="<?= BASE_URL ?>/pages/admin/mesas.php">
        <h3><?= icone('mesas') ?> Mesas</h3>
        <p>Gerencie mesas e capacidade.</p>
    </a>
    <a class="card" href="<?= BASE_URL ?>/pages/admin/pedidos.php">
        <h3><?= icone('pedidos') ?> Pedidos</h3>
        <p>Acompanhe e gerencie todos os pedidos.</p>
    </a>
    <a class="card" href="<?= BASE_URL ?>/pages/admin/despesas.php">
        <h3><?= icone('despesas') ?> Despesas</h3>
        <p>Lance e classifique despesas operacionais.</p>
    </a>
    <a class="card" href="<?= BASE_URL ?>/pages/admin/financeiro.php">
        <h3><?= icone('financeiro') ?> Financeiro</h3>
        <p>Faturamento, lucro líquido e filtros.</p>
    </a>
    <a class="card" href="<?= BASE_URL ?>/pages/admin/relatorios.php">
        <h3><?= icone('relatorios') ?> Relatórios</h3>
        <p>Exporte relatórios em PDF/Excel.</p>
    </a>
</div>

<div class="card" style="margin-top:1.5rem; overflow-x:auto">
    <h3><?= icone('ranking') ?> Top 10 Pratos Mais Pedidos</h3>
    <?php if (!$rankingPratos): ?>
        <p style="color:#777">Ainda não há pedidos finalizados para gerar o ranking.</p>
    <?php else: ?>
        <table style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Prato</th>
                    <th>Quantidade vendida</th>
                    <th>Total gerado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rankingPratos as $posicao => $prato): ?>
                <tr style="<?= $posicao < 3 ? 'font-weight:700; color:var(--cor-dourado-escuro)' : '' ?>">
                    <td><?= $posicao < 3 ? icone('ranking', 15) . ' ' : '' ?>#<?= $posicao + 1 ?></td>
                    <td><?= htmlspecialchars($prato['nome']) ?></td>
                    <td><?= (int) $prato['total_qtd'] ?></td>
                    <td><?= formatarMoedaPhp($prato['total_valor']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
