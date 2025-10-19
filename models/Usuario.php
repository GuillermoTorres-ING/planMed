<?php

namespace models;
class Usuario
{
    private $conn;
    private $table_name = "Usuarios";

    public $Id;
    public $Usuario;
    public $Contrasena;
    public $Nombre;
    public $NivelAcceso;
    public $Activo;
    public $FechaCreacion;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function login($usuario, $contrasena)
    {
        $query = "SELECT Id, Usuario, Contrasena, Nombre, NivelAcceso, Activo 
                  FROM " . $this->table_name . " 
                  WHERE Usuario = ? AND Activo = 1";

        $params = array($usuario);
        $stmt = sqlsrv_query($this->conn, $query, $params);

        if ($stmt === false) {
            error_log("Error en consulta SQL: " . print_r(sqlsrv_errors(), true));
            return false;
        }

        if (sqlsrv_has_rows($stmt)) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            // Para desarrollo: comparación directa
            if ($contrasena === $row['Contrasena']) {
                $this->Id = $row['Id'];
                $this->Usuario = $row['Usuario'];
                $this->Nombre = $row['Nombre'];
                $this->NivelAcceso = $row['NivelAcceso'];
                $this->Activo = $row['Activo'];
                return true;
            }
        }

        return false;
    }

    public function crear()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                (Usuario, Contrasena, Nombre, NivelAcceso) 
                VALUES (?, ?, ?, ?)";

        $hashed_password = $this->Contrasena;

        $params = array(
            $this->Usuario,
            $hashed_password,
            $this->Nombre,
            $this->NivelAcceso
        );

        $stmt = sqlsrv_query($this->conn, $query, $params);
        return $stmt !== false;
    }

    public function listar()
    {
        $query = "SELECT Id, Usuario, Nombre, NivelAcceso, Activo, FechaCreacion 
                  FROM " . $this->table_name . " 
                  ORDER BY Nombre";

        $stmt = sqlsrv_query($this->conn, $query);
        return $stmt;
    }

    public function obtenerPorUsuario($usuario)
    {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE Usuario = ?";

        $params = array($usuario);
        $stmt = sqlsrv_query($this->conn, $query, $params);

        if ($stmt && sqlsrv_has_rows($stmt)) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return false;
    }
}

?>