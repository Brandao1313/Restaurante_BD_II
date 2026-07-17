let baseUrlEstoque = '';
let filtroAtivoEstoque = '';

function iniciarEstoque(baseUrl) {
    baseUrlEstoque = baseUrl;

    document.querySelectorAll('.aba-categoria').forEach((botao) => {
        botao.addEventListener('click', () => {
            filtroAtivoEstoque = botao.dataset.filtro;
            document.querySelectorAll('.aba-categoria').forEach((b) => b.classList.remove('ativa'));
            botao.classList.add('ativa');
            carregarEstoque();
        });
    });

    carregarEstoque();
}

async function carregarEstoque() {
    const corpo = document.getElementById('corpo-tabela-estoque');
    try {
        const resposta = await apiFetch(`${baseUrlEstoque}/api/estoque/listar.php?filtro=${filtroAtivoEstoque}`);

        if (resposta.insumos.length === 0) {
            corpo.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#777; padding:1rem">Nenhum insumo encontrado.</td></tr>';
            return;
        }

        corpo.innerHTML = resposta.insumos.map((insumo) => `
            <tr style="${insumo.abaixo_minimo ? 'background:#fdecea' : ''}">
                <td>${insumo.nome}</td>
                <td style="${insumo.abaixo_minimo ? 'color:var(--cor-ocupada); font-weight:700' : ''}">${insumo.quantidade} ${insumo.unidade}</td>
                <td>${insumo.quantidade_minima} ${insumo.unidade}</td>
                <td>${insumo.ultima_atualizacao ? new Date(insumo.ultima_atualizacao).toLocaleString('pt-BR') : '-'}</td>
                <td>
                    <form style="display:flex; gap:0.4rem" onsubmit="return registrarCompra(event, ${insumo.id_insumo})">
                        <input type="number" step="0.01" min="0.01" placeholder="Qtd." style="width:90px; padding:0.4rem; border:1px solid var(--cor-borda); border-radius:6px" required>
                        <button type="submit" class="btn btn-secundario">Registrar</button>
                    </form>
                </td>
            </tr>
        `).join('');
    } catch (erro) {
        corpo.innerHTML = `<tr><td colspan="5"><div class="mensagem-erro">${erro.message}</div></td></tr>`;
    }
}

async function registrarCompra(evento, idInsumo) {
    evento.preventDefault();
    const mensagem = document.getElementById('mensagem-estoque');
    mensagem.innerHTML = '';
    const input = evento.target.querySelector('input');
    const quantidade = Number(input.value);

    try {
        await apiFetch(`${baseUrlEstoque}/api/estoque/atualizar.php`, {
            method: 'POST',
            body: { id_insumo: idInsumo, quantidade_adicionar: quantidade },
        });
        mensagem.innerHTML = '<div class="mensagem-sucesso">Estoque atualizado com sucesso!</div>';
        carregarEstoque();
    } catch (erro) {
        mensagem.innerHTML = `<div class="mensagem-erro">${erro.message}</div>`;
    }

    return false;
}
