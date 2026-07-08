<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
exigirPerfil(['cliente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? [];

$nome = trim($dados['nome'] ?? '');
$email = trim($dados['email'] ?? '');
$senhaAtual = (string) ($dados['senha_atual'] ?? '');
$novaSenha = (string) ($dados['nova_senha'] ?? '');

if (mb_strlen($nome) < 3) {
    http_response_code(400);
    die(json_encode(['erro' => 'Informe seu nome completo.']));
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    die(json_encode(['erro' => 'Email inválido.']));
}
if ($novaSenha !== '' && mb_strlen($novaSenha) < 6) {
    http_response_code(400);
    die(json_encode(['erro' => 'A nova senha deve ter no mínimo 6 caracteres.']));
}

$pdo = getConexao();
$idCliente = (int) $_SESSION['id_cliente'];

$stmt = $pdo->prepare('SELECT id_usuario, senha FROM UsuariosClientes WHERE id_cliente = ?');
$stmt->execute([$idCliente]);
$usuario = $stmt->fetch();

if (!$usuario) {
    http_response_code(404);
    die(json_encode(['erro' => 'Usuário não encontrado.']));
}

$stmt = $pdo->prepare('SELECT id_usuario FROM UsuariosClientes WHERE email = ? AND id_usuario != ?');
$stmt->execute([$email, $usuario['id_usuario']]);
if ($stmt->fetch()) {
    http_response_code(409);
    die(json_encode(['erro' => 'Este email já está em uso por outra conta.']));
}

$novoHash = null;
if ($novaSenha !== '') {
    if (!password_verify($senhaAtual, $usuario['senha'])) {
        http_response_code(403);
        die(json_encode(['erro' => 'Senha atual incorreta.']));
    }
    $novoHash = password_hash($novaSenha, PASSWORD_DEFAULT);
}

$pdo->beginTransaction();
$pdo->prepare('UPDATE Clientes SET nome = ? WHERE id_cliente = ?')->execute([$nome, $idCliente]);

if ($novoHash !== null) {
    $pdo->prepare('UPDATE UsuariosClientes SET email = ?, senha = ? WHERE id_usuario = ?')
        ->execute([$email, $novoHash, $usuario['id_usuario']]);
} else {
    $pdo->prepare('UPDATE UsuariosClientes SET email = ? WHERE id_usuario = ?')
        ->execute([$email, $usuario['id_usuario']]);
}
$pdo->commit();

$_SESSION['nome'] = $nome;

echo json_encode(['sucesso' => true, 'mensagem' => 'Perfil atualizado com sucesso.']);
