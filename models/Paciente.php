<?php

namespace models;
class Paciente
{
    private $conn;
    private $table_name = "Pacientes";

    public $Id;
    public $Cedula;
    public $Nombre;
    public $Telefono;
    public $Email;
    public $Direccion;
    public $FechaRegistro;
    public $Activo;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function crear()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                (Cedula, Nombre, Telefono, Email, Direccion) 
                VALUES (?, ?, ?, ?, ?)";

        $params = array(
            $this->Cedula,
            $this->Nombre,
            $this->Telefono,
            $this->Email,
            $this->Direccion
        );

        $stmt = sqlsrv_query($this->conn, $query, $params);
        return $stmt !== false;
    }

    public function listar()
    {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE Activo = 1 
                  ORDER BY Nombre";

        $stmt = sqlsrv_query($this->conn, $query);
        return $stmt;
    }

    public function buscarPorCedula($cedula)
    {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE Cedula = ? AND Activo = 1";

        $params = array($cedula);
        $stmt = sqlsrv_query($this->conn, $query, $params);

        if ($stmt && sqlsrv_has_rows($stmt)) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return false;
    }

    public function obtenerPorId($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE Id = ? AND Activo = 1";

        $params = array($id);
        $stmt = sqlsrv_query($this->conn, $query, $params);

        if ($stmt && sqlsrv_has_rows($stmt)) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return false;
    }
}

?>