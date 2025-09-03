<?php
include 'conexao.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $local = $_POST['local'];
    $data = $_POST['data'];
    $hora = $_POST['hora'];
    $preco = $_POST['preco'];
    $qtd = $_POST['qtd'];
    $usuario_id = $_SESSION['usuario_id'];

    $stmt = $mysqli->prepare("INSERT INTO eventos (usuario_id, titulo, descricao, local, data_evento, hora_evento, preco, qtd_ingressos) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("isssssdi", $usuario_id, $titulo, $descricao, $local, $data, $hora, $preco, $qtd);
    if ($stmt->execute()) {
        header("Location: perfil.php");
        exit;
    } else {
        echo "Erro ao criar evento.";
    }
}
?>

<h2>Criar Evento</h2>
<form method="post">
  <input type="text" name="titulo" placeholder="Título" required>
  <textarea name="descricao" placeholder="Descrição"></textarea>
  <input type="text" name="local" placeholder="Local" required>
  <input type="date" name="data" required>
  <input type="time" name="hora" required>
  <input type="number" step="0.01" name="preco" placeholder="Preço" required>
  <input type="number" name="qtd" placeholder="Quantidade de ingressos" required>
  <button type="submit">Criar</button>
</form>
