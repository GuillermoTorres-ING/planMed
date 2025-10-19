<div class="header">
    <h1>MediPay - Sistema de Préstamos Médicos</h1>
    <div class="user-info">
        <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></strong>
        <span>(<?php echo htmlspecialchars($_SESSION['nivel_acceso']); ?>)</span>
        <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
    </div>
</div>