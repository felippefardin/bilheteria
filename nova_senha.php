<?php
session_start();
include 'conexao.php';

$erro = '';
$sucesso = '';
$codigo = $_POST['codigo'] ?? ''; // Recebe do formulário

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifica código válido
    $stmt = $mysqli->prepare("
        SELECT r.id, r.usuario_id, r.usado, u.nome_completo 
        FROM recuperacao_senha r 
        INNER JOIN usuarios u ON r.usuario_id = u.id 
        WHERE r.codigo=?
    ");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        $erro = "Código inválido!";
    } else {
        $recuperacao = $res->fetch_assoc();
        if ($recuperacao['usado']) $erro = "Este código já foi utilizado!";
    }

    if (!$erro) {
        $nova_senha = $_POST['senha'];
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        // Atualiza senha do usuário
        $stmt_update = $mysqli->prepare("UPDATE usuarios SET senha=? WHERE id=?");
        $stmt_update->bind_param("si", $senha_hash, $recuperacao['usuario_id']);
        $stmt_update->execute();

        // Marca código como usado
        $stmt_usado = $mysqli->prepare("UPDATE recuperacao_senha SET usado=1 WHERE id=?");
        $stmt_usado->bind_param("i", $recuperacao['id']);
        $stmt_usado->execute();

        $sucesso = "Senha atualizada com sucesso! <a href='login.php'>Faça login</a>";
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
<div class="container" style="margin-top:30px;">
    <h2>Definir Nova Senha</h2>

    <?php if($erro): ?>
        <p class="msg-erro"><?= $erro ?></p>
    <?php endif; ?>
    <?php if($sucesso): ?>
        <p class="msg-sucesso"><?= $sucesso ?></p>
    <?php endif; ?>

    <?php if(!$sucesso && !$erro): ?>
    <form method="post" class="form-padrao">
    <label>Código recebido no e-mail:</label>
    <input type="text" name="codigo" required>

    <label>Nova Senha:</label>
    <input type="password" name="senha" required>

    <button type="submit">Atualizar Senha</button>
</form>
    <?php endif; ?>
</div>
</body>
</html>

<?php include 'includes/footer.php'; ?>
