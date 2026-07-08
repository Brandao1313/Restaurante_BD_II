<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$nome = trim($dados['nome'] ?? '');
$email = trim($dados['email'] ?? '');
$senha = (string) ($dados['senha'] ?? '');

if (mb_strlen($nome) < 3) {
    http_response_code(400);
    die(json_encode(['erro' => 'Informe seu nome completo.']));
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    die(json_encode(['erro' => 'Email inválido.']));
}
if (mb_strlen($senha) < 6) {
    http_response_code(400);
    die(json_encode(['erro' => 'A senha deve ter no mínimo 6 caracteres.']));
}

$pdo = getConexao();

$stmt = $pdo->prepare('SELECT id_usuario FROM UsuariosClientes WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    die(json_encode(['erro' => 'Este email já está cadastrado.']));
}

try {
    $pdo->beginTransaction();

    $pdo->prepare('INSERT INTO Clientes (nome) VALUES (?)')->execute([$nome]);
    $idCliente = (int) $pdo->lastInsertId();

    $pdo->prepare('INSERT INTO UsuariosClientes (id_cliente, email, senha, ativo) VALUES (?, ?, ?, 1)')
        ->execute([$idCliente, $email, password_hash($senha, PASSWORD_DEFAULT)]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code($e->getCode() === '23000' ? 409 : 500);
    die(json_encode(['erro' => $e->getCode() === '23000' ? 'Este email já está cadastrado.' : 'Erro ao cadastrar. Tente novamente.']));
}

http_response_code(201);
echo json_encode(['sucesso' => true, 'mensagem' => 'Cadastro realizado com sucesso.']);
