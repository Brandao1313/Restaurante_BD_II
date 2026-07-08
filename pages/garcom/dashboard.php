<?php
require_once __DIR__ . '/../../config/session.php';
exigirPerfilPagina(['garcom', 'administrador']);

$tituloPagina = 'Painel do Garçom - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Olá, <?= htmlspecialchars($_SESSION['nome']) ?>!</h1>
<div class="grid grid-cards">
    <a class="card" href="<?= BASE_URL ?>/pages/garcom/mesas.php">
        <h3><?= icone('mesas') ?> Mapa de Mesas</h3>
        <p>Veja o status das mesas em tempo real.</p>
    </a>
    <a class="card" href="<?= BASE_URL ?>/pages/garcom/comandas.php">
        <h3><?= icone('comandas') ?> Comandas</h3>
        <p>Abra comandas e lance pedidos.</p>
    </a>
    <a class="card" href="<?= BASE_URL ?>/pages/garcom/historico.php">
        <h3><?= icone('historico') ?> Histórico</h3>
        <p>Consulte o histórico de consumo das mesas.</p>
    </a>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
