<?php
session_start();
include 'conexao.php';
include 'includes/header.php';


if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$hoje = date('Y-m-d');

// Eventos atuais
$stmt = $mysqli->prepare("SELECT * FROM eventos WHERE usuario_id = ? AND data_inicio >= ? ORDER BY data_inicio ASC");
$stmt->bind_param("is", $usuario_id, $hoje);
$stmt->execute();
$result_atual = $stmt->get_result();
$eventos_atuais = $result_atual->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Eventos passados
$stmt = $mysqli->prepare("SELECT * FROM eventos WHERE usuario_id = ? AND data_inicio < ? ORDER BY data_inicio DESC");
$stmt->bind_param("is", $usuario_id, $hoje);
$stmt->execute();
$result_passado = $stmt->get_result();
$eventos_passados = $result_passado->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Meus Eventos Criados</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* --- CSS Moderno, Interativo e Responsivo --- */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f2f5;
}

.evento-lista {
    max-width: 1400px;
    margin: 30px auto;
    padding: 0 20px;
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.evento-lista h2 {
    color: #1a73e8;
    text-align: center;
    font-size: 2rem;
    margin-bottom: 15px;
}

.eventos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 25px;
}

.evento-card {
    background: linear-gradient(145deg, #ffffff, #e9edf3);
    border-radius: 10px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    padding: 25px 20px;
    position: relative;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
}

.evento-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 35px rgba(0,0,0,0.15);
}

.evento-card h3 {
    color: #1a73e8;
    font-size: 1.6rem;
    margin-bottom: 10px;
}

.evento-card p {
    margin: 6px 0;
    font-size: 1rem;
    color: #333;
    line-height: 1.4;
}

.status {
    padding: 6px 12px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 0.9rem;
    color: #fff;
    display: inline-block;
    margin-top: 5px;
}

.status-ativo { background-color: #28a745; }
.status-passado { background-color: #6c757d; }

/* Botões Editar / Excluir */
.acoes {
    position: absolute;
    top: 15px;
    right: 15px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.acoes a {
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 8px 16px;
    border-radius: 6px;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s ease;
}

.acoes a:first-child { background-color: #1a73e8; }
.acoes a:first-child:hover { background-color: #0c47b7; }

.acoes a:last-child { background-color: #dc3545; }
.acoes a:last-child:hover { background-color: #b02a37; }

/* Botão Voltar */
.btn-voltar {
    display: inline-block;
    margin: 20px auto 40px auto;
    padding: 14px 28px;
    background: #1a73e8;
    color: #fff;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    font-size: 1rem;
    transition: all 0.3s ease, transform 0.2s ease;
}
.btn-voltar:hover { background: #0c47b7; transform: translateY(-3px); }

/* Categorias coloridas */
.evento-card[data-categoria="Show"] { border-left: 6px solid #ff5a5f; }
.evento-card[data-categoria="Teatro"] { border-left: 6px solid #ffb400; }
.evento-card[data-categoria="Workshop"] { border-left: 6px solid #00a699; }
.evento-card[data-categoria="Outro"] { border-left: 6px solid #8c6eff; }

/* Responsividade */
@media (max-width: 1200px) {
    .evento-lista { padding: 0 20px; }
}

@media (max-width: 992px) {
    .evento-card { padding: 20px 15px; }
    .evento-card h3 { font-size: 1.4rem; }
}

@media (max-width: 768px) {
    .evento-card h3 { font-size: 1.3rem; }
    .acoes { position: static; margin-top: 15px; flex-direction: row; gap: 10px; }
    .acoes a { flex: 1; font-size: 0.85rem; padding: 6px 0; }
}

@media (max-width: 480px) {
    .evento-card { padding: 15px; }
    .evento-card h3 { font-size: 1.2rem; }
    .btn-voltar { padding: 12px 24px; font-size: 0.95rem; }
}

</style>


</head>
<body>


<div class="evento-lista">
    <h2><i class="fas fa-calendar-alt"></i> Meus Eventos Criados</h2>

    <h3>Eventos Atuais</h3>
    <div class="eventos-grid">
    <?php if(count($eventos_atuais) === 0): ?>
        <p>Nenhum evento disponível no momento.</p>
    <?php else: ?>
        <?php foreach($eventos_atuais as $evento): ?>
            <div class="evento-card" id="evento-<?= $evento['id'] ?>" data-categoria="<?= htmlspecialchars($evento['categoria'] ?? 'Outro') ?>">
                <h3><?= htmlspecialchars($evento['titulo']) ?></h3>
                <p><strong>Categoria:</strong> <?= htmlspecialchars($evento['categoria'] ?? '') ?></p>
                <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($evento['data_inicio'] ?? '')) ?> 
                   <strong>Hora:</strong> <?= date('H:i', strtotime($evento['hora_inicio'] ?? '')) ?>

                <p><strong>Local:</strong> <?= htmlspecialchars($evento['local'] ?? '') ?></p>
                <p><strong>Status:</strong> <span class="status status-ativo">Ativo</span></p>

                <div class="acoes">
                    <a href="editar_evento.php?id=<?= $evento['id'] ?>"><i class="fas fa-edit"></i> Editar</a>
                    <a href="#" onclick="excluirEvento(<?= $evento['id'] ?>)"><i class="fas fa-trash"></i> Excluir</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>

    <h3>Eventos Passados</h3>
    <div class="eventos-grid">
    <?php if(count($eventos_passados) === 0): ?>
        <p>Nenhum evento passado.</p>
    <?php else: ?>
        <?php foreach($eventos_passados as $evento): ?>
            <div class="evento-card" id="evento-<?= $evento['id'] ?>" data-categoria="<?= htmlspecialchars($evento['categoria'] ?? 'Outro') ?>">
                <h3><?= htmlspecialchars($evento['titulo']) ?></h3>
                <p><strong>Categoria:</strong> <?= htmlspecialchars($evento['categoria'] ?? '') ?></p>
                <p><strong>Data:</strong> <?= date('d/m/Y', strtotime($evento['data_evento'] ?? '')) ?> 
                   <strong>Hora:</strong> <?= date('H:i', strtotime($evento['hora_inicio'] ?? '')) ?></p>
                <p><strong>Local:</strong> <?= htmlspecialchars($evento['local'] ?? '') ?></p>
                <p><strong>Status:</strong> <span class="status status-passado">Encerrado</span></p>

                <div class="acoes">
                    <a href="editar_evento.php?id=<?= $evento['id'] ?>"><i class="fas fa-edit"></i> Editar</a>
                    <a href="#" onclick="excluirEvento(<?= $evento['id'] ?>)"><i class="fas fa-trash"></i> Excluir</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>

    <a href="meu_evento.php" class="btn-voltar"><i class="fas fa-arrow-left"></i> Voltar</a>
</div>



<script>
function excluirEvento(id) {
    if(confirm('Tem certeza que deseja excluir este evento?')) {
        fetch('excluir_evento.php?id=' + id)
            .then(res => res.text())
            .then(res => {
                if(res.trim() === 'ok') {
                    const card = document.getElementById('evento-' + id);
                    card.style.transition = 'opacity 0.5s, transform 0.5s';
                    card.style.opacity = 0;
                    card.style.transform = 'translateY(-20px)';
                    setTimeout(() => card.remove(), 500);
                } else {
                    alert('Erro ao excluir: ' + res);
                }
            });
    }
}
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>
