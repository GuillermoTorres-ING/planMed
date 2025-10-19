<?php
// Este archivo se incluye desde ver_factura.php cuando se genera el PDF
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 700px;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid #ccc;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #006837;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header img {
            height: 90px;
        }
        .header h2 {
            margin: 5px 0 0 0;
            color: #006837;
        }
        .header p {
            margin: 2px;
            font-size: 11px;
        }
        .title {
            text-align: center;
            font-size: 16px;
            margin-top: 10px;
            font-weight: bold;
            color: #333;
        }
        .datos {
            margin-top: 25px;
        }
        .datos p {
            margin: 3px 0;
            font-size: 12px;
        }
        .detalle {
            margin-top: 25px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #555;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .totales {
            width: 40%;
            float: right;
            margin-top: 10px;
        }
        .totales td {
            border: none;
            padding: 4px;
        }
        .firma {
            clear: both;
            margin-top: 80px;
            text-align: center;
        }
        .firma hr {
            width: 200px;
            border: 1px solid #000;
        }
        .footer {
            text-align: center;
            font-size: 11px;
            margin-top: 60px;
            color: #444;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="uploads/logo_corominas.png" alt="Clínica Corominas"><br>
        <h2>Clínica Corominas, S. A.</h2>
        <p>RNC: 1-01-00000-0</p>
        <p>Calle Restauracion 57, Santiago, Rep. Dom.</p>
        <p>Tel: (809) 580-1171 • www.corominas.com.do</p>
    </div>

    <div class="title">Factura N° <?= htmlspecialchars($factura['NumeroFactura']) ?></div>

    <div class="datos">
        <p><strong>Paciente:</strong> <?= htmlspecialchars($factura['PacienteNombre']) ?></p>
        <p><strong>Cédula:</strong> <?= htmlspecialchars($factura['PacienteCedula']) ?></p>
        <p><strong>Médico:</strong> <?= htmlspecialchars($factura['MedicoNombre']) ?></p>
        <p><strong>Fecha de Emisión:</strong> <?= htmlspecialchars($factura['FechaEmision']) ?></p>
        <?php if (!empty($factura['FechaPago'])): ?>
            <p><strong>Fecha de Pago:</strong> <?= htmlspecialchars($factura['FechaPago']) ?></p>
        <?php endif; ?>
        <p><strong>Método de Pago:</strong> <?= htmlspecialchars($factura['MetodoPago'] ?? 'N/A') ?></p>
    </div>

    <div class="detalle">
        <table>
            <thead>
            <tr>
                <th>Descripción del Servicio</th>
                <th style="width: 100px; text-align: right;">Monto (RD$)</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><?= htmlspecialchars($factura['DescripcionServicio']) ?></td>
                <td style="text-align: right;"><?= number_format($factura['Subtotal'], 2) ?></td>
            </tr>
            </tbody>
        </table>
    </div>

    <table class="totales">
        <tr><td><strong>Subtotal:</strong></td><td align="right">RD$ <?= number_format($factura['Subtotal'], 2) ?></td></tr>
        <tr><td><strong>ITBIS:</strong></td><td align="right">RD$ <?= number_format($factura['ITBIS'], 2) ?></td></tr>
        <tr><td><strong>Descuento:</strong></td><td align="right">RD$ <?= number_format($factura['Descuento'], 2) ?></td></tr>
        <tr><td><strong>Total:</strong></td><td align="right"><strong>RD$ <?= number_format($factura['MontoTotal'], 2) ?></strong></td></tr>
    </table>

    <div class="firma">
        <hr>
        <p>Firma y Sello Autorizado</p>
    </div>

    <div class="footer">
        <p><strong>Clínica Corominas, S. A.</strong> Gracias por preferirnos.</p>
        <p>Este documento es válido sin firma digital.</p>
    </div>
</div>
</body>
</html>
