<?php
session_start();
include 'conexao.php';

// Verifica login
if(!isset($_SESSION['usuario_id'])){
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não logado']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$evento_id = $_GET['evento_id'] ?? 0;

if(!$evento_id){
    http_response_code(400);
    echo json_encode(['error' => 'ID do evento não informado']);
    exit();
}

// Pega os lotes do evento
$query_lotes = "SELECT * FROM eventos_lotes WHERE evento_id = ? ORDER BY numero_lote ASC";
$stmt_lotes = $mysqli->prepare($query_lotes);
$stmt_lotes->bind_param("i", $evento_id);
$stmt_lotes->execute();
$result_lotes = $stmt_lotes->get_result();

$lotes = [];

while($lote = $result_lotes->fetch_assoc()){
    $lote_id = $lote['id'];

    // Pega os setores do lote
    $query_setores = "SELECT * FROM eventos_lotes_setores WHERE lote_id = ?";
    $stmt_setores = $mysqli->prepare($query_setores);
    $stmt_setores->bind_param("i", $lote_id);
    $stmt_setores->execute();
    $result_setores = $stmt_setores->get_result();

    $setores = [];
    while($setor = $result_setores->fetch_assoc()){
        $setores[] = [
            'id' => $setor['id'],
            'nome_setor' => $setor['nome_setor'],
            'nome_customizado' => $setor['nome_customizado'],
            'quantidade' => $setor['quantidade'],
            'valor_inteira' => $setor['valor_inteira'],
            'valor_meia' => $setor['valor_meia']
        ];
    }

    $lotes[] = [
        'id' => $lote['id'],
        'numero_lote' => $lote['numero_lote'],
        'quantidade' => $lote['quantidade'],
        'tipo_evento' => $lote['tipo_evento'],
        'inicio_venda' => $lote['inicio_venda'],
        'fim_venda' => $lote['fim_venda'],
        'setores' => $setores
    ];

    $stmt_setores->close();
}

$stmt_lotes->close();
$mysqli->close();

// Retorna JSON
header('Content-Type: application/json');
echo json_encode($lotes);
