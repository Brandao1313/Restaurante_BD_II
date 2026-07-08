let baseUrlReservas = '';
let ultimaBuscaReserva = null;

function iniciarReservas(baseUrl) {
    baseUrlReservas = baseUrl;

    const inputData = document.getElementById('data-reserva');
    inputData.min = new Date().toISOString().split('T')[0];
    inputData.value = inputData.min;

    document.getElementById('form-disponibilidade').addEventListener('submit', buscarDisponibilidade);
    carregarMinhasReservas();
}

async function buscarDisponibilidade(evento) {
    evento.preventDefault();
    const mensagem = document.getElementById('mensagem-reserva');
    const container = document.getElementById('mesas-disponiveis');
    mensagem.innerHTML = '';
    container.innerHTML = '';

    const data = document.getElementById('data-reserva').value;
    const horario = document.getElementById('horario-reserva').value;
    const pessoas = document.getElementById('pessoas-reserva').value;

    ultimaBuscaReserva = { data, horario, pessoas };

    try {
        const resposta = await apiFetch(`${baseUrlReservas}/api/reservas/disponibilidade.php?data=${data}&horario=${horario}&pessoas=${pessoas}`);

        if (resposta.mesas.length === 0) {
            container.innerHTML = '<p style="color:#777">Nenhuma mesa disponível para este horário. Tente outro turno ou horário.</p>';
            return;
        }

        container.innerHTML = `
            <p>Turno: <strong>${resposta.turno}</strong> — escolha uma mesa:</p>
            <div class="grid grid-cards">
                ${resposta.mesas.map((mesa) => `
                    <button type="button" class="btn btn-secundario" style="width:100%" onclick="confirmarReserva(${mesa.id_mesa}, ${mesa.numero})">
                        Mesa ${mesa.numero} (${mesa.capacidade} lugares)
                    </button>
                `).join('')}
            </div>
        `;
    } catch (erro) {
        mensagem.innerHTML = `<div class="mensagem-erro">${erro.message}</div>`;
    }
}

async function confirmarReserva(idMesa, numeroMesa) {
    const mensagem = document.getElementById('mensagem-reserva');
    mensagem.innerHTML = '';

    try {
        const resposta = await apiFetch(`${baseUrlReservas}/api/reservas/criar.php`, {
            method: 'POST',
            body: {
                data_reserva: ultimaBuscaReserva.data,
                hora_reserva: ultimaBuscaReserva.horario,
                quantidade_pessoas: Number(ultimaBuscaReserva.pessoas),
                id_mesa: idMesa,
            },
        });

        mensagem.innerHTML = `<div class="mensagem-sucesso">Reserva confirmada na Mesa ${numeroMesa}! Código: <strong>${resposta.reserva.codigo_confirmacao}</strong></div>`;
        document.getElementById('mesas-disponiveis').innerHTML = '';
        carregarMinhasReservas();
    } catch (erro) {
        mensagem.innerHTML = `<div class="mensagem-erro">${erro.message}</div>`;
    }
}

async function carregarMinhasReservas() {
    const container = document.getElementById('lista-reservas');
    try {
        const resposta = await apiFetch(`${baseUrlReservas}/api/reservas/listar.php`);

        if (resposta.reservas.length === 0) {
            container.innerHTML = '<p style="color:#777">Você ainda não tem reservas.</p>';
            return;
        }

        container.innerHTML = resposta.reservas.map((reserva) => `
            <div class="card" style="margin-bottom:1rem">
                <h3>Mesa ${reserva.numero_mesa} — ${reserva.data_reserva} às ${reserva.hora_reserva}</h3>
                <p>Pessoas: ${reserva.quantidade_pessoas} — Status: <strong>${reserva.status}</strong></p>
                <p>Código de confirmação: <strong>${reserva.codigo_confirmacao}</strong></p>
                ${reserva.pode_cancelar ? `<button type="button" class="btn" style="background:var(--cor-ocupada)" onclick="cancelarReserva(${reserva.id_reserva})">Cancelar Reserva</button>` : ''}
            </div>
        `).join('');
    } catch (erro) {
        container.innerHTML = `<p class="mensagem-erro">${erro.message}</p>`;
    }
}

async function cancelarReserva(idReserva) {
    if (!confirm('Deseja realmente cancelar esta reserva?')) return;
    try {
        await apiFetch(`${baseUrlReservas}/api/reservas/cancelar.php`, { method: 'POST', body: { id_reserva: idReserva } });
        carregarMinhasReservas();
    } catch (erro) {
        alert(erro.message);
    }
}
