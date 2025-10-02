<?php
session_start();
include 'conexao.php';

if(!isset($_SESSION['usuario_id'])){
    die("Acesso negado.");
}

$usuario_id = $_SESSION['usuario_id'];
$evento_id = $_POST['evento_id'] ?? null;

if(!$evento_id){
    die("ID do evento não fornecido.");
}

// Atualiza status do evento para ativo
$stmt = $mysqli->prepare("UPDATE eventos SET status='ativo' WHERE id=? AND usuario_id=?");
$stmt->bind_param("ii", $evento_id, $usuario_id);
if($stmt->execute()){
    $stmt->close();
    echo "Evento ativado com sucesso!";
} else {
    echo "Erro ao ativar evento: " . $stmt->error;
}
$mysqli->close();
?>
