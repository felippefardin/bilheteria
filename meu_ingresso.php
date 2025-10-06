<?php
session_start();
include 'conexao.php';

// Verifica se o usuário está logado
if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Buscar ingressos do usuário
$stmt = $mysqli->prepare("
    SELECT id, evento_nome, data_evento, valor, data_criacao, tipo_ingresso, setor_nome
    FROM ingressos
    WHERE usuario_id = ?
    ORDER BY data_evento DESC
");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php include 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Meus Ingressos - Bilheteria</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .ingresso-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: #1c1c1c;
            border-radius: 10px;
            color: #fff;
        }
        .ingresso {
            border: 1px solid #444;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            position: relative;
        }
        .ingresso-expirado {
            opacity: 0.5;
        }
        .ingresso h3 {
            margin: 0 0 10px 0;
        }
        .ingresso p {
            margin: 3px 0;
        }
    </style>
</head>
<body>

<div class="ingresso-container">
    <h2>Meus Ingressos</h2>
    <?php if($result->num_rows === 0): ?>
        <p>Você ainda não comprou ingressos.</p>
    <?php else: ?>
        <?php while($ingresso = $result->fetch_assoc()): ?>
    <?php 
        $hoje = date('Y-m-d');
        $expirado = ($hoje > $ingresso['data_evento']) ? true : false;
    ?>
    <div class="ingresso <?= $expirado ? 'ingresso-expirado' : '' ?>">
        <h3><?= htmlspecialchars($ingresso['evento_nome']) ?></h3>
        <p><strong>Tipo de Ingresso:</strong> <?= htmlspecialchars($ingresso['tipo_ingresso']) ?> (<?= htmlspecialchars($ingresso['setor_nome']) ?>)</p>
        <p><strong>Data do Evento:</strong> <?= date('d/m/Y', strtotime($ingresso['data_evento'])) ?></p>
        <p><strong>Valor Pago:</strong> R$ <?= number_format($ingresso['valor'], 2, ',', '.') ?></p>
        <p><strong>Data da Compra:</strong> <?= date('d/m/Y H:i', strtotime($ingresso['data_criacao'])) ?></p>
        <?php if(!$expirado): ?>
            <p><strong>Status:</strong> Ingresso disponível</p>
            <form method="POST" action="baixar_ingresso.php" style="margin-top:10px;">
                <input type="hidden" name="ingresso_id" value="<?= $ingresso['id'] ?>">
                <button type="submit" class="btn">Baixar Ingresso (PDF)</button>
            </form>
        <?php else: ?>
            <p><strong>Status:</strong> Evento expirado</p>
        <?php endif; ?>
    </div>
<?php endwhile; ?>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>