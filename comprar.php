<?php
session_start();
include 'conexao.php';
include 'includes/header.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$evento_id = isset($_GET['evento_id']) ? intval($_GET['evento_id']) : 0;
$lote_id = isset($_GET['lote_id']) ? intval($_GET['lote_id']) : 0;

$erro = '';
$sucesso = '';

// Buscar evento e lote com validação
$stmt = $mysqli->prepare("SELECT e.*, el.id AS lote_id, el.numero_lote, el.quantidade AS lote_quantidade FROM eventos e INNER JOIN eventos_lotes el ON e.id = el.evento_id WHERE e.id = ? AND el.id = ? AND e.status = 'ativo'");
$stmt->bind_param("ii", $evento_id, $lote_id);
$stmt->execute();
$evento_result = $stmt->get_result();

if($evento_result->num_rows == 0){
    $erro = "Evento ou lote não encontrado.";
    $stmt->close();
    include 'includes/footer.php';
    exit();
}
$evento = $evento_result->fetch_assoc();
$stmt->close();

// Buscar setores do lote e calcular disponibilidade
$setores_result = $mysqli->query("SELECT id, nome_customizado, nome_setor, quantidade, valor_inteira, valor_meia FROM eventos_lotes_setores WHERE lote_id = {$lote_id}");
$setores = [];
while($s = $setores_result->fetch_assoc()){
    $vendidos_result = $mysqli->query("SELECT SUM(qtd_inteira + qtd_meia) AS vendidos FROM vendas_detalhes vd INNER JOIN vendas v ON vd.venda_id = v.id WHERE vd.setor_id = {$s['id']}");
    $vendidos_row = $vendidos_result->fetch_assoc();
    $vendidos = $vendidos_row['vendidos'] ?? 0;
    $s['disponivel'] = $s['quantidade'] - $vendidos;
    $setores[] = $s;
}

// Processar envio do formulário
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nome = trim($_POST['nome']);
    $cpf = preg_replace('/\D/', '', $_POST['cpf']);
    $totalIngressos = 0;
    $totalValor = 0;
    $ingressos_comprados = [];

    // Validação inicial do CPF e limite de ingressos
    if (strlen($cpf) != 11) {
        $erro = "O CPF deve conter 11 dígitos.";
    } elseif (!ctype_digit($cpf)) {
        $erro = "O CPF deve conter apenas números.";
    } else {
        foreach($setores as $s){
            $qtdInteira = isset($_POST['quantidade_inteira'][$s['id']]) ? intval($_POST['quantidade_inteira'][$s['id']]) : 0;
            $qtdMeia = isset($_POST['quantidade_meia'][$s['id']]) ? intval($_POST['quantidade_meia'][$s['id']]) : 0;
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
            $erro = "Você precisa selecionar pelo menos 1 ingresso.";
        } elseif($totalIngressos > 5){
            $erro = "Você só pode comprar até 5 ingressos por CPF.";
        }
    }

    if(empty($erro)){
        $mysqli->begin_transaction();
        try {
            // Verifica a disponibilidade novamente dentro da transação para evitar condições de corrida
            foreach($ingressos_comprados as $ic){
                $stmt_check = $mysqli->prepare("SELECT quantidade FROM eventos_lotes_setores WHERE id = ? FOR UPDATE");
                $stmt_check->bind_param("i", $ic['setor_id']);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                $setor_db = $result_check->fetch_assoc();
                $stmt_check->close();

                $vendidos_db_result = $mysqli->query("SELECT SUM(qtd_inteira + qtd_meia) AS vendidos FROM vendas_detalhes vd INNER JOIN vendas v ON vd.venda_id = v.id WHERE vd.setor_id = {$ic['setor_id']}");
                $vendidos_db_row = $vendidos_db_result->fetch_assoc();
                $vendidos_db = $vendidos_db_row['vendidos'] ?? 0;

                if(($ic['inteira'] + $ic['meia'] + $vendidos_db) > $setor_db['quantidade']){
                    throw new Exception("Quantidade selecionada excede o estoque disponível.");
                }
            }
            
            // Insere venda
            $stmt = $mysqli->prepare("INSERT INTO vendas (evento_id, lote_id, usuario_id, nome_cliente, cpf_cliente, quantidade_total, valor_total, data_compra) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiissid", $evento_id, $lote_id, $_SESSION['usuario_id'], $nome, $cpf, $totalIngressos, $totalValor);
            $stmt->execute();
            $venda_id = $stmt->insert_id;
            $stmt->close();
    
            // Insere detalhes da venda
            foreach($ingressos_comprados as $ic){
                $stmt = $mysqli->prepare("INSERT INTO vendas_detalhes (venda_id, setor_id, qtd_inteira, qtd_meia) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiii", $venda_id, $ic['setor_id'], $ic['inteira'], $ic['meia']);
                $stmt->execute();
                $stmt->close();
            }
    
            $mysqli->commit();
            $sucesso = "Compra registrada com sucesso! Total: R$ ".number_format($totalValor,2,',','.');
        } catch (Exception $e) {
            $mysqli->rollback();
            $erro = "Erro ao processar a compra: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <h2>Comprar Ingressos - <?= htmlspecialchars($evento['titulo']) ?></h2>
    <p><strong>Lote <?= htmlspecialchars($evento['numero_lote']) ?></strong> - Disponível: <?= htmlspecialchars($evento['lote_quantidade']) ?></p>

    <?php if ($erro) echo "<p style='color:red;'>$erro</p>"; ?>
    <?php if ($sucesso) echo "<p style='color:green;'>$sucesso</p>"; ?>

    <form method="post" onsubmit="return validarQuantidadeTotal()">
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
                    <td><?= htmlspecialchars($s['nome_customizado'] ?? $s['nome_setor']) ?> (Disponível: <?= $s['disponivel'] ?>)</td>
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
// Validação e atualização de totais em tempo real
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

function validarQuantidadeTotal(){
    let totalIngressos = 0;
    document.querySelectorAll('input[name^="quantidade_inteira"], input[name^="quantidade_meia"]').forEach(input => {
        totalIngressos += parseInt(input.value) || 0;
    });
    if(totalIngressos > 5){
        alert("Você só pode comprar um máximo de 5 ingressos por CPF.");
        return false;
    }
    if(totalIngressos === 0){
        alert("Você deve selecionar pelo menos 1 ingresso.");
        return false;
    }
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>