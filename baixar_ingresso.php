<?php
session_start();
include 'conexao.php';
require_once __DIR__ . '/lib/fpdf/fpdf.php';
require_once __DIR__ . '/lib/phpqrcode/qrlib.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_POST['ingresso_id'])) {
    die("Ingresso inválido.");
}

$ingresso_id = intval($_POST['ingresso_id']);
$usuario_id = $_SESSION['usuario_id'];

// Buscar dados do ingresso na nova tabela
$stmt = $mysqli->prepare("
    SELECT i.id, i.evento_nome, i.lote_nome, i.setor_nome, i.data_evento, i.hora_evento, i.tipo_ingresso, i.valor, i.data_criacao, i.qr_code,
           v.nome_cliente, v.cpf_cliente
    FROM ingressos i
    INNER JOIN vendas v ON i.venda_id = v.id
    WHERE i.id = ? AND i.usuario_id = ?
");
$stmt->bind_param("ii", $ingresso_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Ingresso não encontrado ou você não tem permissão para acessá-lo.");
}

$ingresso = $result->fetch_assoc();
$hoje = date('Y-m-d');
$expirado = ($hoje > $ingresso['data_evento']);

// Gerar QR Code temporário
$qrTemp = tempnam(sys_get_temp_dir(), 'qr_');
QRcode::png($ingresso['qr_code'], $qrTemp, QR_ECLEVEL_L, 4);

// Gerar PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// Logo da bilheteria
$logoPath = __DIR__ . '/assets/img/logo.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 80, 10, 50); // centralizado
}
$pdf->Ln(30);

// Cabeçalho
$pdf->SetFont('Arial', 'B', 18);
$pdf->Cell(0, 10, 'Ingresso Bilheteria', 0, 1, 'C');
$pdf->Ln(5);

// Detalhes do ingresso
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 10, 'Evento:', 0, 0);
$pdf->Cell(0, 10, utf8_decode($ingresso['evento_nome']), 0, 1);
$pdf->Cell(50, 10, 'Lote:', 0, 0);
$pdf->Cell(0, 10, utf8_decode($ingresso['lote_nome']), 0, 1);
$pdf->Cell(50, 10, 'Setor:', 0, 0);
$pdf->Cell(0, 10, utf8_decode($ingresso['setor_nome']), 0, 1);
$pdf->Cell(50, 10, 'Tipo:', 0, 0);
$pdf->Cell(0, 10, utf8_decode($ingresso['tipo_ingresso']), 0, 1);
$pdf->Cell(50, 10, 'Data e Hora:', 0, 0);
$pdf->Cell(0, 10, date('d/m/Y', strtotime($ingresso['data_evento'])) . ' ' . date('H:i', strtotime($ingresso['hora_evento'])), 0, 1);
$pdf->Cell(50, 10, 'Valor Pago:', 0, 0);
$pdf->Cell(0, 10, 'R$ ' . number_format($ingresso['valor'], 2, ',', '.'), 0, 1);
$pdf->Cell(50, 10, 'Comprador:', 0, 0);
$pdf->Cell(0, 10, utf8_decode($ingresso['nome_cliente']), 0, 1);
$pdf->Cell(50, 10, 'CPF:', 0, 0);
$pdf->Cell(0, 10, $ingresso['cpf_cliente'], 0, 1);

// QR Code
$pdf->Image($qrTemp, 80, $pdf->GetY() + 10, 50, 50);

// Marca d'água se expirado
if ($expirado) {
    $pdf->SetFont('Arial', 'B', 50);
    $pdf->SetTextColor(255, 0, 0);
    $pdf->SetXY(30, 100);
    $pdf->Rotate(45);
    $pdf->Text(30, 100, 'EXPIRADO');
    $pdf->Rotate(0);
    $pdf->SetTextColor(0, 0, 0);
}

$pdf->Output('D', 'ingresso_' . $ingresso_id . '.pdf');

unlink($qrTemp);
exit();
?>