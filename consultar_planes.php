<?php

use models\Paciente;
use models\PlanPago;

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

include_once 'config/database.php';
include_once 'models/Paciente.php';
include_once 'models/PlanPago.php';

$database = new \config\Database();
$db = $database->getConnection();

$error = '';
$planes = [];
$paciente = null;

// Procesar búsqueda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $cedula = trim($_POST['cedula'] ?? '');

    if (empty($cedula)) {
        $error = 'Debe ingresar una cédula';
    } else {
        $pacienteModel = new \models\Paciente($db);
        $paciente = $pacienteModel->buscarPorCedula($cedula);

        if ($paciente) {
            $planModel = new \models\PlanPago($db);
            $stmt = $planModel->listarPorPaciente($paciente['Id']);

            if ($stmt) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    // Convertir fechas
                    if ($row['FechaCreacion'] instanceof DateTime) {
                        $row['FechaCreacion'] = $row['FechaCreacion']->format('Y-m-d H:i:s');
                    }
                    if ($row['FechaInicio'] instanceof DateTime) {
                        $row['FechaInicio'] = $row['FechaInicio']->format('Y-m-d');
                    }
                    $planes[] = $row;
                }
            }
        } else {
            $error = 'No se encontró ningún paciente con esa cédula';
        }
    }
}
?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Consultar Planes - MediPay</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
    <?php include 'includes/header.php'; ?>

    <div class="content">
        <h2>Consultar Planes de Pago</h2>

        <div class="form-container">
            <h3>Buscar por Paciente</h3>
            <form method="POST" action="consultar_planes.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="cedula">Cédula del Paciente:</label>
                        <input type="text" id="cedula" name="cedula" required
                               placeholder="Ingrese la cédula"
                               value="<?php echo isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : ''; ?>">
                    </div>
                    <div class="form-group" style="align-self: flex-end;">
                        <button type="submit" class="btn btn-primary">Buscar Planes</button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($paciente && count($planes) > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Planes de Pago - <?php echo htmlspecialchars($paciente['Nombre']); ?></h3>
                    <span class="badge">Cédula: <?php echo htmlspecialchars($paciente['Cedula']); ?></span>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>ID Plan</th>
                            <th>Procedimiento</th>
                            <th>Médico</th>
                            <th>Costo Total</th>
                            <th>Cuota Mensual</th>
                            <th>Plazo</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($planes as $plan): ?>
                            <tr>
                                <td><strong>#<?php echo $plan['Id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($plan['ProcedimientoDesc']); ?></td>
                                <td><?php echo htmlspecialchars($plan['MedicoNombre']); ?></td>
                                <td>RD$ <?php echo number_format($plan['CostoTotal'], 2); ?></td>
                                <td>RD$ <?php echo number_format($plan['CuotaMensual'], 2); ?></td>
                                <td><?php echo $plan['PlazoMeses']; ?> meses</td>
                                <td>
                                        <span class="badge badge-<?php echo $plan['Estado'] == 'Activo' ? 'success' : 'secondary'; ?>">
                                            <?php echo htmlspecialchars($plan['Estado']); ?>
                                        </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($plan['FechaCreacion'])); ?></td>
                                <td>
                                    <a href="ver_plan.php?id=<?php echo $plan['Id']; ?>" class="btn btn-sm">
                                        Ver Detalles
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($paciente && count($planes) == 0): ?>
            <div class="alert alert-warning">
                No se encontraron planes de pago para este paciente.
            </div>
        <?php endif; ?>
    </div>
    </body>
    </html>
<?php if ($db) $database->closeConnection(); ?>