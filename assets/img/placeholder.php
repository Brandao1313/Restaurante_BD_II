<?php
/**
 * Gera uma imagem placeholder (SVG) determinística para produtos sem foto cadastrada.
 * Não depende de nenhum serviço externo (offline-safe) — a cor é derivada do nome do
 * produto, então o mesmo prato sempre recebe a mesma cor.
 * Uso: assets/img/placeholder.php?nome=Nome+do+Prato&w=300&h=200
 */
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=604800');

$nome = trim($_GET['nome'] ?? 'Prato');
$largura = max(60, min(1200, (int) ($_GET['w'] ?? 300)));
$altura = max(60, min(1200, (int) ($_GET['h'] ?? 200)));

// Paleta de tons terrosos, para manter consistência com a identidade visual do restaurante.
$paleta = ['#7a2e2e', '#b8860b', '#6b7a4a', '#a0522d', '#8d6e63', '#4e6151', '#c08552', '#5a1f1f'];
$indice = hexdec(substr(md5($nome), 0, 6)) % count($paleta);
$cor = $paleta[$indice];

$iniciais = mb_strtoupper(mb_substr($nome, 0, 1));
$palavras = preg_split('/\s+/', $nome);
if (count($palavras) > 1) {
    $iniciais .= mb_strtoupper(mb_substr($palavras[1], 0, 1));
}

$fontSize = (int) round(min($largura, $altura) * 0.35);
$nomeEscapado = htmlspecialchars($nome, ENT_QUOTES | ENT_XML1);
$iniciaisEscapadas = htmlspecialchars($iniciais, ENT_QUOTES | ENT_XML1);
?>
<svg xmlns="http://www.w3.org/2000/svg" width="<?= $largura ?>" height="<?= $altura ?>" viewBox="0 0 <?= $largura ?> <?= $altura ?>" role="img" aria-label="<?= $nomeEscapado ?>">
    <rect width="100%" height="100%" fill="<?= $cor ?>"/>
    <text x="50%" y="50%" font-family="Georgia, serif" font-size="<?= $fontSize ?>" fill="#ffffff" fill-opacity="0.85" text-anchor="middle" dominant-baseline="central"><?= $iniciaisEscapadas ?></text>
</svg>
