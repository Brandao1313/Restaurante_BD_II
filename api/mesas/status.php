<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
exigirPerfil(['cliente', 'garcom', 'administrador']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

$pdo = getConexao();

$sql = 'SELECT id_mesa, numero, status, capacidade FROM Mesas';
$parametros = [];

if (!empty($_GET['status'])) {
    $sql .= ' WHERE status = ?';
    $parametros[] = $_GET['status'];
}

$sql .= ' ORDER BY numero ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);

echo json_encode(['sucesso' => true, 'mesas' => $stmt->fetchAll()]);
