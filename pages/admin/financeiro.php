<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/helpers.php';
exigirPerfilPagina(['administrador']);

$pdo = getConexao();
$formasPagamento = ['Pix', 'Cartão de Crédito', 'Cartão de Débito', 'Dinheiro'];

$inicio = $_GET['inicio'] ?? date('Y-m-01');
$fim = $_GET['fim'] ?? date('Y-m-d');
$formaPagamento = $_GET['forma_pagamento'] ?? '';
$turno = $_GET['turno'] ?? '';

function faturamentoBruto(PDO $pdo, string $condicaoExtra = '', array $parametrosExtra = []): float
{
    $sql = "SELECT COALESCE(SUM(ip.quantidade * ip.preco_unitario), 0)
            FROM Pedidos ped
            JOIN ItensPedido ip ON ip.id_pedido = ped.id_pedido
            WHERE ped.status = 'Finalizado' $condicaoExtra";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametrosExtra);
    return (float) $stmt->fetchColumn();
}

$faturamentoHoje = faturamentoBruto($pdo, 'AND DATE(ped.data_criacao) = CURDATE()');
$faturamentoSemana = faturamentoBruto($pdo, 'AND YEARWEEK(ped.data_criacao, 1) = YEARWEEK(CURDATE(), 1)');
$faturamentoMes = faturamentoBruto($pdo, 'AND YEAR(ped.data_criacao) = YEAR(CURDATE()) AND MONTH(ped.data_criacao) = MONTH(CURDATE())');

$sqlPeriodo = "SELECT ip.id_produto, ip.quantidade, ip.preco_unitario, ped.data_criacao
               FROM Pedidos ped
               JOIN ItensPedido ip ON ip.id_pedido = ped.id_pedido";
$condicoes = ["ped.status = 'Finalizado'", 'DATE(ped.data_criacao) BETWEEN ? AND ?'];
$parametros = [$inicio, $fim];

if ($formaPagamento !== '') {
    $sqlPeriodo .= ' JOIN Pagamentos pag ON pag.id_pedido = ped.id_pedido';
    $condicoes[] = 'pag.forma_pagamento = ?';
    $parametros[] = $formaPagamento;
}

$sqlPeriodo .= ' WHERE ' . implode(' AND ', $condicoes);
$stmt = $pdo->prepare($sqlPeriodo);
$stmt->execute($parametros);
$itensPeriodo = $stmt->fetchAll();

$faturamentoPorDia = [];
$faturamentoFiltrado = 0.0;
foreach ($itensPeriodo as $item) {
    $subtotal = $item['quantidade'] * $item['preco_unitario'];
    $data = substr($item['data_criacao'], 0, 10);
    $horaTexto = substr($item['data_criacao'], 11, 8);
    $turnoItem = turnoDoHorario($horaTexto);

    if ($turno !== '' && $turnoItem !== $turno) {
        continue;
    }

    $faturamentoPorDia[$data] = ($faturamentoPorDia[$data] ?? 0) + $subtotal;
    $faturamentoFiltrado += $subtotal;
}
ksort($faturamentoPorDia);

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(valor), 0) FROM Despesas WHERE status = 'Pago' AND data_pagamento BETWEEN ? AND ?"
);
$stmt->execute([$inicio, $fim]);
$despesasPeriodo = (float) $stmt->fetchColumn();

$lucroLiquido = $faturamentoFiltrado - $despesasPeriodo;
$margemPercentual = $faturamentoFiltrado > 0 ? ($lucroLiquido / $faturamentoFiltrado) * 100 : 0;

$tituloPagina = 'Financeiro - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('financeiro') ?> Financeiro</h1>
<?php renderizarFlash(); ?>

<div class="grid grid-cards" style="margin-bottom:1.5rem">
    <div class="card">
        <h3>Faturamento hoje</h3>
        <p style="font-size:1.5rem; font-weight:700"><?= formatarMoedaPhp($faturamentoHoje) ?></p>
    </div>
    <div class="card">
        <h3>Faturamento na semana</h3>
        <p style="font-size:1.5rem; font-weight:700"><?= formatarMoedaPhp($faturamentoSemana) ?></p>
    </div>
    <div class="card">
        <h3>Faturamento no mês</h3>
        <p style="font-size:1.5rem; font-weight:700"><?= formatarMoedaPhp($faturamentoMes) ?></p>
    </div>
</div>

<div class="card" style="margin-bottom:1.5rem">
    <h3>Filtros do período</h3>
    <form method="GET">
        <div class="grid grid-cards">
            <div class="campo">
                <label for="inicio">De</label>
                <input type="date" id="inicio" name="inicio" value="<?= htmlspecialchars($inicio) ?>">
            </div>
            <div class="campo">
                <label for="fim">Até</label>
                <input type="date" id="fim" name="fim" value="<?= htmlspecialchars($fim) ?>">
            </div>
            <div class="campo">
                <label for="forma_pagamento">Forma de pagamento</label>
                <select id="forma_pagamento" name="forma_pagamento">
                    <option value="">Todas</option>
                    <?php foreach ($formasPagamento as $fp): ?>
                        <option value="<?= $fp ?>" <?= $formaPagamento === $fp ? 'selected' : '' ?>><?= $fp ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="turno">Turno</label>
                <select id="turno" name="turno">
                    <option value="">Todos</option>
                    <option value="Almoço" <?= $turno === 'Almoço' ? 'selected' : '' ?>>Almoço</option>
                    <option value="Jantar" <?= $turno === 'Jantar' ? 'selected' : '' ?>>Jantar</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn">Aplicar Filtros</button>
    </form>
</div>

<div class="grid grid-cards" style="margin-bottom:1.5rem">
    <div class="card">
        <h3>Faturamento bruto (período)</h3>
        <p style="font-size:1.5rem; font-weight:700"><?= formatarMoedaPhp($faturamentoFiltrado) ?></p>
    </div>
    <div class="card">
        <h3>Despesas pagas (período)</h3>
        <p style="font-size:1.5rem; font-weight:700"><?= formatarMoedaPhp($despesasPeriodo) ?></p>
    </div>
    <div class="card">
        <h3>Lucro líquido</h3>
        <p style="font-size:1.5rem; font-weight:700; color:<?= $lucroLiquido >= 0 ? 'var(--cor-livre)' : 'var(--cor-ocupada)' ?>">
            <?= formatarMoedaPhp($lucroLiquido) ?> (<?= number_format($margemPercentual, 1) ?>%)
        </p>
    </div>
</div>

<div class="card">
    <h3>Faturamento por dia no período</h3>
    <canvas id="graficoFaturamento" height="90"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const rotulos = <?= json_encode(array_keys($faturamentoPorDia)) ?>;
    const valores = <?= json_encode(array_map(fn($v) => round($v, 2), array_values($faturamentoPorDia))) ?>;

    if (window.Chart) {
        new Chart(document.getElementById('graficoFaturamento'), {
            type: 'bar',
            data: {
                labels: rotulos,
                datasets: [{
                    label: 'Faturamento (R$)',
                    data: valores,
                    backgroundColor: '#7a2e2e',
                }],
            },
            options: { responsive: true, plugins: { legend: { display: false } } },
        });
    }
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
