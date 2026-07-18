<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
exigirPerfilPagina(['garcom', 'administrador']);

$pdo = getConexao();
$categorias = $pdo->query('SELECT * FROM Categorias ORDER BY ordem ASC')->fetchAll();
$mesaPreSelecionada = isset($_GET['mesa']) ? (int) $_GET['mesa'] : 0;

$tituloPagina = 'Comandas - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('comandas') ?> Comandas</h1>
<div id="mensagem-comandas"></div>

<div class="card" style="margin-bottom:1.5rem">
    <h3>Abrir Comanda / Lançar Pedido</h3>
    <div class="campo">
        <label for="select-mesa-comanda">Mesa</label>
        <select id="select-mesa-comanda"></select>
    </div>

    <input type="search" id="busca-produto-comanda" class="campo-busca" placeholder="Buscar prato...">
    <div class="abas-categoria" id="abas-categoria-comanda">
        <button type="button" class="aba-categoria ativa" data-categoria="">Todos</button>
        <?php foreach ($categorias as $c): ?>
            <button type="button" class="aba-categoria" data-categoria="<?= $c['id_categoria'] ?>"><?= htmlspecialchars($c['nome']) ?></button>
        <?php endforeach; ?>
    </div>
    <div id="grid-produtos-comanda" class="grid grid-produtos"></div>
</div>

<div id="carrinho-flutuante-comanda" class="btn" style="position:fixed; bottom:1.5rem; right:1.5rem; z-index:50; display:none">
    <span id="carrinho-contador-comanda">0</span> item(ns) — <span id="carrinho-total-comanda">R$ 0,00</span>
</div>

<div id="carrinho-painel-comanda" class="card" style="position:fixed; bottom:5rem; right:1.5rem; width:min(360px, 90vw); max-height:70vh; overflow-y:auto; z-index:50; display:none">
    <h3>Itens da Comanda</h3>
    <div id="carrinho-itens-comanda"></div>
    <div class="campo">
        <label for="observacao-comanda">Observação do pedido</label>
        <textarea id="observacao-comanda" rows="2"></textarea>
    </div>
    <p><strong>Total: <span id="carrinho-total-painel-comanda">R$ 0,00</span></strong></p>
    <div id="mensagem-carrinho-comanda"></div>
    <button type="button" class="btn" style="width:100%" onclick="enviarComanda()">Lançar Pedido</button>
</div>

<div class="card">
    <h3>Comandas abertas</h3>
    <div id="lista-comandas-abertas">Carregando...</div>
</div>

<script src="<?= BASE_URL ?>/assets/js/comandas.js?v=<?= filemtime(__DIR__ . '/../../assets/js/comandas.js') ?>"></script>
<script>iniciarComandas('<?= BASE_URL ?>', <?= $mesaPreSelecionada ?>);</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
