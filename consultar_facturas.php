<?php
use models\Factura;
use config\Database;

session_start();

// ============================================
// VALIDAR SESIÓN
// ============================================
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Si no hay login real, para pruebas:
if (!isset($_SESSION['rol'])) {
    $_SESSION['rol'] = 'admin'; // Cambiar a 'operador' para probar permisos
}

include_once 'config/database.php';
include_once 'models/Factura.php';
include_once 'includes/nav.php';

$database = new Database();
$db = $database->getConnection();

$error = '';
$facturas = [];
$tipoBusqueda = '';

// ============================================
// PROCESAR BÚSQUEDA
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db) {
    $tipoBusqueda = $_POST['tipo_busqueda'] ?? '';
    $valorBusqueda = trim($_POST['valor_busqueda'] ?? '');

    if (empty($valorBusqueda)) {
        $error = 'Debe ingresar un valor de búsqueda.';
    } else {
        $facturaModel = new Factura($db);

        if ($tipoBusqueda === 'paciente') {
            // Buscar por cédula del paciente
            $stmt = sqlsrv_query($db, "SELECT * FROM Facturas WHERE PacienteCedula = ?", [$valorBusqueda]);
        } elseif ($tipoBusqueda === 'numero') {
            // Buscar por número de factura
            $stmt = sqlsrv_query($db, "SELECT * FROM Facturas WHERE NumeroFactura = ?", [$valorBusqueda]);
        } else {
            $error = 'Tipo de búsqueda no válido.';
            $stmt = false;
        }

        if ($stmt) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                if ($row['FechaEmision'] instanceof DateTime)
                    $row['FechaEmision'] = $row['FechaEmision']->format('Y-m-d H:i:s');
                if ($row['FechaVencimiento'] instanceof DateTime)
                    $row['FechaVencimiento'] = $row['FechaVencimiento']->format('Y-m-d');
                if ($row['FechaPago'] instanceof DateTime)
                    $row['FechaPago'] = $row['FechaPago']->format('Y-m-d H:i:s');

                $facturas[] = $row;
            }

            if (count($facturas) === 0) {
                $error = 'No se encontraron facturas para ese criterio.';
            }
        } else {
            $error = 'Error al ejecutar la búsqueda.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Facturas - MediPay</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="content">
    <h2>Consultar Facturas</h2>

    <!-- =================== -->
    <!-- FORMULARIO BÚSQUEDA -->
    <!-- =================== -->
    <div class="form-container">
        <h3>Buscar Facturas</h3>
        <form method="POST" action="consultar_facturas.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="tipo_busqueda">Buscar por:</label>
                    <select id="tipo_busqueda" name="tipo_busqueda" required>
                        <option value="paciente" <?= ($tipoBusqueda == 'paciente') ? 'selected' : ''; ?>>
                            Cédula del Paciente
                        </option>
                        <option value="numero" <?= ($tipoBusqueda == 'numero') ? 'selected' : ''; ?>>
                            Número de Factura
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="valor_busqueda">Valor:</label>
                    <input type="text" id="valor_busqueda" name="valor_busqueda" required
                           placeholder="Ingrese cédula o número"
                           value="<?= isset($_POST['valor_busqueda']) ? htmlspecialchars($_POST['valor_busqueda']) : ''; ?>">
                </div>

                <div class="form-group" style="align-self: flex-end;">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </div>
        </form>
    </div>

    <!-- =================== -->
    <!-- MENSAJES Y RESULTADOS -->
    <!-- =================== -->
    <?php if ($error): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (count($facturas) > 0): ?>
        <div class="card">
            <div class="card-header">
                <h3>Resultados de Búsqueda (<?= count($facturas); ?>)</h3>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th>Número</th>
                        <th>Paciente</th>
                        <th>Fecha Emisión</th>
                        <th>Vencimiento</th>
                        <th>Monto Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($facturas as $factura): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($factura['NumeroFactura']); ?></strong></td>
                            <td><?= htmlspecialchars($factura['PacienteNombre']); ?></td>
                            <td><?= date('d/m/Y', strtotime($factura['FechaEmision'])); ?></td>
                            <td><?= date('d/m/Y', strtotime($factura['FechaVencimiento'])); ?></td>
                            <td>RD$ <?= number_format($factura['MontoTotal'], 2); ?></td>
                            <td>
                                <?php
                                $badgeClass = 'badge-secondary';
                                switch ($factura['Estado']) {
                                    case 'Pagada': $badgeClass = 'badge-success'; break;
                                    case 'Pendiente': $badgeClass = 'badge-warning'; break;
                                    case 'Vencida': $badgeClass = 'badge-danger'; break;
                                    case 'Anulada': $badgeClass = 'badge-danger'; break;
                                }
                                ?>
                                <span class="badge <?= $badgeClass; ?>">
                                    <?= htmlspecialchars($factura['Estado']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="ver_factura.php?id=<?= $factura['Id']; ?>" class="btn btn-sm">Ver</a>

                                <?php if ($factura['Estado'] === 'Pendiente' || $factura['Estado'] === 'Vencida'): ?>
                                    <a href="pagar_factura.php?id=<?= $factura['Id']; ?>" class="btn btn-sm btn-success">Pagar</a>
                                <?php endif; ?>

                                <?php if ($_SESSION['rol'] === 'admin' && $factura['Estado'] !== 'Anulada'): ?>
                                    <a href="anular_factura.php?id=<?= $factura['Id']; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('¿Deseas anular esta factura?')">
                                        Anular
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

<?php if ($db) $database->closeConnection(); ?>
