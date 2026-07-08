<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
exigirPerfilPagina(['cliente']);

$pdo = getConexao();
$categorias = $pdo->query('SELECT * FROM Categorias ORDER BY ordem ASC')->fetchAll();

$tituloPagina = 'Cardápio - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('cardapio') ?> Cardápio</h1>

<input type="search" id="busca-produto" class="campo-busca" placeholder="Buscar prato...">

<div class="abas-categoria" id="abas-categoria">
    <button type="button" class="aba-categoria ativa" data-categoria="">Todos</button>
    <?php foreach ($categorias as $c): ?>
        <button type="button" class="aba-categoria" data-categoria="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nome']) ?></button>
    <?php endforeach; ?>
</div>

<div id="grid-produtos" class="grid grid-produtos"></div>

<div id="carrinho-flutuante" class="btn" style="position:fixed; bottom:1.5rem; right:1.5rem; z-index:50; display:none">
    <span id="carrinho-contador">0</span> item(ns) — <span id="carrinho-total">R$ 0,00</span>
</div>

<div id="carrinho-painel" class="card" style="position:fixed; bottom:5rem; right:1.5rem; width:min(360px, 90vw); max-height:70vh; overflow-y:auto; z-index:50; display:none">
    <h3>Seu Pedido</h3>
    <div id="carrinho-itens"></div>
    <div class="campo">
        <label for="select-mesa">Mesa</label>
        <select id="select-mesa"></select>
    </div>
    <div class="campo">
        <label for="observacao-pedido">Observação do pedido</label>
        <textarea id="observacao-pedido" rows="2"></textarea>
    </div>
    <p><strong>Total: <span id="carrinho-total-painel">R$ 0,00</span></strong></p>
    <div id="mensagem-carrinho"></div>
    <button type="button" class="btn" style="width:100%" onclick="finalizarPedido()">Enviar Pedido</button>
</div>

<script src="<?= BASE_URL ?>/assets/js/cardapio.js?v=<?= filemtime(__DIR__ . '/../../assets/js/cardapio.js') ?>"></script>
<script>iniciarCardapio(<?= (int) $_SESSION['id_cliente'] ?>, '<?= BASE_URL ?>');</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
