<?php
session_start();
include 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

$usuario_id = $_SESSION['usuario_id'];
$evento_id = $_POST['evento_id'] ?? null;

if (!$evento_id) {
    die("ID do evento não fornecido.");
}

$mysqli->begin_transaction();

try {
    // 1. Atualizar dados de lotes e setores
    if (isset($_POST['lote']) && is_array($_POST['lote'])) {
        foreach ($_POST['lote'] as $lote_id => $lote_data) {
            $inicio_venda = $lote_data['inicio_venda'] ?? null;
            $fim_venda = $lote_data['fim_venda'] ?? null;
            $quantidade_lote = $lote_data['quantidade'] ?? 0;

            $query_lote = "UPDATE eventos_lotes SET inicio_venda = ?, fim_venda = ?, quantidade = ? WHERE id = ? AND evento_id = ?";
            $stmt_lote = $mysqli->prepare($query_lote);
            $stmt_lote->bind_param("ssiii", $inicio_venda, $fim_venda, $quantidade_lote, $lote_id, $evento_id);
            if (!$stmt_lote->execute()) {
                throw new Exception("Erro ao atualizar lote: " . $stmt_lote->error);
            }
            $stmt_lote->close();

            if (isset($lote_data['setores']) && is_array($lote_data['setores'])) {
                foreach ($lote_data['setores'] as $setor_id => $setor_data) {
                    $nome_setor = $setor_data['nome_setor'] ?? '';
                    $quantidade_setor = $setor_data['quantidade'] ?? 0;
                    $valor_inteira = $setor_data['valor_inteira'] ?? 0;
                    $valor_meia = $setor_data['valor_meia'] ?? 0;

                    $query_setor = "UPDATE eventos_lotes_setores SET nome_setor = ?, quantidade = ?, valor_inteira = ?, valor_meia = ? WHERE id = ? AND lote_id = ?";
                    $stmt_setor = $mysqli->prepare($query_setor);
                    $stmt_setor->bind_param("sidddi", $nome_setor, $quantidade_setor, $valor_inteira, $valor_meia, $setor_id, $lote_id);
                    if (!$stmt_setor->execute()) {
                        throw new Exception("Erro ao atualizar setor: " . $stmt_setor->error);
                    }
                    $stmt_setor->close();
                }
            }
        }
    }

    // 2. Atualiza status do evento para ativo
    $query_evento_status = "UPDATE eventos SET status = 'ativo' WHERE id = ? AND usuario_id = ?";
    $stmt_evento_status = $mysqli->prepare($query_evento_status);
    $stmt_evento_status->bind_param("ii", $evento_id, $usuario_id);
    if (!$stmt_evento_status->execute()) {
        throw new Exception("Erro ao ativar evento: " . $stmt_evento_status->error);
    }
    $stmt_evento_status->close();

    $mysqli->commit();
    echo "Evento ativado e lotes salvos com sucesso!";

} catch (Exception $e) {
    $mysqli->rollback();
    die($e->getMessage());
}

$mysqli->close();
?>