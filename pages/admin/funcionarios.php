<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';
exigirPerfilPagina(['administrador']);

$pdo = getConexao();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'salvar') {
            $idFuncionario = $_POST['id_funcionario'] !== '' ? (int) $_POST['id_funcionario'] : null;
            $nome = trim($_POST['nome'] ?? '');
            $cargo = trim($_POST['cargo'] ?? '');
            $salario = (float) str_replace(',', '.', $_POST['salario'] ?? '0');
            $email = trim($_POST['email'] ?? '');
            $perfil = $_POST['perfil'] ?? '';
            $senha = $_POST['senha'] ?? '';

            if ($nome === '' || $cargo === '' || $email === '' || !in_array($perfil, ['administrador', 'garcom'], true)) {
                throw new RuntimeException('Preencha todos os campos obrigatórios corretamente.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Email inválido.');
            }
            if ($idFuncionario === null && $senha === '') {
                throw new RuntimeException('Defina uma senha para o novo funcionário.');
            }

            if ($idFuncionario === null) {
                $stmt = $pdo->prepare(
                    'INSERT INTO Funcionarios (nome, cargo, salario, email, perfil, senha, ativo)
                     VALUES (?, ?, ?, ?, ?, ?, 1)'
                );
                $stmt->execute([$nome, $cargo, $salario, $email, $perfil, password_hash($senha, PASSWORD_DEFAULT)]);
                definirFlash('sucesso', 'Funcionário cadastrado com sucesso.');
            } else {
                if ($senha !== '') {
                    $stmt = $pdo->prepare(
                        'UPDATE Funcionarios SET nome=?, cargo=?, salario=?, email=?, perfil=?, senha=? WHERE id_funcionario=?'
                    );
                    $stmt->execute([$nome, $cargo, $salario, $email, $perfil, password_hash($senha, PASSWORD_DEFAULT), $idFuncionario]);
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE Funcionarios SET nome=?, cargo=?, salario=?, email=?, perfil=? WHERE id_funcionario=?'
                    );
                    $stmt->execute([$nome, $cargo, $salario, $email, $perfil, $idFuncionario]);
                }
                definirFlash('sucesso', 'Funcionário atualizado com sucesso.');
            }
        } elseif ($acao === 'alternar_status') {
            $idFuncionario = (int) ($_POST['id_funcionario'] ?? 0);
            $pdo->prepare('UPDATE Funcionarios SET ativo = NOT ativo WHERE id_funcionario = ?')->execute([$idFuncionario]);
            definirFlash('sucesso', 'Status do funcionário atualizado.');
        } elseif ($acao === 'excluir') {
            $idFuncionario = (int) ($_POST['id_funcionario'] ?? 0);
            $pdo->prepare('DELETE FROM Funcionarios WHERE id_funcionario = ?')->execute([$idFuncionario]);
            definirFlash('sucesso', 'Funcionário excluído com sucesso.');
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            definirFlash('erro', 'Não é possível concluir: email já cadastrado ou funcionário possui registros vinculados (pedidos, despesas). Inative-o em vez de excluir.');
        } else {
            definirFlash('erro', 'Erro ao salvar os dados.');
        }
    } catch (RuntimeException $e) {
        definirFlash('erro', $e->getMessage());
    }

    header('Location: ' . BASE_URL . '/pages/admin/funcionarios.php');
    exit;
}

$funcionarios = $pdo->query('SELECT * FROM Funcionarios ORDER BY nome ASC')->fetchAll();

$idEdicao = isset($_GET['editar']) ? (int) $_GET['editar'] : null;
$funcionarioEdicao = null;
if ($idEdicao) {
    $stmt = $pdo->prepare('SELECT * FROM Funcionarios WHERE id_funcionario = ?');
    $stmt->execute([$idEdicao]);
    $funcionarioEdicao = $stmt->fetch();
}

$tituloPagina = 'Funcionários - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('funcionarios') ?> Funcionários</h1>
<?php renderizarFlash(); ?>

<div class="card" style="margin-bottom:1.5rem">
    <h3><?= $funcionarioEdicao ? 'Editar Funcionário' : 'Novo Funcionário' ?></h3>
    <form method="POST">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id_funcionario" value="<?= htmlspecialchars($funcionarioEdicao['id_funcionario'] ?? '') ?>">
        <div class="grid grid-cards">
            <div class="campo">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($funcionarioEdicao['nome'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="cargo">Cargo</label>
                <input type="text" id="cargo" name="cargo" required value="<?= htmlspecialchars($funcionarioEdicao['cargo'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="salario">Salário (R$)</label>
                <input type="number" step="0.01" min="0" id="salario" name="salario" required value="<?= htmlspecialchars($funcionarioEdicao['salario'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($funcionarioEdicao['email'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="perfil">Perfil</label>
                <select id="perfil" name="perfil" required>
                    <option value="garcom" <?= ($funcionarioEdicao['perfil'] ?? '') === 'garcom' ? 'selected' : '' ?>>Garçom</option>
                    <option value="administrador" <?= ($funcionarioEdicao['perfil'] ?? '') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                </select>
            </div>
            <div class="campo">
                <label for="senha">Senha <?= $funcionarioEdicao ? '(deixe em branco para manter)' : '' ?></label>
                <input type="password" id="senha" name="senha" <?= $funcionarioEdicao ? '' : 'required' ?>>
            </div>
        </div>
        <button type="submit" class="btn"><?= $funcionarioEdicao ? 'Salvar Alterações' : 'Cadastrar' ?></button>
        <?php if ($funcionarioEdicao): ?>
            <a class="btn btn-secundario" href="<?= BASE_URL ?>/pages/admin/funcionarios.php">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="card" style="overflow-x:auto">
    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="text-align:left; border-bottom:2px solid var(--cor-borda)">
                <th style="padding:0.5rem">Nome</th>
                <th style="padding:0.5rem">Cargo</th>
                <th style="padding:0.5rem">Salário</th>
                <th style="padding:0.5rem">Email</th>
                <th style="padding:0.5rem">Perfil</th>
                <th style="padding:0.5rem">Status</th>
                <th style="padding:0.5rem">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($funcionarios as $f): ?>
            <tr style="border-bottom:1px solid var(--cor-borda)">
                <td style="padding:0.5rem"><?= htmlspecialchars($f['nome']) ?></td>
                <td style="padding:0.5rem"><?= htmlspecialchars($f['cargo']) ?></td>
                <td style="padding:0.5rem"><?= formatarMoedaPhp($f['salario']) ?></td>
                <td style="padding:0.5rem"><?= htmlspecialchars($f['email'] ?? '-') ?></td>
                <td style="padding:0.5rem"><?= htmlspecialchars($f['perfil'] ?? '-') ?></td>
                <td style="padding:0.5rem"><?= $f['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                <td style="padding:0.5rem; display:flex; gap:0.4rem; flex-wrap:wrap">
                    <a class="btn btn-secundario" href="?editar=<?= $f['id_funcionario'] ?>">Editar</a>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="acao" value="alternar_status">
                        <input type="hidden" name="id_funcionario" value="<?= $f['id_funcionario'] ?>">
                        <button type="submit" class="btn btn-secundario"><?= $f['ativo'] ? 'Inativar' : 'Ativar' ?></button>
                    </form>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Excluir este funcionário?');">
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id_funcionario" value="<?= $f['id_funcionario'] ?>">
                        <button type="submit" class="btn" style="background:var(--cor-ocupada)">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
