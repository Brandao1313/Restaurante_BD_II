<?php
require_once __DIR__ . '/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Expira a sessão por inatividade
if (isset($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > SESSION_TIMEOUT) {
    $_SESSION = [];
    session_destroy();
}
$_SESSION['ultimo_acesso'] = time();

function usuarioLogado(): bool
{
    return isset($_SESSION['usuario_id'], $_SESSION['perfil']);
}

function perfilAtual(): ?string
{
    return $_SESSION['perfil'] ?? null;
}

/**
 * Interrompe a requisição com 401/403 caso o usuário não esteja logado
 * ou não possua um dos perfis permitidos. Usado nas APIs.
 */
function exigirPerfil(array $perfisPermitidos): void
{
    if (!usuarioLogado()) {
        http_response_code(401);
        header('Content-Type: application/json');
        die(json_encode(['erro' => 'Sessão não autenticada.']));
    }

    if (!in_array(perfilAtual(), $perfisPermitidos, true)) {
        http_response_code(403);
        header('Content-Type: application/json');
        die(json_encode(['erro' => 'Acesso negado para este perfil.']));
    }
}

/**
 * Redireciona páginas HTML para o login caso o usuário não tenha o perfil exigido.
 */
function exigirPerfilPagina(array $perfisPermitidos): void
{
    if (!usuarioLogado() || !in_array(perfilAtual(), $perfisPermitidos, true)) {
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }
}
