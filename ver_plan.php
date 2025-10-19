<?php
// Cargar configuración y modelos
include_once 'config/database.php';
include_once 'models/PlanPago.php';
include_once 'models/Paciente.php';
include_once 'includes/nav.php';

use config\Database;
use models\PlanPago;

// Conexión
$database = new Database();
$db = $database->getConnection();

// Validar ID del plan
if (!isset($_GET['id'])) {
    die("❌ ID de plan no especificado.");
}

$idPlan = intval($_GET['id']);
$planModel = new PlanPago($db);

// Obtener datos del plan
$plan = $planModel->obtenerPorId($idPlan);
if (!$plan) {
    die("❌ No se encontró el plan con el ID especificado.");
}

// Obtener cuotas
$cuotas = $planModel->listarCuotas($idPlan);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Plan #<?= htmlspecialchars($plan['Id']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Detalles del Plan #<?= htmlspecialchars($plan['Id']) ?></h4>
        </div>

        <div class="card-body">
            <h5 class="text-secondary mb-3">Información del Paciente</h5>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($plan['PacienteNombre'] ?? 'N/A') ?></p>
            <p><strong>Cédula:</strong> <?= htmlspecialchars($plan['PacienteCedula'] ?? 'N/A') ?></p>
            <p><strong>Teléfono:</strong> <?= htmlspecialchars($plan['PacienteTelefono'] ?? 'No registrado') ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($plan['PacienteEmail'] ?? 'No registrado') ?></p>
            <hr>

            <h5 class="text-secondary mb-3">Información del Procedimiento</h5>
            <p><strong>Médico:</strong> <?= htmlspecialchars($plan['MedicoNombre'] ?? 'N/A') ?></p>
            <p><strong>Procedimiento:</strong> <?= htmlspecialchars($plan['ProcedimientoDesc'] ?? 'N/A') ?></p>
            <p><strong>Descripción:</strong> <?= htmlspecialchars($plan['Descripcion'] ?? '') ?></p>
            <hr>

            <h5 class="text-secondary mb-3">Resumen Financiero</h5>
            <p><strong>Costo Total:</strong> RD$ <?= number_format($plan['CostoTotal'] ?? 0, 2) ?></p>
            <p><strong>Tasa de Interés:</strong> <?= number_format($plan['TasaInteres'] ?? 0, 2) ?>%</p>
            <p><strong>Plazo:</strong> <?= intval($plan['PlazoMeses'] ?? 0) ?> meses</p>
            <p><strong>Cuota Mensual:</strong> RD$ <?= number_format($plan['CuotaMensual'] ?? 0, 2) ?></p>
            <p><strong>Fecha de Inicio:</strong>
                <?= !empty($plan['FechaInicio']) ? date('d/m/Y', strtotime($plan['FechaInicio'])) : 'No definida' ?>
            </p>
        </div>
    </div>

    <div class="card mt-4 shadow-sm border-0">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Tabla de Amortización</h5>
        </div>
        <div class="card-body">
            <?php if ($cuotas && sqlsrv_has_rows($cuotas)) : ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Fecha Vencimiento</th>
                            <th>Monto Cuota</th>
                            <th>Capital</th>
                            <th>Interés</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = sqlsrv_fetch_array($cuotas, SQLSRV_FETCH_ASSOC)) : ?>
                            <tr>
                                <td><?= htmlspecialchars($row['NumeroCuota']) ?></td>
                                <td><?= ($row['FechaVencimiento'] instanceof DateTime)
                                        ? $row['FechaVencimiento']->format('d/m/Y')
                                        : htmlspecialchars($row['FechaVencimiento']) ?></td>
                                <td>RD$ <?= number_format($row['MontoCuota'], 2) ?></td>
                                <td>RD$ <?= number_format($row['Capital'], 2) ?></td>
                                <td>RD$ <?= number_format($row['Interes'], 2) ?></td>
                                <td>RD$ <?= number_format($row['SaldoCapital'], 2) ?></td>
                                <td>
                                    <?php if (strtolower($row['Estado']) === 'pagada') : ?>
                                        <span class="badge bg-success">Pagada</span>
                                    <?php else : ?>
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="text-muted">No hay cuotas registradas para este plan.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center my-4">
        <a href="consultar_planes.php" class="btn btn-outline-primary">← Volver a la lista</a>
    </div>
</div>

</body>
</html>
