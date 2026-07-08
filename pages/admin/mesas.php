<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';
exigirPerfilPagina(['administrador']);

$pdo = getConexao();
$statusValidos = ['Livre', 'Ocupada', 'Reservada'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'salvar') {
            $idMesa = $_POST['id_mesa'] !== '' ? (int) $_POST['id_mesa'] : null;
            $numero = (int) ($_POST['numero'] ?? 0);
            $capacidade = (int) ($_POST['capacidade'] ?? 0);
            $status = $_POST['status'] ?? 'Livre';

            if ($numero <= 0 || $capacidade <= 0 || !in_array($status, $statusValidos, true)) {
                throw new RuntimeException('Preencha número, capacidade e status corretamente.');
            }

            if ($idMesa === null) {
                $pdo->prepare('INSERT INTO Mesas (numero, capacidade, status) VALUES (?, ?, ?)')
                    ->execute([$numero, $capacidade, $status]);
                definirFlash('sucesso', 'Mesa cadastrada com sucesso.');
            } else {
                $pdo->prepare('UPDATE Mesas SET numero=?, capacidade=?, status=? WHERE id_mesa=?')
                    ->execute([$numero, $capacidade, $status, $idMesa]);
                definirFlash('sucesso', 'Mesa atualizada com sucesso.');
            }
        } elseif ($acao === 'alterar_status') {
            $idMesa = (int) ($_POST['id_mesa'] ?? 0);
            $status = $_POST['status'] ?? '';
            if (!in_array($status, $statusValidos, true)) {
                throw new RuntimeException('Status inválido.');
            }
            $pdo->prepare('UPDATE Mesas SET status=? WHERE id_mesa=?')->execute([$status, $idMesa]);
            definirFlash('sucesso', 'Status da mesa atualizado.');
        } elseif ($acao === 'excluir') {
            $idMesa = (int) ($_POST['id_mesa'] ?? 0);
            $pdo->prepare('DELETE FROM Mesas WHERE id_mesa = ?')->execute([$idMesa]);
            definirFlash('sucesso', 'Mesa excluída com sucesso.');
        }
    } catch (PDOException $e) {
        definirFlash('erro', 'Não é possível excluir: mesa possui pedidos ou reservas vinculados.');
    } catch (RuntimeException $e) {
        definirFlash('erro', $e->getMessage());
    }

    header('Location: ' . BASE_URL . '/pages/admin/mesas.php');
    exit;
}

$mesas = $pdo->query('SELECT * FROM Mesas ORDER BY numero ASC')->fetchAll();

$idEdicao = isset($_GET['editar']) ? (int) $_GET['editar'] : null;
$mesaEdicao = null;
if ($idEdicao) {
    $stmt = $pdo->prepare('SELECT * FROM Mesas WHERE id_mesa = ?');
    $stmt->execute([$idEdicao]);
    $mesaEdicao = $stmt->fetch();
}

$tituloPagina = 'Mesas - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('mesas') ?> Mesas</h1>
<?php renderizarFlash(); ?>

<div class="card" style="margin-bottom:1.5rem">
    <h3><?= $mesaEdicao ? 'Editar Mesa' : 'Nova Mesa' ?></h3>
    <form method="POST">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id_mesa" value="<?= htmlspecialchars($mesaEdicao['id_mesa'] ?? '') ?>">
        <div class="grid grid-cards">
            <div class="campo">
                <label for="numero">Número</label>
                <input type="number" min="1" id="numero" name="numero" required value="<?= htmlspecialchars($mesaEdicao['numero'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="capacidade">Capacidade (lugares)</label>
                <input type="number" min="1" id="capacidade" name="capacidade" required value="<?= htmlspecialchars($mesaEdicao['capacidade'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <?php foreach ($statusValidos as $s): ?>
                        <option value="<?= $s ?>" <?= ($mesaEdicao['status'] ?? 'Livre') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="submit" class="btn"><?= $mesaEdicao ? 'Salvar Alterações' : 'Cadastrar' ?></button>
        <?php if ($mesaEdicao): ?>
            <a class="btn btn-secundario" href="<?= BASE_URL ?>/pages/admin/mesas.php">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="grid grid-cards">
<?php foreach ($mesas as $m): ?>
    <div class="mesa mesa-<?= strtolower($m['status']) ?>" style="aspect-ratio:auto; padding:1rem; align-items:flex-start; text-align:left; cursor:default">
        <span>Mesa <?= (int) $m['numero'] ?></span>
        <small>Capacidade: <?= (int) $m['capacidade'] ?> | <?= htmlspecialchars($m['status']) ?></small>
        <form method="POST" style="margin-top:0.5rem; width:100%">
            <input type="hidden" name="acao" value="alterar_status">
            <input type="hidden" name="id_mesa" value="<?= $m['id_mesa'] ?>">
            <select name="status" onchange="this.form.submit()" style="width:100%; padding:0.3rem; border-radius:6px">
                <?php foreach ($statusValidos as $s): ?>
                    <option value="<?= $s ?>" <?= $m['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <div style="display:flex; gap:0.4rem; margin-top:0.5rem; width:100%">
            <a class="btn btn-secundario btn-editar-mesa" style="flex:1; text-align:center" href="?editar=<?= $m['id_mesa'] ?>">Editar</a>
            <form method="POST" onsubmit="return confirm('Excluir esta mesa?');" style="flex:1">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id_mesa" value="<?= $m['id_mesa'] ?>">
                <button type="submit" class="btn" style="width:100%; background:#5a1f1f">Excluir</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
