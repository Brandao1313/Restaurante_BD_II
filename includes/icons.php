<?php
/**
 * Ícones via Font Awesome (carregado por CDN em includes/header.php).
 * Uso: <?= icone('cardapio') ?>
 */
function icone(string $nome, int $tamanho = 16): string
{
    $classes = [
        'cardapio' => 'fa-solid fa-utensils',
        'pedidos' => 'fa-solid fa-receipt',
        'reservas' => 'fa-solid fa-calendar-days',
        'perfil' => 'fa-solid fa-user',
        'mesas' => 'fa-solid fa-chair',
        'comandas' => 'fa-solid fa-clipboard-list',
        'historico' => 'fa-solid fa-clock-rotate-left',
        'dashboard' => 'fa-solid fa-chart-simple',
        'funcionarios' => 'fa-solid fa-users',
        'insumos' => 'fa-solid fa-box-open',
        'despesas' => 'fa-solid fa-file-invoice-dollar',
        'financeiro' => 'fa-solid fa-chart-line',
        'relatorios' => 'fa-solid fa-file-export',
        'sair' => 'fa-solid fa-right-from-bracket',
        'busca' => 'fa-solid fa-magnifying-glass',
        'sino' => 'fa-solid fa-bell',
        'menu' => 'fa-solid fa-bars',
        'email' => 'fa-solid fa-envelope',
        'senha' => 'fa-solid fa-lock',
        'editar' => 'fa-solid fa-pen',
        'excluir' => 'fa-solid fa-trash',
    ];

    $classe = $classes[$nome] ?? 'fa-solid fa-circle';

    return '<i class="' . $classe . ' icone-fa" style="font-size:' . $tamanho . 'px"></i>';
}
