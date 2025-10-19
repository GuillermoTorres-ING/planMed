<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class SessionManager {
    public static function iniciarSesion($usuario) {
        $_SESSION['usuario_id'] = $usuario->Id;
        $_SESSION['usuario_nombre'] = $usuario->Nombre;
        $_SESSION['usuario_user'] = $usuario->Usuario;
        $_SESSION['nivel_acceso'] = $usuario->NivelAcceso;
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();

        session_regenerate_id(true);
    }

    public static function verificarSesion() {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 28800)) {
            self::cerrarSesion();
            return false;
        }
        $_SESSION['last_activity'] = time();

        return true;
    }

    public static function verificarAcceso($nivelRequerido) {
        if (!self::verificarSesion()) {
            return false;
        }

        $nivelActual = $_SESSION['nivel_acceso'];

        if ($nivelActual === 'Administrador') {
            return true;
        }

        if ($nivelRequerido === 'Operador' && $nivelActual === 'Operador') {
            return true;
        }

        return false;
    }

    public static function cerrarSesion() {
        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }

    public static function getUsuarioActual() {
        if (!self::verificarSesion()) {
            return null;
        }

        return [
            'id' => $_SESSION['usuario_id'] ?? null,
            'nombre' => $_SESSION['usuario_nombre'] ?? null,
            'usuario' => $_SESSION['usuario_user'] ?? null,
            'nivel_acceso' => $_SESSION['nivel_acceso'] ?? null
        ];
    }
}
?>