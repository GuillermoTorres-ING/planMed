<?php

namespace models;
class Cuota
{
    private $conn;
    private $table_name = "Cuotas";

    public $Id;
    public $IdPlanPago;
    public $NumeroCuota;
    public $FechaVencimiento;
    public $MontoCuota;
    public $Capital;
    public $Interes;
    public $SaldoCapital;
    public $Estado;
    public $FechaPago;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function listarPorPlan($idPlanPago)
    {
        $query = "SELECT c.* 
                  FROM " . $this->table_name . " c
                  WHERE c.IdPlanPago = ? 
                  ORDER BY c.NumeroCuota";

        $params = array($idPlanPago);
        $stmt = sqlsrv_query($this->conn, $query, $params);
        return $stmt;
    }

    public function registrarPago($idCuota, $monto, $metodoPago, $referencia = null, $comentarios = null, $idUsuario = null)
    {
        if (sqlsrv_begin_transaction($this->conn) === false) {
            return false;
        }

        try {
            $query_pago = "INSERT INTO Pagos 
                          (IdCuota, MontoPago, MetodoPago, Referencia, Comentarios, IdUsuarioRegistro) 
                          VALUES (?, ?, ?, ?, ?, ?)";

            $params_pago = array($idCuota, $monto, $metodoPago, $referencia, $comentarios, $idUsuario);
            $stmt_pago = sqlsrv_query($this->conn, $query_pago, $params_pago);

            if ($stmt_pago === false) {
                throw new Exception("Error al registrar pago");
            }

            $query_cuota = "UPDATE Cuotas 
                           SET Estado = 'Pagado', FechaPago = GETDATE() 
                           WHERE Id = ?";

            $params_cuota = array($idCuota);
            $stmt_cuota = sqlsrv_query($this->conn, $query_cuota, $params_cuota);

            if ($stmt_cuota === false) {
                throw new Exception("Error al actualizar cuota");
            }

            sqlsrv_commit($this->conn);
            return true;
        } catch (Exception $e) {
            sqlsrv_rollback($this->conn);
            error_log("Error en transacción de pago: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerResumenPlan($idPlanPago)
    {
        $query = "SELECT 
                    COUNT(*) as TotalCuotas,
                    SUM(CASE WHEN Estado = 'Pagado' THEN 1 ELSE 0 END) as CuotasPagadas,
                    SUM(CASE WHEN Estado = 'Pendiente' THEN 1 ELSE 0 END) as CuotasPendientes,
                    SUM(MontoCuota) as TotalAPagar,
                    SUM(CASE WHEN Estado = 'Pagado' THEN MontoCuota ELSE 0 END) as TotalPagado,
                    SUM(CASE WHEN Estado = 'Pendiente' THEN MontoCuota ELSE 0 END) as TotalPendiente
                  FROM Cuotas 
                  WHERE IdPlanPago = ?";

        $params = array($idPlanPago);
        $stmt = sqlsrv_query($this->conn, $query, $params);

        if ($stmt && sqlsrv_has_rows($stmt)) {
            return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        }
        return false;
    }
}

?>