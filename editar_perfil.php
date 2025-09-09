<?php
session_start();
include 'conexao.php';

// Verifica se o usuário está logado
if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$nome_usuario = $_SESSION['usuario_nome'];

$erro = '';
$sucesso = '';

// Buscar informações atuais do usuário
$stmt = $mysqli->prepare("SELECT nome_completo, email FROM usuarios WHERE id=?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$usuario = $res->fetch_assoc();

// Atualizar dados
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $novo_nome = $_POST['nome'];
    $novo_email = $_POST['email'];
    $nova_senha = $_POST['senha'];

    // Verifica se o e-mail já existe em outro usuário
    $stmt_check = $mysqli->prepare("SELECT id FROM usuarios WHERE email=? AND id<>?");
    $stmt_check->bind_param("si", $novo_email, $usuario_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $erro = "O e-mail já está sendo usado por outro usuário!";
    } else {
        // Atualiza usuário
        if(!empty($nova_senha)){
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt_update = $mysqli->prepare("UPDATE usuarios SET nome_completo=?, email=?, senha=? WHERE id=?");
            $stmt_update->bind_param("sssi", $novo_nome, $novo_email, $senha_hash, $usuario_id);
        } else {
            $stmt_update = $mysqli->prepare("UPDATE usuarios SET nome_completo=?, email=? WHERE id=?");
            $stmt_update->bind_param("ssi", $novo_nome, $novo_email, $usuario_id);
        }

        if($stmt_update->execute()){
            $sucesso = "Dados atualizados com sucesso!";
            $_SESSION['usuario_nome'] = $novo_nome; // Atualiza nome na sessão
        } else {
            $erro = "Erro ao atualizar os dados. Tente novamente!";
        }
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
</head>
<body>
<div class="container" style="margin-top: 30px;">
    <h2>Editar Perfil</h2>

    <?php if($erro): ?>
        <p class="msg-erro"><?= $erro ?></p>
    <?php endif; ?>
    
    <?php if($sucesso): ?>
        <p class="msg-sucesso"><?= $sucesso ?></p>
    <?php endif; ?>

    <form method="post" class="form-padrao">
        <label>Nome Completo:</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($usuario['nome_completo']) ?>" required>

        <label>E-mail:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>

        <label>Nova Senha (opcional):</label>
        <input type="password" name="senha" placeholder="Deixe em branco para manter a senha atual">

        <button type="submit">Atualizar</button>
    </form>

    <p style="margin-top:15px;"><a href="perfil.php">Voltar para Meu Perfil</a></p>
</div>
</body>
</html>
<?php include 'includes/footer.php'; ?>
