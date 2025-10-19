<?php
session_start();
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Factura.php';
include_once 'includes/nav.php';

use config\Database;
use models\Factura;

// ======================================
// SESIÓN TEMPORAL (si no hay login activo)
// ======================================
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['rol'] = 'admin'; // Cambiar a 'operador' para probar permisos
}

$db = new Database();
$conn = $db->getConnection();
if (!$conn) die("No se pudo conectar a la base de datos.");

$factura = new Factura($conn);

// =======================
// FILTROS DE BÚSQUEDA
// =======================
$filtroPaciente = $_GET['paciente'] ?? '';
$filtroEstado = $_GET['estado'] ?? '';
$filtroMetodo = $_GET['metodo'] ?? '';
$filtroFecha = $_GET['fecha'] ?? '';

$query = "SELECT * FROM Facturas WHERE 1=1";
$params = [];

if ($filtroPaciente) {
    $query .= " AND PacienteNombre LIKE ?";
    $params[] = "%$filtroPaciente%";
}
if ($filtroEstado) {
    $query .= " AND Estado = ?";
    $params[] = $filtroEstado;
}
if ($filtroMetodo) {
    $query .= " AND MetodoPago = ?";
    $params[] = $filtroMetodo;
}
if ($filtroFecha) {
    $query .= " AND CONVERT(date, FechaEmision) = ?";
    $params[] = $filtroFecha;
}

$query .= " ORDER BY FechaEmision DESC";
$stmt = sqlsrv_query($conn, $query, $params);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Facturas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="content">
    <h2>Listado de Facturas Emitidas</h2>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'anulada'): ?>
        <div class="alert alert-success">✅ Factura anulada correctamente.</div>
    <?php endif; ?>

    <!-- =================== -->
    <!-- FORMULARIO FILTROS -->
    <!-- =================== -->
    <form method="get" class="form-row">
        <div class="form-group">
            <label>Paciente:</label>
            <input type="text" name="paciente" value="<?= htmlspecialchars($filtroPaciente) ?>">
        </div>
        <div class="form-group">
            <label>Estado:</label>
            <select name="estado">
                <option value="">Todos</option>
                <option value="Pendiente" <?= $filtroEstado=='Pendiente'?'selected':'' ?>>Pendiente</option>
                <option value="Pagada" <?= $filtroEstado=='Pagada'?'selected':'' ?>>Pagada</option>
                <option value="Anulada" <?= $filtroEstado=='Anulada'?'selected':'' ?>>Anulada</option>
            </select>
        </div>
        <div class="form-group">
            <label>Método de pago:</label>
            <select name="metodo">
                <option value="">Todos</option>
                <option value="Efectivo" <?= $filtroMetodo=='Efectivo'?'selected':'' ?>>Efectivo</option>
                <option value="Tarjeta" <?= $filtroMetodo=='Tarjeta'?'selected':'' ?>>Tarjeta</option>
                <option value="Transferencia" <?= $filtroMetodo=='Transferencia'?'selected':'' ?>>Transferencia</option>
            </select>
        </div>
        <div class="form-group">
            <label>Fecha emisión:</label>
            <input type="date" name="fecha" value="<?= htmlspecialchars($filtroFecha) ?>">
        </div>
        <div class="form-group" style="align-self: flex-end;">
            <button class="btn btn-primary">Filtrar</button>
            <a href="facturas_listado.php" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>

    <!-- =================== -->
    <!-- TABLA DE FACTURAS -->
    <!-- =================== -->
    <div class="table-responsive">
        <table class="table">
            <thead>
            <tr>
                <th>#</th>
                <th>N° Factura</th>
                <th>Paciente</th>
                <th>Procedimiento</th>
                <th>Subtotal</th>
                <th>ITBIS</th>
                <th>Total</th>
                <th>Método</th>
                <th>Fecha Emisión</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if ($stmt && sqlsrv_has_rows($stmt)) {
                $i = 1;
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $estado = $row['Estado'] ?? 'Pendiente';
                    $badgeClass = 'badge-warning';
                    if ($estado == 'Pagada') $badgeClass = 'badge-success';
                    if ($estado == 'Anulada') $badgeClass = 'badge-danger';
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['NumeroFactura']) ?></td>
                        <td><?= htmlspecialchars($row['PacienteNombre']) ?></td>
                        <td><?= htmlspecialchars($row['DescripcionServicio']) ?></td>
                        <td>RD$ <?= number_format($row['Subtotal'], 2) ?></td>
                        <td>RD$ <?= number_format($row['ITBIS'], 2) ?></td>
                        <td><strong>RD$ <?= number_format($row['MontoTotal'], 2) ?></strong></td>
                        <td><?= htmlspecialchars($row['MetodoPago']) ?></td>
                        <td><?= $row['FechaEmision'] instanceof DateTime ? $row['FechaEmision']->format('Y-m-d H:i') : '' ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($estado) ?></span></td>
                        <td>
                            <a class="btn btn-sm btn-primary" href="ver_factura.php?id=<?= $row['Id'] ?>">Ver</a>
                            <?php if ($_SESSION['rol'] === 'admin' && $estado != 'Anulada'): ?>
                                <a class="btn btn-sm btn-danger" href="anular_factura.php?id=<?= $row['Id'] ?>"
                                   onclick="return confirm('¿Deseas anular esta factura?')">Anular</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo "<tr><td colspan='11'>No se encontraron facturas.</td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
