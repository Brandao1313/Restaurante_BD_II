let baseUrl = '';
let idClienteAtual = null;
let produtosCarregados = [];
let categoriaAtiva = '';

function chaveCarrinho() {
    return `carrinho_cliente_${idClienteAtual}`;
}

function obterCarrinho() {
    return JSON.parse(localStorage.getItem(chaveCarrinho()) || '[]');
}

function salvarCarrinho(carrinho) {
    localStorage.setItem(chaveCarrinho(), JSON.stringify(carrinho));
}

function iniciarCardapio(idCliente, baseUrlParam) {
    idClienteAtual = idCliente;
    baseUrl = baseUrlParam;

    document.getElementById('busca-produto').addEventListener('input', renderizarGrid);
    document.querySelectorAll('.aba-categoria').forEach((botao) => {
        botao.addEventListener('click', () => {
            categoriaAtiva = botao.dataset.categoria;
            document.querySelectorAll('.aba-categoria').forEach((b) => b.classList.remove('ativa'));
            botao.classList.add('ativa');
            renderizarGrid();
        });
    });

    document.getElementById('carrinho-flutuante').addEventListener('click', () => {
        const painel = document.getElementById('carrinho-painel');
        painel.style.display = painel.style.display === 'none' ? 'block' : 'none';
    });

    carregarProdutos();
    carregarMesas();
    renderizarCarrinho();
}

async function carregarProdutos() {
    try {
        const resposta = await apiFetch(`${baseUrl}/api/produtos/listar.php`);
        produtosCarregados = resposta.produtos;
        renderizarGrid();
    } catch (erro) {
        document.getElementById('grid-produtos').innerHTML = `<p class="mensagem-erro">${erro.message}</p>`;
    }
}

async function carregarMesas() {
    try {
        const resposta = await apiFetch(`${baseUrl}/api/mesas/status.php`);
        const select = document.getElementById('select-mesa');
        select.innerHTML = resposta.mesas
            .filter((mesa) => mesa.status !== 'Reservada')
            .map((mesa) => `<option value="${mesa.id_mesa}">Mesa ${mesa.numero} (${mesa.status}, ${mesa.capacidade} lugares)</option>`)
            .join('');
    } catch (erro) {
        console.error(erro);
    }
}

function renderizarGrid() {
    const termoBusca = document.getElementById('busca-produto').value.trim().toLowerCase();
    const grid = document.getElementById('grid-produtos');

    const produtosFiltrados = produtosCarregados.filter((produto) => {
        const combinaCategoria = categoriaAtiva === '' || String(produto.id_categoria) === String(categoriaAtiva);
        const combinaBusca = produto.nome.toLowerCase().includes(termoBusca);
        return combinaCategoria && combinaBusca;
    });

    if (produtosFiltrados.length === 0) {
        grid.innerHTML = '<p style="color:#777">Nenhum prato encontrado.</p>';
        return;
    }

    grid.innerHTML = produtosFiltrados.map((produto) => `
        <div class="produto-card ${produto.disponivel ? '' : 'indisponivel'}">
            <div class="produto-imagem-wrap">
                <img src="${produto.url_imagem}" alt="${produto.nome}" loading="lazy">
            </div>
            <div class="produto-info">
                <span class="badge-categoria">${produto.nome_categoria}</span>
                <h3 class="produto-nome">${produto.nome}</h3>
                <span class="produto-preco">${formatarMoeda(produto.preco)}</span>
                <button type="button" class="btn" ${produto.disponivel ? '' : 'disabled'} onclick="adicionarAoCarrinho(${produto.id_produto})">
                    ${produto.disponivel ? 'Adicionar ao Pedido' : 'Indisponível'}
                </button>
            </div>
        </div>
    `).join('');
}

function adicionarAoCarrinho(idProduto) {
    const produto = produtosCarregados.find((p) => p.id_produto === idProduto);
    if (!produto) return;

    const carrinho = obterCarrinho();
    const itemExistente = carrinho.find((item) => item.id_produto === idProduto);

    if (itemExistente) {
        itemExistente.quantidade += 1;
    } else {
        carrinho.push({ id_produto: idProduto, nome: produto.nome, preco: produto.preco, quantidade: 1, observacao: '' });
    }

    salvarCarrinho(carrinho);
    renderizarCarrinho();
    document.getElementById('carrinho-painel').style.display = 'block';
}

function atualizarQuantidade(idProduto, delta) {
    let carrinho = obterCarrinho();
    const item = carrinho.find((i) => i.id_produto === idProduto);
    if (!item) return;

    item.quantidade += delta;
    if (item.quantidade <= 0) {
        carrinho = carrinho.filter((i) => i.id_produto !== idProduto);
    }

    salvarCarrinho(carrinho);
    renderizarCarrinho();
}

function calcularTotalCarrinho(carrinho) {
    return carrinho.reduce((total, item) => total + item.preco * item.quantidade, 0);
}

function renderizarCarrinho() {
    const carrinho = obterCarrinho();
    const total = calcularTotalCarrinho(carrinho);
    const quantidadeTotal = carrinho.reduce((soma, item) => soma + item.quantidade, 0);

    document.getElementById('carrinho-contador').textContent = quantidadeTotal;
    document.getElementById('carrinho-total').textContent = formatarMoeda(total);
    document.getElementById('carrinho-total-painel').textContent = formatarMoeda(total);
    document.getElementById('carrinho-flutuante').style.display = quantidadeTotal > 0 ? 'block' : 'none';

    document.getElementById('carrinho-itens').innerHTML = carrinho.map((item) => `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem">
            <span>${item.nome}</span>
            <span style="display:flex; align-items:center; gap:0.4rem">
                <button type="button" class="btn btn-secundario" style="padding:0.2rem 0.6rem" onclick="atualizarQuantidade(${item.id_produto}, -1)">-</button>
                ${item.quantidade}
                <button type="button" class="btn btn-secundario" style="padding:0.2rem 0.6rem" onclick="atualizarQuantidade(${item.id_produto}, 1)">+</button>
            </span>
        </div>
    `).join('') || '<p style="color:#777">Carrinho vazio.</p>';
}

async function finalizarPedido() {
    const mensagem = document.getElementById('mensagem-carrinho');
    mensagem.innerHTML = '';

    const carrinho = obterCarrinho();
    const idMesa = document.getElementById('select-mesa').value;

    if (carrinho.length === 0) {
        mensagem.innerHTML = '<div class="mensagem-erro">Seu carrinho está vazio.</div>';
        return;
    }
    if (!idMesa) {
        mensagem.innerHTML = '<div class="mensagem-erro">Selecione uma mesa.</div>';
        return;
    }

    try {
        const resposta = await apiFetch(`${baseUrl}/api/pedidos/criar.php`, {
            method: 'POST',
            body: {
                id_mesa: Number(idMesa),
                observacao: document.getElementById('observacao-pedido').value.trim(),
                itens: carrinho.map((item) => ({ id_produto: item.id_produto, quantidade: item.quantidade, observacao: item.observacao })),
            },
        });

        localStorage.removeItem(chaveCarrinho());
        mensagem.innerHTML = `<div class="mensagem-sucesso">Pedido #${resposta.id_pedido} enviado com sucesso!</div>`;
        renderizarCarrinho();
        setTimeout(() => { window.location.href = `${baseUrl}/pages/cliente/meus-pedidos.php`; }, 1500);
    } catch (erro) {
        mensagem.innerHTML = `<div class="mensagem-erro">${erro.message}</div>`;
    }
}
