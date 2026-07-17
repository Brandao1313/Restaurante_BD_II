<?php
require_once __DIR__ . '/config/session.php';

if (usuarioLogado()) {
    switch (perfilAtual()) {
        case 'administrador':
            header('Location: ' . BASE_URL . '/pages/admin/dashboard.php');
            break;
        case 'garcom':
            header('Location: ' . BASE_URL . '/pages/garcom/dashboard.php');
            break;
        case 'cozinheiro':
            header('Location: ' . BASE_URL . '/pages/cozinheiro/dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . '/pages/cliente/dashboard.php');
    }
    exit;
}

header('Location: ' . BASE_URL . '/pages/login.php');
exit;
