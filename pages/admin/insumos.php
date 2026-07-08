<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';
exigirPerfilPagina(['administrador']);

$pdo = getConexao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'salvar_insumo') {
            $idInsumo = $_POST['id_insumo'] !== '' ? (int) $_POST['id_insumo'] : null;
            $nome = trim($_POST['nome'] ?? '');
            $quantidade = (float) str_replace(',', '.', $_POST['quantidade'] ?? '0');
            $unidade = trim($_POST['unidade'] ?? '');
            $quantidadeMinima = (float) str_replace(',', '.', $_POST['quantidade_minima'] ?? '0');

            if ($nome === '' || $unidade === '' || $quantidade < 0 || $quantidadeMinima < 0) {
                throw new RuntimeException('Preencha todos os campos corretamente.');
            }

            if ($idInsumo === null) {
                $pdo->prepare('INSERT INTO Insumos (nome, quantidade, unidade, quantidade_minima) VALUES (?, ?, ?, ?)')
                    ->execute([$nome, $quantidade, $unidade, $quantidadeMinima]);
                definirFlash('sucesso', 'Insumo cadastrado com sucesso.');
            } else {
                $pdo->prepare('UPDATE Insumos SET nome=?, quantidade=?, unidade=?, quantidade_minima=? WHERE id_insumo=?')
                    ->execute([$nome, $quantidade, $unidade, $quantidadeMinima, $idInsumo]);
                definirFlash('sucesso', 'Insumo atualizado com sucesso.');
            }
        } elseif ($acao === 'excluir_insumo') {
            $idInsumo = (int) ($_POST['id_insumo'] ?? 0);
            $pdo->prepare('DELETE FROM Insumos WHERE id_insumo = ?')->execute([$idInsumo]);
            definirFlash('sucesso', 'Insumo excluído com sucesso.');
        } elseif ($acao === 'vincular') {
            $idProduto = (int) ($_POST['id_produto'] ?? 0);
            $idInsumo = (int) ($_POST['id_insumo_vinculo'] ?? 0);
            $quantidadeNecessaria = (float) str_replace(',', '.', $_POST['quantidade_necessaria'] ?? '0');

            if ($idProduto <= 0 || $idInsumo <= 0 || $quantidadeNecessaria <= 0) {
                throw new RuntimeException('Selecione produto, insumo e uma quantidade válida.');
            }

            $pdo->prepare('INSERT INTO ProdutoInsumo (id_produto, id_insumo, quantidade_necessaria) VALUES (?, ?, ?)')
                ->execute([$idProduto, $idInsumo, $quantidadeNecessaria]);
            definirFlash('sucesso', 'Vínculo criado com sucesso.');
        } elseif ($acao === 'desvincular') {
            $idProdutoInsumo = (int) ($_POST['id_produto_insumo'] ?? 0);
            $pdo->prepare('DELETE FROM ProdutoInsumo WHERE id_produto_insumo = ?')->execute([$idProdutoInsumo]);
            definirFlash('sucesso', 'Vínculo removido com sucesso.');
        }
    } catch (PDOException $e) {
        definirFlash('erro', 'Não é possível excluir: insumo está vinculado a produtos do cardápio.');
    } catch (RuntimeException $e) {
        definirFlash('erro', $e->getMessage());
    }

    header('Location: ' . BASE_URL . '/pages/admin/insumos.php');
    exit;
}

$insumos = $pdo->query('SELECT * FROM Insumos ORDER BY nome ASC')->fetchAll();
$produtos = $pdo->query('SELECT id_produto, nome FROM Produtos ORDER BY nome ASC')->fetchAll();
$vinculos = $pdo->query(
    'SELECT pi.id_produto_insumo, pi.quantidade_necessaria, p.nome AS nome_produto, i.nome AS nome_insumo, i.unidade
     FROM ProdutoInsumo pi
     JOIN Produtos p ON p.id_produto = pi.id_produto
     JOIN Insumos i ON i.id_insumo = pi.id_insumo
     ORDER BY p.nome ASC'
)->fetchAll();

$idEdicao = isset($_GET['editar']) ? (int) $_GET['editar'] : null;
$insumoEdicao = null;
if ($idEdicao) {
    $stmt = $pdo->prepare('SELECT * FROM Insumos WHERE id_insumo = ?');
    $stmt->execute([$idEdicao]);
    $insumoEdicao = $stmt->fetch();
}

$tituloPagina = 'Insumos - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('insumos') ?> Insumos</h1>
<?php renderizarFlash(); ?>

<div class="card" style="margin-bottom:1.5rem">
    <h3><?= $insumoEdicao ? 'Editar Insumo' : 'Novo Insumo' ?></h3>
    <form method="POST">
        <input type="hidden" name="acao" value="salvar_insumo">
        <input type="hidden" name="id_insumo" value="<?= htmlspecialchars($insumoEdicao['id_insumo'] ?? '') ?>">
        <div class="grid grid-cards">
            <div class="campo">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($insumoEdicao['nome'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="quantidade">Quantidade em estoque</label>
                <input type="number" step="0.01" min="0" id="quantidade" name="quantidade" required value="<?= htmlspecialchars($insumoEdicao['quantidade'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="unidade">Unidade (kg, l, un...)</label>
                <input type="text" id="unidade" name="unidade" required value="<?= htmlspecialchars($insumoEdicao['unidade'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="quantidade_minima">Quantidade mínima (alerta)</label>
                <input type="number" step="0.01" min="0" id="quantidade_minima" name="quantidade_minima" required value="<?= htmlspecialchars($insumoEdicao['quantidade_minima'] ?? '') ?>">
            </div>
        </div>
        <button type="submit" class="btn"><?= $insumoEdicao ? 'Salvar Alterações' : 'Cadastrar' ?></button>
        <?php if ($insumoEdicao): ?>
            <a class="btn btn-secundario" href="<?= BASE_URL ?>/pages/admin/insumos.php">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="card" style="overflow-x:auto; margin-bottom:1.5rem">
    <h3>Estoque atual</h3>
    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align:left; border-bottom:2px solid var(--cor-borda)">
                <th style="padding:0.5rem">Nome</th>
                <th style="padding:0.5rem">Quantidade</th>
                <th style="padding:0.5rem">Mínima</th>
                <th style="padding:0.5rem">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($insumos as $i): ?>
            <tr style="border-bottom:1px solid var(--cor-borda); <?= $i['quantidade'] <= $i['quantidade_minima'] ? 'background:#fdecea' : '' ?>">
                <td style="padding:0.5rem"><?= htmlspecialchars($i['nome']) ?></td>
                <td style="padding:0.5rem"><?= htmlspecialchars($i['quantidade']) ?> <?= htmlspecialchars($i['unidade']) ?></td>
                <td style="padding:0.5rem"><?= htmlspecialchars($i['quantidade_minima']) ?> <?= htmlspecialchars($i['unidade']) ?></td>
                <td style="padding:0.5rem; display:flex; gap:0.4rem; flex-wrap:wrap">
                    <a class="btn btn-secundario" href="?editar=<?= $i['id_insumo'] ?>">Editar</a>
                    <form method="POST" onsubmit="return confirm('Excluir este insumo?');">
                        <input type="hidden" name="acao" value="excluir_insumo">
                        <input type="hidden" name="id_insumo" value="<?= $i['id_insumo'] ?>">
                        <button type="submit" class="btn" style="background:var(--cor-ocupada)">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" style="margin-bottom:1.5rem">
    <h3>Vincular insumo a um produto</h3>
    <form method="POST">
        <input type="hidden" name="acao" value="vincular">
        <div class="grid grid-cards">
            <div class="campo">
                <label for="id_produto">Produto</label>
                <select id="id_produto" name="id_produto" required>
                    <?php foreach ($produtos as $p): ?>
                        <option value="<?= $p['id_produto'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="id_insumo_vinculo">Insumo</label>
                <select id="id_insumo_vinculo" name="id_insumo_vinculo" required>
                    <?php foreach ($insumos as $i): ?>
                        <option value="<?= $i['id_insumo'] ?>"><?= htmlspecialchars($i['nome']) ?> (<?= htmlspecialchars($i['unidade']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="quantidade_necessaria">Quantidade necessária por unidade vendida</label>
                <input type="number" step="0.01" min="0.01" id="quantidade_necessaria" name="quantidade_necessaria" required>
            </div>
        </div>
        <button type="submit" class="btn">Vincular</button>
    </form>
</div>

<div class="card" style="overflow-x:auto">
    <h3>Vínculos cadastrados</h3>
    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align:left; border-bottom:2px solid var(--cor-borda)">
                <th style="padding:0.5rem">Produto</th>
                <th style="padding:0.5rem">Insumo</th>
                <th style="padding:0.5rem">Qtd. necessária</th>
                <th style="padding:0.5rem">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($vinculos as $v): ?>
            <tr style="border-bottom:1px solid var(--cor-borda)">
                <td style="padding:0.5rem"><?= htmlspecialchars($v['nome_produto']) ?></td>
                <td style="padding:0.5rem"><?= htmlspecialchars($v['nome_insumo']) ?></td>
                <td style="padding:0.5rem"><?= htmlspecialchars($v['quantidade_necessaria']) ?> <?= htmlspecialchars($v['unidade']) ?></td>
                <td style="padding:0.5rem">
                    <form method="POST" onsubmit="return confirm('Remover este vínculo?');">
                        <input type="hidden" name="acao" value="desvincular">
                        <input type="hidden" name="id_produto_insumo" value="<?= $v['id_produto_insumo'] ?>">
                        <button type="submit" class="btn" style="background:var(--cor-ocupada)">Remover</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
