<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/funcoes_estoque.php';
exigirPerfilPagina(['administrador']);

$pdo = getConexao();
$statusValidos = ['Aberto', 'Em Preparo', 'Pronto', 'Finalizado', 'Cancelado'];
$formasPagamento = ['Pix', 'Cartão de Crédito', 'Cartão de Débito', 'Dinheiro'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $idPedido = (int) ($_POST['id_pedido'] ?? 0);

    try {
        if ($acao === 'finalizar_pagamento') {
            $formaPagamento = $_POST['forma_pagamento'] ?? '';
            if (!in_array($formaPagamento, $formasPagamento, true)) {
                throw new RuntimeException('Selecione uma forma de pagamento válida.');
            }

            $stmt = $pdo->prepare('SELECT status FROM Pedidos WHERE id_pedido = ?');
            $stmt->execute([$idPedido]);
            $statusAtual = $stmt->fetchColumn();

            if ($statusAtual === false) {
                throw new RuntimeException('Pedido não encontrado.');
            }
            if (in_array($statusAtual, ['Finalizado', 'Cancelado'], true)) {
                throw new RuntimeException("Pedido \"$statusAtual\" é definitivo e não pode receber pagamento.");
            }

            $stmt = $pdo->prepare(
                'SELECT COALESCE(SUM(quantidade * preco_unitario), 0) AS total FROM ItensPedido WHERE id_pedido = ?'
            );
            $stmt->execute([$idPedido]);
            $total = (float) $stmt->fetchColumn();

            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO Pagamentos (id_pedido, valor, forma_pagamento) VALUES (?, ?, ?)')
                ->execute([$idPedido, $total, $formaPagamento]);
            $pdo->prepare("UPDATE Pedidos SET status = 'Finalizado' WHERE id_pedido = ?")->execute([$idPedido]);
            $pdo->commit();
            definirFlash('sucesso', 'Pedido finalizado e pagamento registrado.');
        } elseif ($acao === 'alterar_status') {
            $novoStatus = $_POST['status'] ?? '';
            if (!in_array($novoStatus, $statusValidos, true)) {
                throw new RuntimeException('Status inválido.');
            }

            $stmt = $pdo->prepare('SELECT status FROM Pedidos WHERE id_pedido = ?');
            $stmt->execute([$idPedido]);
            $statusAtual = $stmt->fetchColumn();

            if ($statusAtual === false) {
                throw new RuntimeException('Pedido não encontrado.');
            }
            if (in_array($statusAtual, ['Finalizado', 'Cancelado'], true)) {
                throw new RuntimeException("Pedido \"$statusAtual\" é definitivo e não pode mais ter o status alterado.");
            }

            $pdo->beginTransaction();

            if ($novoStatus === 'Cancelado') {
                $stmt = $pdo->prepare(
                    "SELECT id_produto, quantidade FROM ItensPedido WHERE id_pedido = ? AND status != 'Cancelado'"
                );
                $stmt->execute([$idPedido]);
                foreach ($stmt->fetchAll() as $item) {
                    estornarEstoqueItem($pdo, (int) $item['id_produto'], (int) $item['quantidade']);
                }
                $pdo->prepare("UPDATE ItensPedido SET status = 'Cancelado' WHERE id_pedido = ?")->execute([$idPedido]);
            }

            $pdo->prepare('UPDATE Pedidos SET status = ? WHERE id_pedido = ?')->execute([$novoStatus, $idPedido]);
            $pdo->commit();
            definirFlash('sucesso', 'Status do pedido atualizado.');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        definirFlash('erro', $e->getMessage());
    }

    header('Location: ' . BASE_URL . '/pages/admin/pedidos.php?' . http_build_query($_GET));
    exit;
}

$filtroStatus = $_GET['status'] ?? '';
$filtroData = $_GET['data'] ?? '';
$filtroMesa = $_GET['mesa'] ?? '';

$sql = "SELECT ped.*, m.numero AS numero_mesa, c.nome AS nome_cliente,
               (SELECT COALESCE(SUM(ip.quantidade * ip.preco_unitario), 0) FROM ItensPedido ip WHERE ip.id_pedido = ped.id_pedido) AS total
        FROM Pedidos ped
        LEFT JOIN Mesas m ON m.id_mesa = ped.id_mesa
        LEFT JOIN Clientes c ON c.id_cliente = ped.id_cliente
        WHERE 1=1";
$parametros = [];

if ($filtroStatus !== '') {
    $sql .= ' AND ped.status = ?';
    $parametros[] = $filtroStatus;
}
if ($filtroData !== '') {
    $sql .= ' AND DATE(ped.data_criacao) = ?';
    $parametros[] = $filtroData;
}
if ($filtroMesa !== '') {
    $sql .= ' AND m.numero = ?';
    $parametros[] = $filtroMesa;
}
$sql .= ' ORDER BY ped.data_criacao DESC';

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

$tituloPagina = 'Pedidos - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('pedidos') ?> Pedidos</h1>
<?php renderizarFlash(); ?>

<div class="card" style="margin-bottom:1.5rem">
    <h3>Filtros</h3>
    <form method="GET">
        <div class="grid grid-cards">
            <div class="campo">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Todos</option>
                    <?php foreach ($statusValidos as $s): ?>
                        <option value="<?= $s ?>" <?= $filtroStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="data">Data</label>
                <input type="date" id="data" name="data" value="<?= htmlspecialchars($filtroData) ?>">
            </div>
            <div class="campo">
                <label for="mesa">Mesa</label>
                <select id="mesa" name="mesa">
                    <option value="">Todas</option>
                    <?php foreach ($mesasDisponiveis as $m): ?>
                        <option value="<?= $m['numero'] ?>" <?= (string) $filtroMesa === (string) $m['numero'] ? 'selected' : '' ?>>Mesa <?= $m['numero'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn">Filtrar</button>
        <a class="btn btn-secundario" href="<?= BASE_URL ?>/pages/admin/pedidos.php">Limpar</a>
    </form>
</div>

<div class="card" style="overflow-x:auto">
    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align:left; border-bottom:2px solid var(--cor-borda)">
                <th style="padding:0.5rem">#</th>
                <th style="padding:0.5rem">Cliente</th>
                <th style="padding:0.5rem">Mesa</th>
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
                <td style="padding:0.5rem"><?= htmlspecialchars($p['nome_cliente'] ?? '-') ?></td>
                <td style="padding:0.5rem"><?= $p['numero_mesa'] ? 'Mesa ' . $p['numero_mesa'] : '-' ?></td>
                <td style="padding:0.5rem"><?= formatarDataHoraPhp($p['data_criacao']) ?></td>
                <td style="padding:0.5rem"><?= formatarMoedaPhp($p['total']) ?></td>
                <td style="padding:0.5rem">
                    <?php if (in_array($p['status'], ['Finalizado', 'Cancelado'], true)): ?>
                        <strong><?= htmlspecialchars($p['status']) ?></strong>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="acao" value="alterar_status">
                            <input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
                            <select name="status" onchange="this.form.submit()">
                                <?php foreach ($statusValidos as $s): ?>
                                    <option value="<?= $s ?>" <?= $p['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php endif; ?>
                </td>
                <td style="padding:0.5rem; display:flex; gap:0.4rem; flex-wrap:wrap">
                    <a class="btn btn-secundario" href="?<?= http_build_query(array_merge($_GET, ['detalhes' => $p['id_pedido']])) ?>">Detalhes</a>
                    <?php if (!in_array($p['status'], ['Finalizado', 'Cancelado'], true)): ?>
                        <form method="POST" style="display:flex; gap:0.3rem">
                            <input type="hidden" name="acao" value="finalizar_pagamento">
                            <input type="hidden" name="id_pedido" value="<?= $p['id_pedido'] ?>">
                            <select name="forma_pagamento" required>
                                <option value="">Forma de pagamento...</option>
                                <?php foreach ($formasPagamento as $fp): ?>
                                    <option value="<?= $fp ?>"><?= $fp ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn">Finalizar</button>
                        </form>
                    <?php endif; ?>
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
            <tr><td colspan="7" style="padding:0.75rem; text-align:center; color:#777">Nenhum pedido encontrado.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
