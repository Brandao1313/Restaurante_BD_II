<?php
require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

if (usuarioLogado()) {
    echo json_encode([
        'autenticado' => true,
        'perfil' => $_SESSION['perfil'],
        'nome' => $_SESSION['nome'] ?? null,
    ]);
} else {
    echo json_encode(['autenticado' => false]);
}
