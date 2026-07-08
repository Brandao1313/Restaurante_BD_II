/**
 * Wrapper simples sobre a Fetch API usado por todos os módulos.
 * Sempre envia/recebe JSON e propaga erros HTTP como exceções.
 */
async function apiFetch(url, options = {}) {
    const config = {
        headers: { 'Content-Type': 'application/json' },
        ...options,
    };

    if (config.body && typeof config.body !== 'string') {
        config.body = JSON.stringify(config.body);
    }

    const resposta = await fetch(url, config);
    const dados = await resposta.json().catch(() => ({}));

    if (!resposta.ok) {
        throw new Error(dados.erro || `Erro na requisição (HTTP ${resposta.status})`);
    }

    return dados;
}

function formatarMoeda(valor) {
    return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

async function fazerLogout() {
    await apiFetch('/BD_II_RESTAURANTE/api/auth/logout.php', { method: 'POST' });
    window.location.href = '/BD_II_RESTAURANTE/pages/login.php';
}
