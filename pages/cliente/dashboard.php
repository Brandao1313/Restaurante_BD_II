<?php
require_once __DIR__ . '/../../config/session.php';
exigirPerfilPagina(['cliente']);

$tituloPagina = 'Painel do Cliente - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Bem-vindo(a), <?= htmlspecialchars($_SESSION['nome']) ?>!</h1>
<div class="grid grid-cards">
    <a class="card" href="<?= BASE_URL ?>/pages/cliente/cardapio.php">
        <h3><?= icone('cardapio') ?> Cardápio</h3>
        <p>Veja os pratos disponíveis e faça seu pedido.</p>
    </a>
    <a class="card" href="<?= BASE_URL ?>/pages/cliente/meus-pedidos.php">
        <h3><?= icone('pedidos') ?> Meus Pedidos</h3>
        <p>Acompanhe o status dos seus pedidos.</p>
    </a>
    <a class="card" href="<?= BASE_URL ?>/pages/cliente/reservas.php">
        <h3><?= icone('reservas') ?> Reservas</h3>
        <p>Reserve uma mesa ou gerencie suas reservas.</p>
    </a>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
