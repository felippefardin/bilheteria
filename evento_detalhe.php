<?php
session_start();
include 'conexao.php';
include 'includes/header.php';

if (!isset($_GET['id'])) {
    echo "<p style='color:red;'>Evento não encontrado.</p>";
    exit();
}

$evento_id = intval($_GET['id']);
$is_proprietario = false;

// Consulta única para evento, lotes e setores (otimizada)
$stmt = $mysqli->prepare("
    SELECT e.*, u.nome_completo AS produtor_nome, u.email AS produtor_email,
           el.id AS lote_id, el.numero_lote, el.quantidade AS lote_quantidade, el.inicio_venda, el.fim_venda,
           els.id AS setor_id, els.nome_setor, els.nome_customizado, els.quantidade AS setor_quantidade, els.valor_inteira, els.valor_meia
    FROM eventos e
    INNER JOIN usuarios u ON e.usuario_id = u.id
    LEFT JOIN eventos_lotes el ON e.id = el.evento_id
    LEFT JOIN eventos_lotes_setores els ON el.id = els.lote_id
    WHERE e.id = ? AND e.status = 'ativo'
    ORDER BY el.numero_lote ASC, els.nome_setor ASC
");
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color:red;'>Evento não encontrado ou não está ativo.</p>";
    exit();
}

$event_data = $result->fetch_all(MYSQLI_ASSOC);
$evento = $event_data[0];

// Organiza os dados em um formato mais útil
$lotes_organizados = [];
foreach ($event_data as $row) {
    $lote_id = $row['lote_id'];
    if (!isset($lotes_organizados[$lote_id])) {
        $lotes_organizados[$lote_id] = [
            'id' => $row['lote_id'],
            'numero_lote' => $row['numero_lote'],
            'quantidade' => $row['lote_quantidade'],
            'inicio_venda' => $row['inicio_venda'],
            'fim_venda' => $row['fim_venda'],
            'setores' => [],
        ];
    }
    if ($row['setor_id']) {
        $lotes_organizados[$lote_id]['setores'][] = [
            'id' => $row['setor_id'],
            'nome' => !empty($row['nome_customizado']) ? $row['nome_customizado'] : $row['nome_setor'],
            'quantidade' => $row['setor_quantidade'],
            'valor_inteira' => $row['valor_inteira'],
            'valor_meia' => $row['valor_meia']
        ];
    }
}

// Verifica se o usuário logado é o produtor
$is_proprietario = isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $evento['usuario_id'];
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
    <?php if (!empty($evento['video'])): ?>
        <div style="margin-top: 20px; text-align: center;">
            <video controls style="max-width: 100%; border-radius: 12px;">
                <source src="<?= htmlspecialchars($evento['video']) ?>" type="video/mp4">
                Seu navegador não suporta a tag de vídeo.
            </video>
        </div>
    <?php endif; ?>

    <?php foreach($lotes_organizados as $lote): ?>
    <div class="lote-card">
        <h3>Lote <?= htmlspecialchars($lote['numero_lote']) ?> 
        <?php if ($is_proprietario): ?>
            - <?= htmlspecialchars($lote['quantidade']) ?> ingressos
        <?php endif; ?>
        </h3>
        <table class="setor-table">
            <tr>
                <th>Setor</th>
                <th>Inteira (R$)</th>
                <th>Meia (R$)</th>
                <?php if ($is_proprietario): ?>
                    <th>Quantidade</th>
                <?php endif; ?>
            </tr>
            <?php foreach($lote['setores'] as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['nome']) ?></td>
                    <td><?= number_format($s['valor_inteira'], 2, ',', '.') ?></td>
                    <td><?= number_format($s['valor_meia'], 2, ',', '.') ?></td>
                    <?php if ($is_proprietario): ?>
                        <td><?= htmlspecialchars($s['quantidade']) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </table>
        <a class="btn-continuar" href="comprar.php?evento_id=<?= $evento['id'] ?>&lote_id=<?= $lote['id'] ?>">Continuar Compra</a>
    </div>
    <?php endforeach; ?>

    <div class="policy">
        <p><strong>Política de Cancelamento:</strong> Solicitar cancelamento via e-mail do produtor (<?= htmlspecialchars($evento['produtor_email']) ?>) nos 7 primeiros dias. Após isso, o estorno deve ser solicitado até 72h antes do evento. Dúvidas: enviar mensagem ao produtor clicando no e-mail acima.</p>
    </div>
</div>

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