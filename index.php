<?php
session_start();
include 'conexao.php';
include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Bilheteira Online</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.categoria-section { margin-top: 40px; }
.categoria-title { font-size: 1.8em; color: #004080; margin-bottom: 20px; display: flex; align-items: center; }
.categoria-title i { margin-right: 10px; }
.cards-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 24px; }
.card-evento { position: relative; overflow: hidden; border-radius: 8px; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; }
.card-evento:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
.card-evento img { width: 100%; height: 180px; object-fit: cover; display: block; }
.card-info { padding: 16px; }
.card-info h4 { margin: 0 0 8px 0; font-size: 1.2em; color: #222; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.card-info p { margin: 4px 0; color: #555; font-size: 0.95em; }
.btn-card { display: inline-block; margin-top: 8px; padding: 6px 12px; color: #fff; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.9em; }
.btn-comprar { background-color: #f05a28; }
.btn-pausar { background-color: #ffc107; }
.btn-cancelar { background-color: #dc3545; }
.btn-reativar { background-color: #28a745; }
.btn-card:hover { opacity: 0.9; }
</style>
</head>
<body>
<main class="container">
<h2>Eventos Disponíveis</h2>

<?php
// Para usuários logados, pegar ID do usuário
$usuario_id = $_SESSION['usuario_id'] ?? null;

// Buscar categorias
$categorias = $mysqli->query("SELECT DISTINCT categoria FROM eventos WHERE status='ativo' AND data_inicio >= CURDATE() ORDER BY categoria ASC");

while($cat = $categorias->fetch_assoc()):
    $categoria = $cat['categoria'];
?>
    <section class="categoria-section">
        <div class="categoria-title">
            <i class="fas fa-folder"></i>
            <?= htmlspecialchars($categoria) ?>
        </div>
        <div class="cards-row">
        <?php
        // Buscar eventos por categoria
        $stmt = $mysqli->prepare("SELECT * FROM eventos WHERE categoria=? AND status='ativo' AND data_inicio >= CURDATE() ORDER BY data_inicio ASC");
        $stmt->bind_param("s", $categoria);
        $stmt->execute();
        $res = $stmt->get_result();

        while($evento = $res->fetch_assoc()):
        ?>
            <div class="card-evento">
                <img src="<?= htmlspecialchars($evento['imagem'] ?? 'assets/img/default.jpg') ?>" alt="<?= htmlspecialchars($evento['titulo']) ?>">
                <div class="card-info">
                    <h4><?= htmlspecialchars($evento['titulo']) ?></h4>
                    <p><i class="fas fa-calendar-alt"></i>
                    <?= date('d/m/Y', strtotime($evento['data_inicio'])) ?>
                    <?php if(!empty($evento['hora_inicio'])): ?> às <?= date('H:i', strtotime($evento['hora_inicio'])) ?><?php endif; ?>
                    <?php if(!empty($evento['data_fim'])): ?> até <?= date('d/m/Y', strtotime($evento['data_fim'])) ?><?php endif; ?>
                    </p>
                    <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($evento['endereco']) ?>, <?= htmlspecialchars($evento['cidade']) ?>/<?= htmlspecialchars($evento['estado']) ?></p>

                    <a href="evento_detalhe.php?id=<?= $evento['id'] ?>" class="btn-card btn-comprar">Comprar</a>
                </div>
            </div>
        <?php endwhile; $stmt->close(); ?>
        </div>
    </section>
<?php endwhile; ?>

</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>