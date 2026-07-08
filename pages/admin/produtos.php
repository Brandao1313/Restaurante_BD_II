<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/flash.php';
exigirPerfilPagina(['administrador']);

$pdo = getConexao();
$diretorioUploads = __DIR__ . '/../../assets/img/produtos/';
$extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

function processarUploadImagem(array $arquivo, string $diretorioUploads, array $extensoesPermitidas): ?string
{
    if ($arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no upload da imagem.');
    }
    if ($arquivo['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('A imagem deve ter no máximo 5MB.');
    }
    if (!getimagesize($arquivo['tmp_name'])) {
        throw new RuntimeException('Arquivo enviado não é uma imagem válida.');
    }

    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extensao, $extensoesPermitidas, true)) {
        throw new RuntimeException('Formato de imagem não suportado. Use JPG, PNG, WEBP ou GIF.');
    }

    $nomeArquivo = uniqid('produto_', true) . '.' . $extensao;
    if (!move_uploaded_file($arquivo['tmp_name'], $diretorioUploads . $nomeArquivo)) {
        throw new RuntimeException('Não foi possível salvar a imagem no servidor.');
    }

    return 'assets/img/produtos/' . $nomeArquivo;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        if ($acao === 'salvar') {
            $idProduto = $_POST['id_produto'] !== '' ? (int) $_POST['id_produto'] : null;
            $nome = trim($_POST['nome'] ?? '');
            $preco = (float) str_replace(',', '.', $_POST['preco'] ?? '0');
            $idCategoria = (int) ($_POST['id_categoria'] ?? 0);
            $disponivel = isset($_POST['disponivel']) ? 1 : 0;
            $imagemUrl = trim($_POST['imagem_url'] ?? '');

            if ($nome === '' || $preco <= 0 || $idCategoria <= 0) {
                throw new RuntimeException('Preencha nome, preço (> 0) e categoria corretamente.');
            }

            $caminhoImagem = processarUploadImagem($_FILES['imagem_arquivo'] ?? ['error' => UPLOAD_ERR_NO_FILE], $diretorioUploads, $extensoesPermitidas);
            if ($caminhoImagem === null && $imagemUrl !== '') {
                $caminhoImagem = $imagemUrl;
            }

            if ($idProduto === null) {
                $stmt = $pdo->prepare(
                    'INSERT INTO Produtos (nome, preco, id_categoria, disponivel, imagem) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$nome, $preco, $idCategoria, $disponivel, $caminhoImagem]);
                definirFlash('sucesso', 'Produto cadastrado com sucesso.');
            } else {
                if ($caminhoImagem !== null) {
                    $stmt = $pdo->prepare(
                        'UPDATE Produtos SET nome=?, preco=?, id_categoria=?, disponivel=?, imagem=? WHERE id_produto=?'
                    );
                    $stmt->execute([$nome, $preco, $idCategoria, $disponivel, $caminhoImagem, $idProduto]);
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE Produtos SET nome=?, preco=?, id_categoria=?, disponivel=? WHERE id_produto=?'
                    );
                    $stmt->execute([$nome, $preco, $idCategoria, $disponivel, $idProduto]);
                }
                definirFlash('sucesso', 'Produto atualizado com sucesso.');
            }
        } elseif ($acao === 'excluir') {
            $idProduto = (int) ($_POST['id_produto'] ?? 0);
            $pdo->prepare('DELETE FROM Produtos WHERE id_produto = ?')->execute([$idProduto]);
            definirFlash('sucesso', 'Produto excluído com sucesso.');
        }
    } catch (PDOException $e) {
        definirFlash('erro', 'Não é possível excluir: produto possui pedidos ou insumos vinculados. Marque como indisponível em vez de excluir.');
    } catch (RuntimeException $e) {
        definirFlash('erro', $e->getMessage());
    }

    header('Location: ' . BASE_URL . '/pages/admin/produtos.php');
    exit;
}

$categorias = $pdo->query('SELECT * FROM Categorias ORDER BY ordem ASC')->fetchAll();
$produtos = $pdo->query(
    'SELECT p.*, c.nome AS nome_categoria FROM Produtos p
     JOIN Categorias c ON c.id_categoria = p.id_categoria
     ORDER BY c.ordem ASC, p.nome ASC'
)->fetchAll();

$idEdicao = isset($_GET['editar']) ? (int) $_GET['editar'] : null;
$produtoEdicao = null;
if ($idEdicao) {
    $stmt = $pdo->prepare('SELECT * FROM Produtos WHERE id_produto = ?');
    $stmt->execute([$idEdicao]);
    $produtoEdicao = $stmt->fetch();
}

$tituloPagina = 'Produtos - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('cardapio') ?> Produtos</h1>
<?php renderizarFlash(); ?>

<div class="card" style="margin-bottom:1.5rem">
    <h3><?= $produtoEdicao ? 'Editar Produto' : 'Novo Produto' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="acao" value="salvar">
        <input type="hidden" name="id_produto" value="<?= htmlspecialchars($produtoEdicao['id_produto'] ?? '') ?>">
        <div class="grid grid-cards">
            <div class="campo">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($produtoEdicao['nome'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="preco">Preço (R$)</label>
                <input type="number" step="0.01" min="0.01" id="preco" name="preco" required value="<?= htmlspecialchars($produtoEdicao['preco'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="id_categoria">Categoria</label>
                <select id="id_categoria" name="id_categoria" required>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id_categoria'] ?>" <?= ($produtoEdicao['id_categoria'] ?? null) == $c['id_categoria'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="imagem_url">URL da imagem (opcional)</label>
                <input type="text" id="imagem_url" name="imagem_url" placeholder="https://..." value="<?= htmlspecialchars(str_starts_with($produtoEdicao['imagem'] ?? '', 'http') ? $produtoEdicao['imagem'] : '') ?>">
            </div>
            <div class="campo">
                <label for="imagem_arquivo">Ou envie um arquivo de imagem</label>
                <input type="file" id="imagem_arquivo" name="imagem_arquivo" accept="image/*">
            </div>
            <div class="campo">
                <label for="disponivel">Disponível</label>
                <input type="checkbox" id="disponivel" name="disponivel" style="width:auto" <?= ($produtoEdicao['disponivel'] ?? 1) ? 'checked' : '' ?>>
            </div>
        </div>
        <button type="submit" class="btn"><?= $produtoEdicao ? 'Salvar Alterações' : 'Cadastrar' ?></button>
        <?php if ($produtoEdicao): ?>
            <a class="btn btn-secundario" href="<?= BASE_URL ?>/pages/admin/produtos.php">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="grid grid-cards">
<?php foreach ($produtos as $p): ?>
    <div class="card">
        <h3 style="justify-content:space-between">
            <span><?= htmlspecialchars($p['nome']) ?></span>
            <?php if (!$p['disponivel']): ?><span style="font-size:0.75rem;color:var(--cor-ocupada)">Indisponível</span><?php endif; ?>
        </h3>
        <p style="color:#777;margin:0 0 0.5rem"><?= htmlspecialchars($p['nome_categoria']) ?></p>
        <p style="font-weight:700"><?= formatarMoedaPhp($p['preco']) ?></p>
        <div style="display:flex; gap:0.4rem; flex-wrap:wrap">
            <a class="btn btn-secundario" href="?editar=<?= $p['id_produto'] ?>">Editar</a>
            <form method="POST" onsubmit="return confirm('Excluir este produto?');">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id_produto" value="<?= $p['id_produto'] ?>">
                <button type="submit" class="btn" style="background:var(--cor-ocupada)">Excluir</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
