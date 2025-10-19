<?php
namespace models;

class Factura
{
    private $conn;
    private $table_name = "Facturas";

    public $Id;
    public $IdPlanPago;
    public $IdCuota;
    public $NumeroFactura;
    public $FechaEmision;
    public $FechaVencimiento;
    public $Subtotal;
    public $ITBIS;
    public $Descuento;
    public $MontoTotal;
    public $Estado;
    public $MetodoPago;
    public $ReferenciaPago;
    public $FechaPago;
    public $PacienteNombre;
    public $PacienteCedula;
    public $PacienteDireccion;
    public $DescripcionServicio;
    public $MedicoNombre;
    public $IdUsuarioEmision;
    public $Comentarios;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Crear factura sin marcar cuota pagada (se marca al pagar).
     * Devuelve TRUE si se crea correctamente.
     */
    public function crear($cuotasSeleccionadas)
    {
        foreach ($cuotasSeleccionadas as $cuota) {
            $this->IdPlanPago = $cuota['IdPlanPago'];
            $this->IdCuota = $cuota['Id'];
            $this->Subtotal = $cuota['MontoCuota'];
            $this->ITBIS = 0;
            $this->Descuento = 0;
            $this->MontoTotal = $this->Subtotal + $this->ITBIS - $this->Descuento;
            $this->FechaVencimiento = $cuota['FechaVencimiento'];
            $this->Estado = 'Pendiente';
            $this->FechaEmision = date('Y-m-d H:i:s');
            $this->MetodoPago = $cuota['MetodoPago'] ?? 'Pendiente';
            $this->ReferenciaPago = null;
            $this->PacienteNombre = $cuota['PacienteNombre'];
            $this->PacienteCedula = $cuota['PacienteCedula'];
            $this->PacienteDireccion = $cuota['PacienteDireccion'] ?? '';
            $this->DescripcionServicio = $cuota['DescripcionServicio'];
            $this->MedicoNombre = $cuota['MedicoNombre'];
            $this->IdUsuarioEmision = $_SESSION['usuario_id'] ?? 1;
            $this->Comentarios = $cuota['Comentarios'] ?? '';

            $this->NumeroFactura = 'FAC-' . date('YmdHis') . rand(100,999);

            $query = "INSERT INTO $this->table_name 
                      (IdPlanPago, IdCuota, NumeroFactura, FechaEmision, FechaVencimiento, Subtotal, ITBIS, Descuento, MontoTotal, Estado, MetodoPago, ReferenciaPago, PacienteNombre, PacienteCedula, PacienteDireccion, DescripcionServicio, MedicoNombre, IdUsuarioEmision, Comentarios)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $this->IdPlanPago, $this->IdCuota, $this->NumeroFactura,
                $this->FechaEmision, $this->FechaVencimiento, $this->Subtotal,
                $this->ITBIS, $this->Descuento, $this->MontoTotal,
                $this->Estado, $this->MetodoPago, $this->ReferenciaPago,
                $this->PacienteNombre, $this->PacienteCedula,
                $this->PacienteDireccion, $this->DescripcionServicio,
                $this->MedicoNombre, $this->IdUsuarioEmision, $this->Comentarios
            ];

            $stmt = sqlsrv_query($this->conn, $query, $params);
            if ($stmt === false) {
                error_log('Error al crear factura: ' . print_r(sqlsrv_errors(), true));
                return false;
            }
        }
        return true;
    }

    /**
     * ✅ Crear factura y devolver su ID recién insertado.
     */
    public function crearYRetornarId($cuotasSeleccionadas)
    {
        foreach ($cuotasSeleccionadas as $cuota) {
            $this->IdPlanPago = $cuota['IdPlanPago'];
            $this->IdCuota = $cuota['Id'];
            $this->Subtotal = $cuota['MontoCuota'];
            $this->ITBIS = 0;
            $this->Descuento = 0;
            $this->MontoTotal = $this->Subtotal;
            $this->FechaVencimiento = $cuota['FechaVencimiento'];
            $this->Estado = 'Pendiente';
            $this->FechaEmision = date('Y-m-d H:i:s');
            $this->MetodoPago = 'Pendiente';
            $this->PacienteNombre = $cuota['PacienteNombre'];
            $this->PacienteCedula = $cuota['PacienteCedula'];
            $this->PacienteDireccion = $cuota['PacienteDireccion'];
            $this->DescripcionServicio = $cuota['DescripcionServicio'];
            $this->MedicoNombre = $cuota['MedicoNombre'];
            $this->IdUsuarioEmision = $_SESSION['usuario_id'] ?? 1;
            $this->Comentarios = $cuota['Comentarios'];

            $this->NumeroFactura = 'FAC-' . date('YmdHis') . rand(100, 999);

            $query = "INSERT INTO Facturas 
                      (IdPlanPago, IdCuota, NumeroFactura, FechaEmision, FechaVencimiento, Subtotal, ITBIS, Descuento, MontoTotal, Estado, MetodoPago, PacienteNombre, PacienteCedula, PacienteDireccion, DescripcionServicio, MedicoNombre, IdUsuarioEmision, Comentarios)
                      OUTPUT INSERTED.Id
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $this->IdPlanPago, $this->IdCuota, $this->NumeroFactura,
                $this->FechaEmision, $this->FechaVencimiento, $this->Subtotal,
                $this->ITBIS, $this->Descuento, $this->MontoTotal, $this->Estado,
                $this->MetodoPago, $this->PacienteNombre, $this->PacienteCedula,
                $this->PacienteDireccion, $this->DescripcionServicio, $this->MedicoNombre,
                $this->IdUsuarioEmision, $this->Comentarios
            ];

            $stmt = sqlsrv_query($this->conn, $query, $params);
            if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
                return $row['Id'];
            }
        }
        return false;
    }

    /**
     * 🔄 Marcar factura como pagada y actualizar cuota
     */
    public function marcarPagada($idFactura, $metodoPago, $referencia = null)
    {
        $query = "UPDATE Facturas 
                  SET Estado='Pagada', MetodoPago=?, ReferenciaPago=?, FechaPago=GETDATE()
                  WHERE Id=?";
        $params = [$metodoPago, $referencia, $idFactura];
        $stmt = sqlsrv_query($this->conn, $query, $params);

        if ($stmt) {
            // ✅ Marcar cuota como pagada también
            $query2 = "UPDATE Cuotas SET Estado='Pagada', FechaPago=GETDATE()
                       WHERE Id = (SELECT IdCuota FROM Facturas WHERE Id = ?)";
            sqlsrv_query($this->conn, $query2, [$idFactura]);
            return true;
        }
        return false;
    }

    /**
     * 📋 Listar facturas por plan
     */
    public function listarPorPlan($idPlan)
    {
        $query = "SELECT * FROM $this->table_name WHERE IdPlanPago=? ORDER BY FechaEmision DESC";
        return sqlsrv_query($this->conn, $query, [$idPlan]);
    }

    /**
     * 🔎 Obtener una factura por ID
     */
    public function obtenerPorId($id)
    {
        $query = "SELECT * FROM $this->table_name WHERE Id = ?";
        $stmt = sqlsrv_query($this->conn, $query, [$id]);
        if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            return $row;
        }
        return false;
    }
}
?>
