<?php
require_once __DIR__ . '/../../config/session.php';
exigirPerfilPagina(['cozinheiro', 'administrador']);

$tituloPagina = 'Pedidos da Cozinha - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('pedidos') ?> Pedidos da Cozinha</h1>
<div id="mensagem-cozinha"></div>

<div class="grid quadro-cozinha" id="quadro-pedidos">
    <div>
        <h3>Pendente</h3>
        <div id="coluna-aberto"></div>
    </div>
    <div>
        <h3>Em Preparo</h3>
        <div id="coluna-em-preparo"></div>
    </div>
    <div>
        <h3>Pronto</h3>
        <div id="coluna-pronto"></div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/cozinha.js?v=<?= filemtime(__DIR__ . '/../../assets/js/cozinha.js') ?>"></script>
<script>iniciarPedidosCozinha('<?= BASE_URL ?>');</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
