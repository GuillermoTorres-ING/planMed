<?php

namespace models;
class Procedimiento
{
    private $conn;
    private $table_name = "Procedimientos";

    public $Id;
    public $Codigo;
    public $Descripcion;
    public $CostoBase;
    public $Activo;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function crear()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                (Codigo, Descripcion, CostoBase) 
                VALUES (?, ?, ?)";

        $params = array(
            $this->Codigo,
            $this->Descripcion,
            $this->CostoBase
        );

        $stmt = sqlsrv_query($this->conn, $query, $params);
        return $stmt !== false;
    }

    public function listar()
    {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE Activo = 1 
                  ORDER BY Descripcion";

        $stmt = sqlsrv_query($this->conn, $query);
        return $stmt;
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