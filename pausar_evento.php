<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

$evento_id = $_POST['id'] ?? null;
$usuario_id = $_SESSION['usuario_id'];

if (!$evento_id) {
    die("ID do evento não fornecido.");
}

// Verifica se o evento pertence ao usuário logado
$stmt = $mysqli->prepare("SELECT id FROM eventos WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $evento_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Você não tem permissão para pausar este evento.");
}
$stmt->close();

// Atualiza status para pausado
$update = $mysqli->prepare("UPDATE eventos SET status = 'pausado' WHERE id = ?");
$update->bind_param("i", $evento_id);
if ($update->execute()) {
    echo "Evento pausado com sucesso!";
} else {
    echo "Erro ao pausar evento: " . $update->error;
}
$update->close();
$mysqli->close();
?>