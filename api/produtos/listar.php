<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');
exigirPerfil(['cliente', 'garcom', 'administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$pdo = getConexao();

$sql = 'SELECT p.id_produto, p.nome, p.preco, p.disponivel, p.imagem, p.id_categoria,
               c.nome AS nome_categoria, c.ordem
        FROM Produtos p
        JOIN Categorias c ON c.id_categoria = p.id_categoria';
$parametros = [];

if (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
    if (!is_numeric($_GET['categoria'])) {
        http_response_code(400);
        die(json_encode(['erro' => 'Categoria inválida.']));
    }
    $sql .= ' WHERE p.id_categoria = ?';
    $parametros[] = (int) $_GET['categoria'];
}

$sql .= ' ORDER BY c.ordem ASC, p.nome ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$produtos = $stmt->fetchAll();

foreach ($produtos as &$produto) {
    $produto['disponivel'] = (bool) $produto['disponivel'];
    $produto['preco'] = (float) $produto['preco'];
    $produto['url_imagem'] = urlImagemProduto($produto['imagem'], $produto['nome']);
}

echo json_encode(['sucesso' => true, 'produtos' => $produtos]);
