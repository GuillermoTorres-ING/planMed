<?php
session_start();

// Destruir todas las variables de sesión
$_SESSION = array();

// Si hay una cookie de sesión, eliminarla
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión completamente
session_destroy();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesión Cerrada - MediPay</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .logout-container {
            max-width: 450px;
            margin: 80px auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            text-align: center;
            animation: fadeIn 0.6s ease-out;
        }

        .logout-container h2 {
            font-size: 2em;
            color: #667eea;
            margin-bottom: 15px;
        }

        .logout-container p {
            color: #555;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .btn-login {
            background: #667eea;
            color: white;
            padding: 10px 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="logout-container">
    <h2>Sesión Cerrada</h2>
    <p>Has cerrado sesión correctamente en <strong>MediPay</strong>.<br>
        Esperamos verte pronto.</p>

    <a href="login.php" class="btn-login">Volver a Iniciar Sesión</a>
</div>
</body>
</html>
