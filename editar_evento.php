<?php
session_start();
include 'conexao.php';
include 'includes/header.php';

if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

if(!isset($_GET['id'])){
    header("Location: eventos_criados.php");
    exit();
}

$evento_id = intval($_GET['id']);

// Buscar dados do evento completo
$stmt = $mysqli->prepare("SELECT * FROM eventos WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $evento_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$evento = $result->fetch_assoc();
$stmt->close();

if(!$evento){
    die("Evento não encontrado ou você não tem permissão.");
}

// Buscar lotes e setores
$stmt_lotes = $mysqli->prepare("
    SELECT el.*, els.id AS setor_id, els.nome_setor, els.nome_customizado, els.quantidade AS setor_quantidade, els.valor_inteira, els.valor_meia
    FROM eventos_lotes el
    LEFT JOIN eventos_lotes_setores els ON el.id = els.lote_id
    WHERE el.evento_id = ?
    ORDER BY el.numero_lote ASC, els.id ASC
");
$stmt_lotes->bind_param("i", $evento_id);
$stmt_lotes->execute();
$res_lotes = $stmt_lotes->get_result();

$lotes = [];
$setores_por_lote = [];
while($row = $res_lotes->fetch_assoc()) {
    $lote_id_atual = $row['id'];
    if (!isset($lotes[$lote_id_atual])) {
        $lotes[$lote_id_atual] = $row;
        $setores_por_lote[$lote_id_atual] = [];
    }
    if ($row['setor_id']) {
        $setores_por_lote[$lote_id_atual][] = [
            'id' => $row['setor_id'],
            'nome_setor' => $row['nome_setor'],
            'nome_customizado' => $row['nome_customizado'],
            'quantidade' => $row['setor_quantidade'],
            'valor_inteira' => $row['valor_inteira'],
            'valor_meia' => $row['valor_meia']
        ];
    }
}
$stmt_lotes->close();

// Desestruturar local para preencher os campos do formulário
$local_parts = explode(', ', $evento['local']);
$endereco = $local_parts[0] ?? '';
$cidade_estado = $local_parts[1] ?? '';
$cidade_estado_parts = explode(' - ', $cidade_estado);
$cidade = $cidade_estado_parts[0] ?? '';
$estado = $cidade_estado_parts[1] ?? '';


// Atualizar evento e lotes
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $titulo = $_POST['titulo'];
    $categoria = $_POST['categoria'];
    $descricao = $_POST['descricao'];
    $endereco = $_POST['endereco'];
    $cidade = $_POST['cidade'];
    $estado = $_POST['estado'];
    $local = "$endereco, $cidade - $estado";
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];
    $tipo_ingresso = $_POST['tipo_ingresso'];
    $link_privado = ($tipo_ingresso === 'privado') ? ($_POST['link_privado'] ?? '') : '';

    // Upload imagem
    $imagem = $evento['imagem'];
    if(isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK){
        $pasta = "uploads/";
        if(!is_dir($pasta)) mkdir($pasta, 0777, true);
        $nome_arquivo = time() . "_" . basename($_FILES["imagem"]["name"]);
        $caminho = $pasta . $nome_arquivo;

        if(move_uploaded_file($_FILES["imagem"]["tmp_name"], $caminho)){
            if($evento['imagem'] && file_exists($evento['imagem'])){
                unlink($evento['imagem']);
            }
            $imagem = $caminho;
        }
    }

    // Upload vídeo
    $video = $evento['video'];
    if(isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK){
        $pasta = "uploads/";
        if(!is_dir($pasta)) mkdir($pasta, 0777, true);
        $nome_video = time() . "_" . basename($_FILES["video"]["name"]);
        $caminho_video = $pasta . $nome_video;

        if(move_uploaded_file($_FILES["video"]["tmp_name"], $caminho_video)){
            if($evento['video'] && file_exists($evento['video'])){
                unlink($evento['video']);
            }
            $video = $caminho_video;
        }
    }


    // Atualizar evento
    $stmt = $mysqli->prepare("UPDATE eventos SET titulo=?, categoria=?, descricao=?, endereco=?, cidade=?, estado=?, local=?, data_inicio=?, data_fim=?, hora_evento=?, hora_fim=?, tipo_ingresso=?, link_privado=?, imagem=?, video=? WHERE id=? AND usuario_id=?");
    $stmt->bind_param("sssssssssssssssii",
        $titulo,
        $categoria,
        $descricao,
        $endereco,
        $cidade,
        $estado,
        $local,
        $data_inicio,
        $data_fim,
        $hora_inicio,
        $hora_fim,
        $tipo_ingresso,
        $link_privado,
        $imagem,
        $video,
        $evento_id,
        $usuario_id
    );
    $stmt->execute();
    $stmt->close();

    // Redireciona de volta para a lista de eventos após a edição
    header("Location: eventos_criados.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Evento</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Estilos para o layout em desktop */
body {
    background-color: #f5f5f5;
    font-family: Arial, sans-serif;
}

.evento-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 30px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.evento-container h2 {
    text-align: center;
    color: #004080;
    margin-bottom: 25px;
    font-size: 2rem;
}

.evento-container h3 {
    color: #004080;
    margin-top: 30px;
    margin-bottom: 15px;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.form-full-width {
    grid-column: span 2;
}

.evento-container label {
    font-weight: bold;
    margin-top: 10px;
    color: #555;
}

.evento-container input,
.evento-container textarea,
.evento-container select {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border-radius: 6px;
    border: 1px solid #ccc;
    box-sizing: border-box;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.evento-container input:focus,
.evento-container textarea:focus,
.evento-container select:focus {
    border-color: #007bff;
    outline: none;
}

.media-preview {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 15px;
}

.media-preview img,
.media-preview video {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    border: 1px solid #ddd;
    padding: 5px;
}

.lote-card {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.setor-card {
    background-color: #eaf4ff;
    border: 1px dashed #007bff;
    padding: 15px;
    margin-top: 15px;
    border-radius: 8px;
}

.btn-container {
    margin-top: 30px;
    text-align: center;
}

.btn-submit, .btn-back {
    padding: 12px 25px;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: bold;
    text-decoration: none;
    transition: background-color 0.2s;
}

.btn-submit {
    background-color: #007bff;
    color: #fff;
    border: none;
    cursor: pointer;
}

.btn-submit:hover {
    background-color: #0056b3;
}

.btn-back {
    background-color: #6c757d;
    color: #fff;
}

.btn-back:hover {
    background-color: #5a6268;
}

/* Esconde campos e elementos que não devem aparecer */
.hidden { display: none; }

/* Media Queries para responsividade */
@media (max-width: 768px) {
    .evento-container {
        padding: 15px;
        margin: 10px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-full-width {
        grid-column: span 1;
    }
}
</style>
</head>
<body>
<div class="evento-container">
    <h2><i class="fas fa-edit"></i> Editar Evento</h2>
    <form method="POST" enctype="multipart/form-data">
        <h3>1. Dados do Evento</h3>
        <div class="form-grid">
            <div class="form-field form-full-width">
                <label>Título</label>
                <input type="text" name="titulo" value="<?= htmlspecialchars($evento['titulo'] ?? '') ?>" required>
            </div>
            <div class="form-field form-full-width">
                <label>Categoria</label>
                <select name="categoria" required>
                    <option value="">Selecione</option>
                    <?php
                    $categorias = ["Festas e Shows","Teatros e Espetáculos","Cursos e Workshops","Congressos e Palestras","Esporte","Passeios e Tours","Gastronomia","Grátis","Saúde e Bem-Estar","Arte, Cultura e Lazer","Infantil","Religião e Espiritualidade","Games e Geek","Moda e Beleza"];
                    foreach($categorias as $cat){
                        $sel = ($evento['categoria']?? '') == $cat ? "selected" : "";
                        echo "<option value=\"$cat\" $sel>$cat</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-field form-full-width">
                <label>Descrição</label>
                <textarea name="descricao" rows="5" required><?= htmlspecialchars($evento['descricao'] ?? '') ?></textarea>
            </div>
        </div>

        <h3>2. Local e Datas</h3>
        <div class="form-grid">
            <div class="form-field">
                <label>Endereço</label>
                <input type="text" name="endereco" value="<?= htmlspecialchars($endereco) ?>" required>
            </div>
            <div class="form-field">
                <label>Cidade</label>
                <input type="text" name="cidade" value="<?= htmlspecialchars($cidade) ?>" required>
            </div>
            <div class="form-field">
                <label>Estado</label>
                <input type="text" name="estado" value="<?= htmlspecialchars($estado) ?>" required>
            </div>
            <div class="form-field">
                <label>Data de Início</label>
                <input type="date" name="data_inicio" value="<?= $evento['data_inicio'] ?? '' ?>" required>
            </div>
            <div class="form-field">
                <label>Data de Término</label>
                <input type="date" name="data_fim" value="<?= $evento['data_fim'] ?? '' ?>">
            </div>
            <div class="form-field">
                <label>Hora de Início</label>
                <input type="time" name="hora_inicio" value="<?= $evento['hora_evento'] ?? '' ?>" required>
            </div>
            <div class="form-field">
                <label>Hora de Término</label>
                <input type="time" name="hora_fim" value="<?= $evento['hora_fim'] ?? '' ?>">
            </div>
        </div>

        <h3>3. Mídia</h3>
        <div class="form-grid">
            <div class="form-field">
                <label>Imagem do Evento</label>
                <?php if(!empty($evento['imagem'])): ?>
                    <div class="media-preview">
                        <img src="<?= htmlspecialchars($evento['imagem']) ?>" alt="Imagem atual">
                    </div>
                <?php endif; ?>
                <input type="file" name="imagem" accept="image/*">
            </div>
            <div class="form-field">
                <label>Vídeo do Evento (MP4)</label>
                <?php if(!empty($evento['video'])): ?>
                    <div class="media-preview">
                        <video src="<?= htmlspecialchars($evento['video']) ?>" controls></video>
                    </div>
                <?php endif; ?>
                <input type="file" name="video" accept="video/mp4">
            </div>
        </div>

        <h3>4. Ingressos e Lotes</h3>
        <div class="form-grid form-full-width">
            <div class="form-field">
                <label>Tipo de Ingresso</label>
                <select name="tipo_ingresso" id="tipo_ingresso" required onchange="toggleLinkPrivado()">
                    <option value="gratuito" <?= ($evento['tipo_ingresso'] ?? '') == 'gratuito' ? 'selected' : '' ?>>Gratuito</option>
                    <option value="pago" <?= ($evento['tipo_ingresso'] ?? '') == 'pago' ? 'selected' : '' ?>>Pago</option>
                    <option value="privado" <?= ($evento['tipo_ingresso'] ?? '') == 'privado' ? 'selected' : '' ?>>Privado</option>
                </select>
            </div>
            <div id="link-privado-div" class="form-field <?= ($evento['tipo_ingresso'] ?? '') == 'privado' ? '' : 'hidden' ?>">
                <label>Link Privado</label>
                <input type="text" name="link_privado" id="link_privado" value="<?= htmlspecialchars($evento['link_privado'] ?? '') ?>">
            </div>
        </div>

        <div class="lote-section form-full-width">
            <h4>Lotes do Evento</h4>
            <?php foreach($lotes as $lote): ?>
                <div class="lote-card">
                    <h5>Lote <?= htmlspecialchars($lote['numero_lote'] ?? '') ?></h5>
                    <input type="hidden" name="lotes[<?= $lote['id'] ?>][id]" value="<?= htmlspecialchars($lote['id'] ?? '') ?>">
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Número do Lote</label>
                            <input type="number" name="lotes[<?= $lote['id'] ?>][numero_lote]" value="<?= htmlspecialchars($lote['numero_lote'] ?? '') ?>" required>
                        </div>
                        <div class="form-field">
                            <label>Tipo do Lote</label>
                            <select name="lotes[<?= $lote['id'] ?>][tipo_evento]">
                                <option value="pago" <?= ($lote['tipo_evento'] ?? '') == 'pago' ? 'selected' : '' ?>>Pago</option>
                                <option value="gratuito" <?= ($lote['tipo_evento'] ?? '') == 'gratuito' ? 'selected' : '' ?>>Gratuito</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Início das Vendas</label>
                            <input type="datetime-local" name="lotes[<?= $lote['id'] ?>][inicio_venda]" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($lote['inicio_venda'] ?? ''))) ?>" required>
                        </div>
                        <div class="form-field">
                            <label>Fim das Vendas</label>
                            <input type="datetime-local" name="lotes[<?= $lote['id'] ?>][fim_venda]" value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($lote['fim_venda'] ?? ''))) ?>" required>
                        </div>
                        <div class="form-field">
                            <label>Quantidade Máxima</label>
                            <input type="number" name="lotes[<?= $lote['id'] ?>][quantidade]" value="<?= htmlspecialchars($lote['quantidade'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="setor-container">
                        <h5>Setores do Lote</h5>
                        <?php 
                        $setores_do_lote = $setores_por_lote[$lote['id']] ?? [];
                        foreach($setores_do_lote as $setor): 
                        ?>
                            <div class="setor-card">
                                <input type="hidden" name="setores[<?= $setor['id'] ?>][id]" value="<?= htmlspecialchars($setor['id'] ?? '') ?>">
                                <div class="form-grid">
                                    <div class="form-field">
                                        <label>Nome do Setor</label>
                                        <select name="setores[<?= $setor['id'] ?>][nome_setor]" onchange="toggleCustomSetor(this)">
                                            <option value="Pista" <?= ($setor['nome_setor'] ?? '') == 'Pista' ? 'selected' : '' ?>>Pista</option>
                                            <option value="Arquibancada" <?= ($setor['nome_setor'] ?? '') == 'Arquibancada' ? 'selected' : '' ?>>Arquibancada</option>
                                            <option value="Camarote" <?= ($setor['nome_setor'] ?? '') == 'Camarote' ? 'selected' : '' ?>>Camarote</option>
                                            <option value="VIP" <?= ($setor['nome_setor'] ?? '') == 'VIP' ? 'selected' : '' ?>>VIP</option>
                                            <option value="Open Bar" <?= ($setor['nome_setor'] ?? '') == 'Open Bar' ? 'selected' : '' ?>>Open Bar</option>
                                            <option value="customizar" <?= !empty($setor['nome_customizado']) ? 'selected' : '' ?>>Customizar...</option>
                                        </select>
                                        <input type="text" name="setores[<?= $setor['id'] ?>][nome_customizado]" placeholder="Nome personalizado" value="<?= htmlspecialchars($setor['nome_customizado'] ?? '') ?>" class="<?= !empty($setor['nome_customizado']) ? '' : 'hidden' ?>">
                                    </div>
                                    <div class="form-field">
                                        <label>Quantidade</label>
                                        <input type="number" name="setores[<?= $setor['id'] ?>][quantidade]" value="<?= htmlspecialchars($setor['quantidade'] ?? '') ?>">
                                    </div>
                                    <div class="form-field">
                                        <label>Valor Inteira</label>
                                        <input type="number" step="0.01" name="setores[<?= $setor['id'] ?>][valor_inteira]" value="<?= htmlspecialchars($setor['valor_inteira'] ?? '') ?>">
                                    </div>
                                    <div class="form-field">
                                        <label>Valor Meia</label>
                                        <input type="number" step="0.01" name="setores[<?= $setor['id'] ?>][valor_meia]" value="<?= htmlspecialchars($setor['valor_meia'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="btn-container form-full-width">
            <button type="submit" class="btn-submit">Salvar Alterações</button>
            <a href="eventos_criados.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </form>
</div>

<script>
function toggleLinkPrivado() {
    const tipoIngresso = document.getElementById("tipo_ingresso").value;
    const linkPrivadoDiv = document.getElementById("link-privado-div");
    if (tipoIngresso === "privado") {
        linkPrivadoDiv.classList.remove("hidden");
    } else {
        linkPrivadoDiv.classList.add("hidden");
        // Limpar o valor quando o campo é escondido
        document.getElementById("link_privado").value = '';
    }
}

function toggleCustomSetor(selectElement) {
    const customInput = selectElement.nextElementSibling;
    if (selectElement.value === 'customizar') {
        customInput.classList.remove('hidden');
        customInput.setAttribute('required', 'required');
    } else {
        customInput.classList.add('hidden');
        customInput.removeAttribute('required');
        customInput.value = '';
    }
}

// Inicializa a visibilidade do campo de link privado
document.addEventListener('DOMContentLoaded', () => {
    toggleLinkPrivado();
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>