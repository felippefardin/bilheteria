<?php
session_start();
include 'conexao.php';

// Verifica se o usuário está logado
if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit();
}

// Dados do usuário logado
$usuario_id = $_SESSION['usuario_id'];
$nome_usuario = $_SESSION['usuario_nome'];

// Buscar informações adicionais do usuário (opcional)
$stmt = $mysqli->prepare("SELECT cpf, email, criado_em FROM usuarios WHERE id=?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$usuario = $res->fetch_assoc();
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

<style>
    /* Botões meu perfil meu ingresso e meu evento da pagina perfil */

.perfil-acoes {
    display: flex;
    flex-wrap: wrap; /* para responsivo */
    gap: 15px; /* espaçamento entre os botões */
}

.perfil-acoes .btn {
    flex: 1; /* todos os botões com largura igual */
    text-align: center;
    padding: 10px 15px;
    background-color: #1e88e5; /* cor do botão, ajusta conforme seu dark mode */
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    transition: background 0.3s;
}

.perfil-acoes .btn:hover {
    background-color: #1565c0; /* efeito hover */
}
</style>
<div class="container" style="margin-top: 30px;">
    <h2>Meu Perfil</h2>

    <div class="perfil-info">
        <p><strong>Nome:</strong> <?= htmlspecialchars($nome_usuario) ?></p>
        <p><strong>CPF:</strong> <?= htmlspecialchars($usuario['cpf']) ?></p>
        <p><strong>E-mail:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
        <p><strong>Data de Cadastro:</strong> <?= date('d/m/Y H:i', strtotime($usuario['criado_em'])) ?></p>
    </div>

   <div class="perfil-acoes" style="margin-top:20px;">
    <a href="editar_perfil.php" class="btn">Editar Perfil</a>
    <a href="meu_ingresso.php" class="btn">Meus Ingressos</a>
    <a href="meu_evento.php" class="btn">Criar Evento</a>
    <!-- <a href="logout.php" class="btn-sair">Sair</a> -->
</div>


</div>

<?php include 'includes/footer.php'; ?>
