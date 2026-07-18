let baseUrlComandas = '';
let mesaPreSelecionadaComanda = 0;
let produtosCarregadosComanda = [];
let categoriaAtivaComanda = '';
let carrinhoComanda = [];

const ACOES_POR_STATUS_COMANDA = {
    'Pronto': { proximo: 'Finalizado', rotulo: 'Entregar' },
};

function iniciarComandas(baseUrl, mesaPreSelecionada) {
    baseUrlComandas = baseUrl;
    mesaPreSelecionadaComanda = mesaPreSelecionada;

    document.getElementById('busca-produto-comanda').addEventListener('input', renderizarGridComanda);
    document.querySelectorAll('#abas-categoria-comanda .aba-categoria').forEach((botao) => {
        botao.addEventListener('click', () => {
            categoriaAtivaComanda = botao.dataset.categoria;
            document.querySelectorAll('#abas-categoria-comanda .aba-categoria').forEach((b) => b.classList.remove('ativa'));
            botao.classList.add('ativa');
            renderizarGridComanda();
        });
    });

    document.getElementById('carrinho-flutuante-comanda').addEventListener('click', () => {
        const painel = document.getElementById('carrinho-painel-comanda');
        painel.style.display = painel.style.display === 'none' ? 'block' : 'none';
    });

    carregarMesasSelectComanda();
    carregarProdutosComanda();
    carregarComandasAbertas();
    renderizarCarrinhoComanda();

    setInterval(carregarComandasAbertas, 20000);
}

async function carregarMesasSelectComanda() {
    const select = document.getElementById('select-mesa-comanda');
    try {
        const resposta = await apiFetch(`${baseUrlComandas}/api/mesas/status.php`);
        select.innerHTML = resposta.mesas
            .filter((mesa) => mesa.status !== 'Reservada')
            .map((mesa) => `<option value="${mesa.id_mesa}">Mesa ${mesa.numero} (${mesa.status}, ${mesa.capacidade} lugares)</option>`)
            .join('');

        if (mesaPreSelecionadaComanda) {
            select.value = String(mesaPreSelecionadaComanda);
        }
    } catch (erro) {
        console.error(erro);
    }
}

async function carregarProdutosComanda() {
    try {
        const resposta = await apiFetch(`${baseUrlComandas}/api/produtos/listar.php`);
        produtosCarregadosComanda = resposta.produtos;
        renderizarGridComanda();
    } catch (erro) {
        document.getElementById('grid-produtos-comanda').innerHTML = `<p class="mensagem-erro">${erro.message}</p>`;
    }
}

function renderizarGridComanda() {
    const termoBusca = document.getElementById('busca-produto-comanda').value.trim().toLowerCase();
    const grid = document.getElementById('grid-produtos-comanda');

    const produtosFiltrados = produtosCarregadosComanda.filter((produto) => {
        const combinaCategoria = categoriaAtivaComanda === '' || String(produto.id_categoria) === String(categoriaAtivaComanda);
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
                <button type="button" class="btn" ${produto.disponivel ? '' : 'disabled'} onclick="adicionarAoCarrinhoComanda(${produto.id_produto})">
                    ${produto.disponivel ? 'Adicionar' : 'Indisponível'}
                </button>
            </div>
        </div>
    `).join('');
}

function adicionarAoCarrinhoComanda(idProduto) {
    const produto = produtosCarregadosComanda.find((p) => p.id_produto === idProduto);
    if (!produto) return;

    const itemExistente = carrinhoComanda.find((item) => item.id_produto === idProduto);
    if (itemExistente) {
        itemExistente.quantidade += 1;
    } else {
        carrinhoComanda.push({ id_produto: idProduto, nome: produto.nome, preco: produto.preco, quantidade: 1, observacao: '' });
    }

    renderizarCarrinhoComanda();
    document.getElementById('carrinho-painel-comanda').style.display = 'block';
}

function atualizarQuantidadeComanda(idProduto, delta) {
    const item = carrinhoComanda.find((i) => i.id_produto === idProduto);
    if (!item) return;

    item.quantidade += delta;
    if (item.quantidade <= 0) {
        carrinhoComanda = carrinhoComanda.filter((i) => i.id_produto !== idProduto);
    }

    renderizarCarrinhoComanda();
}

function renderizarCarrinhoComanda() {
    const total = carrinhoComanda.reduce((soma, item) => soma + item.preco * item.quantidade, 0);
    const quantidadeTotal = carrinhoComanda.reduce((soma, item) => soma + item.quantidade, 0);

    document.getElementById('carrinho-contador-comanda').textContent = quantidadeTotal;
    document.getElementById('carrinho-total-comanda').textContent = formatarMoeda(total);
    document.getElementById('carrinho-total-painel-comanda').textContent = formatarMoeda(total);
    document.getElementById('carrinho-flutuante-comanda').style.display = quantidadeTotal > 0 ? 'block' : 'none';

    document.getElementById('carrinho-itens-comanda').innerHTML = carrinhoComanda.map((item) => `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem">
            <span>${item.nome}</span>
            <span style="display:flex; align-items:center; gap:0.4rem">
                <button type="button" class="btn btn-secundario" style="padding:0.2rem 0.6rem" onclick="atualizarQuantidadeComanda(${item.id_produto}, -1)">-</button>
                ${item.quantidade}
                <button type="button" class="btn btn-secundario" style="padding:0.2rem 0.6rem" onclick="atualizarQuantidadeComanda(${item.id_produto}, 1)">+</button>
            </span>
        </div>
    `).join('') || '<p style="color:#777">Nenhum item selecionado.</p>';
}

async function enviarComanda() {
    const mensagem = document.getElementById('mensagem-carrinho-comanda');
    mensagem.innerHTML = '';

    const idMesa = document.getElementById('select-mesa-comanda').value;

    if (carrinhoComanda.length === 0) {
        mensagem.innerHTML = '<div class="mensagem-erro">Adicione ao menos um item.</div>';
        return;
    }
    if (!idMesa) {
        mensagem.innerHTML = '<div class="mensagem-erro">Selecione uma mesa.</div>';
        return;
    }

    try {
        const resposta = await apiFetch(`${baseUrlComandas}/api/pedidos/criar.php`, {
            method: 'POST',
            body: {
                id_mesa: Number(idMesa),
                observacao: document.getElementById('observacao-comanda').value.trim(),
                itens: carrinhoComanda.map((item) => ({ id_produto: item.id_produto, quantidade: item.quantidade, observacao: item.observacao })),
            },
        });

        carrinhoComanda = [];
        document.getElementById('observacao-comanda').value = '';
        renderizarCarrinhoComanda();
        mensagem.innerHTML = `<div class="mensagem-sucesso">Comanda #${resposta.id_pedido} lançada com sucesso!</div>`;
        carregarMesasSelectComanda();
        carregarComandasAbertas();
    } catch (erro) {
        mensagem.innerHTML = `<div class="mensagem-erro">${erro.message}</div>`;
    }
}

async function carregarComandasAbertas() {
    const mensagem = document.getElementById('mensagem-comandas');
    const container = document.getElementById('lista-comandas-abertas');

    try {
        const resposta = await apiFetch(`${baseUrlComandas}/api/pedidos/listar-abertos.php`);

        if (resposta.pedidos.length === 0) {
            container.innerHTML = '<p style="color:#777">Nenhuma comanda aberta no momento.</p>';
            return;
        }

        container.innerHTML = resposta.pedidos.map((pedido) => {
            const acao = ACOES_POR_STATUS_COMANDA[pedido.status];
            const itensHtml = pedido.itens.map((item) => `
                <li style="display:flex; justify-content:space-between; align-items:center; gap:0.5rem">
                    <span>${item.quantidade}x ${item.nome_produto} — ${formatarMoeda(item.preco_unitario * item.quantidade)}${item.observacao ? ` <em>(${item.observacao})</em>` : ''}</span>
                    <button type="button" class="btn btn-secundario" style="padding:0.15rem 0.5rem; font-size:0.75rem; white-space:nowrap" onclick="cancelarItemComanda(${item.id_item})">Cancelar 1</button>
                </li>
            `).join('') || '<li>Sem itens.</li>';

            return `
                <div class="card" style="margin-bottom:1rem">
                    <h3>#${pedido.id_pedido} ${pedido.numero_mesa ? '— Mesa ' + pedido.numero_mesa : ''}</h3>
                    <p style="color:#777; margin:0">${pedido.nome_responsavel || 'Não identificado'} — ${new Date(pedido.data_criacao).toLocaleString('pt-BR')}</p>
                    <p>Status: <strong>${pedido.status}</strong> — Total: <strong>${formatarMoeda(pedido.total)}</strong></p>
                    <ul>${itensHtml}</ul>
                    ${pedido.observacao ? `<p><em>Obs.: ${pedido.observacao}</em></p>` : ''}
                    ${acao ? `<button type="button" class="btn" onclick="entregarComanda(${pedido.id_pedido}, '${acao.proximo}')">${acao.rotulo}</button>` : ''}
                </div>
            `;
        }).join('');
    } catch (erro) {
        mensagem.innerHTML = `<div class="mensagem-erro">${erro.message}</div>`;
    }
}

async function cancelarItemComanda(idItem) {
    if (!confirm('Cancelar 1 unidade deste item da comanda?')) return;

    const mensagem = document.getElementById('mensagem-comandas');
    mensagem.innerHTML = '';

    try {
        await apiFetch(`${baseUrlComandas}/api/pedidos/cancelar-item.php`, {
            method: 'POST',
            body: { id_item: idItem, quantidade_cancelar: 1 },
        });
        carregarComandasAbertas();
    } catch (erro) {
        mensagem.innerHTML = `<div class="mensagem-erro">${erro.message}</div>`;
    }
}

async function entregarComanda(idPedido, novoStatus) {
    const mensagem = document.getElementById('mensagem-comandas');
    mensagem.innerHTML = '';

    try {
        await apiFetch(`${baseUrlComandas}/api/pedidos/atualizar-status.php`, {
            method: 'POST',
            body: { id_pedido: idPedido, novo_status: novoStatus },
        });
        mensagem.innerHTML = `<div class="mensagem-sucesso">Comanda #${idPedido} entregue.</div>`;
        carregarComandasAbertas();
    } catch (erro) {
        mensagem.innerHTML = `<div class="mensagem-erro">${erro.message}</div>`;
    }
}
