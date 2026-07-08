<?php
/**
 * Espera opcionalmente $tituloPagina definido antes do include.
 * Requer que config/session.php já tenha sido incluído pela página chamadora.
 */
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/helpers.php';

$perfil = perfilAtual();

$menus = [
    'cliente' => [
        'Cardápio' => ['cardapio', '/pages/cliente/cardapio.php'],
        'Meus Pedidos' => ['pedidos', '/pages/cliente/meus-pedidos.php'],
        'Reservas' => ['reservas', '/pages/cliente/reservas.php'],
        'Perfil' => ['perfil', '/pages/cliente/perfil.php'],
    ],
    'garcom' => [
        'Mesas' => ['mesas', '/pages/garcom/mesas.php'],
        'Comandas' => ['comandas', '/pages/garcom/comandas.php'],
        'Histórico' => ['historico', '/pages/garcom/historico.php'],
    ],
    'administrador' => [
        'Dashboard' => ['dashboard', '/pages/admin/dashboard.php'],
        'Funcionários' => ['funcionarios', '/pages/admin/funcionarios.php'],
        'Produtos' => ['cardapio', '/pages/admin/produtos.php'],
        'Insumos' => ['insumos', '/pages/admin/insumos.php'],
        'Mesas' => ['mesas', '/pages/admin/mesas.php'],
        'Pedidos' => ['pedidos', '/pages/admin/pedidos.php'],
        'Despesas' => ['despesas', '/pages/admin/despesas.php'],
        'Financeiro' => ['financeiro', '/pages/admin/financeiro.php'],
        'Relatórios' => ['relatorios', '/pages/admin/relatorios.php'],
    ],
];

$nomeUsuario = $_SESSION['nome'] ?? '';
$inicialAvatar = mb_strtoupper(mb_substr($nomeUsuario !== '' ? $nomeUsuario : '?', 0, 1));
$caminhoAtual = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina ?? 'Restaurante Bom Sabor') ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232C1810' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M3 2v7c0 1.1.9 2 2 2h1v11'/><path d='M6 2v6'/><path d='M9 2v6'/><path d='M18 2c-2 3-2 5-2 7 0 2 1 3 2 3v10'/></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css?v=<?= filemtime(__DIR__ . '/../assets/css/responsive.css') ?>">
    <script src="<?= BASE_URL ?>/assets/js/utils.js?v=<?= filemtime(__DIR__ . '/../assets/js/utils.js') ?>"></script>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo"><?= icone('cardapio', 22) ?> <span>Bom Sabor</span></div>
        <nav class="sidebar-nav">
            <?php foreach ($menus[$perfil] ?? [] as $rotulo => [$chaveIcone, $rota]): ?>
                <a href="<?= BASE_URL . $rota ?>" class="<?= $caminhoAtual === BASE_URL . $rota ? 'ativo' : '' ?>">
                    <?= icone($chaveIcone, 18) ?> <span><?= htmlspecialchars($rotulo) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <button type="button" class="sidebar-sair" onclick="fazerLogout()"><?= icone('sair', 18) ?> <span>Sair</span></button>
    </aside>

    <div class="app-content">
        <header class="topo-header">
            <button type="button" class="botao-hamburguer" onclick="document.getElementById('sidebar').classList.toggle('aberta')">
                <?= icone('menu', 20) ?>
            </button>
            <label class="busca-header">
                <?= icone('busca', 14) ?>
                <input type="text" placeholder="Pesquisar...">
            </label>
            <div class="topo-header-direita">
                <button type="button" class="botao-notificacao"><?= icone('sino', 18) ?></button>
                <div class="avatar-usuario" title="<?= htmlspecialchars($nomeUsuario) ?>"><?= htmlspecialchars($inicialAvatar) ?></div>
                <span class="nome-usuario"><?= htmlspecialchars($nomeUsuario) ?></span>
            </div>
        </header>
        <main class="container">
