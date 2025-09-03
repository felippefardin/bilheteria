<?php
session_start();
include 'conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bilheteria Online</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container" style="margin-top:30px;">
    <h2>Eventos por Categoria</h2>

    <?php
    $categorias = [
        "Festas e Shows", "Teatros e Espetáculos", "Cursos e Workshops",
        "Congressos e Palestras", "Esporte", "Passeios e Tours",
        "Gastronomia", "Grátis", "Saúde e Bem-Estar", "Arte, Cultura e Lazer",
        "Infantil", "Religião e Espiritualidade", "Games e Geek", "Moda e Beleza"
    ];

    foreach($categorias as $cat){
        echo "<h3 style='margin-top:30px;'>$cat</h3>";

        $sql = "SELECT * FROM eventos WHERE categoria=? ORDER BY data_evento ASC";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $cat);
        $stmt->execute();
        $res = $stmt->get_result();

        if($res->num_rows > 0){
            echo "<div class='event-card-container'>";
            while($evento = $res->fetch_assoc()){
                echo "<div class='event-card'>";
                echo "<h4>".htmlspecialchars($evento['titulo'])."</h4>";
                echo "<p>".htmlspecialchars($evento['descricao'])."</p>";
                echo "<p><strong>Local:</strong> ".htmlspecialchars($evento['local'])."</p>";
                echo "<p><strong>Data:</strong> ".htmlspecialchars($evento['data_evento'])." ".htmlspecialchars($evento['hora_evento'])."</p>";
                echo "<p><strong>Preço:</strong> R$ ".number_format($evento['preco'], 2, ',', '.')."</p>";

                if (isset($_SESSION['usuario_id'])) {
                    echo "<a href='compra.php?id=".$evento['id']."' class='btn-comprar'>Comprar Ingresso</a>";
                } else {
                    echo "<a href='login.php' class='btn-login'>Faça login para comprar</a>";
                }
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<p>Nenhum evento cadastrado nesta categoria.</p>";
        }
    }
    ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
