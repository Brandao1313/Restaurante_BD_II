<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
exigirPerfilPagina(['administrador']);

$pdo = getConexao();
$tiposRelatorio = [
    'vendas' => 'Vendas',
    'despesas' => 'Despesas',
    'produtos_mais_vendidos' => 'Produtos Mais Vendidos',
    'desempenho_funcionarios' => 'Desempenho de Funcionários',
];

$tipo = $_GET['tipo'] ?? 'vendas';
$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? date('Y-m-d');
$formato = $_GET['formato'] ?? '';

function buscarDadosRelatorio(PDO $pdo, string $tipo, string $inicio, string $fim): array
{
    switch ($tipo) {
        case 'vendas':
            $stmt = $pdo->prepare(
                "SELECT ped.id_pedido, ped.data_criacao, c.nome AS nome_cliente, m.numero AS numero_mesa,
                        (SELECT COALESCE(SUM(ip.quantidade * ip.preco_unitario), 0) FROM ItensPedido ip WHERE ip.id_pedido = ped.id_pedido) AS total
                 FROM Pedidos ped
                 LEFT JOIN Clientes c ON c.id_cliente = ped.id_cliente
                 LEFT JOIN Mesas m ON m.id_mesa = ped.id_mesa
                 WHERE ped.status = 'Finalizado' AND DATE(ped.data_criacao) BETWEEN ? AND ?
                 ORDER BY ped.data_criacao DESC"
            );
            $stmt->execute([$inicio, $fim]);
            return ['colunas' => ['Pedido', 'Data', 'Cliente', 'Mesa', 'Total'], 'linhas' => $stmt->fetchAll()];

        case 'despesas':
            $stmt = $pdo->prepare(
                'SELECT descricao, valor, categoria, data_vencimento, status FROM Despesas
                 WHERE data_vencimento BETWEEN ? AND ? ORDER BY data_vencimento DESC'
            );
            $stmt->execute([$inicio, $fim]);
            return ['colunas' => ['Descrição', 'Valor', 'Categoria', 'Vencimento', 'Status'], 'linhas' => $stmt->fetchAll()];

        case 'produtos_mais_vendidos':
            $stmt = $pdo->prepare(
                "SELECT p.nome, SUM(ip.quantidade) AS quantidade_total, SUM(ip.quantidade * ip.preco_unitario) AS total_vendido
                 FROM ItensPedido ip
                 JOIN Pedidos ped ON ped.id_pedido = ip.id_pedido
                 JOIN Produtos p ON p.id_produto = ip.id_produto
                 WHERE ped.status = 'Finalizado' AND DATE(ped.data_criacao) BETWEEN ? AND ?
                 GROUP BY p.id_produto, p.nome
                 ORDER BY quantidade_total DESC"
            );
            $stmt->execute([$inicio, $fim]);
            return ['colunas' => ['Produto', 'Quantidade Vendida', 'Total Vendido'], 'linhas' => $stmt->fetchAll()];

        case 'desempenho_funcionarios':
            $stmt = $pdo->prepare(
                "SELECT COALESCE(f.nome, 'Não informado') AS nome_funcionario, COUNT(DISTINCT ped.id_pedido) AS total_pedidos,
                        SUM(ip.quantidade * ip.preco_unitario) AS total_vendido
                 FROM Pedidos ped
                 JOIN ItensPedido ip ON ip.id_pedido = ped.id_pedido
                 LEFT JOIN Funcionarios f ON f.id_funcionario = ped.id_funcionario
                 WHERE ped.status = 'Finalizado' AND DATE(ped.data_criacao) BETWEEN ? AND ?
                 GROUP BY ped.id_funcionario, f.nome
                 ORDER BY total_vendido DESC"
            );
            $stmt->execute([$inicio, $fim]);
            return ['colunas' => ['Funcionário', 'Pedidos Atendidos', 'Total Vendido'], 'linhas' => $stmt->fetchAll()];

        default:
            return ['colunas' => [], 'linhas' => []];
    }
}

$dados = buscarDadosRelatorio($pdo, $tipo, $inicio, $fim);

if ($formato === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorio_' . $tipo . '_' . $inicio . '_a_' . $fim . '.csv"');
    $saida = fopen('php://output', 'w');
    fputs($saida, "\xEF\xBB\xBF"); // BOM para acentuação correta no Excel
    fputcsv($saida, $dados['colunas'], ';');
    foreach ($dados['linhas'] as $linha) {
        fputcsv($saida, $linha, ';');
    }
    fclose($saida);
    exit;
}

$tituloPagina = 'Relatórios - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('relatorios') ?> Relatórios Gerenciais</h1>

<div class="card" style="margin-bottom:1.5rem">
    <form method="GET" id="form-relatorio">
        <div class="grid grid-cards">
            <div class="campo">
                <label for="tipo">Tipo de relatório</label>
                <select id="tipo" name="tipo">
                    <?php foreach ($tiposRelatorio as $chave => $rotulo): ?>
                        <option value="<?= $chave ?>" <?= $tipo === $chave ? 'selected' : '' ?>><?= $rotulo ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="inicio">De</label>
                <input type="date" id="inicio" name="inicio" value="<?= htmlspecialchars($inicio) ?>">
            </div>
            <div class="campo">
                <label for="fim">Até</label>
                <input type="date" id="fim" name="fim" value="<?= htmlspecialchars($fim) ?>">
            </div>
        </div>
        <button type="submit" class="btn">Visualizar</button>
        <button type="submit" class="btn btn-secundario" name="formato" value="csv">Exportar Excel/CSV</button>
        <button type="button" class="btn btn-secundario" onclick="window.print()">Exportar PDF (imprimir)</button>
    </form>
</div>

<div class="card" id="area-impressao">
    <h3><?= htmlspecialchars($tiposRelatorio[$tipo] ?? '') ?> — <?= formatarDataPhp($inicio) ?> a <?= formatarDataPhp($fim) ?></h3>
    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align:left; border-bottom:2px solid var(--cor-borda)">
                <?php foreach ($dados['colunas'] as $coluna): ?>
                    <th style="padding:0.5rem"><?= htmlspecialchars($coluna) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dados['linhas'] as $linha): ?>
            <tr style="border-bottom:1px solid var(--cor-borda)">
                <?php foreach ($linha as $chave => $valor): ?>
                    <?php if (is_int($chave)) continue; ?>
                    <td style="padding:0.5rem"><?= htmlspecialchars((string) $valor) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$dados['linhas']): ?>
            <tr><td colspan="<?= count($dados['colunas']) ?>" style="padding:0.75rem; text-align:center; color:#777">Nenhum dado encontrado para o período.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    @media print {
        .topbar, form, .btn { display: none !important; }
        #area-impressao { box-shadow: none; border: none; }
    }
</style>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
