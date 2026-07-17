<?php
/**
 * Registro de auditoria (RN de rastreabilidade), reusado por login e pelas
 * ações administrativas/operacionais que precisam deixar rastro em LogsAuditoria.
 */
function registrarLog(PDO $pdo, ?int $idFuncionario, ?int $idCliente, string $acao, string $detalhes): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO LogsAuditoria (id_funcionario, id_cliente, acao, detalhes, ip) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$idFuncionario, $idCliente, $acao, $detalhes, $_SERVER['REMOTE_ADDR'] ?? null]);
}
