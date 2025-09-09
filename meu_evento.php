<?php
session_start();
include 'conexao.php';

// Verifica se o usuário está logado
if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit();
}

$nome_usuario = $_SESSION['usuario_nome'];
?>

<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Meu Evento - Bilheteria</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <style>
        /* ========================== */
/* Navbar do dashboard interno */
/* ========================== */
.dashboard-nav ul {
    list-style: none;
    display: flex;
    gap: 15px;
    padding: 0;
    margin: 20px 0;
    flex-wrap: wrap;
}

.dashboard-nav ul li a {
    text-decoration: none;
    padding: 10px 18px;
    background-color: #004080;
    color: #fff;
    border-radius: 5px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background 0.2s;
}

.dashboard-nav ul li a i {
    font-size: 1.1em;
}

.dashboard-nav ul li a:hover {
    background-color: #003366;
}

body.dark-mode .dashboard-nav ul li a {
    background-color: #003366;
    color: #f0f0f0;
}

body.dark-mode .dashboard-nav ul li a:hover {
    background-color: #002244;
}

/* ========================== */
/* Botões de criar evento com ícones */
/* ========================== */
.dashboard-actions a.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-right: 10px;
    margin-top: 10px;
    padding: 12px 20px;
    background-color: #004080;
    color: #fff;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: background 0.2s;
}

.dashboard-actions a.btn i {
    font-size: 1.2em;
}

.dashboard-actions a.btn:hover {
    background-color: #003366;
}

body.dark-mode .dashboard-actions a.btn {
    background-color: #003366;
    color: #f0f0f0;
}

body.dark-mode .dashboard-actions a.btn:hover {
    background-color: #002244;
}

    </style>
<div class="container">
    <h2>Olá, <?= htmlspecialchars($nome_usuario) ?>!</h2>

    <!-- Navbar interna do dashboard -->
    <nav class="dashboard-nav">
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Início</a></li>
            <li><a href="perfil.php"><i class="fas fa-chart-bar"></i> Dados De Eventos</a></li>
            <li><a href="meu_evento.php"><i class="fas fa-calendar-alt"></i> Meu Evento</a></li>
        </ul>
    </nav>

    <!-- Botões para criar eventos -->
    <div class="dashboard-actions">
        <a href="criar_evento_online.php" class="btn">
            <i class="fas fa-video"></i> Criar Evento Online
        </a>
        <a href="criar_evento_presencial.php" class="btn">
            <i class="fas fa-map-marker-alt"></i> Criar Evento Presencial
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
