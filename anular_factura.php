<?php
session_start();
require_once __DIR__ . '/config/Database.php';

use config\Database;

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("<div class='content'><div class='alert alert-error'>Acceso denegado: solo el administrador puede anular facturas.</div></div>");
}

$db = new Database();
$conn = $db->getConnection();

$idFactura = $_GET['id'] ?? null;
if (!$idFactura) die("ID de factura no especificado.");

// Obtener la factura
$query = "SELECT IdCuota FROM Facturas WHERE Id = ?";
$stmt = sqlsrv_query($conn, $query, [$idFactura]);
if (!$stmt || !sqlsrv_has_rows($stmt)) {
    die("<div class='content'><div class='alert alert-warning'>Factura no encontrada.</div></div>");
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$idCuota = $row['IdCuota'];

// Anular factura y revertir cuota
sqlsrv_query($conn, "UPDATE Facturas SET Estado='Anulada', FechaModificacion=GETDATE() WHERE Id=?", [$idFactura]);
sqlsrv_query($conn, "UPDATE Cuotas SET Estado='Pendiente', FechaPago=NULL WHERE Id=?", [$idCuota]);

header("Location: facturas_listado.php?msg=anulada");
exit;
?>
