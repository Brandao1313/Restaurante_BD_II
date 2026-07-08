<?php
require_once __DIR__ . '/../../config/session.php';
exigirPerfilPagina(['cliente']);

$tituloPagina = 'Reservas - Bom Sabor';
require __DIR__ . '/../../includes/header.php';
?>
<h1><?= icone('reservas') ?> Reservas</h1>

<div class="card" style="margin-bottom:1.5rem">
    <h3>Fazer uma nova reserva</h3>
    <div id="mensagem-reserva"></div>
    <form id="form-disponibilidade">
        <div class="grid grid-cards">
            <div class="campo">
                <label for="data-reserva">Data</label>
                <input type="date" id="data-reserva" name="data" required>
            </div>
            <div class="campo">
                <label for="horario-reserva">Horário</label>
                <select id="horario-reserva" name="horario" required>
                    <option value="11:00">11:00 (Almoço)</option>
                    <option value="12:00">12:00 (Almoço)</option>
                    <option value="13:00">13:00 (Almoço)</option>
                    <option value="14:00">14:00 (Almoço)</option>
                    <option value="18:00">18:00 (Jantar)</option>
                    <option value="19:00">19:00 (Jantar)</option>
                    <option value="20:00">20:00 (Jantar)</option>
                    <option value="21:00">21:00 (Jantar)</option>
                    <option value="22:00">22:00 (Jantar)</option>
                </select>
            </div>
            <div class="campo">
                <label for="pessoas-reserva">Quantidade de pessoas</label>
                <input type="number" id="pessoas-reserva" name="pessoas" min="1" value="2" required>
            </div>
        </div>
        <button type="submit" class="btn">Buscar Mesas Disponíveis</button>
    </form>

    <div id="mesas-disponiveis" style="margin-top:1rem"></div>
</div>

<div class="card">
    <h3>Minhas Reservas</h3>
    <div id="lista-reservas">Carregando...</div>
</div>

<script src="<?= BASE_URL ?>/assets/js/reservas.js?v=<?= filemtime(__DIR__ . '/../../assets/js/reservas.js') ?>"></script>
<script>iniciarReservas('<?= BASE_URL ?>');</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
