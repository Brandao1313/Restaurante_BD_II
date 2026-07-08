<?php
require_once __DIR__ . '/../../config/session.php';
exigirPerfilPagina(['cliente']);

$tituloPagina = 'Meus Pedidos - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('pedidos') ?> Meus Pedidos</h1>
<div id="lista-pedidos">Carregando...</div>

<script>
    const baseUrl = '<?= BASE_URL ?>';

    async function carregarPedidos() {
        const container = document.getElementById('lista-pedidos');
        try {
            const resposta = await apiFetch(`${baseUrl}/api/pedidos/listar.php`);
            if (resposta.pedidos.length === 0) {
                container.innerHTML = '<p style="color:#777">Você ainda não fez nenhum pedido.</p>';
                return;
            }

            container.innerHTML = resposta.pedidos.map((pedido) => {
                const podeCancelar = pedido.itens.every((item) => ['Pendente', 'Recebido'].includes(item.status));
                const itensHtml = pedido.itens.map((item) => `
                    <li>${item.quantidade}x ${item.nome_produto} — ${formatarMoeda(item.preco_unitario * item.quantidade)} <em>(${item.status})</em></li>
                `).join('');

                return `
                    <div class="card" style="margin-bottom:1rem">
                        <h3>Pedido #${pedido.id_pedido} ${pedido.numero_mesa ? '— Mesa ' + pedido.numero_mesa : ''}</h3>
                        <p>Status: <strong>${pedido.status}</strong> — Total: <strong>${formatarMoeda(pedido.total)}</strong></p>
                        <ul>${itensHtml}</ul>
                        ${podeCancelar && pedido.status !== 'Cancelado' ? `<button type="button" class="btn" style="background:var(--cor-ocupada)" onclick="cancelarPedido(${pedido.id_pedido})">Cancelar Pedido</button>` : ''}
                    </div>
                `;
            }).join('');
        } catch (erro) {
            container.innerHTML = `<p class="mensagem-erro">${erro.message}</p>`;
        }
    }

    async function cancelarPedido(idPedido) {
        if (!confirm('Deseja realmente cancelar este pedido?')) return;
        try {
            await apiFetch(`${baseUrl}/api/pedidos/excluir.php`, { method: 'POST', body: { id_pedido: idPedido } });
            carregarPedidos();
        } catch (erro) {
            alert(erro.message);
        }
    }

    carregarPedidos();
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
