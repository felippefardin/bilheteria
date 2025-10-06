<?php
session_start();
include 'conexao.php';

// Verifica login
if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit();
}
$nome_usuario = $_SESSION['usuario_nome'];

// Mensagem de sucesso (após salvar evento)
$mensagem_sucesso = "";
if(isset($_GET['sucesso']) && $_GET['sucesso'] == 1){
    $mensagem_sucesso = "Vá até os dados do evento para ativar seu evento e começar a vender.";
}
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
        /* Estilos do formulário */
        body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; }
        .container { max-width: 1200px; padding:20px; border-radius:10px; }
        h2 { margin-bottom: 10px; }
        .form-padrao label { font-weight: bold; margin-top: 10px; display:block; }
        .form-padrao input, .form-padrao select, .form-padrao textarea {
            width: 100%; padding: 8px; border:1px solid #ccc; border-radius:5px; margin-bottom:8px;
        }
        .btn { background:#004080; color:#fff; padding:10px 18px; border:none; border-radius:5px; cursor:pointer; }
        .btn:hover { background:#003366; }
        .lote-ingresso { border:1px solid #ccc; padding:15px; margin:15px 0; border-radius:8px; }
        .setor-ingresso { border:1px dashed #aaa; padding:10px; margin:10px 0; border-radius:6px; }
        .hidden { display:none; }

        /* Preview */
        #preview-container {
            display:none;
            margin-top:30px; padding:20px;
            background:#fafafa; border:2px solid #004080; border-radius:10px;
        }
        #preview-container h3 { color:#004080; }
        .preview-lote { border:1px dashed #aaa; padding:10px; margin:10px 0; border-radius:6px; }

        /* Mensagem de sucesso */
        #mensagem-sucesso {
            background: #28a745;
            color: #fff;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h2><i class="fas fa-map-marker-alt"></i> Criar Evento Presencial</h2>
    <p>Olá, <?= htmlspecialchars($nome_usuario) ?>! Preencha os dados do seu evento.</p>

    <?php if($mensagem_sucesso): ?>
        <div id="mensagem-sucesso"><?= $mensagem_sucesso ?></div>
        <script>
            setTimeout(function(){
                document.getElementById('mensagem-sucesso').style.display = 'none';
                window.location.href = "meu_eventos.php";
            }, 5000);
        </script>
    <?php endif; ?>

   <form class="form-padrao" id="eventoForm" method="POST" action="salvar_evento_presencial.php" enctype="multipart/form-data">
    <h3>1. Dados do Evento</h3>

    <label>Título *</label>
    <input type="text" name="titulo" id="titulo" maxlength="150" required>

    <label>Categoria *</label>
    <select name="categoria" id="categoria" required>
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
    <textarea name="descricao" id="descricao" rows="4"></textarea>

    <label>Imagem do Evento</label>
    <input type="file" name="imagem" id="imagem_evento" accept="image/*">
    <br>
    <label>Vídeo do Evento (Opcional)</label>
    <input type="file" name="video" id="video_evento" accept="video/*">

    <h3>2. Tipo de Ingresso</h3>
    <label>Tipo de Ingresso *</label>
    <select name="tipo_ingresso" id="tipo_ingresso" required onchange="toggleLinkPrivado()">
        <option value="gratuito">Gratuito</option>
        <option value="pago">Pago</option>
        <option value="privado">Privado</option> </select>

    <div id="link-privado-div" class="hidden">
        <label>Link Privado</label>
        <input type="text" name="link_privado" id="link_privado" maxlength="255">
    </div>

    <h3>3. Local do Evento</h3>
    <label>Endereço *</label>
    <input type="text" name="endereco" id="endereco" required>

    <label>Cidade *</label>
    <input type="text" name="cidade" id="cidade" required>

    <label>Estado *</label>
    <input type="text" name="estado" id="estado" required>

    <h3>4. Datas e Horários</h3>
    <label>Data de Início *</label>
    <input type="date" name="data_inicio" id="data_inicio" required>

    <label>Hora de Início *</label>
    <input type="time" name="hora_inicio" id="hora_inicio" required>

    <label>Data de Fim</label>
    <input type="date" name="data_fim" id="data_fim">

    <label>Hora de Fim</label>
    <input type="time" name="hora_fim" id="hora_fim">

    <h3>5. Ingressos e Lotes</h3>
    <div id="lotes-ingressos"></div>
    <button type="button" class="btn" onclick="adicionarLote()">+ Adicionar Lote</button>

    <br><br>
    <button type="button" class="btn" onclick="mostrarPreview()">Pré-visualizar</button>
    <button type="submit" class="btn">Salvar Evento</button>
</form>

<div id="preview-container" style="display:none;">
    <h3>Pré-visualização do Evento</h3>
    <h4 id="pv-titulo"></h4>
    <p><strong>Categoria:</strong> <span id="pv-categoria"></span></p>
    <p><strong>Descrição:</strong> <span id="pv-descricao"></span></p>
    <p><strong>Local:</strong> <span id="pv-local"></span></p>
    <p><strong>Datas:</strong> <span id="pv-datas"></span></p>

    <h4>Imagem do Evento</h4>
    <img id="pv-imagem" src="" alt="Imagem do Evento" style="max-width: 100%; border: 1px solid #ccc; padding: 10px;">

    <h4>Vídeo do Evento</h4>
    <video id="pv-video" controls style="max-width: 100%; border-radius: 12px; margin-top: 10px;" class="hidden"></video>

    <h4>Ingressos</h4>
    <div id="pv-lotes"></div>
</div>


<script>
// Mostrar/ocultar link privado
function toggleLinkPrivado() {
    const tipoIngresso = document.getElementById("tipo_ingresso").value;
    const linkPrivadoDiv = document.getElementById("link-privado-div");
    if (tipoIngresso === "privado") {
        linkPrivadoDiv.classList.remove("hidden");
    } else {
        linkPrivadoDiv.classList.add("hidden");
    }
}

let loteCount = 0;

function adicionarLote(){
    loteCount++;
    const lotesDiv = document.getElementById("lotes-ingressos");

    const loteDiv = document.createElement("div");
    loteDiv.classList.add("lote-ingresso");
    loteDiv.innerHTML = `
        <h4>Lote ${loteCount} <button type="button" onclick="removerLote(this)">Remover</button></h4>

        <label>Tipo de Evento</label>
        <select name="lotes[${loteCount}][tipo_evento]" required>
            <option value="pago">Pago</option>
            <option value="gratuito">Gratuito</option>
        </select>

        <label>Início das Vendas:</label>
        <input type="datetime-local" name="lotes[${loteCount}][inicio_venda]" required>

        <label>Fim das Vendas:</label>
        <input type="datetime-local" name="lotes[${loteCount}][fim_venda]" required>

        <label>Quantidade Máxima *</label>
        <input type="number" name="lotes[${loteCount}][quantidade]" required min="1" class="qtd-max">

        <div class="setores"></div>
        <button type="button" onclick="adicionarSetor(this, ${loteCount})">+ Adicionar Setor</button>
    `;
    lotesDiv.appendChild(loteDiv);
}

function removerLote(btn){
    btn.closest(".lote-ingresso").remove();
}

function adicionarSetor(btn, loteId){
    const setoresDiv = btn.parentElement.querySelector(".setores");
    const setorDiv = document.createElement("div");
    setorDiv.classList.add("setor-ingresso");
    setorDiv.innerHTML = `
        <label>Setor</label>
        <select name="lotes[${loteId}][setores][][nome_setor]" onchange="mostrarCampoCustomizado(this)" required>
            <option value="" selected disabled>Selecione o setor</option>
            <option value="Pista">Pista</option>
            <option value="Arquibancada">Arquibancada</option>
            <option value="Camarote">Camarote</option>
            <option value="VIP">VIP</option>
            <option value="Open Bar">Open Bar</option>
            <option value="customizar">Customizar...</option>
        </select>
        <input type="text" name="lotes[${loteId}][setores][][nome_setor_personalizado]" placeholder="Nome do setor" class="hidden custom-setor" />

        <label>Qtd Ingressos</label>
        <input type="number" name="lotes[${loteId}][setores][][quantidade]" min="0" class="qtd-setor" oninput="validarQtd(this)">

        <label>Valor Inteira</label>
        <input type="number" name="lotes[${loteId}][setores][][valor_inteira]" step="0.01">

        <label>Valor Meia</label>
        <input type="number" name="lotes[${loteId}][setores][][valor_meia]" step="0.01">

        <button type="button" onclick="this.parentElement.remove()">Remover Setor</button>
    `;
    setoresDiv.appendChild(setorDiv);
}

function validarQtd(input){
    const loteDiv = input.closest(".lote-ingresso");
    const qtdMax = parseInt(loteDiv.querySelector(".qtd-max").value) || 0;
    let soma = 0;
    loteDiv.querySelectorAll(".qtd-setor").forEach(el => soma += parseInt(el.value) || 0);

    if(soma > qtdMax){
        alert("A soma dos ingressos dos setores não pode ultrapassar o limite do lote!");
        input.value = "";
    }
}

function mostrarPreview(){
    document.getElementById("preview-container").style.display = "block";

    // Dados básicos
    const titulo = document.getElementById("titulo").value;
    const categoria = document.getElementById("categoria").value;
    const descricao = document.getElementById("descricao").value;
    const local = document.getElementById("endereco").value + ", " + document.getElementById("cidade").value + "/" + document.getElementById("estado").value;
    const dataInicio = document.getElementById("data_inicio").value;
    const dataFim = document.getElementById("data_fim").value;
    const horaInicio = document.getElementById("hora_inicio").value;
    const horaFim = document.getElementById("hora_fim").value;

    document.getElementById("pv-titulo").textContent = titulo;
    document.getElementById("pv-categoria").textContent = categoria;
    document.getElementById("pv-descricao").textContent = descricao;
    document.getElementById("pv-local").textContent = local;

    let datasTexto = dataInicio;
    if(dataFim) datasTexto += " até " + dataFim;
    if(horaInicio) datasTexto += " às " + horaInicio;
    if(horaFim) datasTexto += " - " + horaFim;
    document.getElementById("pv-datas").textContent = datasTexto;

    // Imagem
    const imagemEvento = document.getElementById("imagem_evento").files[0];
    const previewImagem = document.getElementById("pv-imagem");
    if(imagemEvento) {
        const reader = new FileReader();
        reader.onload = e => previewImagem.src = e.target.result;
        reader.readAsDataURL(imagemEvento);
    } else {
        previewImagem.src = 'assets/img/default.jpg';
    }

    // Vídeo
    const videoEvento = document.getElementById("video_evento").files[0];
    const previewVideo = document.getElementById("pv-video");
    if(videoEvento) {
        const reader = new FileReader();
        reader.onload = e => {
            previewVideo.src = e.target.result;
            previewVideo.classList.remove("hidden");
        }
        reader.readAsDataURL(videoEvento);
    } else {
        previewVideo.src = '';
        previewVideo.classList.add("hidden");
    }

    // Lotes e setores
    const pvLotes = document.getElementById("pv-lotes");
    pvLotes.innerHTML = "";
    document.querySelectorAll(".lote-ingresso").forEach((lote, idx) => {
        let div = document.createElement("div");
        div.classList.add("preview-lote");

        let qtd = lote.querySelector(".qtd-max").value;
        let tipo = lote.querySelector("select[name*='[tipo_evento]']").value;
        div.innerHTML = `<strong>Lote ${idx+1} - ${tipo.toUpperCase()} - Máximo: ${qtd}</strong><br>`;

        lote.querySelectorAll(".setor-ingresso").forEach(setor => {
            let setorNome = setor.querySelector("select").value;
            if(setorNome === "customizar") {
                setorNome = setor.querySelector(".custom-setor").value;
            }
            let setorQtd  = setor.querySelector(".qtd-setor").value;
            let valorInt  = setor.querySelector("input[name*='[valor_inteira]']").value;
            let valorMeia = setor.querySelector("input[name*='[valor_meia]']").value;

            div.innerHTML += `Setor ${setorNome}: ${setorQtd} ingressos (Inteira: R$${valorInt} / Meia: R$${valorMeia})<br>`;
        });
        pvLotes.appendChild(div);
    });
}
</script>


<?php include 'includes/footer.php'; ?>
</body>
</html>