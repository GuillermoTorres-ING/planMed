<?php
session_start();

// Si ya está autenticado, redirigir según su nivel
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once 'config/database.php';
    include_once 'models/Usuario.php';


    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    if (empty($usuario) || empty($contrasena)) {
        $error = 'Usuario y contraseña son requeridos';
    } else {
        $database = new \config\Database();
        $db = $database->getConnection();

        if ($db) {
            $usuarioModel = new \models\Usuario($db);

            if ($usuarioModel->login($usuario, $contrasena)) {
                // Verificar que el usuario esté activo
                if ($usuarioModel->Activo != 1) {
                    $error = 'Usuario inactivo. Contacte al administrador.';
                    $database->closeConnection();
                } else {
                    // Login exitoso - Guardar datos en sesión
                    $_SESSION['usuario_id'] = $usuarioModel->Id;
                    $_SESSION['usuario_nombre'] = $usuarioModel->Nombre;
                    $_SESSION['usuario_user'] = $usuarioModel->Usuario;
                    $_SESSION['nivel_acceso'] = $usuarioModel->NivelAcceso;
                    $_SESSION['logged_in'] = true;
                    $_SESSION['last_activity'] = time();

                    // Registrar login en log (opcional)
                    error_log("Login exitoso: Usuario {$usuarioModel->Usuario} ({$usuarioModel->NivelAcceso}) - IP: {$_SERVER['REMOTE_ADDR']}");

                    $database->closeConnection();

                    // Redirigir al dashboard
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'Usuario o contraseña incorrectos';
                // Registrar intento fallido (seguridad)
                error_log("Login fallido: Usuario {$usuario} - IP: {$_SERVER['REMOTE_ADDR']}");
            }

            $database->closeConnection();
        } else {
            $error = 'Error de conexión a la base de datos';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediPay - Iniciar Sesión</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <h2>MediPay</h2>
        <p>Sistema de Planes de Pago Médicos</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="login-form">
        <div class="form-group">
            <label for="usuario">Usuario:</label>
            <input type="text" id="usuario" name="usuario" required
                   value="<?php echo htmlspecialchars($usuario ?? ''); ?>"
                   placeholder="Ingrese su usuario"
                   autofocus>
        </div>

        <div class="form-group">
            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" required
                   placeholder="Ingrese su contraseña">
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            Iniciar Sesión
        </button>
    </form>

    <div class="login-info">
        <h4>Credenciales de Prueba:</h4>
        <p><strong>Administrador:</strong> admin / admin123</p>
        <p style="margin-bottom: 10px;"><small>Acceso completo al sistema</small></p>

        <p><strong>Operador:</strong> operador / operador123</p>
        <p><small>Acceso limitado (sin anular facturas ni gestión de usuarios)</small></p>
    </div>
</div>
</body>
</html>