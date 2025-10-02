<?php
session_start();
include 'conexao.php';
include 'includes/header.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$evento_id = isset($_GET['evento_id']) ? intval($_GET['evento_id']) : 0;
$numero_lote = isset($_GET['lote']) ? intval($_GET['lote']) : 0;

// Buscar evento
$stmt = $mysqli->prepare("SELECT * FROM eventos WHERE id = ?");
$stmt->bind_param("i", $evento_id);
$stmt->execute();
$evento_result = $stmt->get_result();
if($evento_result->num_rows == 0){
    echo "<p>Evento não encontrado.</p>";
    include 'includes/footer.php';
    exit();
}
$evento = $evento_result->fetch_assoc();

// Buscar lote
$stmt = $mysqli->prepare("SELECT * FROM eventos_lotes WHERE evento_id = ? AND numero_lote = ?");
$stmt->bind_param("ii", $evento_id, $numero_lote);
$stmt->execute();
$lote_result = $stmt->get_result();
if($lote_result->num_rows == 0){
    echo "<p>Lote não encontrado.</p>";
    include 'includes/footer.php';
    exit();
}
$lote = $lote_result->fetch_assoc();

// Buscar setores do lote e calcular disponível
$setores_result = $mysqli->query("SELECT * FROM eventos_lotes_setores WHERE lote_id = {$lote['id']}");
$setores = [];
while($s = $setores_result->fetch_assoc()){
    // Quantidade vendida deste setor
    $vendidos_result = $mysqli->query("SELECT SUM(qtd_inteira + qtd_meia) AS vendidos FROM vendas_detalhes vd 
        INNER JOIN vendas v ON vd.venda_id = v.id 
        WHERE vd.setor_id = {$s['id']}");
    $vendidos_row = $vendidos_result->fetch_assoc();
    $vendidos = $vendidos_row['vendidos'] ?? 0;

    // Disponível real
    $s['disponivel'] = $s['quantidade'] - $vendidos;
    $setores[] = $s;
}

// Processar envio do formulário
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nome = $_POST['nome'];
    $cpf = preg_replace('/\D/', '', $_POST['cpf']); // remover caracteres não numéricos
    $totalIngressos = 0;
    $totalValor = 0;
    $ingressos_comprados = [];

    foreach($setores as $s){
        $qtdInteira = isset($_POST['quantidade_inteira'][$s['id']]) ? intval($_POST['quantidade_inteira'][$s['id']]) : 0;
        $qtdMeia = isset($_POST['quantidade_meia'][$s['id']]) ? intval($_POST['quantidade_meia'][$s['id']]) : 0;

        if($qtdInteira + $qtdMeia > $s['disponivel']){
            echo "<p style='color:red;'>Quantidade selecionada para ".htmlspecialchars($s['nome_customizado'] ?? $s['nome_setor'])." excede o estoque disponível.</p>";
            include 'includes/footer.php';
            exit();
        }

        $totalIngressos += $qtdInteira + $qtdMeia;
        $totalValor += ($qtdInteira * $s['valor_inteira']) + ($qtdMeia * $s['valor_meia']);

        if($qtdInteira + $qtdMeia > 0){
            $ingressos_comprados[] = [
                'setor_id' => $s['id'],
                'inteira' => $qtdInteira,
                'meia' => $qtdMeia
            ];
        }
    }

    if($totalIngressos == 0){
        echo "<p style='color:red;'>Você precisa selecionar pelo menos 1 ingresso.</p>";
        include 'includes/footer.php';
        exit();
    }

    if($totalIngressos > 5){
        echo "<p style='color:red;'>Você só pode comprar até 5 ingressos por CPF.</p>";
        include 'includes/footer.php';
        exit();
    }

    // Inserir venda
    $stmt = $mysqli->prepare("INSERT INTO vendas (evento_id, lote_id, usuario_id, nome_cliente, cpf_cliente, quantidade_total, valor_total, data_compra) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiissid", $evento_id, $lote['id'], $_SESSION['usuario_id'], $nome, $cpf, $totalIngressos, $totalValor);
    $stmt->execute();
    $venda_id = $stmt->insert_id;

    // Inserir detalhes da venda
    foreach($ingressos_comprados as $ic){
        $stmt = $mysqli->prepare("INSERT INTO vendas_detalhes (venda_id, setor_id, qtd_inteira, qtd_meia) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiii", $venda_id, $ic['setor_id'], $ic['inteira'], $ic['meia']);
        $stmt->execute();
    }

    echo "<p style='color:green;'>Compra registrada com sucesso! Total: R$ ".number_format($totalValor,2,',','.')."</p>";
}
?>

<div class="container">
    <h2>Comprar Ingressos - <?= htmlspecialchars($evento['titulo']) ?></h2>
    <p><strong>Lote <?= $lote['numero_lote'] ?></strong> - Disponível: <?= $lote['quantidade'] ?></p>

    <form method="post">
        <label>Seu Nome:</label><br>
        <input type="text" name="nome" required><br><br>
        <label>CPF:</label><br>
        <input type="text" name="cpf" required pattern="\d{11}" title="Apenas números, 11 dígitos"><br><br>

        <table border="1" cellpadding="5" cellspacing="0" style="width:100%; margin-top:15px;">
            <tr>
                <th>Setor</th>
                <th>Valor Inteira</th>
                <th>Valor Meia</th>
                <th>Qtd Inteira</th>
                <th>Qtd Meia</th>
                <th>Subtotal</th>
            </tr>
            <?php foreach($setores as $s): ?>
                <tr data-setor-id="<?= $s['id'] ?>">
                    <td><?= htmlspecialchars($s['nome_customizado'] ?? $s['nome_setor']) ?></td>
                    <td class="valor-inteira"><?= number_format($s['valor_inteira'],2,',','.') ?></td>
                    <td class="valor-meia"><?= number_format($s['valor_meia'],2,',','.') ?></td>
                    <td><input type="number" name="quantidade_inteira[<?= $s['id'] ?>]" min="0" max="<?= $s['disponivel'] ?>" value="0"></td>
                    <td><input type="number" name="quantidade_meia[<?= $s['id'] ?>]" min="0" max="<?= $s['disponivel'] ?>" value="0"></td>
                    <td class="subtotal">0,00</td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="5" style="text-align:right"><strong>Total:</strong></td>
                <td id="total">0,00</td>
            </tr>
        </table>

        <button type="submit">Finalizar Compra</button>
    </form>
</div>

<script>
// Atualiza subtotal e total
function parseBR(str){ return parseFloat(str.replace('.', '').replace(',', '.')); }

function atualizarTotais(){
    let total = 0;
    document.querySelectorAll('tr[data-setor-id]').forEach(tr => {
        const valorInteira = parseBR(tr.querySelector('.valor-inteira').innerText);
        const valorMeia = parseBR(tr.querySelector('.valor-meia').innerText);
        let qtdInteira = parseInt(tr.querySelector('[name^="quantidade_inteira"]').value) || 0;
        let qtdMeia = parseInt(tr.querySelector('[name^="quantidade_meia"]').value) || 0;
        const subtotal = (valorInteira * qtdInteira) + (valorMeia * qtdMeia);
        tr.querySelector('.subtotal').innerText = subtotal.toFixed(2).replace('.',',');
        total += subtotal;
    });
    document.getElementById('total').innerText = total.toFixed(2).replace('.',',');
}

document.querySelectorAll('input[name^="quantidade_inteira"], input[name^="quantidade_meia"]').forEach(input => {
    input.addEventListener('input', atualizarTotais);
});
</script>

<?php include 'includes/footer.php'; ?>
