<?php
session_start();
include 'conexao.php';

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = intval($_SESSION['usuario_id']);
$erro_msg = '';

// Receber dados do formulário
$titulo = $_POST['titulo'] ?? '';
$categoria = $_POST['categoria'] ?? '';
$descricao = $_POST['descricao'] ?? '';
$endereco = $_POST['endereco'] ?? '';
$cidade = $_POST['cidade'] ?? '';
$estado = $_POST['estado'] ?? '';
$local = $endereco . ', ' . $cidade . ' - ' . $estado;
$data_inicio = $_POST['data_inicio'] ?? null;
$data_fim = $_POST['data_fim'] ?? null;
$hora_inicio = $_POST['hora_inicio'] ?? null;
$hora_fim = $_POST['hora_fim'] ?? null;
$tipo_ingresso = $_POST['tipo_ingresso'] ?? 'gratuito';
$link_privado = ($tipo_ingresso === 'privado') ? ($_POST['link_privado'] ?? '') : '';

// Upload da imagem
$imagem = 'assets/img/default.jpg';
if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
    $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
    $nomeArquivo = uniqid() . '.' . $extensao;
    $caminho = 'uploads/' . $nomeArquivo;

    if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $caminho)) {
        $erro_msg = "Erro ao fazer upload da imagem.";
    } else {
        $imagem = $caminho;
    }
}

// Upload do vídeo
$video = null;
if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
    $extensao = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
    $nomeVideo = uniqid() . '.' . $extensao;
    $caminhoVideo = 'uploads/' . $nomeVideo;

    if (!move_uploaded_file($_FILES['video']['tmp_name'], $caminhoVideo)) {
        $erro_msg = "Erro ao fazer upload do vídeo.";
    } else {
        $video = $caminhoVideo;
    }
}

if (!empty($erro_msg)) {
    die($erro_msg);
}

// Inicia uma transação para garantir que tudo seja salvo ou nada seja salvo
$mysqli->begin_transaction();

try {
    // 1. Inserir evento na tabela 'eventos'
    $status_inicial = 'pausado';

    $query = "INSERT INTO eventos (usuario_id, titulo, categoria, descricao, endereco, cidade, estado, local, data_inicio, data_fim, hora_evento, hora_fim, tipo_ingresso, link_privado, imagem, video, criado_por, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);

    $stmt->bind_param("isssssssssssssssis",
        $usuario_id,
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
        $usuario_id,
        $status_inicial
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar evento: " . $stmt->error);
    }
    $evento_id = $stmt->insert_id;
    $stmt->close();

    // 2. Inserir lotes e setores
    if (isset($_POST['lotes']) && is_array($_POST['lotes'])) {
        foreach ($_POST['lotes'] as $lote_data) {
            $numero_lote = $lote_data['numero_lote'] ?? 1;
            $quantidade_lote = $lote_data['quantidade'] ?? 0;
            $tipo_evento_lote = $lote_data['tipo_evento'] ?? 'pago';
            $inicio_venda = $lote_data['inicio_venda'] ?? null;
            $fim_venda = $lote_data['fim_venda'] ?? null;

            // Inserir lote
            $query_lote = "INSERT INTO eventos_lotes (evento_id, numero_lote, quantidade, tipo_evento, inicio_venda, fim_venda) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_lote = $mysqli->prepare($query_lote);
            $stmt_lote->bind_param("iiisss", $evento_id, $numero_lote, $quantidade_lote, $tipo_evento_lote, $inicio_venda, $fim_venda);
            if (!$stmt_lote->execute()) {
                throw new Exception("Erro ao salvar lote: " . $stmt_lote->error);
            }
            $lote_id = $stmt_lote->insert_id;
            $stmt_lote->close();

            // Inserir setores do lote
            if (isset($lote_data['setores']) && is_array($lote_data['setores'])) {
                foreach ($lote_data['setores'] as $setor_data) {
                    $nome_setor_selecionado = $setor_data['nome_setor'] ?? '';
                    $nome_setor_personalizado = $setor_data['nome_setor_personalizado'] ?? '';
                    
                    if ($nome_setor_selecionado === 'customizar') {
                        if (empty($nome_setor_personalizado)) {
                            throw new Exception("Erro: O nome do setor customizado não pode ser vazio.");
                        }
                        $nome_setor = $nome_setor_personalizado;
                    } else {
                        if (empty($nome_setor_selecionado)) {
                            throw new Exception("Erro: Você deve selecionar um setor.");
                        }
                        $nome_setor = $nome_setor_selecionado;
                    }

                    $quantidade_setor = $setor_data['quantidade'] ?? 0;
                    $valor_inteira = $setor_data['valor_inteira'] ?? 0;
                    $valor_meia = $setor_data['valor_meia'] ?? 0;

                    $query_setor = "INSERT INTO eventos_lotes_setores (lote_id, nome_setor, quantidade, valor_inteira, valor_meia, criado_em) VALUES (?, ?, ?, ?, ?, NOW())";
                    $stmt_setor = $mysqli->prepare($query_setor);
                    $stmt_setor->bind_param("isidd", $lote_id, $nome_setor, $quantidade_setor, $valor_inteira, $valor_meia);
                    if (!$stmt_setor->execute()) {
                        throw new Exception("Erro ao salvar setor: " . $stmt_setor->error);
                    }
                    $stmt_setor->close();
                }
            }
        }
    }

    $mysqli->commit();
    $mysqli->close();
    
    header("Location: meu_evento.php?sucesso=1");
    exit();

} catch (Exception $e) {
    $mysqli->rollback();
    $mysqli->close();
    die($e->getMessage());
}
?>