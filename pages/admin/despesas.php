<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';
exigirPerfilPagina(['administrador']);

$pdo = getConexao();
$categoriasDespesa = ['Aquisição de insumos', 'Manutenção preventiva', 'Despesas fixas', 'Folha de pagamento', 'Marketing', 'Outros'];
$statusValidos = ['Pendente', 'Pago'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'salvar') {
            $idDespesa = $_POST['id_despesa'] !== '' ? (int) $_POST['id_despesa'] : null;
            $descricao = trim($_POST['descricao'] ?? '');
            $valor = (float) str_replace(',', '.', $_POST['valor'] ?? '0');
            $categoria = $_POST['categoria'] ?? '';
            $dataVencimento = $_POST['data_vencimento'] ?: null;
            $dataPagamento = $_POST['data_pagamento'] ?: null;
            $status = $_POST['status'] ?? 'Pendente';

            if ($descricao === '' || $valor <= 0 || !in_array($categoria, $categoriasDespesa, true) || !in_array($status, $statusValidos, true)) {
                throw new RuntimeException('Preencha todos os campos obrigatórios corretamente.');
            }

            if ($idDespesa === null) {
                $pdo->prepare(
                    'INSERT INTO Despesas (descricao, valor, categoria, data_vencimento, data_pagamento, status, id_funcionario)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                )->execute([$descricao, $valor, $categoria, $dataVencimento, $dataPagamento, $status, $_SESSION['id_funcionario']]);
                definirFlash('sucesso', 'Despesa lançada com sucesso.');
            } else {
                $pdo->prepare(
                    'UPDATE Despesas SET descricao=?, valor=?, categoria=?, data_vencimento=?, data_pagamento=?, status=? WHERE id_despesa=?'
                )->execute([$descricao, $valor, $categoria, $dataVencimento, $dataPagamento, $status, $idDespesa]);
                definirFlash('sucesso', 'Despesa atualizada com sucesso.');
            }
        } elseif ($acao === 'excluir') {
            $idDespesa = (int) ($_POST['id_despesa'] ?? 0);
            $pdo->prepare('DELETE FROM Despesas WHERE id_despesa = ?')->execute([$idDespesa]);
            definirFlash('sucesso', 'Despesa excluída com sucesso.');
        }
    } catch (RuntimeException $e) {
        definirFlash('erro', $e->getMessage());
    }

    header('Location: ' . BASE_URL . '/pages/admin/despesas.php');
    exit;
}

$filtroCategoria = $_GET['categoria'] ?? '';
$filtroInicio = $_GET['inicio'] ?? '';
$filtroFim = $_GET['fim'] ?? '';

$sql = 'SELECT * FROM Despesas WHERE 1=1';
$parametros = [];
if ($filtroCategoria !== '') {
    $sql .= ' AND categoria = ?';
    $parametros[] = $filtroCategoria;
}
if ($filtroInicio !== '') {
    $sql .= ' AND data_vencimento >= ?';
    $parametros[] = $filtroInicio;
}
if ($filtroFim !== '') {
    $sql .= ' AND data_vencimento <= ?';
    $parametros[] = $filtroFim;
}
$sql .= ' ORDER BY data_vencimento DESC, id_despesa DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$despesas = $stmt->fetchAll();
$totalFiltrado = array_sum(array_column($despesas, 'valor'));

$idEdicao = isset($_GET['editar']) ? (int) $_GET['editar'] : null;
$despesaEdicao = null;
if ($idEdicao) {
    $stmt = $pdo->prepare('SELECT * FROM Despesas WHERE id_despesa = ?');
    $stmt->execute([$idEdicao]);
    $despesaEdicao = $stmt->fetch();
}

$tituloPagina = 'Despesas - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('despesas') ?> Despesas</h1>
<?php renderizarFlash(); ?>

<div class="card" style="margin-bottom:1.5rem">
    <h3><?= $despesaEdicao ? 'Editar Despesa' : 'Nova Despesa' ?></h3>
    <form method="POST">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id_despesa" value="<?= htmlspecialchars($despesaEdicao['id_despesa'] ?? '') ?>">
        <div class="grid grid-cards">
            <div class="campo">
                <label for="descricao">Descrição</label>
                <input type="text" id="descricao" name="descricao" required value="<?= htmlspecialchars($despesaEdicao['descricao'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="valor">Valor (R$)</label>
                <input type="number" step="0.01" min="0.01" id="valor" name="valor" required value="<?= htmlspecialchars($despesaEdicao['valor'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="categoria">Categoria</label>
                <select id="categoria" name="categoria" required>
                    <?php foreach ($categoriasDespesa as $c): ?>
                        <option value="<?= $c ?>" <?= ($despesaEdicao['categoria'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="data_vencimento">Data de vencimento</label>
                <input type="date" id="data_vencimento" name="data_vencimento" value="<?= htmlspecialchars($despesaEdicao['data_vencimento'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="data_pagamento">Data de pagamento</label>
                <input type="date" id="data_pagamento" name="data_pagamento" value="<?= htmlspecialchars($despesaEdicao['data_pagamento'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <?php foreach ($statusValidos as $s): ?>
                        <option value="<?= $s ?>" <?= ($despesaEdicao['status'] ?? 'Pendente') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn"><?= $despesaEdicao ? 'Salvar Alterações' : 'Lançar Despesa' ?></button>
        <?php if ($despesaEdicao): ?>
            <a class="btn btn-secundario" href="<?= BASE_URL ?>/pages/admin/despesas.php">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="card" style="margin-bottom:1.5rem">
    <h3>Filtros</h3>
    <form method="GET">
        <div class="grid grid-cards">
            <div class="campo">
                <label for="f_categoria">Categoria</label>
                <select id="f_categoria" name="categoria">
                    <option value="">Todas</option>
                    <?php foreach ($categoriasDespesa as $c): ?>
                        <option value="<?= $c ?>" <?= $filtroCategoria === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="inicio">De</label>
                <input type="date" id="inicio" name="inicio" value="<?= htmlspecialchars($filtroInicio) ?>">
            </div>
            <div class="campo">
                <label for="fim">Até</label>
                <input type="date" id="fim" name="fim" value="<?= htmlspecialchars($filtroFim) ?>">
            </div>
        </div>
        <button type="submit" class="btn">Filtrar</button>
        <a class="btn btn-secundario" href="<?= BASE_URL ?>/pages/admin/despesas.php">Limpar</a>
    </form>
</div>

<div class="card" style="overflow-x:auto">
    <h3>Total no período filtrado: <?= formatarMoedaPhp($totalFiltrado) ?></h3>
    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align:left; border-bottom:2px solid var(--cor-borda)">
                <th style="padding:0.5rem">Descrição</th>
                <th style="padding:0.5rem">Valor</th>
                <th style="padding:0.5rem">Categoria</th>
                <th style="padding:0.5rem">Vencimento</th>
                <th style="padding:0.5rem">Pagamento</th>
                <th style="padding:0.5rem">Status</th>
                <th style="padding:0.5rem">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($despesas as $d): ?>
            <tr style="border-bottom:1px solid var(--cor-borda)">
                <td style="padding:0.5rem"><?= htmlspecialchars($d['descricao']) ?></td>
                <td style="padding:0.5rem"><?= formatarMoedaPhp($d['valor']) ?></td>
                <td style="padding:0.5rem"><?= htmlspecialchars($d['categoria']) ?></td>
                <td style="padding:0.5rem"><?= formatarDataPhp($d['data_vencimento']) ?></td>
                <td style="padding:0.5rem"><?= formatarDataPhp($d['data_pagamento']) ?></td>
                <td style="padding:0.5rem"><?= htmlspecialchars($d['status']) ?></td>
                <td style="padding:0.5rem; display:flex; gap:0.4rem; flex-wrap:wrap">
                    <a class="btn btn-secundario" href="?editar=<?= $d['id_despesa'] ?>">Editar</a>
                    <form method="POST" onsubmit="return confirm('Excluir esta despesa?');">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id_despesa" value="<?= $d['id_despesa'] ?>">
                        <button type="submit" class="btn" style="background:var(--cor-ocupada)">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$despesas): ?>
            <tr><td colspan="7" style="padding:0.75rem; text-align:center; color:#777">Nenhuma despesa encontrada.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
