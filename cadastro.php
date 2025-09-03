<?php
session_start();
include 'conexao.php';

// Importa as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';
require 'PHPMailer/PHPMailer/src/Exception.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    // Verifica se CPF ou E-mail já estão cadastrados
    $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE cpf=? OR email=?");
    $stmt->bind_param("ss", $cpf, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $erro = "CPF ou e-mail já cadastrado!";
    } else {
        // Insere usuário
        $stmt = $mysqli->prepare("INSERT INTO usuarios (nome_completo, cpf, email, senha) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $cpf, $email, $senha);

        if ($stmt->execute()) {
            $sucesso = "Cadastro realizado com sucesso!";

            // Enviar e-mail de confirmação com PHPMailer
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
                $mail->addAddress($email, $nome);

                $mail->isHTML(true);
                $mail->Subject = 'Confirmaçao de Cadastro - Bilheteria Online';
                $mail->Body = "Olá <b>$nome</b>,<br><br>
                Seu cadastro no site <b>Bilheteria Online</b> foi realizado com sucesso!<br>
                Agora você já pode acessar e comprar seus ingressos ou criar seu evento.<br><br>
                <a href='http://localhost/bilheteria/login.php'>Clique aqui para fazer login</a><br><br>
                Obrigado por se cadastrar!";

                $mail->send();
                $sucesso .= " E-mail de confirmação enviado.";
            } catch (Exception $e) {
                $erro .= " Não foi possível enviar e-mail. Erro: {$mail->ErrorInfo}";
            }
        } else {
            $erro = "Erro ao cadastrar!";
        }
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
</head>
<body>
<div class="container">
    <div class="form-box">
        <h2>Cadastro</h2>
        
        <?php if ($erro): ?>
            <p class="msg-erro"><?= $erro ?></p>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <p class="msg-sucesso"><?= $sucesso ?></p>
        <?php endif; ?>

        <form method="post" class="form-padrao">
            <label>Nome Completo:</label>
            <input type="text" name="nome" required>

            <label>CPF:</label>
            <input type="text" name="cpf" required>

            <label>E-mail:</label>
            <input type="email" name="email" required>

            <label>Senha:</label>
            <input type="password" name="senha" required>

            <button type="submit">Cadastrar</button>
        </form>

        <p class="link-login">Já tem conta? <a href="login.php">Faça login</a></p>
    </div>
</div>
  
</body>

<?php include 'includes/footer.php'; ?>
