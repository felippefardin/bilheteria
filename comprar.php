<?php
session_start();
include 'conexao.php';
include 'includes/header.php';

// Importa classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';
require 'PHPMailer/PHPMailer/src/Exception.php';

// FPDF e QRCODE
require_once __DIR__ . '/lib/fpdf/fpdf.php';
require_once __DIR__ . '/lib/phpqrcode/qrlib.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$evento_id = isset($_GET['evento_id']) ? intval($_GET['evento_id']) : 0;
$lote_id = isset($_GET['lote_id']) ? intval($_GET['lote_id']) : 0;

$erro = '';
$sucesso = '';

// Buscar evento e lote com validação
$stmt = $mysqli->prepare("
    SELECT e.*, el.id AS lote_id, el.numero_lote, el.quantidade AS lote_quantidade,
           els.id AS setor_id, els.nome_setor, els.nome_customizado, els.quantidade AS setor_quantidade, els.valor_inteira, els.valor_meia
    FROM eventos e
    INNER JOIN eventos_lotes el ON e.id = el.evento_id
    INNER JOIN eventos_lotes_setores els ON el.id = els.lote_id
    WHERE e.id = ? AND el.id = ? AND e.status = 'ativo'
    ORDER BY els.id ASC
");
$stmt->bind_param("ii", $evento_id, $lote_id);
$stmt->execute();
$evento_result = $stmt->get_result();

if ($evento_result->num_rows == 0) {
    $erro = "Evento ou lote não encontrado.";
    $stmt->close();
} else {
    $evento = $evento_result->fetch_assoc();
    // Reinicia o ponteiro do resultado para buscar todos os setores
    $evento_result->data_seek(0);
    $setores = [];
    while($row = $evento_result->fetch_assoc()){
        // Calcular ingressos já vendidos para cada setor
        $vendidos_result = $mysqli->query("SELECT SUM(qtd_inteira + qtd_meia) AS vendidos FROM vendas_detalhes vd INNER JOIN vendas v ON vd.venda_id = v.id WHERE vd.setor_id = {$row['setor_id']}");
        $vendidos_row = $vendidos_result->fetch_assoc();
        $vendidos = $vendidos_row['vendidos'] ?? 0;
        
        $row['disponivel'] = $row['setor_quantidade'] - $vendidos;
        $setores[] = $row;
    }
    $stmt->close();
}

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($erro)) {
    $nome_comprador = trim($_POST['nome']);
    $cpf_comprador = preg_replace('/\D/', '', $_POST['cpf']);
    
    // Buscar email do usuário logado
    $email_comprador = '';
    $stmt_user = $mysqli->prepare("SELECT email FROM usuarios WHERE id = ?");
    $stmt_user->bind_param("i", $_SESSION['usuario_id']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user_data = $result_user->fetch_assoc();
        $email_comprador = $user_data['email'];
    }
    $stmt_user->close();

    $totalIngressos = 0;
    $totalValor = 0;
    $ingressos_comprados = [];

    // Validação inicial do CPF e limite de ingressos
    if (strlen($cpf_comprador) != 11) {
        $erro = "O CPF deve conter 11 dígitos.";
    } elseif (!ctype_digit($cpf_comprador)) {
        $erro = "O CPF deve conter apenas números.";
    } else {
        foreach ($setores as $s) {
            $qtdInteira = isset($_POST['quantidade_inteira'][$s['setor_id']]) ? intval($_POST['quantidade_inteira'][$s['setor_id']]) : 0;
            $qtdMeia = isset($_POST['quantidade_meia'][$s['setor_id']]) ? intval($_POST['quantidade_meia'][$s['setor_id']]) : 0;
            $totalIngressos += $qtdInteira + $qtdMeia;
            $totalValor += ($qtdInteira * $s['valor_inteira']) + ($qtdMeia * $s['valor_meia']);
    
            if ($qtdInteira + $qtdMeia > 0) {
                $ingressos_comprados[] = [
                    'setor_id' => $s['setor_id'],
                    'qtd_inteira' => $qtdInteira,
                    'qtd_meia' => $qtdMeia,
                    'valor_inteira' => $s['valor_inteira'],
                    'valor_meia' => $s['valor_meia'],
                    'nome_setor' => !empty($s['nome_customizado']) ? $s['nome_customizado'] : $s['nome_setor'],
                    'nome_lote' => $evento['numero_lote']
                ];
            }
        }

        if ($totalIngressos == 0) {
            $erro = "Você precisa selecionar pelo menos 1 ingresso.";
        } elseif ($totalIngressos > 4) {
            $erro = "Você só pode comprar até 4 ingressos por CPF.";
        }
    }

    if (empty($erro)) {
        $mysqli->begin_transaction();
        try {
            // Verifica a disponibilidade novamente dentro da transação para evitar condições de corrida
            foreach ($ingressos_comprados as $ic) {
                $stmt_check = $mysqli->prepare("SELECT quantidade FROM eventos_lotes_setores WHERE id = ? FOR UPDATE");
                $stmt_check->bind_param("i", $ic['setor_id']);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                $setor_db = $result_check->fetch_assoc();
                $stmt_check->close();

                $vendidos_db_result = $mysqli->query("SELECT SUM(qtd_inteira + qtd_meia) AS vendidos FROM vendas_detalhes vd WHERE vd.setor_id = {$ic['setor_id']}");
                $vendidos_db_row = $vendidos_db_result->fetch_assoc();
                $vendidos_db = $vendidos_db_row['vendidos'] ?? 0;

                if (($ic['qtd_inteira'] + $ic['qtd_meia'] + $vendidos_db) > $setor_db['quantidade']) {
                    throw new Exception("Quantidade selecionada excede o estoque disponível para o setor " . $ic['nome_setor'] . ".");
                }
            }
            
            // Simulação de pagamento autorizado
            // Insere venda
            $stmt = $mysqli->prepare("INSERT INTO vendas (evento_id, lote_id, usuario_id, nome_cliente, cpf_cliente, quantidade_total, valor_total, data_compra) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiissid", $evento_id, $lote_id, $_SESSION['usuario_id'], $nome_comprador, $cpf_comprador, $totalIngressos, $totalValor);
            $stmt->execute();
            $venda_id = $stmt->insert_id;
            $stmt->close();
    
            // Insere detalhes da venda
            foreach ($ingressos_comprados as $ic) {
                $stmt = $mysqli->prepare("INSERT INTO vendas_detalhes (venda_id, setor_id, qtd_inteira, qtd_meia) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiii", $venda_id, $ic['setor_id'], $ic['qtd_inteira'], $ic['qtd_meia']);
                $stmt->execute();
                $stmt->close();
            }

            // Gerar e-mail de confirmação com ingressos
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Use o seu host SMTP
                $mail->SMTPAuth = true;
                $mail->Username = 'felippefardin@gmail.com'; // Seu e-mail
                $mail->Password = 'zngp biyj beeq hkqy'; // Sua senha ou App Password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                $mail->setFrom('no-reply@bilheteriaonline.com.br', 'Bilheteria Online');
                $mail->addAddress($email_comprador, $nome_comprador);
                $mail->isHTML(true);
                $mail->Subject = 'Seus Ingressos para ' . $evento['titulo'];
                
                $mail_body = "Olá <b>" . htmlspecialchars($nome_comprador) . "</b>,<br><br>Sua compra foi autorizada! Seguem os ingressos para o evento <b>" . htmlspecialchars($evento['titulo']) . "</b>:<br><br>";
                
                foreach ($ingressos_comprados as $ic) {
                    $setor_nome = !empty($ic['nome_customizado']) ? $ic['nome_customizado'] : $ic['nome_setor'];
                    $lote_numero = (int)$evento['numero_lote'];
                    
                    for ($i = 0; $i < $ic['qtd_inteira']; $i++) {
                        $ingresso_tipo = 'Inteira';
                        $ingresso_valor = $ic['valor_inteira'];
                        $qr_code_data = uniqid('ing_') . time();
                        
                        $stmt_ingresso = $mysqli->prepare("INSERT INTO ingressos (venda_id, usuario_id, evento_id, lote_id, setor_id, evento_nome, lote_nome, setor_nome, data_evento, hora_evento, tipo_ingresso, valor, qr_code, data_criacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                        
                        // Armazena as variáveis em um array para passar para bind_param
                        $params = [
                            $venda_id,
                            $_SESSION['usuario_id'],
                            $evento_id,
                            $lote_id,
                            $ic['setor_id'],
                            $evento['titulo'],
                            $lote_numero,
                            $setor_nome,
                            $evento['data_inicio'],
                            $evento['hora_inicio'],
                            $ingresso_tipo,
                            $ingresso_valor,
                            $qr_code_data
                        ];

                        $stmt_ingresso->bind_param("iiiiisissdssdss", ...$params);

                        $stmt_ingresso->execute();
                        $ingresso_id = $stmt_ingresso->insert_id;
                        $stmt_ingresso->close();

                        $qrTemp = tempnam(sys_get_temp_dir(), 'qr_');
                        QRcode::png($qr_code_data, $qrTemp, QR_ECLEVEL_L, 4);

                        $mail_body .= "<b>Ingresso " . htmlspecialchars($setor_nome) . " (" . $ingresso_tipo . ")</b><br>";
                        $mail_body .= "Valor: R$ " . number_format($ingresso_valor, 2, ',', '.') . "<br>";
                        $mail->addAttachment($qrTemp, "qrcode_ingresso_{$ingresso_id}.png", 'base64', 'image/png');
                    }
                    
                    for ($i = 0; $i < $ic['qtd_meia']; $i++) {
                        $ingresso_tipo = 'Meia';
                        $ingresso_valor = $ic['valor_meia'];
                        $qr_code_data = uniqid('ing_') . time();
                        
                        $stmt_ingresso = $mysqli->prepare("INSERT INTO ingressos (venda_id, usuario_id, evento_id, lote_id, setor_id, evento_nome, lote_nome, setor_nome, data_evento, hora_evento, tipo_ingresso, valor, qr_code, data_criacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                        // Armazena as variáveis em um array para passar para bind_param
                        $params = [
                            $venda_id,
                            $_SESSION['usuario_id'],
                            $evento_id,
                            $lote_id,
                            $ic['setor_id'],
                            $evento['titulo'],
                            $lote_numero,
                            $setor_nome,
                            $evento['data_inicio'],
                            $evento['hora_inicio'],
                            $ingresso_tipo,
                            $ingresso_valor,
                            $qr_code_data
                        ];
                        
                        $stmt_ingresso->bind_param("iiiiisissdssdss", ...$params);
                        $stmt_ingresso->execute();
                        $ingresso_id = $stmt_ingresso->insert_id;
                        $stmt_ingresso->close();

                        $qrTemp = tempnam(sys_get_temp_dir(), 'qr_');
                        QRcode::png($qr_code_data, $qrTemp, QR_ECLEVEL_L, 4);

                        $mail_body .= "<b>Ingresso " . htmlspecialchars($setor_nome) . " (" . $ingresso_tipo . ")</b><br>";
                        $mail_body .= "Valor: R$ " . number_format($ingresso_valor, 2, ',', '.') . "<br>";
                        $mail->addAttachment($qrTemp, "qrcode_ingresso_{$ingresso_id}.png", 'base64', 'image/png');
                    }
                }

                $mail_body .= "<br>Obrigado por comprar na Bilheteria Online!";
                $mail->Body = $mail_body;
                $mail->send();

                $sucesso = "Compra realizada com sucesso! Um e-mail com seus ingressos foi enviado para o seu endereço de cadastro. Total: R$ " . number_format($totalValor, 2, ',', '.');
            } catch (Exception $e) {
                $erro = "Compra realizada, mas não foi possível enviar os ingressos por e-mail. Erro: {$mail->ErrorInfo}";
                $mysqli->rollback();
                die($erro);
            }

            $mysqli->commit();
            
            // Redireciona para a página de "Meus Ingressos"
            header("Location: meu_ingresso.php");
            exit();

        } catch (Exception $e) {
            $mysqli->rollback();
            $erro = "Erro ao processar a compra: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Comprar Evento</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<div class="container">
    <h2>Comprar Ingressos - <?= htmlspecialchars($evento['titulo']) ?></h2>
    <p><strong>Lote <?= htmlspecialchars($evento['numero_lote']) ?></strong> - Disponível: <?= htmlspecialchars($evento['lote_quantidade']) ?></p>

    <?php if ($erro) echo "<p style='color:red;'>$erro</p>"; ?>
    <?php if ($sucesso) {
        echo "<p style='color:green;'>$sucesso</p>";
    } else { ?>

    <form method="post" onsubmit="return validarQuantidadeTotal()">
        <label>Seu Nome:</label><br>
        <input type="text" name="nome" value="<?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?>" required><br><br>
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
            <?php foreach ($setores as $s): ?>
                <tr data-setor-id="<?= $s['setor_id'] ?>">
                    <td><?= htmlspecialchars(!empty($s['nome_customizado']) ? $s['nome_customizado'] : $s['nome_setor']) ?> (Disponível: <?= $s['disponivel'] ?>)</td>
                    <td class="valor-inteira"><?= number_format($s['valor_inteira'], 2, ',', '.') ?></td>
                    <td class="valor-meia"><?= number_format($s['valor_meia'], 2, ',', '.') ?></td>
                    <td><input type="number" name="quantidade_inteira[<?= $s['setor_id'] ?>]" min="0" max="<?= $s['disponivel'] ?>" value="0"></td>
                    <td><input type="number" name="quantidade_meia[<?= $s['setor_id'] ?>]" min="0" max="<?= $s['disponivel'] ?>" value="0"></td>
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
    <?php } ?>
</div>

<script>
// Validação e atualização de totais em tempo real
function parseBR(str) { return parseFloat(str.replace('.', '').replace(',', '.')); }

function atualizarTotais() {
    let total = 0;
    let totalIngressos = 0;
    document.querySelectorAll('tr[data-setor-id]').forEach(tr => {
        const valorInteira = parseBR(tr.querySelector('.valor-inteira').innerText);
        const valorMeia = parseBR(tr.querySelector('.valor-meia').innerText);
        let qtdInteira = parseInt(tr.querySelector('[name^="quantidade_inteira"]').value) || 0;
        let qtdMeia = parseInt(tr.querySelector('[name^="quantidade_meia"]').value) || 0;
        const subtotal = (valorInteira * qtdInteira) + (valorMeia * qtdMeia);
        tr.querySelector('.subtotal').innerText = subtotal.toFixed(2).replace('.', ',');
        total += subtotal;
        totalIngressos += qtdInteira + qtdMeia;
    });
    document.getElementById('total').innerText = total.toFixed(2).replace('.', ',');

    if (totalIngressos > 4) {
        alert("Você só pode comprar um máximo de 4 ingressos por CPF.");
        return false;
    }
    return true;
}

document.querySelectorAll('input[name^="quantidade_inteira"], input[name^="quantidade_meia"]').forEach(input => {
    input.addEventListener('input', atualizarTotais);
});

function validarQuantidadeTotal() {
    let totalIngressos = 0;
    document.querySelectorAll('input[name^="quantidade_inteira"], input[name^="quantidade_meia"]').forEach(input => {
        totalIngressos += parseInt(input.value) || 0;
    });
    if (totalIngressos > 4) {
        alert("Você só pode comprar um máximo de 4 ingressos por CPF.");
        return false;
    }
    if (totalIngressos === 0) {
        alert("Você deve selecionar pelo menos 1 ingresso.");
        return false;
    }
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>