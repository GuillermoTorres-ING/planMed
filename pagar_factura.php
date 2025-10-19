<?php
session_start();

use config\Database;
use models\Factura;

require_once 'config/Database.php';
require_once 'models/Factura.php';
include_once 'includes/nav.php';

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();
if (!$conn) die("No se pudo conectar a la base de datos.");

$factura = new Factura($conn);
$idFactura = $_GET['id'] ?? null;

if (!$idFactura) {
    die("ID de factura no especificado.");
}

// Obtener datos de la factura
$stmt = sqlsrv_query($conn, "SELECT * FROM Facturas WHERE Id = ?", [$idFactura]);
$facturaData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$facturaData) {
    die("Factura no encontrada.");
}

// Procesar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodoPago = $_POST['metodo_pago'] ?? 'Efectivo';
    $comentarios = $_POST['comentarios'] ?? '';

    $update = "UPDATE Facturas 
               SET Estado = 'Pagada', MetodoPago = ?, FechaPago = GETDATE(), Comentarios = ?, IdUsuarioModificacion = ?, FechaModificacion = GETDATE() 
               WHERE Id = ?";
    $params = [$metodoPago, $comentarios, $_SESSION['usuario_id'], $idFactura];
    $stmt = sqlsrv_query($conn, $update, $params);

    if ($stmt) {
        header("Location: ver_factura.php?id=$idFactura&msg=pago_ok");
        exit;
    } else {
        $error = "Error al registrar el pago.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pagar Factura</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="content">
    <h2>Pagar Factura N° <?= htmlspecialchars($facturaData['NumeroFactura']) ?></h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <p><strong>Paciente:</strong> <?= htmlspecialchars($facturaData['PacienteNombre']) ?></p>
        <p><strong>Servicio:</strong> <?= htmlspecialchars($facturaData['DescripcionServicio']) ?></p>
        <p><strong>Total:</strong> RD$ <?= number_format($facturaData['MontoTotal'], 2) ?></p>
        <p><strong>Estado actual:</strong> <?= htmlspecialchars($facturaData['Estado']) ?></p>
    </div>

    <form method="post" class="form-container">
        <div class="form-group">
            <label for="metodo_pago">Método de Pago:</label>
            <select name="metodo_pago" id="metodo_pago" required>
                <option value="Efectivo">Efectivo</option>
                <option value="Transferencia">Transferencia</option>
            </select>
        </div>

        <div class="form-group">
            <label for="comentarios">Comentarios:</label>
            <textarea name="comentarios" id="comentarios" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-success">Registrar Pago</button>
        <a href="consultar_facturas.php" class="btn btn-secondary">Volver</a>
    </form>
</div>
</body>
</html>
