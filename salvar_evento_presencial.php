<?php
session_start();
include 'conexao.php';

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = intval($_SESSION['usuario_id']);

// Receber dados do formulário
$titulo = $_POST['titulo'] ?? '';
$categoria = $_POST['categoria'] ?? '';
$descricao = $_POST['descricao'] ?? '';
$endereco = $_POST['endereco'] ?? '';
$cidade = $_POST['cidade'] ?? '';
$estado = $_POST['estado'] ?? '';
$data_inicio = $_POST['data_inicio'] ?? null;
$data_fim = $_POST['data_fim'] ?? null;
$hora_inicio = $_POST['hora_inicio'] ?? null;
$hora_fim = $_POST['hora_fim'] ?? null;
$tipo_evento = $_POST['tipo_evento'] ?? 'geral';
$link_privado = $_POST['link_privado'] ?? '';
$preco = isset($_POST['preco']) ? floatval($_POST['preco']) : 0;
$qtd_ingressos = isset($_POST['qtd_ingressos']) ? intval($_POST['qtd_ingressos']) : 0;
$tipo_ingresso = $_POST['tipo_ingresso'] ?? 'pago';
$status = $_POST['status'] ?? 'ativo';
$local = $_POST['local'] ?? '';

// Upload da imagem
if(isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0){
    $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
    $nomeArquivo = uniqid() . '.' . $extensao;
    $caminho = 'uploads/' . $nomeArquivo;

    if(!move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho)){
        die("Erro ao fazer upload da imagem.");
    }
    $imagem = $caminho;
} else {
    die("É obrigatório enviar a imagem do evento.");
}

// Inserir evento
$query = "INSERT INTO eventos
(usuario_id, titulo, categoria, descricao, data_inicio, data_fim, hora_evento, hora_fim, tipo, link_privado, preco, qtd_ingressos, imagem, criado_por, status, tipo_ingresso, local, endereco, cidade, estado)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $mysqli->prepare($query);
$stmt->bind_param(
    "issssssssdsissssssss",
    $usuario_id, $titulo, $categoria, $descricao,
    $data_inicio, $data_fim, $hora_inicio, $hora_fim, $tipo_evento, $link_privado,
    $preco, $qtd_ingressos, $imagem, $usuario_id, $status, $tipo_ingresso,
    $local, $endereco, $cidade, $estado
);
if(!$stmt->execute()){
    die("Erro ao salvar evento: " . $stmt->error);
}

$evento_id = $stmt->insert_id;
$stmt->close();

// Inserir lote
$numero_lote = $_POST['numero_lote'] ?? 1;
$quantidade = isset($_POST['quantidade']) ? intval($_POST['quantidade']) : 0;
$tipo_lote = $_POST['tipo_lote'] ?? 'normal';
$inicio_venda = $_POST['inicio_venda'] ?? null;
$fim_venda = $_POST['fim_venda'] ?? null;

$query_lote = "INSERT INTO eventos_lotes
(evento_id, numero_lote, quantidade, tipo_evento, inicio_venda, fim_venda)
VALUES (?, ?, ?, ?, ?, ?)";
$stmt_lote = $mysqli->prepare($query_lote);
$stmt_lote->bind_param("iiisss", $evento_id, $numero_lote, $quantidade, $tipo_lote, $inicio_venda, $fim_venda);
$stmt_lote->execute();
$lote_id = $stmt_lote->insert_id;
$stmt_lote->close();

// Inserir setores do lote
$lote = $_POST['lote'] ?? [];
if(isset($lote['setores']) && is_array($lote['setores'])){
    foreach($lote['setores'] as $setor){
        $nome_setor = $setor['nome_setor'] ?? '';
        if($nome_setor === "customizar"){
            $nome_setor = $setor['nome_setor_personalizado'] ?? '';
        }
        $quantidade_setor = $setor['quantidade'] ?? 0;
        $valor_inteira = $setor['valor_inteira'] ?? 0;
        $valor_meia = $setor['valor_meia'] ?? 0;

        if(empty($nome_setor)) continue;

        $query_setor = "INSERT INTO eventos_lotes_setores
        (lote_id, nome_setor, quantidade, valor_inteira, valor_meia, criado_em)
        VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt_setor = $mysqli->prepare($query_setor);
        $stmt_setor->bind_param("isidd", $lote_id, $nome_setor, $quantidade_setor, $valor_inteira, $valor_meia);
        $stmt_setor->execute();
        $stmt_setor->close();
    }
}

$mysqli->close();

// Redirecionar para página de eventos
header("Location: dados_eventos.php?msg=Evento criado com sucesso!");
exit();
?>
