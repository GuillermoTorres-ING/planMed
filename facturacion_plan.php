<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/PlanPago.php';
require_once __DIR__ . '/models/Factura.php';
include_once 'includes/nav.php';

use config\Database;
use models\PlanPago;
use models\Factura;

$db = new Database();
$conn = $db->getConnection();
if (!$conn) die("❌ No se pudo conectar a la base de datos.");

$plan = new PlanPago($conn);
$factura = new Factura($conn);

$idPlan = $_GET['id'] ?? null;

// ==========================================
// 1️⃣ LISTADO DE PLANES
// ==========================================
if (!$idPlan) {
    $filtroPaciente = trim($_GET['paciente'] ?? '');
    $query = "
        SELECT pp.*, p.Nombre AS PacienteNombre, p.Cedula AS PacienteCedula,
               m.Nombre AS MedicoNombre, pr.Descripcion AS ProcedimientoDesc
        FROM PlanesPago pp
        INNER JOIN Pacientes p ON pp.IdPaciente = p.Id
        INNER JOIN Medicos m ON pp.IdMedico = m.Id
        INNER JOIN Procedimientos pr ON pp.IdProcedimiento = pr.Id
        WHERE 1=1
    ";
    $params = [];
    if ($filtroPaciente !== '') {
        $query .= " AND (p.Nombre LIKE ? OR p.Cedula LIKE ?)";
        $params = ["%$filtroPaciente%", "%$filtroPaciente%"];
    }
    $stmtPlanes = sqlsrv_query($conn, $query, $params);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Seleccionar Plan</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
    <div class="content">
        <h2>Seleccione un Plan de Pago</h2>
        <form method="get" class="form-row">
            <input type="text" name="paciente" placeholder="Buscar por nombre o cédula..." value="<?= htmlspecialchars($filtroPaciente) ?>">
            <button class="btn btn-primary">Buscar</button>
        </form>

        <table class="table table-responsive">
            <thead>
            <tr>
                <th>ID</th><th>Paciente</th><th>Cédula</th><th>Procedimiento</th>
                <th>Costo Total</th><th>Estado</th><th>Facturar</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($p = sqlsrv_fetch_array($stmtPlanes, SQLSRV_FETCH_ASSOC)): ?>
                <tr>
                    <td><?= $p['Id'] ?></td>
                    <td><?= htmlspecialchars($p['PacienteNombre']) ?></td>
                    <td><?= htmlspecialchars($p['PacienteCedula']) ?></td>
                    <td><?= htmlspecialchars($p['ProcedimientoDesc']) ?></td>
                    <td>RD$ <?= number_format($p['CostoTotal'], 2) ?></td>
                    <td><?= htmlspecialchars($p['Estado']) ?></td>
                    <td><a class="btn btn-primary" href="facturacion_plan.php?id=<?= $p['Id'] ?>">Ver Cuotas</a></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    </body>
    </html>
    <?php exit;
}

// ==========================================
// 2️⃣ MOSTRAR CUOTAS DEL PLAN
// ==========================================
$detallePlan = $plan->obtenerPorId($idPlan);
if (!$detallePlan) die("Plan no encontrado.");

$stmtCuotas = $plan->listarCuotas($idPlan);
$cuotas = [];
while ($row = sqlsrv_fetch_array($stmtCuotas, SQLSRV_FETCH_ASSOC)) $cuotas[] = $row;

// ==========================================
// 3️⃣ CREAR FACTURA AL SELECCIONAR CUOTA
// ==========================================
if (isset($_GET['cuota'])) {
    $numeroCuota = intval($_GET['cuota']);
    $cuotaSeleccionada = null;

    foreach ($cuotas as $c) {
        // Diagnóstico temporal
        if (!isset($c['NumeroCuota'])) {
            die("⚠️ Tu consulta no trae 'NumeroCuota'. Renómbralo en listarCuotas() o cámbialo aquí por el nombre correcto.");
        }

        if ($c['NumeroCuota'] == $numeroCuota) {
            $cuotaSeleccionada = $c;
            break;
        }
    }

    if ($cuotaSeleccionada) {
        $cuotaSeleccionada['PacienteNombre'] = $detallePlan['PacienteNombre'];
        $cuotaSeleccionada['PacienteCedula'] = $detallePlan['PacienteCedula'];
        $cuotaSeleccionada['PacienteDireccion'] = $detallePlan['PacienteDireccion'] ?? '';
        $cuotaSeleccionada['DescripcionServicio'] = $detallePlan['ProcedimientoDesc'];
        $cuotaSeleccionada['MedicoNombre'] = $detallePlan['MedicoNombre'];
        $cuotaSeleccionada['MetodoPago'] = 'Pendiente';
        $cuotaSeleccionada['Comentarios'] = 'Factura creada automáticamente desde facturación.';

        $facturaId = $factura->crearYRetornarId([$cuotaSeleccionada]);

        if ($facturaId) {
            header("Location: pagar_factura.php?id=" . $facturaId);
            exit;
        } else {
            die("❌ No se pudo crear la factura. Verifica el modelo Factura.");
        }
    } else {
        die("❌ No se encontró la cuota con número $numeroCuota.");
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Facturación del Plan #<?= htmlspecialchars($detallePlan['Id']) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="content">
    <h2>Facturación del Plan #<?= htmlspecialchars($detallePlan['Id']) ?></h2>
    <p><strong>Paciente:</strong> <?= htmlspecialchars($detallePlan['PacienteNombre']) ?></p>
    <p><strong>Procedimiento:</strong> <?= htmlspecialchars($detallePlan['ProcedimientoDesc']) ?></p>

    <table class="table table-responsive">
        <thead>
        <tr>
            <th>#</th><th>Vencimiento</th><th>Monto</th><th>Capital</th><th>Interés</th>
            <th>Saldo</th><th>Estado</th><th>Acción</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($cuotas as $c): ?>
            <tr>
                <td><?= $c['NumeroCuota'] ?? 'Sin dato' ?></td>
                <td><?= ($c['FechaVencimiento'] instanceof DateTime) ? $c['FechaVencimiento']->format('d/m/Y') : $c['FechaVencimiento'] ?></td>
                <td>RD$ <?= number_format($c['MontoCuota'] ?? 0,2) ?></td>
                <td>RD$ <?= number_format($c['Capital'] ?? 0,2) ?></td>
                <td>RD$ <?= number_format($c['Interes'] ?? 0,2) ?></td>
                <td>RD$ <?= number_format($c['SaldoCapital'] ?? 0,2) ?></td>
                <td><?= htmlspecialchars($c['Estado'] ?? '-') ?></td>
                <td>
                    <?php if (($c['Estado'] ?? '') !== 'Pagada'): ?>
                        <a href="facturacion_plan.php?id=<?= $idPlan ?>&cuota=<?= $c['NumeroCuota'] ?>" class="btn btn-success btn-sm">💰 Facturar y Pagar</a>
                    <?php else: ?>
                        <span class="badge bg-success">Pagada</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <a href="facturacion_plan.php" class="btn btn-secondary">⬅ Volver</a>
</div>
</body>
</html>
