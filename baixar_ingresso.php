<?php
session_start();
include 'conexao.php';
require_once __DIR__ . '/lib/fpdf/fpdf.php';
require_once __DIR__ . '/lib/phpqrcode/qrlib.php';

if(!isset($_SESSION['usuario_id'])){
    header("Location: login.php");
    exit();
}

if(!isset($_POST['ingresso_id'])){
    die("Ingresso inválido.");
}

$ingresso_id = intval($_POST['ingresso_id']);
$usuario_id = $_SESSION['usuario_id'];

// Buscar dados do ingresso
$stmt = $mysqli->prepare("
    SELECT evento_nome, data_evento, quantidade, valor, data_compra
    FROM ingressos
    WHERE id = ? AND usuario_id = ?
");
$stmt->bind_param("ii", $ingresso_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    die("Ingresso não encontrado.");
}

$ingresso = $result->fetch_assoc();
$hoje = date('Y-m-d');
$expirado = ($hoje > $ingresso['data_evento']);

// ------------------------
// Gerar QR Code temporário
// ------------------------
$qrTemp = tempnam(sys_get_temp_dir(), 'qr_');
QRcode::png("ID:$ingresso_id|Usuario:$usuario_id", $qrTemp, QR_ECLEVEL_L, 4);

// ------------------------
// Gerar PDF
// ------------------------
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// Logo da bilheteria
$logoPath = __DIR__ . '/assets/img/logo.png';
if(file_exists($logoPath)){
    $pdf->Image($logoPath, 80, 10, 50); // centralizado
}
$pdf->Ln(30);

// Cabeçalho
$pdf->SetFont('Arial','B',18);
$pdf->Cell(0,10,'Ingresso Bilheteria',0,1,'C');
$pdf->Ln(5);

// Detalhes do ingresso
$pdf->SetFont('Arial','',12);
$pdf->Cell(50,10,'Evento:',0,0);
$pdf->Cell(0,10,$ingresso['evento_nome'],0,1);
$pdf->Cell(50,10,'Data do Evento:',0,0);
$pdf->Cell(0,10,date('d/m/Y', strtotime($ingresso['data_evento'])),0,1);
$pdf->Cell(50,10,'Quantidade:',0,0);
$pdf->Cell(0,10,$ingresso['quantidade'],0,1);
$pdf->Cell(50,10,'Valor Pago:',0,0);
$pdf->Cell(0,10,'R$ '.number_format($ingresso['valor'],2,',','.'),0,1);
$pdf->Cell(50,10,'Data da Compra:',0,0);
$pdf->Cell(0,10,date('d/m/Y H:i', strtotime($ingresso['data_compra'])),0,1);

// QR Code
$pdf->Image($qrTemp, 80, $pdf->GetY()+10, 50, 50);

// Marca d'água se expirado
if($expirado){
    $pdf->SetFont('Arial','B',50);
    $pdf->SetTextColor(255,0,0);
    $pdf->SetXY(30, 100);
    $pdf->Rotate(45);
    $pdf->Text(30, 100, 'EXPIRADO');
    $pdf->Rotate(0);
    $pdf->SetTextColor(0,0,0);
}

// Forçar download
$pdf->Output('D','ingresso_'.$ingresso_id.'.pdf');

// Apagar QR temporário
unlink($qrTemp);
exit();

// Função Rotate (FPDF)
if(!function_exists('Rotate')){
    function Rotate($angle, $x=-1, $y=-1){
        if($x==-1) $x=$this->x;
        if($y==-1) $y=$this->y;
        if($this->angle!=0) $this->_out('Q');
        $this->angle=$angle;
        if($angle!=0){
            $angle*=M_PI/180;
            $c=cos($angle);
            $s=sin($angle);
            $cx=$x*$this->k;
            $cy=($this->h-$y)*$this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.5F %.5F cm 1 0 0 1 %.5F %.5F cm', $c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
        }
    }
}
?>
