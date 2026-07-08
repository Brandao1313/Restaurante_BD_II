<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/icons.php';

if (usuarioLogado()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Restaurante Bom Sabor</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232C1810' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M3 2v7c0 1.1.9 2 2 2h1v11'/><path d='M6 2v6'/><path d='M9 2v6'/><path d='M18 2c-2 3-2 5-2 7 0 2 1 3 2 3v10'/></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css?v=<?= filemtime(__DIR__ . '/../assets/css/responsive.css') ?>">
</head>
<body>
    <div class="login-wrapper">
        <div class="card login-card">
            <h1><?= icone('cardapio', 28) ?> Bom Sabor</h1>
            <div id="mensagem">
                <?php if (($_GET['cadastro'] ?? '') === 'ok'): ?>
                    <div class="mensagem-sucesso">Cadastro realizado com sucesso! Faça login para continuar.</div>
                <?php endif; ?>
            </div>
            <form id="form-login">
                <div class="campo">
                    <label for="email">Email</label>
                    <div class="campo-com-icone">
                        <?= icone('email', 15) ?>
                        <input type="email" id="email" name="email" required autocomplete="username">
                    </div>
                </div>
                <div class="campo">
                    <label for="senha">Senha</label>
                    <div class="campo-com-icone">
                        <?= icone('senha', 15) ?>
                        <input type="password" id="senha" name="senha" required autocomplete="current-password">
                    </div>
                </div>
                <button type="submit" class="btn" style="width:100%">Entrar</button>
            </form>
            <p style="text-align:center; margin-top:1rem">
                <a href="<?= BASE_URL ?>/pages/cliente/cadastro.php">Ainda não tem conta? Cadastre-se</a>
            </p>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/assets/js/utils.js"></script>
    <script>
        document.getElementById('form-login').addEventListener('submit', async (evento) => {
            evento.preventDefault();
            const mensagem = document.getElementById('mensagem');
            mensagem.innerHTML = '';

            const email = document.getElementById('email').value.trim();
            const senha = document.getElementById('senha').value;

            try {
                const resposta = await apiFetch('<?= BASE_URL ?>/api/auth/login.php', {
                    method: 'POST',
                    body: { email, senha },
                });
                window.location.href = '<?= BASE_URL ?>' + resposta.redirecionar;
            } catch (erro) {
                mensagem.innerHTML = `<div class="mensagem-erro">${erro.message}</div>`;
            }
        });
    </script>
</body>
</html>
