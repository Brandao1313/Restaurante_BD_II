<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
exigirPerfilPagina(['garcom', 'administrador']);

$pdo = getConexao();

$filtroMesa = $_GET['mesa'] ?? '';
$filtroData = $_GET['data'] ?? '';

$sql = "SELECT ped.id_pedido, ped.status, ped.data_criacao, ped.observacao, m.numero AS numero_mesa,
               COALESCE(c.nome, f.nome) AS nome_responsavel,
               (SELECT COALESCE(SUM(ip.quantidade * ip.preco_unitario), 0) FROM ItensPedido ip WHERE ip.id_pedido = ped.id_pedido AND ip.status != 'Cancelado') AS total
        FROM Pedidos ped
        LEFT JOIN Mesas m ON m.id_mesa = ped.id_mesa
        LEFT JOIN Clientes c ON c.id_cliente = ped.id_cliente
        LEFT JOIN Funcionarios f ON f.id_funcionario = ped.id_funcionario
        WHERE ped.status IN ('Finalizado', 'Cancelado')";
$parametros = [];

if ($filtroMesa !== '') {
    $sql .= ' AND m.numero = ?';
    $parametros[] = $filtroMesa;
}
if ($filtroData !== '') {
    $sql .= ' AND DATE(ped.data_criacao) = ?';
    $parametros[] = $filtroData;
}
$sql .= ' ORDER BY ped.data_criacao DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$pedidos = $stmt->fetchAll();

$idDetalhes = isset($_GET['detalhes']) ? (int) $_GET['detalhes'] : null;
$itensDetalhes = [];
if ($idDetalhes) {
    $stmt = $pdo->prepare(
        'SELECT ip.*, p.nome AS nome_produto FROM ItensPedido ip
         JOIN Produtos p ON p.id_produto = ip.id_produto
         WHERE ip.id_pedido = ?'
    );
    $stmt->execute([$idDetalhes]);
    $itensDetalhes = $stmt->fetchAll();
}

$mesasDisponiveis = $pdo->query('SELECT DISTINCT numero FROM Mesas ORDER BY numero ASC')->fetchAll();

$tituloPagina = 'Histórico - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('historico') ?> Histórico de Consumo das Mesas</h1>

<div class="card" style="margin-bottom:1.5rem">
    <h3>Filtros</h3>
    <form method="GET">
        <div class="grid grid-cards">
            <div class="campo">
                <label for="mesa">Mesa</label>
                <select id="mesa" name="mesa">
                    <option value="">Todas</option>
                    <?php foreach ($mesasDisponiveis as $m): ?>
                        <option value="<?= $m['numero'] ?>" <?= (string) $filtroMesa === (string) $m['numero'] ? 'selected' : '' ?>>Mesa <?= $m['numero'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="data">Data</label>
                <input type="date" id="data" name="data" value="<?= htmlspecialchars($filtroData) ?>">
            </div>
        </div>
        <button type="submit" class="btn">Filtrar</button>
        <a class="btn btn-secundario" href="<?= BASE_URL ?>/pages/garcom/historico.php">Limpar</a>
    </form>
</div>

<div class="card" style="overflow-x:auto">
    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align:left; border-bottom:2px solid var(--cor-borda)">
                <th style="padding:0.5rem">#</th>
                <th style="padding:0.5rem">Mesa</th>
                <th style="padding:0.5rem">Responsável</th>
                <th style="padding:0.5rem">Data</th>
                <th style="padding:0.5rem">Total</th>
                <th style="padding:0.5rem">Status</th>
                <th style="padding:0.5rem">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($pedidos as $p): ?>
            <tr style="border-bottom:1px solid var(--cor-borda)">
                <td style="padding:0.5rem">#<?= $p['id_pedido'] ?></td>
                <td style="padding:0.5rem"><?= $p['numero_mesa'] ? 'Mesa ' . $p['numero_mesa'] : '-' ?></td>
                <td style="padding:0.5rem"><?= htmlspecialchars($p['nome_responsavel'] ?? '-') ?></td>
                <td style="padding:0.5rem"><?= formatarDataHoraPhp($p['data_criacao']) ?></td>
                <td style="padding:0.5rem"><?= formatarMoedaPhp($p['total']) ?></td>
                <td style="padding:0.5rem"><strong><?= htmlspecialchars($p['status']) ?></strong></td>
                <td style="padding:0.5rem">
                    <a class="btn btn-secundario" href="?<?= http_build_query(array_merge($_GET, ['detalhes' => $p['id_pedido']])) ?>">Detalhes</a>
                </td>
            </tr>
            <?php if ($idDetalhes === (int) $p['id_pedido']): ?>
            <tr>
                <td colspan="7" style="padding:0.75rem; background:var(--cor-fundo)">
                    <strong>Itens do pedido #<?= $p['id_pedido'] ?></strong>
                    <table style="width:100%; margin-top:0.5rem">
                        <thead>
                            <tr style="text-align:left">
                                <th>Produto</th><th>Qtd.</th><th>Preço unit.</th><th>Subtotal</th><th>Status</th><th>Obs.</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($itensDetalhes as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nome_produto']) ?></td>
                                <td><?= (int) $item['quantidade'] ?></td>
                                <td><?= formatarMoedaPhp($item['preco_unitario']) ?></td>
                                <td><?= formatarMoedaPhp($item['quantidade'] * $item['preco_unitario']) ?></td>
                                <td><?= htmlspecialchars($item['status']) ?></td>
                                <td><?= htmlspecialchars($item['observacao'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$pedidos): ?>
            <tr><td colspan="7" style="padding:0.75rem; text-align:center; color:#777">Nenhum registro encontrado.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
