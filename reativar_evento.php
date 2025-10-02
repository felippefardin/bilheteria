<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "Evento não encontrado.";
    exit();
}

$evento_id = intval($_GET['id']);
$usuario_id = $_SESSION['usuario_id'];

// Verifica se o evento pertence ao usuário logado
$stmt = $mysqli->prepare("SELECT * FROM eventos WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $evento_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Você não tem permissão para reativar este evento.";
    exit();
}

// Atualiza status para ativo
$update = $mysqli->prepare("UPDATE eventos SET status = 'ativo' WHERE id = ?");
$update->bind_param("i", $evento_id);
$update->execute();

header("Location: index.php");
exit();
?>
