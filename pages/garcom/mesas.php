<?php
require_once __DIR__ . '/../../config/session.php';
exigirPerfilPagina(['garcom', 'administrador']);

$tituloPagina = 'Mapa de Mesas - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('mesas') ?> Mapa de Mesas</h1>
<p style="color:#777">Clique em uma mesa livre ou ocupada para abrir uma comanda.</p>

<div class="grid grid-cards" id="grid-mesas">Carregando...</div>

<script src="<?= BASE_URL ?>/assets/js/mesas-garcom.js?v=<?= filemtime(__DIR__ . '/../../assets/js/mesas-garcom.js') ?>"></script>
<script>iniciarMapaMesas('<?= BASE_URL ?>');</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
