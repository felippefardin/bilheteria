<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Receber dados do formulário
$nome = $_POST['nome'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$telefone = $_POST['telefone'] ?? '';
$mensagem = $_POST['mensagem'] ?? '';
$email_produtor = $_POST['email_produtor'] ?? '';

if (!$nome || !$cpf || !$telefone || !$mensagem || !$email_produtor) {
    echo "Todos os campos são obrigatórios.";
    exit;
}

// Montar corpo do e-mail
$body = "
    <p><strong>Nome:</strong> {$nome}</p>
    <p><strong>CPF:</strong> {$cpf}</p>
    <p><strong>Telefone:</strong> {$telefone}</p>
    <p><strong>Mensagem:</strong><br>{$mensagem}</p>
";

$mail = new PHPMailer(true);

try {
    // Configurações do servidor SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.seuprovedor.com'; // substitua pelo seu SMTP
    $mail->SMTPAuth = true;
    $mail->Username = 'seuemail@dominio.com'; // seu e-mail
    $mail->Password = 'sua_senha'; // senha do e-mail
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Destinatário
    $mail->setFrom('seuemail@dominio.com', 'Bilheteria Online');
    $mail->addAddress($email_produtor);

    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = 'Mensagem de interesse no seu evento';
    $mail->Body = $body;

    $mail->send();
    echo "<script>alert('Mensagem enviada com sucesso!'); window.history.back();</script>";
} catch (Exception $e) {
    echo "Erro ao enviar mensagem: {$mail->ErrorInfo}";
}
