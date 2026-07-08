<?php
/**
 * Mensagens flash (sucesso/erro) exibidas uma única vez após um redirect.
 * Requer que a sessão já tenha sido iniciada (config/session.php).
 */
function definirFlash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function obterFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function renderizarFlash(): void
{
    $flash = obterFlash();
    if (!$flash) {
        return;
    }
    $classe = $flash['tipo'] === 'sucesso' ? 'mensagem-sucesso' : 'mensagem-erro';
    echo '<div class="' . $classe . '">' . htmlspecialchars($flash['mensagem']) . '</div>';
}
