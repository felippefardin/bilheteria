<?php
session_start();
include 'conexao.php';

if(!isset($_SESSION['usuario_id'])){
    echo "erro: não logado";
    exit();
}

if(!isset($_GET['id']) || empty($_GET['id'])){
    echo "erro: id inválido";
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$evento_id = intval($_GET['id']);

// Verifica se o evento pertence ao usuário logado
$stmt = $mysqli->prepare("SELECT id FROM eventos WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $evento_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    echo "erro: evento não encontrado ou não pertence ao usuário";
    exit();
}
$stmt->close();

// Exclui o evento
$stmt = $mysqli->prepare("DELETE FROM eventos WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $evento_id, $usuario_id);
if($stmt->execute()){
    echo "ok";
} else {
    echo "erro: não foi possível excluir";
}
$stmt->close();
$mysqli->close();
?>
