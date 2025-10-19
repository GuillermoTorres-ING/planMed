<?php
// nav.php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$usuario = $_SESSION['usuario_nombre'] ?? 'Invitado';
$nivel = $_SESSION['nivel_acceso'] ?? 'Desconocido';
?>

<nav class="navbar">
    <div class="navbar-left">
        <h2 class="navbar-logo">MediPay</h2>
        <ul class="navbar-menu">
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="planes.php">Planes</a></li>
            <li><a href="pacientes.php">Pacientes</a></li>
            <li><a href="pagos.php">Pagos</a></li>

            <?php if ($nivel === 'Administrador'): ?>
                <li><a href="usuarios.php">Usuarios</a></li>
                <li><a href="reportes.php">Reportes</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="navbar-right">
        <span class="navbar-user">
            👤 <?php echo htmlspecialchars($usuario); ?> (<?php echo htmlspecialchars($nivel); ?>)
        </span>
        <a href="../logout.php" class="btn-logout">Cerrar Sesión</a>
    </div>
</nav>

<style>
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #0078D7;
        color: white;
        padding: 10px 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .navbar-logo {
        margin: 0;
    }
    .navbar-menu {
        list-style: none;
        display: flex;
        gap: 15px;
        margin: 0;
        padding: 0;
    }
    .navbar-menu li a {
        color: white;
        text-decoration: none;
        font-weight: 500;
    }
    .navbar-menu li a:hover {
        text-decoration: underline;
    }
    .navbar-user {
        margin-right: 15px;
    }
    .btn-logout {
        background: #ff4b5c;
        color: white;
        padding: 6px 12px;
        border-radius: 5px;
        text-decoration: none;
    }
    .btn-logout:hover {
        background: #ff2a3a;
    }
</style>
