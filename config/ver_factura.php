<?php
session_start();

require_once 'vendor/autoload.php'; // Cargar Dompdf
require_once 'config/Database.php';
include_once 'includes/nav.php';

use Dompdf\Dompdf;
use config\Database;

$db = new Database();
$conn = $db->getConnection();
if (!$conn) die("No se pudo conectar a la base de datos.");

// ======== Variables iniciales ========
$id = $_GET['id'] ?? null;
$formato = $_GET['formato'] ?? 'carta';
$duplicado = $_GET['duplicado'] ?? 0;

if (!$id) die("Factura no especificada.");

// ======== Obtener datos de la factura ========
$stmt = sqlsrv_query($conn, "SELECT * FROM Facturas WHERE Id = ?", [$id]);
$factura = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$factura) die("Factura no encontrada.");

// Convertir fechas a formato legible
if ($factura['FechaEmision'] instanceof DateTime)
    $factura['FechaEmision'] = $factura['FechaEmision']->format('d/m/Y H:i');
if ($factura['FechaPago'] instanceof DateTime)
    $factura['FechaPago'] = $factura['FechaPago']->format('d/m/Y H:i');

// ======== Generar PDF ========
if (isset($_GET['pdf']) && $_GET['pdf'] == 1) {
    ob_start();
    include 'templates/factura_pdf.php'; // Plantilla principal
    $html = ob_get_clean();

    // Agregar marca de agua "COPIA" si es duplicado
    if ($duplicado == 1) {
        $html .= '<div style="position:fixed;top:40%;left:20%;opacity:0.1;transform:rotate(-30deg);font-size:100px;color:red;">COPIA</div>';
    }

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $nombreArchivo = "Factura_" . $factura['NumeroFactura'];
    if ($duplicado == 1) $nombreArchivo .= "_COPIA";
    $dompdf->stream($nombreArchivo . ".pdf", ["Attachment" => true]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?= htmlspecialchars($factura['NumeroFactura']) ?> - Clínica Corominas</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: Arial, sans-serif; color:#333; }
        .factura-carta { width: 800px; margin: 0 auto; padding: 40px; border: 1px solid #ccc; }
        .factura-recibo { width: 300px; padding: 10px; font-size: 13px; border: 1px dashed #aaa; }
        .header { text-align: center; }
        .header img { height: 80px; }
        .datos-paciente, .detalle { margin-top: 20px; }
        .footer { text-align: center; margin-top: 30px; font-size: 13px; }
        .firma { margin-top: 50px; text-align: center; }
        .firma hr { width: 200px; }
        .totales { margin-top: 20px; float: right; width: 40%; }
        .btn-print, .btn-primary, .btn-secondary { display: inline-block; margin: 5px; padding: 8px 14px; border-radius: 5px; text-decoration: none; color: #fff; }
        .btn-print { background: #28a745; }
        .btn-primary { background: #007bff; }
        .btn-secondary { background: #555; }
        @media print { .btn-print, .btn-primary, .btn-secondary { display: none; } }
    </style>
</head>
<body>
<div class="<?= $formato == 'recibo' ? 'factura-recibo' : 'factura-carta' ?>">
    <div class="header">
        <img src="uploads/logo_corominas.png" alt="Clínica Corominas">
        <h2>Clínica Corominas, S. A.</h2>
        <p>RNC 1-02-00000-0 • Calle Restauración #57, Santiago</p>
        <h3>Factura N° <?= htmlspecialchars($factura['NumeroFactura']) ?></h3>
    </div>

    <div class="datos-paciente">
        <p><strong>Paciente:</strong> <?= htmlspecialchars($factura['PacienteNombre']) ?></p>
        <p><strong>Cédula:</strong> <?= htmlspecialchars($factura['PacienteCedula']) ?></p>
        <p><strong>Médico:</strong> <?= htmlspecialchars($factura['MedicoNombre']) ?></p>
        <p><strong>Fecha emisión:</strong> <?= htmlspecialchars($factura['FechaEmision']) ?></p>
        <?php if (!empty($factura['FechaPago'])): ?>
            <p><strong>Fecha pago:</strong> <?= htmlspecialchars($factura['FechaPago']) ?></p>
        <?php endif; ?>
    </div>

    <div class="detalle">
        <table width="100%" border="1" cellspacing="0" cellpadding="5">
            <tr>
                <th>Descripción del Servicio</th>
                <th>Monto</th>
            </tr>
            <tr>
                <td><?= htmlspecialchars($factura['DescripcionServicio']) ?></td>
                <td align="right">RD$ <?= number_format($factura['Subtotal'], 2) ?></td>
            </tr>
        </table>
    </div>

    <div class="totales">
        <table width="100%" cellspacing="0" cellpadding="5">
            <tr><td><strong>Subtotal:</strong></td><td align="right">RD$ <?= number_format($factura['Subtotal'],2) ?></td></tr>
            <tr><td><strong>ITBIS:</strong></td><td align="right">RD$ <?= number_format($factura['ITBIS'],2) ?></td></tr>
            <tr><td><strong>Descuento:</strong></td><td align="right">RD$ <?= number_format($factura['Descuento'],2) ?></td></tr>
            <tr><td><strong>Total:</strong></td><td align="right"><strong>RD$ <?= number_format($factura['MontoTotal'],2) ?></strong></td></tr>
        </table>
    </div>

    <div class="firma">
        <hr>
        <p>Firma y Sello Autorizado</p>
    </div>

    <div class="footer">
        <p>Gracias por preferirnos</p>
        <p>www.cacorominas.com.do</p>
        <p>Tel: (809) 580-1171</p>
    </div>

    <div style="text-align:center; margin-top:20px;">
        <a href="#" onclick="window.print()" class="btn-print">🖨️ Imprimir</a>
        <a href="ver_factura.php?id=<?= $id ?>&pdf=1" class="btn-primary">📄 Descargar PDF</a>
        <?php if ($factura['Estado'] === 'Pagada'): ?>
            <a href="ver_factura.php?id=<?= $id ?>&pdf=1&duplicado=1" class="btn-primary" style="background:#17a2b8;">📋 Descargar Duplicado (Copia)</a>
        <?php endif; ?>
        <a href="consultar_facturas.php" class="btn-secondary">⬅ Volver</a>
        <?php if ($formato == 'carta'): ?>
            <a href="ver_factura.php?id=<?= $id ?>&formato=recibo" class="btn-primary">🧾 Ver Recibo</a>
        <?php else: ?>
            <a href="ver_factura.php?id=<?= $id ?>&formato=carta" class="btn-primary">📄 Ver Carta</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
