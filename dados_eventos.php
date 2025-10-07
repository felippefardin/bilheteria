<?php
session_start();
include 'conexao.php';
include 'includes/header.php';

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Consulta eventos do usuário
$query = "SELECT * FROM eventos WHERE usuario_id = ? ORDER BY data_inicio ASC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$eventos = [];
while ($row = $result->fetch_assoc()) {
    $eventos[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Dados dos Eventos</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Estilos gerais */
.container { margin: 20px auto; width: 90%; background-color: #fff; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
h2 { text-align: center; }
.table-eventos, .table-lotes, .table-vendas { width: 100%; border-collapse: collapse; margin-top: 20px; }
.table-eventos th, .table-eventos td, .table-lotes th, .table-lotes td, .table-vendas th, .table-vendas td { padding: 10px; border: 1px solid #ddd; text-align: left; }
.table-eventos th, .table-lotes th, .table-vendas th { background-color: #f8f8f8; }
.table-eventos tr:nth-child(even), .table-lotes tr:nth-child(even), .table-vendas tr:nth-child(even) { background-color: #f2f2f2; }
button { padding: 6px 12px; margin: 3px; cursor: pointer; border: none; border-radius: 5px; font-size: 13px; }
.btn-start { background-color: #28a745; color: white; }
.btn-pause { background-color: #ffc107; color: white; }
.btn-cancel { background-color: #dc3545; color: white; }

/* Estilos do Modal Moderno */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

.modal-content {
    background-color: #fff;
    padding: 30px;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    text-align: left;
    transform: scale(0.95);
    animation: scale-in 0.2s forwards;
}

@keyframes scale-in {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.modal-content h3 {
    text-align: center;
    color: #333;
    margin-top: 0;
    font-size: 1.5rem;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.modal-content form label {
    font-weight: 600;
    margin-top: 10px;
    display: block;
    color: #555;
}

.modal-content form input {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-sizing: border-box;
}

.modal-content form button {
    width: 100%;
    margin-top: 20px;
    padding: 12px;
    font-weight: bold;
    font-size: 1rem;
    color: white;
    background-color: #007bff;
    border-radius: 6px;
    transition: background-color 0.2s;
}

.modal-content form button[type="button"] {
    background-color: #6c757d;
}

.modal-content form button[type="submit"]:hover {
    background-color: #0056b3;
}

.modal-content form button[type="button"]:hover {
    background-color: #5a6268;
}

/* Modal specific styles */
.lote {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.lote h4 {
    color: #495057;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 10px;
    margin-bottom: 10px;
    font-size: 1.2rem;
}

.setores .setor-ingresso {
    margin-bottom: 10px;
    padding: 10px;
    background: #eaf4ff;
    border-radius: 6px;
    border-left: 3px solid #007bff;
}

.hidden { display: none; }
</style>
</head>
<body>

<div class="container">
<h2>Meus Eventos</h2>

<?php if(empty($eventos)): ?>
    <p>Você ainda não possui eventos cadastrados.</p>
<?php else: ?>
    <table class="table-eventos">
        <thead>
            <tr>
                <th>Título</th>
                <th>Categoria</th>
                <th>Data</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($eventos as $e): ?>
            <tr>
                <td><?= htmlspecialchars($e['titulo']) ?></td>
                <td><?= htmlspecialchars($e['categoria']) ?></td>
                <td><?= date('d/m/Y', strtotime($e['data_inicio'])) ?><?php if($e['data_fim']) echo " - ".date('d/m/Y', strtotime($e['data_fim'])); ?></td>
                <td><?= ucfirst($e['status']) ?></td>
                <td>
                    <?php if($e['status'] == 'pausado'): ?>
                        <button class="btn-start" data-id="<?= $e['id'] ?>">Ativar Evento</button>
                    <?php endif; ?>
                    <?php if($e['status'] == 'ativo'): ?>
                        <button class="btn-pause" data-id="<?= $e['id'] ?>">Pausar</button>
                        <button class="btn-cancel" data-id="<?= $e['id'] ?>">Cancelar</button>
                    <?php endif; ?>
                </td>
            </tr>

            <?php
            // Carrega lotes do evento
            $stmt_lotes = $mysqli->prepare("
                SELECT el.*, els.nome_setor, els.nome_customizado, els.quantidade AS setor_quantidade, els.valor_inteira, els.valor_meia
                FROM eventos_lotes el
                LEFT JOIN eventos_lotes_setores els ON el.id = els.lote_id
                WHERE el.evento_id=?
                ORDER BY el.numero_lote ASC, els.id ASC
            ");
            $stmt_lotes->bind_param("i", $e['id']);
            $stmt_lotes->execute();
            $result_lotes = $stmt_lotes->get_result();
            
            $lotes = [];
            while($row = $result_lotes->fetch_assoc()) {
                $lotes[$row['id']]['info'] = [
                    'numero' => $row['numero_lote'],
                    'tipo' => $row['tipo_evento'],
                    'quantidade' => $row['quantidade']
                ];
                $lotes[$row['id']]['setores'][] = [
                    'nome' => $row['nome_customizado'] ?: $row['nome_setor'],
                    'quantidade' => $row['setor_quantidade'],
                    'inteira' => $row['valor_inteira'],
                    'meia' => $row['valor_meia']
                ];
            }
            $stmt_lotes->close();
            ?>

            <?php if(!empty($lotes)): ?>
            <tr>
                <td colspan="5">
                    <table class="table-lotes">
                        <thead>
                            <tr>
                                <th>Lote</th>
                                <th>Detalhes do Lote</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($lotes as $lote): ?>
                            <tr>
                                <td>Lote <?= $lote['info']['numero'] ?></td>
                                <td>
                                    Tipo: <?= $lote['info']['tipo'] ?><br>
                                    Qtd. Lote: <?= $lote['info']['quantidade'] ?><br>
                                    <hr>
                                    <strong>Setores:</strong><br>
                                    <?php foreach($lote['setores'] as $setor): ?>
                                        - Setor: <?= htmlspecialchars($setor['nome']) ?><br>
                                        &nbsp;&nbsp;Qtd: <?= $setor['quantidade'] ?><br>
                                        &nbsp;&nbsp;Inteira: R$ <?= number_format($setor['inteira'], 2, ',', '.') ?><br>
                                        &nbsp;&nbsp;Meia: R$ <?= number_format($setor['meia'], 2, ',', '.') ?><br>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <?php endif; ?>

            <?php
            $stmt_vendas = $mysqli->prepare("
                SELECT v.nome_cliente, v.cpf_cliente, el.numero_lote, els.nome_setor, els.nome_customizado, vd.qtd_inteira, vd.qtd_meia
                FROM vendas v
                INNER JOIN vendas_detalhes vd ON v.id = vd.venda_id
                INNER JOIN eventos_lotes_setores els ON vd.setor_id = els.id
                INNER JOIN eventos_lotes el ON els.lote_id = el.id
                WHERE v.evento_id = ?
            ");
            $stmt_vendas->bind_param("i", $e['id']);
            $stmt_vendas->execute();
            $result_vendas = $stmt_vendas->get_result();
            ?>
            
            <?php if($result_vendas->num_rows > 0): ?>
            <tr>
                <td colspan="5">
                    <h4>Vendas para o Evento: <?= htmlspecialchars($e['titulo']) ?></h4>
                    <table class="table-vendas">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>CPF</th>
                                <th>Lote</th>
                                <th>Setor</th>
                                <th>Qtd. Inteira</th>
                                <th>Qtd. Meia</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($venda = $result_vendas->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($venda['nome_cliente']) ?></td>
                                <td><?= htmlspecialchars($venda['cpf_cliente']) ?></td>
                                <td><?= htmlspecialchars($venda['numero_lote']) ?></td>
                                <td><?= htmlspecialchars($venda['nome_customizado'] ?: $venda['nome_setor']) ?></td>
                                <td><?= htmlspecialchars($venda['qtd_inteira']) ?></td>
                                <td><?= htmlspecialchars($venda['qtd_meia']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <?php endif; ?>

            <?php $stmt_vendas->close(); ?>

        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

<div id="modalStart" class="modal" style="display:none;">
    <div class="modal-content">
        <h3>Ativar Evento</h3>
        <form id="formStart">
            <input type="hidden" name="evento_id" id="evento_id">
            <div id="lotesContainer"></div>
            <div class="modal-buttons">
                <button type="submit" class="btn-primary">Salvar e Ativar</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modalStart').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Abrir modal para ativar evento
document.querySelectorAll('.btn-start').forEach(btn => {
    btn.addEventListener('click', function() {
        const eventoId = this.dataset.id;
        document.getElementById('evento_id').value = eventoId;

        fetch('get_lotes.php?evento_id=' + eventoId)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('lotesContainer');
            container.innerHTML = '';
            data.forEach(lote => {
                const loteDiv = document.createElement('div');
                loteDiv.classList.add('lote');
                loteDiv.innerHTML = `<h4>Lote ${lote.numero_lote}</h4>
                    <label>Data início venda:</label>
                    <input type="datetime-local" name="lote[${lote.id}][inicio_venda]" value="${lote.inicio_venda || ''}" required><br>
                    <label>Data fim venda:</label>
                    <input type="datetime-local" name="lote[${lote.id}][fim_venda]" value="${lote.fim_venda || ''}" required><br>
                    <label>Quantidade máxima:</label>
                    <input type="number" name="lote[${lote.id}][quantidade]" value="${lote.quantidade}" required><br>
                    <div class="setores"></div>
                `;
                container.appendChild(loteDiv);
                const setoresDiv = loteDiv.querySelector('.setores');
                lote.setores.forEach(setor => {
                    const setorDiv = document.createElement('div');
                    setorDiv.classList.add('setor-ingresso');
                    setorDiv.innerHTML = `
                        <label>Setor:</label>
                        <input type="text" name="lote[${lote.id}][setores][${setor.id}][nome_setor]" value="${setor.nome_customizado || setor.nome_setor}" required>
                        <label>Qtd:</label>
                        <input type="number" name="lote[${lote.id}][setores][${setor.id}][quantidade]" value="${setor.quantidade}" required>
                        <label>Valor Inteira:</label>
                        <input type="number" name="lote[${lote.id}][setores][${setor.id}][valor_inteira]" value="${setor.valor_inteira}" step="0.01" required>
                        <label>Valor Meia:</label>
                        <input type="number" name="lote[${lote.id}][setores][${setor.id}][valor_meia]" value="${setor.valor_meia}" step="0.01" required>
                    `;
                    setoresDiv.appendChild(setorDiv);
                });
            });
            document.getElementById('modalStart').style.display = 'flex';
        });
    });
});

// Salvar e ativar evento
document.getElementById('formStart').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    fetch('salvar_lotes_ativacao.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(res => { 
            alert(res); 
            location.reload(); 
        });
});

// Pausar evento
document.querySelectorAll('.btn-pause').forEach(btn => {
    btn.addEventListener('click', function(){
        const eventoId = this.dataset.id;
        fetch('pausar_evento.php', {
            method: 'POST',
            body: new URLSearchParams({ id: eventoId })
        }).then(res => res.text())
          .then(res => { 
            alert(res); 
            location.reload(); 
        });
    });
});

// Cancelar evento
document.querySelectorAll('.btn-cancel').forEach(btn => {
    btn.addEventListener('click', function(){
        const eventoId = this.dataset.id;
        fetch('cancelar_evento.php', {
            method: 'POST',
            body: new URLSearchParams({ id: eventoId })
        }).then(res => res.text())
          .then(res => { 
            alert(res); 
            location.reload(); 
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>