<?php
session_start();
include 'conexao.php';

// Verifica login
if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit();
}

$nome_usuario = $_SESSION['usuario_nome'];
?>
<?php include 'includes/header.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Criar Evento Presencial</title>
    <link rel="stylesheet" href="assets/css/style.css">    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .form-padrao label { font-weight: bold; display: block; margin-top: 10px; }
        .form-padrao input, .form-padrao select, .form-padrao textarea {
            width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; margin-bottom: 5px;
        }
        .form-padrao button.btn { margin-top: 15px; background-color: #004080; color: #fff; padding: 10px 18px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .form-padrao button.btn:hover { background-color: #003366; }
        .lote-ingresso, .setor-ingresso { border: 1px solid #ccc; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        .hidden { display: none; }

        /* Pré-visualização */
        #preview-container {
            margin-top: 40px; padding: 20px; border: 2px solid #004080; border-radius: 10px; background: #f9f9f9;
        }
        #preview-container h3 { color: #004080; }
        #preview-container img { max-width: 300px; margin-top: 10px; border-radius: 10px; }
        .preview-lote { border: 1px dashed #aaa; padding: 10px; margin: 10px 0; border-radius: 6px; }
    </style>
</head>
<body>
<div class="container">
    <img id="pv-imagem" src="" alt="Imagem do Evento" style="max-width: 100%; margin-top: 20px;">
    <h2><i class="fas fa-map-marker-alt"></i> Criar Evento Presencial</h2>
    <p>Olá, <?= htmlspecialchars($nome_usuario) ?>! Preencha os dados do seu evento.</p>

    <form class="form-padrao" action="salvar_evento.php" method="POST" enctype="multipart/form-data" id="eventoForm">
        <!-- Dados Básicos do Evento -->
        <h3>1. Dados do Evento</h3>
        <label>Título *</label>
        <input type="text" name="titulo" id="titulo" required>

        <label>Categoria *</label>
        <select name="categoria" id="categoria" required>
            <option value="">Selecione</option>
            <option>Festas e Shows</option>
            <option>Teatros e Espetáculos</option>
            <option>Cursos e Workshops</option>
            <option>Congressos e Palestras</option>
            <option>Esporte</option>
            <option>Passeios e Tours</option>
            <option>Gastronomia</option>
            <option>Grátis</option>
            <option>Saúde e Bem-Estar</option>
            <option>Arte, Cultura e Lazer</option>
            <option>Infantil</option>
            <option>Religião e Espiritualidade</option>
            <option>Games e Geek</option>
            <option>Moda e Beleza</option>
        </select>

        <label>Descrição</label>
        <textarea name="descricao" id="descricao" rows="5"></textarea>

        <!-- Local do Evento -->
        <h3>2. Local do Evento</h3>
        <label>Endereço *</label>
        <input type="text" name="endereco" id="endereco" required>
        <label>Número *</label>
        <input type="text" name="numero" id="numero" required>
        <label>Bairro *</label>
        <input type="text" name="bairro" id="bairro" required>
        <label>Cidade *</label>
        <input type="text" name="cidade" id="cidade" required>
        <label>Estado *</label>
        <select name="estado" id="estado" required>
            <option value="">Selecione o estado</option>
            <?php
            $estados = ["AC","AL","AP","AM","BA","CE","DF","ES","GO","MA","MT","MS","MG","PA","PB","PR","PE","PI","RJ","RN","RS","RO","RR","SC","SP","SE","TO"];
            foreach($estados as $uf){
                echo "<option value='$uf'>$uf</option>";
            }
            ?>
        </select>

        <!-- Datas -->
        <h3>3. Datas e Horários</h3>
        <label>Data de Início *</label>
        <input type="date" name="data_inicio" id="data_inicio" required>
        <label>Data de Término</label>
        <input type="date" name="data_fim" id="data_fim">
        <label>Hora do Evento</label>
        <input type="time" name="hora_evento" id="hora_evento">

        <!-- Imagem -->
        <h3>4. Imagem do Evento</h3>
        <label>Escolher imagem</label>
        <input type="file" name="imagem" id="imagem" accept="image/*">

        <!-- Ingressos e Lotes -->
        <h3>5. Ingressos e Lotes</h3>
        <label>Tipo de ingresso *</label>
        <select id="tipo_ingresso" name="tipo_ingresso" required>
            <option value="gratuito">Gratuito</option>
            <option value="pago">Pago</option>
        </select>

        <div id="lotes-ingressos">
            <?php for($i=1; $i<=3; $i++): // reduzi para 3 só no exemplo ?>
            <div class="lote-ingresso">
                <h4>Lote <?= $i ?></h4>
                <label>Data Início Venda</label>
                <input type="date" name="lotes[<?= $i ?>][data_inicio]" class="lote-input">
                <label>Hora Início Venda</label>
                <input type="time" name="lotes[<?= $i ?>][hora_inicio]" class="lote-input">
                <label>Data Término Venda</label>
                <input type="date" name="lotes[<?= $i ?>][data_fim]" class="lote-input">
                <label>Hora Término Venda</label>
                <input type="time" name="lotes[<?= $i ?>][hora_fim]" class="lote-input">
                <label>Quantidade máxima *</label>
                <input type="number" name="lotes[<?= $i ?>][quantidade]" min="1" class="lote-input">
                <label>Valor Inteira (R$)</label>
                <input type="number" name="lotes[<?= $i ?>][valor_inteira]" step="0.01" min="0" class="lote-input">
                <label>Valor Meia (R$)</label>
                <input type="number" name="lotes[<?= $i ?>][valor_meia]" step="0.01" min="0" class="lote-input">
                <label>Descrição do ingresso</label>
                <textarea name="lotes[<?= $i ?>][descricao]" rows="2" class="lote-input"></textarea>

                <!-- Setores -->
                <label>Setor</label>
                <select name="lotes[<?= $i ?>][setor_tipo]" class="setor_tipo" onchange="toggleCustomSetor(this)">
                    <option value="">Selecione</option>
                    <option>Pista</option>
                    <option>Arquibancada</option>
                    <option>Camarote</option>
                    <option>Área VIP</option>
                    <option>Open Bar</option>
                    <option value="custom">Criar Meu Setor</option>
                </select>
                <input type="text" name="lotes[<?= $i ?>][setor_nome]" class="custom_setor hidden lote-input" placeholder="Digite o nome do setor">

                <label>Valor Inteira do setor (R$)</label>
                <input type="number" name="lotes[<?= $i ?>][setor_valor_inteira]" step="0.01" min="0" class="lote-input">
                <label>Valor Meia do setor (R$)</label>
                <input type="number" name="lotes[<?= $i ?>][setor_valor_meia]" step="0.01" min="0" class="lote-input">
            </div>
            <?php endfor; ?>
        </div>

        <button type="submit" class="btn">Salvar Evento</button>
    </form>

    <!-- PRÉ-VISUALIZAÇÃO -->
    <div id="preview-container">
        <h3>Pré-visualização do Evento</h3>
        <h4 id="preview-titulo"></h4>
        <p><strong>Categoria:</strong> <span id="preview-categoria"></span></p>
        <p><strong>Descrição:</strong> <span id="preview-descricao"></span></p>
        <p><strong>Local:</strong> <span id="preview-local"></span></p>
        <p><strong>Datas:</strong> <span id="preview-datas"></span></p>
        <img id="preview-imagem" src="" alt="" class="hidden">
        <h4>Ingressos</h4>
        <div id="preview-lotes"></div>
    </div>
</div>

<script>
function toggleCustomSetor(select){
    const input = select.parentElement.querySelector('.custom_setor');
    if(select.value === 'custom'){
        input.classList.remove('hidden');
        input.required = true;
    } else {
        input.classList.add('hidden');
        input.required = false;
    }
}

// Atualizar preview em tempo real
const form = document.getElementById("eventoForm");
const previewTitulo = document.getElementById("preview-titulo");
const previewCategoria = document.getElementById("preview-categoria");
const previewDescricao = document.getElementById("preview-descricao");
const previewLocal = document.getElementById("preview-local");
const previewDatas = document.getElementById("preview-datas");
const previewImagem = document.getElementById("preview-imagem");
const previewLotes = document.getElementById("preview-lotes");

form.addEventListener("input", atualizarPreview);
document.getElementById("imagem").addEventListener("change", atualizarImagem);

function atualizarPreview(){
    previewTitulo.textContent = document.getElementById("titulo").value;
    previewCategoria.textContent = document.getElementById("categoria").value;
    previewDescricao.textContent = document.getElementById("descricao").value;
    previewLocal.textContent = 
        document.getElementById("endereco").value + ", " +
        document.getElementById("numero").value + ", " +
        document.getElementById("bairro").value + " - " +
        document.getElementById("cidade").value + "/" +
        document.getElementById("estado").value;
    previewDatas.textContent =
        document.getElementById("data_inicio").value + " " +
        document.getElementById("hora_evento").value +
        (document.getElementById("data_fim").value ? " até " + document.getElementById("data_fim").value : "");

    // Lotes
    previewLotes.innerHTML = "";
    document.querySelectorAll(".lote-ingresso").forEach((lote, idx) => {
        let loteDiv = document.createElement("div");
        loteDiv.classList.add("preview-lote");
        loteDiv.innerHTML = `<strong>Lote ${idx+1}:</strong><br>
            Quantidade: ${lote.querySelector("[name*='quantidade']").value || "-"}<br>
            Valor Inteira: R$ ${lote.querySelector("[name*='valor_inteira']").value || "0"}<br>
            Valor Meia: R$ ${lote.querySelector("[name*='valor_meia']").value || "0"}<br>
            Setor: ${(lote.querySelector(".setor_tipo").value === "custom" 
                        ? lote.querySelector(".custom_setor").value 
                        : lote.querySelector(".setor_tipo").value) || "-"}
        `;
        previewLotes.appendChild(loteDiv);
    });
}

function atualizarImagem(event){
    const file = event.target.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(e){
            previewImagem.src = e.target.result;
            previewImagem.classList.remove("hidden");
        }
        reader.readAsDataURL(file);
    }
}
</script>
<?php include 'includes/footer.php'; ?>
</body>
</html>
