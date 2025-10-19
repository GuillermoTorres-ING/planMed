<?php

namespace config;
class Database
{
    private $serverName;
    private $connectionOptions;
    public $conn;

    public function __construct()
    {
        // Configuración para SQL Server - XAMPP
        $this->serverName = "localhost";
        $this->connectionOptions = array(
            "Database" => "MediPayDB",
            "Uid" => "sa",
            "PWD" => "admin123",
            "TrustServerCertificate" => true,
            "CharacterSet" => "UTF-8",
            "ReturnDatesAsStrings" => true
        );
    }

    public function getConnection()
    {
        try {
            $this->conn = sqlsrv_connect($this->serverName, $this->connectionOptions);

            if ($this->conn === false) {
                $errors = sqlsrv_errors();
                $errorMessage = "Error de conexión SQL Server: ";
                foreach ($errors as $error) {
                    $errorMessage .= "SQLSTATE: " . $error['SQLSTATE'] . ", código: " . $error['code'] . ", mensaje: " . $error['message'];
                }
                error_log($errorMessage);
                return false;
            }

            return $this->conn;
        } catch (Exception $e) {
            error_log("Error de conexión: " . $e->getMessage());
            return false;
        }
    }

    public function closeConnection()
    {
        if ($this->conn) {
            sqlsrv_close($this->conn);
        }
    }
}

?>