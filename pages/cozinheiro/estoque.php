<?php
require_once __DIR__ . '/../../config/session.php';
exigirPerfilPagina(['cozinheiro', 'administrador']);

$tituloPagina = 'Estoque - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('insumos') ?> Estoque de Insumos</h1>
<div id="mensagem-estoque"></div>

<div class="abas-categoria">
    <button type="button" class="aba-categoria ativa" data-filtro="">Todos</button>
    <button type="button" class="aba-categoria" data-filtro="abaixo_minimo">Abaixo do mínimo</button>
    <button type="button" class="aba-categoria" data-filtro="em_falta">Em falta</button>
</div>

<div class="card" style="overflow-x:auto">
    <table style="width:100%">
        <thead>
            <tr>
                <th>Insumo</th>
                <th>Quantidade</th>
                <th>Mínima</th>
                <th>Última atualização</th>
                <th>Registrar compra</th>
            </tr>
        </thead>
        <tbody id="corpo-tabela-estoque"></tbody>
    </table>
</div>

<script src="<?= BASE_URL ?>/assets/js/estoque.js?v=<?= filemtime(__DIR__ . '/../../assets/js/estoque.js') ?>"></script>
<script>iniciarEstoque('<?= BASE_URL ?>');</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
