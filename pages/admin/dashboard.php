<?php
require_once __DIR__ . '/../../config/session.php';
exigirPerfilPagina(['administrador']);

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
<?php require __DIR__ . '/../../includes/footer.php'; ?>
