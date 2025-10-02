<?php
session_start();
include 'conexao.php';
include 'includes/header.php';

if (!isset($_GET['id'])) {
    echo "<p style='color:red;'>Evento não encontrado.</p>";
    exit();
}

$evento_id = intval($_GET['id']);

// Buscar detalhes do evento + produtor
$stmt = $mysqli->prepare("
    SELECT e.*, u.nome_completo AS produtor_nome, u.email AS produtor_email
    FROM eventos e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color:red;'>Evento não encontrado ou não está ativo.</p>";
    exit();
}

$evento = $result->fetch_assoc();

// Verificar se o usuário logado é o produtor
$is_proprietario = isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $evento['usuario_id'];

// Buscar lotes e setores
$lotes_result = $mysqli->query("SELECT * FROM eventos_lotes WHERE evento_id = {$evento['id']}");
$lotes = [];
while ($lote = $lotes_result->fetch_assoc()) {
    $setores_result = $mysqli->query("SELECT * FROM eventos_lotes_setores WHERE lote_id = {$lote['id']}");
    $setores = [];
    while ($s = $setores_result->fetch_assoc()) {
        $setores[] = $s;
    }
    $lote['setores'] = $setores;
    $lotes[] = $lote;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Detalhes do Evento</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* CSS básico */
.container { max-width:1100px; margin:20px auto; padding:20px; font-family:Arial,sans-serif; color:#333; }
.evento-header { display:flex; flex-wrap:wrap; gap:20px; align-items:center; margin-bottom:25px; }
.evento-header img { width:100%; max-width:500px; max-height:500px; object-fit:contain; border-radius:12px; box-shadow:0 6px 15px rgba(0,0,0,0.2);}
.evento-info { flex:1; }
.evento-info h2 { font-size:32px; color:#f05a28; margin-bottom:10px; }
.evento-info p { margin:6px 0; font-size:16px; }
.lote-card { border:1px solid #ddd; border-radius:8px; padding:15px; margin-bottom:15px; background:linear-gradient(135deg,#fdf1e5,#fff7f0); transition: transform 0.2s, box-shadow 0.2s; }
.lote-card:hover { transform: translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,0.15);}
.lote-card h3 { color:#d94e1f; margin-bottom:8px; }
.setor-table { width:100%; border-collapse:collapse; margin-top:10px; }
.setor-table th, .setor-table td { padding:8px; border:1px solid #ccc; text-align:center; }
.setor-table th { background-color:#f05a28; color:#fff; }
.btn-continuar { display:inline-block; margin-top:15px; padding:10px 25px; background-color:#28a745; color:white; border-radius:6px; text-decoration:none; font-weight:bold; }
.btn-continuar:hover { background-color:#218838; }
.policy { margin-top:25px; padding:15px; background:#fff3cd; border-left:6px solid #ffeeba; border-radius:6px; }
.produtor-email { color:#0056b3; cursor:pointer; text-decoration:underline; }
.modal-bg { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:9999; }
.modal { background:white; padding:20px; border-radius:8px; width:90%; max-width:400px; position:relative; }
.modal h3 { margin-top:0; color:#f05a28; }
.modal input, .modal textarea { width:100%; margin:5px 0 10px 0; padding:8px; border-radius:4px; border:1px solid #ccc; }
.modal button { background:#f05a28; color:white; padding:10px; border:none; border-radius:4px; cursor:pointer; }
.modal button:hover { background:#d04921; }
.modal-close { position:absolute; top:10px; right:10px; cursor:pointer; font-weight:bold; font-size:18px; }
</style>
</head>
<body>
<div class="container">
    <div class="evento-header">
        <img src="<?= htmlspecialchars($evento['imagem'] ?? 'assets/img/default.jpg') ?>" alt="<?= htmlspecialchars($evento['titulo']) ?>">
        <div class="evento-info">
            <h2><?= htmlspecialchars($evento['titulo']) ?></h2>
            <p><strong>Categoria:</strong> <?= htmlspecialchars($evento['categoria']) ?></p>
            <p><strong>Data:</strong> 
                <?= !empty($evento['data_inicio']) ? date('d/m/Y', strtotime($evento['data_inicio'])) : 'Data não definida' ?>
                <?= !empty($evento['hora_inicio']) ? ' às ' . date('H:i', strtotime($evento['hora_inicio'])) : '' ?>
                <?php if (!empty($evento['data_fim'])): ?>
                    - <?= date('d/m/Y', strtotime($evento['data_fim'])) ?>
                    <?= !empty($evento['hora_fim']) ? ' às ' . date('H:i', strtotime($evento['hora_fim'])) : '' ?>
                <?php endif; ?>
            </p>
            <p><strong>Produtor:</strong> <?= htmlspecialchars($evento['produtor_nome']) ?> - 
               <span class="produtor-email" onclick="abrirModal()"><?= htmlspecialchars($evento['produtor_email']) ?></span></p>
            <p><strong>Descrição:</strong><br><?= nl2br(htmlspecialchars($evento['descricao'])) ?></p>
        </div>
    </div>

    <?php foreach($lotes as $lote): ?>
    <div class="lote-card">
        <h3>Lote <?= $lote['numero_lote'] ?> - <?= $lote['quantidade'] ?> ingressos <?= $is_proprietario ? "(Disponível: {$lote['quantidade']})" : "" ?></h3>
        <table class="setor-table">
            <tr>
                <th>Setor</th>
                <th>Inteira (R$)</th>
                <th>Meia (R$)</th>
                <?php if($is_proprietario): ?><th>Quantidade</th><?php endif; ?>
            </tr>
            <?php foreach($lote['setores'] as $s): ?>
                <tr>
                    <td><?= htmlspecialchars(!empty($s['nome_customizado']) ? $s['nome_customizado'] : $s['nome_setor']) ?></td>
                    <td><?= number_format($s['valor_inteira'], 2, ',', '.') ?></td>
                    <td><?= number_format($s['valor_meia'], 2, ',', '.') ?></td>
                    <?php if($is_proprietario): ?><td><?= $s['quantidade'] ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </table>
        <a class="btn-continuar" href="comprar.php?evento_id=<?= $evento['id'] ?>&lote=<?= $lote['numero_lote'] ?>">Continuar Compra</a>
    </div>
    <?php endforeach; ?>

    <div class="policy">
        <p><strong>Política de Cancelamento:</strong> Solicitar cancelamento via e-mail do produtor (<?= htmlspecialchars($evento['produtor_email']) ?>) nos 7 primeiros dias. Após isso, o estorno deve ser solicitado até 72h antes do evento. Dúvidas: enviar mensagem ao produtor clicando no e-mail acima.</p>
    </div>
</div>

<!-- Modal Contato -->
<div class="modal-bg" id="modalContato">
    <div class="modal">
        <span class="modal-close" onclick="fecharModal()">×</span>
        <h3>Enviar mensagem ao produtor</h3>
        <form method="post" action="enviar_mensagem_produtor.php">
            <input type="hidden" name="email_produtor" value="<?= htmlspecialchars($evento['produtor_email']) ?>">
            <label>Nome completo:</label>
            <input type="text" name="nome" required>
            <label>CPF:</label>
            <input type="text" name="cpf" required pattern="\d{11}" title="Apenas números, 11 dígitos">
            <label>Telefone:</label>
            <input type="text" name="telefone" required>
            <label>Mensagem:</label>
            <textarea name="mensagem" rows="5" required></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>
</div>

<script>
function abrirModal() { document.getElementById('modalContato').style.display = 'flex'; }
function fecharModal() { document.getElementById('modalContato').style.display = 'none'; }
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
