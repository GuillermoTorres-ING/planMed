<?php

use models\PlanPago;
use models\Paciente;

include_once 'config/database.php';
include_once 'models/PlanPago.php';
include_once 'models/Paciente.php';
include_once 'includes/nav.php';

$database = new \config\Database();
$db = $database->getConnection();

$error = '';
$success = '';
$editar = false;
$planExistente = null;

// Cargar pacientes
$pacienteModel = new Paciente($db);
$pacientesStmt = $pacienteModel->listar();

// Cargar médicos y procedimientos
$medicosStmt = sqlsrv_query($db, "SELECT Id, Nombre FROM Medicos WHERE Activo = 1 ORDER BY Nombre");
$procedimientosStmt = sqlsrv_query($db, "SELECT Id, Descripcion, CostoBase FROM Procedimientos ORDER BY Descripcion");

// Revisar si es edición de plan
if (isset($_GET['id'])) {
    $editar = true;
    $planModel = new PlanPago($db);
    $planExistente = $planModel->obtenerPorId($_GET['id']);
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = $editar ? $planModel : new PlanPago($db);

    $plan->IdPaciente = $_POST['IdPaciente'] ?? null;
    $plan->IdMedico = $_POST['IdMedico'] ?? null;
    $plan->IdProcedimiento = $_POST['IdProcedimiento'] ?? null;
    $plan->Descripcion = trim($_POST['Descripcion'] ?? '');
    $plan->CostoTotal = floatval($_POST['CostoTotal'] ?? 0);
    $plan->TasaInteres = floatval($_POST['TasaInteres'] ?? 8); // Por defecto 8%
    $plan->PlazoMeses = intval($_POST['PlazoMeses'] ?? 0);
    $plan->FechaInicio = $_POST['FechaInicio'] ?? date('Y-m-d');
    $plan->IdUsuarioCreacion = $_SESSION['usuario_id'] ?? 1;

    if ($plan->crear()) {
        $success = "✅ Plan de pago " . ($editar ? "actualizado" : "creado") . " correctamente con ID #{$plan->Id}";
        // Recargar datos del plan para mostrar al editar
        $planExistente = $plan->obtenerPorId($plan->Id);
    } else {
        $error = "❌ Error al crear el plan de pago. Verifica los datos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $editar ? "Editar" : "Crear"; ?> Plan de Pago</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        // Calcular cuota mensual en tiempo real
        function calcularCuota() {
            const costo = parseFloat(document.getElementById('CostoTotal').value) || 0;
            const tasa = parseFloat(document.getElementById('TasaInteres').value) || 0;
            const plazo = parseInt(document.getElementById('PlazoMeses').value) || 0;

            let cuota = 0;
            const tasaMensual = tasa / 100 / 12;

            if (plazo > 0) {
                if (tasaMensual === 0) {
                    cuota = costo / plazo;
                } else {
                    const factor = Math.pow(1 + tasaMensual, plazo);
                    cuota = costo * (tasaMensual * factor) / (factor - 1);
                }
            }

            document.getElementById('CuotaMensual').value = cuota.toFixed(2);
        }

        // Al seleccionar un procedimiento, traer costo base
        function actualizarCostoBase(select) {
            const costoInput = document.getElementById('CostoTotal');
            const costoBase = select.options[select.selectedIndex].getAttribute('data-costo');
            if(costoBase) {
                costoInput.value = parseFloat(costoBase).toFixed(2);
                calcularCuota();
            }
        }
    </script>
</head>
<body>


<div class="content">
    <h2><?php echo $editar ? "Editar" : "Crear"; ?> Plan de Pago</h2>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" action="">
            <!-- DATOS DEL PACIENTE -->
            <fieldset>
                <legend>Paciente y Procedimiento</legend>

                <div class="form-group">
                    <label for="IdPaciente">Paciente:</label>
                    <select name="IdPaciente" id="IdPaciente" required>
                        <option value="">-- Seleccione --</option>
                        <?php while ($pac = sqlsrv_fetch_array($pacientesStmt, SQLSRV_FETCH_ASSOC)) : ?>
                            <option value="<?php echo $pac['Id']; ?>"
                                <?php echo $planExistente && $planExistente['IdPaciente']==$pac['Id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pac['Nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="IdMedico">Médico:</label>
                    <select name="IdMedico" id="IdMedico" required>
                        <option value="">-- Seleccione --</option>
                        <?php while ($med = sqlsrv_fetch_array($medicosStmt, SQLSRV_FETCH_ASSOC)) : ?>
                            <option value="<?php echo $med['Id']; ?>"
                                <?php echo $planExistente && $planExistente['IdMedico']==$med['Id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($med['Nombre']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="IdProcedimiento">Procedimiento:</label>
                    <select name="IdProcedimiento" id="IdProcedimiento" required onchange="actualizarCostoBase(this)">
                        <option value="">-- Seleccione --</option>
                        <?php while ($proc = sqlsrv_fetch_array($procedimientosStmt, SQLSRV_FETCH_ASSOC)) : ?>
                            <option value="<?php echo $proc['Id']; ?>"
                                    data-costo="<?php echo $proc['CostoBase']; ?>"
                                <?php echo $planExistente && $planExistente['IdProcedimiento']==$proc['Id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($proc['Descripcion']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </fieldset>

            <!-- DATOS FINANCIEROS -->
            <fieldset>
                <legend>Datos del Plan</legend>

                <div class="form-group">
                    <label for="Descripcion">Descripción:</label>
                    <input type="text" name="Descripcion" id="Descripcion" required
                           value="<?php echo $planExistente['Descripcion'] ?? ''; ?>">
                </div>

                <div class="form-group">
                    <label for="CostoTotal">Costo Total (RD$):</label>
                    <input type="number" step="0.01" name="CostoTotal" id="CostoTotal" required
                           value="<?php echo $planExistente['CostoTotal'] ?? ''; ?>"
                           oninput="calcularCuota()">
                </div>

                <div class="form-group">
                    <label for="TasaInteres">Tasa de Interés (%):</label>
                    <input type="number" step="0.01" name="TasaInteres" id="TasaInteres"
                           value="<?php echo $planExistente['TasaInteres'] ?? 8; ?>"
                           oninput="calcularCuota()">
                </div>

                <div class="form-group">
                    <label for="PlazoMeses">Plazo (meses):</label>
                    <input type="number" name="PlazoMeses" id="PlazoMeses" required
                           value="<?php echo $planExistente['PlazoMeses'] ?? ''; ?>"
                           oninput="calcularCuota()">
                </div>

                <div class="form-group">
                    <label for="FechaInicio">Fecha de Inicio:</label>
                    <input type="date" name="FechaInicio" id="FechaInicio"
                           value="<?php echo $planExistente['FechaInicio'] ?? date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="CuotaMensual">Cuota Mensual (calculada):</label>
                    <input type="text" id="CuotaMensual" disabled
                           value="<?php echo $planExistente ? number_format($planExistente['CuotaMensual'],2) : '0.00'; ?>">
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-success btn-block">
                    <?php echo $editar ? "Actualizar Plan" : "Crear Plan"; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    calcularCuota(); // Inicializa la cuota al cargar
</script>

</body>
</html>
