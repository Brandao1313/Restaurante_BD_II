let baseUrlMesasGarcom = '';

function iniciarMapaMesas(baseUrl) {
    baseUrlMesasGarcom = baseUrl;
    carregarMapaMesas();
    setInterval(carregarMapaMesas, 15000);
}

async function carregarMapaMesas() {
    const grid = document.getElementById('grid-mesas');
    try {
        const resposta = await apiFetch(`${baseUrlMesasGarcom}/api/mesas/status.php`);

        if (resposta.mesas.length === 0) {
            grid.innerHTML = '<p style="color:#777">Nenhuma mesa cadastrada.</p>';
            return;
        }

        grid.innerHTML = resposta.mesas.map((mesa) => {
            const classeStatus = `mesa mesa-${mesa.status.toLowerCase()}`;
            const conteudo = `<span>Mesa ${mesa.numero}</span><small>${mesa.status} — ${mesa.capacidade} lugares</small>`;

            if (mesa.status === 'Reservada') {
                return `<div class="${classeStatus}" style="cursor:not-allowed">${conteudo}</div>`;
            }

            return `<a class="${classeStatus}" style="text-decoration:none" href="${baseUrlMesasGarcom}/pages/garcom/comandas.php?mesa=${mesa.id_mesa}">${conteudo}</a>`;
        }).join('');
    } catch (erro) {
        grid.innerHTML = `<p class="mensagem-erro">${erro.message}</p>`;
    }
}
