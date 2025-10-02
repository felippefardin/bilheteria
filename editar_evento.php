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

// Buscar dados do evento
$stmt = $mysqli->prepare("SELECT * FROM eventos WHERE id = ? AND usuario_id = ?");
$stmt->bind_param("ii", $evento_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$evento = $result->fetch_assoc();
$stmt->close();

if(!$evento){
    die("Evento não encontrado ou você não tem permissão.");
}

// Buscar lotes
$stmt_lotes = $mysqli->prepare("SELECT * FROM eventos_lotes WHERE evento_id = ? ORDER BY numero_lote ASC");
$stmt_lotes->bind_param("i", $evento_id);
$stmt_lotes->execute();
$res_lotes = $stmt_lotes->get_result();
$lotes = [];
while($l = $res_lotes->fetch_assoc()){
    $lotes[] = $l;
}
$stmt_lotes->close();

// Atualizar evento e lotes
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $titulo = $_POST['titulo'];
    $categoria = $_POST['categoria'];
    $descricao = $_POST['descricao'];
    $endereco = $_POST['endereco'];
    $numero = $_POST['numero'];
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $estado = $_POST['estado'];
    $local = "$endereco, $numero, $bairro, $cidade - $estado";
    $data_evento = $_POST['data_evento'];
    $hora_evento = $_POST['hora_evento'];
    $preco = floatval($_POST['preco']);
    $qtd_ingressos = intval($_POST['qtd_ingressos']);

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

    // Atualizar evento
    $stmt = $mysqli->prepare("UPDATE eventos SET titulo=?, categoria=?, descricao=?, local=?, data_evento=?, hora_evento=?, preco=?, qtd_ingressos=?, imagem=? WHERE id=? AND usuario_id=?");
    $stmt->bind_param("ssssssdissi",
        $titulo,
        $categoria,
        $descricao,
        $local,
        $data_evento,
        $hora_evento,
        $preco,
        $qtd_ingressos,
        $imagem,
        $evento_id,
        $usuario_id
    );
    $stmt->execute();
    $stmt->close();

    // Atualizar lotes existentes
    if(isset($_POST['lote_id'])){
        foreach($_POST['lote_id'] as $index => $lote_id){
            $numero_lote = $_POST['lote_num'][$index];
            $data_inicio = $_POST['data_inicio'][$index];
            $hora_inicio = $_POST['hora_inicio'][$index];
            $data_fim = $_POST['data_fim'][$index];
            $valor_inteira = floatval($_POST['valor_inteira'][$index]);
            $valor_meia = floatval($_POST['valor_meia'][$index]);
            $quantidade = intval($_POST['quantidade'][$index]);

            $stmt = $mysqli->prepare("UPDATE eventos_lotes SET numero_lote=?, data_inicio=?, hora_inicio=?, data_fim=?, valor_inteira=?, valor_meia=?, quantidade=? WHERE id=? AND evento_id=?");
            $stmt->bind_param("isssddiii",
                $numero_lote,
                $data_inicio,
                $hora_inicio,
                $data_fim,
                $valor_inteira,
                $valor_meia,
                $quantidade,
                $lote_id,
                $evento_id
            );
            $stmt->execute();
            $stmt->close();
        }
    }

    // Adicionar novos lotes
    if(isset($_POST['novo_lote_num'])){
        foreach($_POST['novo_lote_num'] as $index => $novo_num){
            if(empty($novo_num)) continue;
            $novo_data_inicio = $_POST['novo_data_inicio'][$index];
            $novo_hora_inicio = $_POST['novo_hora_inicio'][$index];
            $novo_data_fim = $_POST['novo_data_fim'][$index];
            $novo_valor_inteira = floatval($_POST['novo_valor_inteira'][$index]);
            $novo_valor_meia = floatval($_POST['novo_valor_meia'][$index]);
            $novo_quantidade = intval($_POST['novo_quantidade'][$index]);

            $stmt = $mysqli->prepare("INSERT INTO eventos_lotes (evento_id, numero_lote, data_inicio, hora_inicio, data_fim, valor_inteira, valor_meia, quantidade) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssddi",
                $evento_id,
                $novo_num,
                $novo_data_inicio,
                $novo_hora_inicio,
                $novo_data_fim,
                $novo_valor_inteira,
                $novo_valor_meia,
                $novo_quantidade
            );
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: eventos_criados.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Editar Evento</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.evento-container{max-width:900px;margin:20px auto;padding:20px;background:#f9f9f9;border-radius:8px;box-shadow:0 0 8px rgba(0,0,0,0.1);}
.evento-container h2{margin-bottom:20px;color:#004080;}
.evento-container label{display:block;margin-top:10px;font-weight:bold;}
.evento-container input,.evento-container textarea,.evento-container select{width:100%;padding:8px;margin-top:5px;border-radius:4px;border:1px solid #ccc;box-sizing:border-box;}
.evento-container button{margin-top:15px;padding:10px 20px;background:#004080;color:#fff;border:none;border-radius:5px;cursor:pointer;font-weight:bold;}
.evento-container button:hover{background:#003366;}
.evento-container img{margin-top:10px;max-width:200px;display:block;}
.lote-section{margin-top:30px;}
.lote-card,.novo-lote-card{padding:10px;border:1px solid #ccc;border-radius:5px;margin-bottom:10px;background:#f0f0f0;}
.lote-card h4,.novo-lote-card h4{margin:0 0 10px 0;color:#004080;}
.add-lote-btn{margin-top:10px;padding:8px 12px;background:#28a745;color:#fff;border:none;border-radius:4px;cursor:pointer;}
.add-lote-btn:hover{background:#218838;}
</style>
<script>
function adicionarLote(){
    let container = document.getElementById('novos-lotes');
    let index = container.children.length;
    let html = `
    <div class="novo-lote-card">
        <h4>Novo Lote</h4>
        <label>Número do Lote</label><input type="number" name="novo_lote_num[]" required>
        <label>Data Início</label><input type="date" name="novo_data_inicio[]" required>
        <label>Hora Início</label><input type="time" name="novo_hora_inicio[]" required>
        <label>Data Fim</label><input type="date" name="novo_data_fim[]" required>
        <label>Valor Inteira</label><input type="number" step="0.01" name="novo_valor_inteira[]" required>
        <label>Valor Meia</label><input type="number" step="0.01" name="novo_valor_meia[]" required>
        <label>Quantidade</label><input type="number" name="novo_quantidade[]" required>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
}
</script>
</head>
<body>
<div class="evento-container">
<h2><i class="fas fa-edit"></i> Editar Evento</h2>
<form method="POST" enctype="multipart/form-data">
    <!-- Dados do evento (mesmo que antes) -->
    <label>Título</label><input type="text" name="titulo" value="<?= htmlspecialchars($evento['titulo']) ?>" required>
    <label>Categoria</label>
    <select name="categoria" required>
        <option value="">Selecione</option>
        <?php
        $categorias = ["Festas e Shows","Teatros e Espetáculos","Cursos e Workshops","Congressos e Palestras","Esporte","Passeios e Tours","Gastronomia","Grátis","Saúde e Bem-Estar","Arte, Cultura e Lazer","Infantil","Religião e Espiritualidade","Games e Geek","Moda e Beleza"];
        foreach($categorias as $cat){
            $sel = ($evento['categoria']==$cat) ? "selected" : "";
            echo "<option value=\"$cat\" $sel>$cat</option>";
        }
        ?>
    </select>
    <label>Descrição</label><textarea name="descricao" rows="5" required><?= htmlspecialchars($evento['descricao']) ?></textarea>
    <label>Endereço</label><input type="text" name="endereco" value="<?= htmlspecialchars(explode(',', $evento['local'])[0]) ?>" required>
    <label>Número</label><input type="text" name="numero" value="<?= htmlspecialchars(explode(',', $evento['local'])[1] ?? '') ?>">
    <label>Bairro</label><input type="text" name="bairro" value="<?= htmlspecialchars(explode(',', $evento['local'])[2] ?? '') ?>">

<label>Cidade</label><input type="text" name="cidade" value="<?= htmlspecialchars(explode(',', $evento['local'])[3] ?? '') ?>">

<label>Estado</label><input type="text" name="estado" value="<?= htmlspecialchars(explode(',', $evento['local'])[4] ?? '') ?>">

    <label>Data do Evento</label><input type="date" name="data_evento" value="<?= $evento['data_evento'] ?>" required>
    <label>Hora do Evento</label><input type="time" name="hora_evento" value="<?= $evento['hora_evento'] ?>" required>
    <label>Preço</label><input type="number" step="0.01" name="preco" value="<?= $evento['preco'] ?>">
    <label>Quantidade de Ingressos</label><input type="number" name="qtd_ingressos" value="<?= $evento['qtd_ingressos'] ?>">
    <label>Imagem do Evento</label><br>
    <?php if($evento['imagem']): ?><img src="<?= $evento['imagem'] ?>" alt="Imagem atual"><?php endif; ?>
    <input type="file" name="imagem" accept="image/*">

    <!-- Lotes existentes -->
    <div class="lote-section">
        <h3>Lotes do Evento</h3>
        <?php foreach($lotes as $l): ?>
            <div class="lote-card">
                <h4>Lote <?= $l['numero_lote'] ?></h4>
                <input type="hidden" name="lote_id[]" value="<?= $l['id'] ?>">
                <label>Número do Lote</label><input type="number" name="lote_num[]" value="<?= $l['numero_lote'] ?>" required>
                <label>Data Início</label><input type="date" name="data_inicio[]" value="<?= $l['data_inicio'] ?>" required>
                <label>Hora Início</label><input type="time" name="hora_inicio[]" value="<?= $l['hora_inicio'] ?>" required>
                <label>Data Fim</label><input type="date" name="data_fim[]" value="<?= $l['data_fim'] ?>" required>
                <label>Valor Inteira</label><input type="number" step="0.01" name="valor_inteira[]" value="<?= $l['valor_inteira'] ?>" required>
                <label>Valor Meia</label><input type="number" step="0.01" name="valor_meia[]" value="<?= $l['valor_meia'] ?>" required>
                <label>Quantidade</label><input type="number" name="quantidade[]" value="<?= $l['quantidade'] ?>" required>
            </div>
        <?php endforeach; ?>

        <!-- Novos lotes -->
        <div id="novos-lotes"></div>
        <button type="button" class="add-lote-btn" onclick="adicionarLote()">Adicionar Novo Lote</button>
    </div>

    <button type="submit">Atualizar Evento</button>
</form>
<a href="eventos_criados.php" style="display:inline-block;margin-top:15px;padding:10px 20px;background:#ccc;color:#000;border-radius:5px;text-decoration:none;">
    <i class="fas fa-arrow-left"></i> Voltar
</a>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
