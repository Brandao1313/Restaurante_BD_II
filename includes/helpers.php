<?php
/**
 * Funções utilitárias compartilhadas entre as páginas PHP (server-side).
 */

function formatarMoedaPhp(?float $valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function formatarDataPhp(?string $data): string
{
    if (!$data) {
        return '-';
    }
    $timestamp = strtotime($data);
    return $timestamp ? date('d/m/Y', $timestamp) : '-';
}

function formatarDataHoraPhp(?string $dataHora): string
{
    if (!$dataHora) {
        return '-';
    }
    $timestamp = strtotime($dataHora);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
}

/**
 * Determina o turno ('Almoço' ou 'Jantar') de um horário HH:MM:SS, ou null se fora do expediente.
 */
function turnoDoHorario(string $hora): ?string
{
    if ($hora >= TURNO_ALMOCO_INICIO && $hora <= TURNO_ALMOCO_FIM) {
        return 'Almoço';
    }
    if ($hora >= TURNO_JANTAR_INICIO && $hora <= TURNO_JANTAR_FIM) {
        return 'Jantar';
    }
    return null;
}

/**
 * Resolve a URL de imagem de um produto: usa o valor cadastrado em `imagem`
 * (caminho local ou URL externa), ou gera um placeholder local determinístico
 * (sem depender de serviços externos) quando não há imagem cadastrada.
 */
function urlImagemProduto(?string $imagem, string $nomeProduto, int $largura = 300, int $altura = 225): string
{
    if ($imagem) {
        return str_starts_with($imagem, 'http') ? $imagem : BASE_URL . '/' . ltrim($imagem, '/');
    }

    return BASE_URL . '/assets/img/placeholder.php?nome=' . urlencode($nomeProduto) . '&w=' . $largura . '&h=' . $altura;
}
