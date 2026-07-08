<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
exigirPerfilPagina(['cliente']);

$pdo = getConexao();
$stmt = $pdo->prepare(
    'SELECT c.nome, uc.email FROM Clientes c
     JOIN UsuariosClientes uc ON uc.id_cliente = c.id_cliente
     WHERE c.id_cliente = ?'
);
$stmt->execute([$_SESSION['id_cliente']]);
$cliente = $stmt->fetch();

$tituloPagina = 'Meus Dados - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('perfil') ?> Meus Dados</h1>

<div class="card" style="max-width:480px">
    <div id="mensagem-perfil"></div>
    <form id="form-perfil">
        <div class="campo">
            <label for="nome">Nome completo</label>
            <div class="campo-com-icone">
                <?= icone('perfil', 15) ?>
                <input type="text" id="nome" name="nome" required minlength="3" value="<?= htmlspecialchars($cliente['nome']) ?>">
            </div>
        </div>
        <div class="campo">
            <label for="email">Email</label>
            <div class="campo-com-icone">
                <?= icone('email', 15) ?>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($cliente['email']) ?>">
            </div>
        </div>
        <hr style="border-color:var(--cor-borda); margin:1.25rem 0">
        <h3 style="margin-top:0">Alterar senha (opcional)</h3>
        <div class="campo">
            <label for="senha_atual">Senha atual</label>
            <div class="campo-com-icone">
                <?= icone('senha', 15) ?>
                <input type="password" id="senha_atual" name="senha_atual" autocomplete="current-password">
            </div>
        </div>
        <div class="campo">
            <label for="nova_senha">Nova senha</label>
            <div class="campo-com-icone">
                <?= icone('senha', 15) ?>
                <input type="password" id="nova_senha" name="nova_senha" minlength="6" autocomplete="new-password">
            </div>
        </div>
        <button type="submit" class="btn" style="width:100%">Salvar Alterações</button>
    </form>
</div>

<script>
    document.getElementById('form-perfil').addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const mensagem = document.getElementById('mensagem-perfil');
        mensagem.innerHTML = '';

        const nome = document.getElementById('nome').value.trim();
        const email = document.getElementById('email').value.trim();
        const senhaAtual = document.getElementById('senha_atual').value;
        const novaSenha = document.getElementById('nova_senha').value;

        if (novaSenha && !senhaAtual) {
            mensagem.innerHTML = '<div class="mensagem-erro">Informe a senha atual para definir uma nova senha.</div>';
            return;
        }

        try {
            await apiFetch('<?= BASE_URL ?>/api/clientes/atualizar.php', {
                method: 'POST',
                body: { nome, email, senha_atual: senhaAtual, nova_senha: novaSenha },
            });
            mensagem.innerHTML = '<div class="mensagem-sucesso">Dados atualizados com sucesso!</div>';
            document.getElementById('senha_atual').value = '';
            document.getElementById('nova_senha').value = '';
        } catch (erro) {
            mensagem.innerHTML = `<div class="mensagem-erro">${erro.message}</div>`;
        }
    });
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
