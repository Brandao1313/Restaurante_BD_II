<?php
require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');
exigirPerfil(['cliente']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['erro' => 'Método não permitido.']));
}

unset($_SESSION['id_mesa_atual']);

echo json_encode(['sucesso' => true]);
