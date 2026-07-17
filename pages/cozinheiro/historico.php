<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
exigirPerfilPagina(['cozinheiro', 'administrador']);

$pdo = getConexao();
$stmt = $pdo->prepare(
    'SELECT acao, detalhes, data_hora FROM LogsAuditoria WHERE id_funcionario = ? ORDER BY data_hora DESC LIMIT 100'
);
$stmt->execute([$_SESSION['id_funcionario']]);
$logs = $stmt->fetchAll();

$tituloPagina = 'Histórico - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('historico') ?> Meu Histórico de Ações</h1>

<div class="card" style="overflow-x:auto">
    <table style="width:100%">
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Ação</th>
                <th>Detalhes</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= formatarDataHoraPhp($log['data_hora']) ?></td>
                <td><?= htmlspecialchars($log['acao']) ?></td>
                <td><?= htmlspecialchars($log['detalhes'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?>
            <tr><td colspan="3" style="text-align:center; color:#777; padding:1rem">Nenhuma ação registrada ainda.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
