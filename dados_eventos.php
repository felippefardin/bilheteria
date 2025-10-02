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
<title>Meus Eventos</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.container { margin: 20px auto; width: 90%; background-color: #fff; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
h2 { text-align: center; }
.table-eventos, .table-lotes { width: 100%; border-collapse: collapse; margin-top: 20px; }
.table-eventos th, .table-eventos td, .table-lotes th, .table-lotes td { padding: 10px; border: 1px solid #ddd; text-align: left; }
.table-eventos th, .table-lotes th { background-color: #f8f8f8; }
.table-eventos tr:nth-child(even), .table-lotes tr:nth-child(even) { background-color: #f2f2f2; }
button { padding: 6px 12px; margin: 3px; cursor: pointer; border: none; border-radius: 5px; font-size: 13px; }
.btn-start { background-color: green; color: white; }
.btn-pause { background-color: orange; color: white; }
.btn-cancel { background-color: red; color: white; }

/* Modal */
.modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; }
.modal-content { background-color: #fff; padding: 20px; border-radius: 10px; max-width: 600px; width: 100%; text-align: center; }
.lote { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; background-color: #f9f9f9; }
.lote h4 { margin-top: 0; }
.setores .setor-ingresso { margin-bottom: 10px; }
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
            $stmt_lotes = $mysqli->prepare("SELECT * FROM eventos_lotes WHERE evento_id=? ORDER BY numero_lote ASC");
            $stmt_lotes->bind_param("i", $e['id']);
            $stmt_lotes->execute();
            $result_lotes = $stmt_lotes->get_result();
            ?>

            <?php if($result_lotes->num_rows > 0): ?>
            <tr>
                <td colspan="5">
                    <table class="table-lotes">
                        <thead>
                            <tr>
                                <th>Lote</th>
                                <th>Tipo</th>
                                <th>Quantidade</th>
                                <th>Setores</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while($lote = $result_lotes->fetch_assoc()): ?>
                            <tr>
                                <td><?= $lote['numero_lote'] ?></td>
                                <td><?= $lote['tipo_evento'] ?></td>
                                <td><?= $lote['quantidade'] ?></td>
                                <td>
                                    <?php
                                    $stmt_setores = $mysqli->prepare("SELECT nome_setor, nome_customizado, quantidade, valor_inteira, valor_meia FROM eventos_lotes_setores WHERE lote_id=? ORDER BY id ASC");
                                    $stmt_setores->bind_param("i", $lote['id']);
                                    $stmt_setores->execute();
                                    $result_setores = $stmt_setores->get_result();
                                    while($setor = $result_setores->fetch_assoc()){
                                        echo "Setor: ".($setor['nome_customizado'] ?: $setor['nome_setor'])."<br>";
                                        echo "Qtd: ".$setor['quantidade']."<br>";
                                        echo "Inteira: R$ ".number_format($setor['valor_inteira'], 2, ',', '.')." / Meia: R$ ".number_format($setor['valor_meia'], 2, ',', '.')."<hr>";
                                    }
                                    $stmt_setores->close();
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </td>
            </tr>
            <?php endif; ?>

            <?php $stmt_lotes->close(); ?>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

<div id="modalStart" class="modal">
    <div class="modal-content">
        <h3>Ativar Evento</h3>
        <form id="formStart">
            <input type="hidden" name="evento_id" id="evento_id">
            <div id="lotesContainer"></div>
            <button type="submit">Salvar e Ativar</button>
            <button type="button" onclick="document.getElementById('modalStart').style.display='none'">Cancelar</button>
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
    fetch('ativar_evento.php', { method: 'POST', body: formData })
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

// ... no final do arquivo dados_eventos.php
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

// ... os outros códigos JavaScript continuam iguais

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