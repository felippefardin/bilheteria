<?php
session_start();
include 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cpf = $_POST['cpf'];
    $senha = $_POST['senha'];

    $stmt = $mysqli->prepare("SELECT id, nome_completo, senha FROM usuarios WHERE cpf=?");
    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();

        if (password_verify($senha, $usuario['senha'])) {
            // Login bem-sucedido
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome_completo'];

            // Redireciona para index
            header("Location: index.php");
            exit();
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "CPF não cadastrado!";
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
    
</body>
<div class="container">
    <h2>Login</h2>
    <?php if ($erro) echo "<p class='msg-erro'>$erro</p>"; ?>

    <form method="post" class="form-padrao">
        <label>CPF:</label>
        <input type="text" name="cpf" required>

        <label>Senha:</label>
        <input type="password" name="senha" required>

        <button type="submit">Entrar</button>
        <p class="link-login">
    <a href="esqueci_senha.php">Esqueci minha senha</a>
</p>

    </form>
</div>
</body>
</html>

<?php include 'includes/footer.php'; ?>
