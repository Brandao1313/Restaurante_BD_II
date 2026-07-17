<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
exigirPerfil(['cozinheiro', 'administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$filtro = $_GET['filtro'] ?? '';

$sql = 'SELECT id_insumo, nome, quantidade, unidade, quantidade_minima, ultima_atualizacao FROM Insumos';
if ($filtro === 'em_falta') {
    $sql .= ' WHERE quantidade <= 0';
} elseif ($filtro === 'abaixo_minimo') {
    $sql .= ' WHERE quantidade <= quantidade_minima';
}
$sql .= ' ORDER BY nome ASC';

$pdo = getConexao();
$insumos = $pdo->query($sql)->fetchAll();

foreach ($insumos as &$insumo) {
    $insumo['quantidade'] = (float) $insumo['quantidade'];
    $insumo['quantidade_minima'] = (float) $insumo['quantidade_minima'];
    $insumo['em_falta'] = $insumo['quantidade'] <= 0;
    $insumo['abaixo_minimo'] = $insumo['quantidade'] <= $insumo['quantidade_minima'];
}

echo json_encode(['sucesso' => true, 'insumos' => $insumos]);
