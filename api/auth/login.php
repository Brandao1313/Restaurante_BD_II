<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$dados = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$email = trim($dados['email'] ?? '');
$senha = (string) ($dados['senha'] ?? '');

if ($email === '' || $senha === '') {
    http_response_code(400);
    die(json_encode(['erro' => 'Informe email e senha.']));
}

$pdo = getConexao();

// 1. Tenta autenticar como funcionário (administrador ou garçom)
$stmt = $pdo->prepare(
    'SELECT id_funcionario, nome, senha, perfil, ativo FROM Funcionarios WHERE email = ? LIMIT 1'
);
$stmt->execute([$email]);
$funcionario = $stmt->fetch();

if ($funcionario && $funcionario['senha'] && password_verify($senha, $funcionario['senha'])) {
    if (!$funcionario['ativo']) {
        http_response_code(403);
        die(json_encode(['erro' => 'Usuário inativo.']));
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $funcionario['id_funcionario'];
    $_SESSION['id_funcionario'] = $funcionario['id_funcionario'];
    $_SESSION['nome'] = $funcionario['nome'];
    $_SESSION['perfil'] = $funcionario['perfil'];

    registrarLog($pdo, $funcionario['id_funcionario'], null, 'login', 'Login realizado');

    die(json_encode([
        'sucesso' => true,
        'perfil' => $funcionario['perfil'],
        'nome' => $funcionario['nome'],
        'redirecionar' => $funcionario['perfil'] === 'administrador'
            ? '/pages/admin/dashboard.php'
            : '/pages/garcom/dashboard.php',
    ]));
}

// 2. Tenta autenticar como cliente
$stmt = $pdo->prepare(
    'SELECT uc.id_usuario, uc.id_cliente, uc.senha, uc.ativo, c.nome
     FROM UsuariosClientes uc
     JOIN Clientes c ON c.id_cliente = uc.id_cliente
     WHERE uc.email = ? LIMIT 1'
);
$stmt->execute([$email]);
$cliente = $stmt->fetch();

if ($cliente && password_verify($senha, $cliente['senha'])) {
    if (!$cliente['ativo']) {
        http_response_code(403);
        die(json_encode(['erro' => 'Usuário inativo.']));
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $cliente['id_usuario'];
    $_SESSION['id_cliente'] = $cliente['id_cliente'];
    $_SESSION['nome'] = $cliente['nome'];
    $_SESSION['perfil'] = 'cliente';

    registrarLog($pdo, null, $cliente['id_cliente'], 'login', 'Login realizado');

    die(json_encode([
        'sucesso' => true,
        'perfil' => 'cliente',
        'nome' => $cliente['nome'],
        'redirecionar' => '/pages/cliente/dashboard.php',
    ]));
}

http_response_code(401);
echo json_encode(['erro' => 'Email ou senha inválidos.']);

function registrarLog(PDO $pdo, ?int $idFuncionario, ?int $idCliente, string $acao, string $detalhes): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO LogsAuditoria (id_funcionario, id_cliente, acao, detalhes, ip) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$idFuncionario, $idCliente, $acao, $detalhes, $_SERVER['REMOTE_ADDR'] ?? null]);
}
