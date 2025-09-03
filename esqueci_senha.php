<?php
session_start();
include 'conexao.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';
require 'PHPMailer/PHPMailer/src/Exception.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    $stmt = $mysqli->prepare("SELECT id, nome_completo FROM usuarios WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $usuario = $res->fetch_assoc();
        $codigo = bin2hex(random_bytes(4)); // Código de 8 caracteres

        // Insere na tabela de recuperação
        $stmt_insert = $mysqli->prepare("INSERT INTO recuperacao_senha (usuario_id, codigo, data_criacao) VALUES (?, ?, NOW())");
        $stmt_insert->bind_param("is", $usuario['id'], $codigo);
        $stmt_insert->execute();

        // Envia e-mail com o código
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'felippefardin@gmail.com';
            $mail->Password = 'zngp biyj beeq hkqy';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('no-reply@bilheteriaonline.com.br', 'Bilheteria Online');
            $mail->addAddress($email, $usuario['nome_completo']);

            $mail->isHTML(true);
            $mail->Subject = 'Recuperação de Senha - Bilheteria Online';
            $mail->Body = "
    Olá <b>{$usuario['nome_completo']}</b>,<br><br>
    Você solicitou a recuperação de senha.<br>
    Seu código de verificação é: <b>$codigo</b><br><br>
    <a href='http://localhost/bilheteria/nova_senha.php?codigo=$codigo'>Clique aqui para digitar seu código e definir a nova senha</a><br><br>
    Se você não solicitou, ignore este e-mail.
";

            $mail->send();
            $sucesso = "Um código de verificação foi enviado para seu e-mail.";
        } catch (Exception $e) {
            $erro = "Não foi possível enviar o e-mail. Erro: {$mail->ErrorInfo}";
        }
    } else {
        $erro = "E-mail não encontrado!";
    }
}
?>

<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bilheteria</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/mododark.css">
</head>
<body>    
<div class="container" style="margin-top:30px;">
    <h2>Recuperar Senha</h2>

    <?php if($erro): ?>
        <p class="msg-erro"><?= $erro ?></p>
    <?php endif; ?>
    <?php if($sucesso): ?>
        <p class="msg-sucesso"><?= $sucesso ?></p>
    <?php endif; ?>

    <form method="post" class="form-padrao">
        <label>E-mail cadastrado:</label>
        <input type="email" name="email" required>
        <button type="submit">Enviar Código</button>
    </form>

    <p style="margin-top:15px;"><a href="login.php">Voltar ao Login</a></p>
</div>
</body>
</html>

<?php include 'includes/footer.php'; ?>
