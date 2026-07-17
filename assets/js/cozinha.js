let baseUrlCozinha = '';

const ACOES_POR_STATUS = {
    'Aberto': { proximo: 'Em Preparo', rotulo: 'Iniciar Preparo' },
    'Em Preparo': { proximo: 'Pronto', rotulo: 'Finalizar Preparo' },
    'Pronto': { proximo: 'Finalizado', rotulo: 'Entregar' },
};

const COLUNA_POR_STATUS = {
    'Aberto': 'coluna-aberto',
    'Em Preparo': 'coluna-em-preparo',
    'Pronto': 'coluna-pronto',
};

function iniciarPedidosCozinha(baseUrl) {
    baseUrlCozinha = baseUrl;
    carregarPedidosCozinha();
    setInterval(carregarPedidosCozinha, 30000);
}

async function carregarPedidosCozinha() {
    const mensagem = document.getElementById('mensagem-cozinha');
    try {
        const resposta = await apiFetch(`${baseUrlCozinha}/api/pedidos/listar-cozinha.php`);

        Object.values(COLUNA_POR_STATUS).forEach((idColuna) => {
            document.getElementById(idColuna).innerHTML = '';
        });

        resposta.pedidos.forEach((pedido) => {
            const idColuna = COLUNA_POR_STATUS[pedido.status];
            if (!idColuna) return;

            const acao = ACOES_POR_STATUS[pedido.status];
            const itensHtml = pedido.itens.map((item) => `
                <li>${item.quantidade}x ${item.nome_produto}${item.observacao ? ` <em>(${item.observacao})</em>` : ''}</li>
            `).join('') || '<li>Sem itens.</li>';

            const cartao = document.createElement('div');
            cartao.className = 'card pedido-cozinha-card';
            cartao.innerHTML = `
                <h3>#${pedido.id_pedido} ${pedido.numero_mesa ? '— Mesa ' + pedido.numero_mesa : ''}</h3>
                <p style="color:#777; margin:0">${pedido.nome_cliente || 'Cliente não identificado'} — ${new Date(pedido.data_criacao).toLocaleString('pt-BR')}</p>
                <ul>${itensHtml}</ul>
                ${pedido.observacao ? `<p><em>Obs.: ${pedido.observacao}</em></p>` : ''}
                ${acao ? `<button type="button" class="btn" style="width:100%" onclick="mudarStatusPedido(${pedido.id_pedido}, '${acao.proximo}')">${acao.rotulo}</button>` : ''}
            `;
            document.getElementById(idColuna).appendChild(cartao);
        });

        ['coluna-aberto', 'coluna-em-preparo', 'coluna-pronto'].forEach((idColuna) => {
            const coluna = document.getElementById(idColuna);
            if (!coluna.children.length) {
                coluna.innerHTML = '<p style="color:#777">Nenhum pedido aqui.</p>';
            }
        });
    } catch (erro) {
        mensagem.innerHTML = `<div class="mensagem-erro">${erro.message}</div>`;
    }
}

async function mudarStatusPedido(idPedido, novoStatus) {
    const mensagem = document.getElementById('mensagem-cozinha');
    mensagem.innerHTML = '';

    try {
        await apiFetch(`${baseUrlCozinha}/api/pedidos/atualizar-status.php`, {
            method: 'POST',
            body: { id_pedido: idPedido, novo_status: novoStatus },
        });
        mensagem.innerHTML = `<div class="mensagem-sucesso">Pedido #${idPedido} atualizado para "${novoStatus}".</div>`;
        carregarPedidosCozinha();
    } catch (erro) {
        mensagem.innerHTML = `<div class="mensagem-erro">${erro.message}</div>`;
    }
}
