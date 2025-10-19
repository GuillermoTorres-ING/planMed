<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_nombre = $_SESSION['usuario_nombre'];
$nivel_acceso = $_SESSION['nivel_acceso'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediPay - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="header">
    <h1>MediPay - Sistema de Préstamos Médicos</h1>
    <div class="user-info">
        <strong><?php echo htmlspecialchars($usuario_nombre); ?></strong>
        <span>(<?php echo htmlspecialchars($nivel_acceso); ?>)</span>
        <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
    </div>
</div>

<div class="nav">
    <a href="index.php" class="active">Dashboard</a>
    <a href="crear_plan.php">Crear Plan de Pago</a>
    <a href="consultar_planes.php">Consultar Planes</a>
    <a href="consultar_facturas.php">Consultar Facturas</a>
    <a href="facturacion_plan.php">Facturar Plan</a>
    <?php if ($nivel_acceso === 'Administrador'): ?>
        <a href="gestion_usuarios.php">Gestión Usuarios</a>
        <a href="reportes.php">Reportes</a>
    <?php endif; ?>
</div>

<div class="content">
    <div class="dashboard">
        <h2>Bienvenido al Sistema MediPay</h2>

        <div class="cards-container">
            <div class="card">
                <h3>Crear Plan de Pago</h3>
                <p>Registre un nuevo plan de pago para un paciente</p>
                <a href="crear_plan.php" class="btn">Ir a Crear Plan</a>
            </div>

            <div class="card">
                <h3>Consultar Planes</h3>
                <p>Busque y consulte planes de pago existentes</p>
                <a href="consultar_planes.php" class="btn">Ir a Consultar</a>
            </div>

            <div class="card">
                <h3>Consultar Facturas</h3>
                <p>Busque facturas por paciente o número</p>
                <a href="consultar_facturas.php" class="btn">Ver Facturas</a>
            </div>

            <?php if ($nivel_acceso === 'Administrador'): ?>
                <div class="card">
                    <h3>Reportes</h3>
                    <p>Consulte estadísticas y reportes del sistema</p>
                    <a href="reportes.php" class="btn">Ver Reportes</a>
                </div>
            <?php endif; ?>
        </div>

        <?php
        // Mostrar estadísticas rápidas
        include_once 'config/database.php';
        $database = new \config\Database();
        $db = $database->getConnection();

        if ($db) {
            // Total de planes activos
            $query = "SELECT COUNT(*) as total FROM PlanesPago WHERE Estado = 'Activo'";
            $stmt = sqlsrv_query($db, $query);
            $planes = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            // Total de facturas pendientes
            $query2 = "SELECT COUNT(*) as total FROM Facturas WHERE Estado = 'Pendiente'";
            $stmt2 = sqlsrv_query($db, $query2);
            $facturas = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);

            // Total recaudado este mes
            $query3 = "SELECT ISNULL(SUM(MontoTotal), 0) as total 
                          FROM Facturas 
                          WHERE Estado = 'Pagada' 
                          AND MONTH(FechaPago) = MONTH(GETDATE())
                          AND YEAR(FechaPago) = YEAR(GETDATE())";
            $stmt3 = sqlsrv_query($db, $query3);
            $recaudado = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC);

            $database->closeConnection();
            ?>

            <div class="stats-container">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $planes['total']; ?></div>
                    <div class="stat-label">Planes Activos</div>
                </div>
                <div class="stat-box warning">
                    <div class="stat-number"><?php echo $facturas['total']; ?></div>
                    <div class="stat-label">Facturas Pendientes</div>
                </div>
                <div class="stat-box success">
                    <div class="stat-number">RD$ <?php echo number_format($recaudado['total'], 2); ?></div>
                    <div class="stat-label">Recaudado Este Mes</div>
                </div>
            </div>

        <?php } ?>
    </div>
</div>
</body>
</html>