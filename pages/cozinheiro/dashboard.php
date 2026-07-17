<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
exigirPerfilPagina(['cozinheiro', 'administrador']);

$pdo = getConexao();

$contagemStatus = $pdo->query(
    "SELECT status, COUNT(*) AS total FROM Pedidos WHERE status IN ('Aberto', 'Em Preparo', 'Pronto') GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$ultimosPedidos = $pdo->query(
    "SELECT ped.id_pedido, ped.status, ped.data_criacao, m.numero AS numero_mesa
     FROM Pedidos ped
     LEFT JOIN Mesas m ON m.id_mesa = ped.id_mesa
     WHERE ped.status IN ('Aberto', 'Em Preparo', 'Pronto')
     ORDER BY ped.data_criacao ASC
     LIMIT 10"
)->fetchAll();

$insumosBaixos = $pdo->query(
    "SELECT nome, quantidade, unidade, quantidade_minima FROM Insumos WHERE quantidade <= quantidade_minima ORDER BY quantidade ASC"
)->fetchAll();

$tituloPagina = 'Painel do Cozinheiro - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('dashboard') ?> Painel da Cozinha</h1>
<p>Olá, <?= htmlspecialchars($_SESSION['nome']) ?>!</p>

<div class="grid grid-cards" style="margin-bottom:1.5rem">
    <div class="card">
        <h3><?= icone('pedidos') ?> Pendentes</h3>
        <p style="font-size:2rem; font-weight:700"><?= (int) ($contagemStatus['Aberto'] ?? 0) ?></p>
    </div>
    <div class="card">
        <h3><?= icone('historico') ?> Em Preparo</h3>
        <p style="font-size:2rem; font-weight:700"><?= (int) ($contagemStatus['Em Preparo'] ?? 0) ?></p>
    </div>
    <div class="card">
        <h3><?= icone('cardapio') ?> Prontos</h3>
        <p style="font-size:2rem; font-weight:700"><?= (int) ($contagemStatus['Pronto'] ?? 0) ?></p>
    </div>
</div>

<div class="grid grid-cards">
    <div class="card">
        <h3>Últimos pedidos recebidos</h3>
        <?php if (!$ultimosPedidos): ?>
            <p style="color:#777">Nenhum pedido em andamento.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($ultimosPedidos as $p): ?>
                    <li>
                        Pedido #<?= $p['id_pedido'] ?> <?= $p['numero_mesa'] ? '— Mesa ' . $p['numero_mesa'] : '' ?>
                        — <strong><?= htmlspecialchars($p['status']) ?></strong>
                        (<?= formatarDataHoraPhp($p['data_criacao']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <a class="btn" href="<?= BASE_URL ?>/pages/cozinheiro/pedidos.php">Ver todos os pedidos</a>
    </div>

    <div class="card">
        <h3>Alertas de estoque baixo</h3>
        <?php if (!$insumosBaixos): ?>
            <p style="color:#777">Nenhum insumo abaixo do mínimo.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($insumosBaixos as $i): ?>
                    <li style="color:var(--cor-ocupada)">
                        <?= htmlspecialchars($i['nome']) ?>: <?= htmlspecialchars($i['quantidade']) ?> <?= htmlspecialchars($i['unidade']) ?>
                        (mínimo: <?= htmlspecialchars($i['quantidade_minima']) ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <a class="btn" href="<?= BASE_URL ?>/pages/cozinheiro/estoque.php">Gerenciar estoque</a>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
