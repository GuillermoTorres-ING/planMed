<?php
namespace models;

class PlanPago
{
    private $conn;
    private $table_name = "PlanesPago";

    public $Id;
    public $IdPaciente;
    public $IdMedico;
    public $IdProcedimiento;
    public $Descripcion;
    public $CostoTotal;
    public $TasaInteres;
    public $PlazoMeses;
    public $CuotaMensual;
    public $FechaInicio;
    public $FechaCreacion;
    public $Estado;
    public $IdUsuarioCreacion;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Crear plan de pago
    public function crear()
    {
        $this->CuotaMensual = $this->calcularCuotaMensual();

        $query = "INSERT INTO $this->table_name 
                  (IdPaciente, IdMedico, IdProcedimiento, Descripcion, CostoTotal, 
                   TasaInteres, PlazoMeses, CuotaMensual, FechaInicio, IdUsuarioCreacion)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
                  SELECT CAST(SCOPE_IDENTITY() AS INT) AS Id;";

        $params = [
            $this->IdPaciente, $this->IdMedico, $this->IdProcedimiento,
            $this->Descripcion, $this->CostoTotal, $this->TasaInteres,
            $this->PlazoMeses, $this->CuotaMensual, $this->FechaInicio,
            $this->IdUsuarioCreacion
        ];

        $stmt = sqlsrv_query($this->conn, $query, $params);
        if ($stmt === false) {
            error_log("Error al insertar plan: " . print_r(sqlsrv_errors(), true));
            return false;
        }

        // Obtener ID recién insertado
        if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            $this->Id = $row['Id'];
        } else {
            return false;
        }

        // Generar tabla de amortización
        $this->generarTablaAmortizacion();
        return true;
    }

    // Calcular cuota mensual
    private function calcularCuotaMensual()
    {
        $monto = $this->CostoTotal;
        $tasa = $this->TasaInteres / 100 / 12;
        $plazo = $this->PlazoMeses;

        if ($plazo <= 0) return 0;
        if ($tasa == 0) return round($monto / $plazo, 2);

        $factor = pow(1 + $tasa, $plazo);
        return round($monto * ($tasa * $factor) / ($factor - 1), 2);
    }

    // Generar tabla de amortización
    private function generarTablaAmortizacion()
    {
        $saldoCapital = $this->CostoTotal;
        $tasaMensual = $this->TasaInteres / 100 / 12;

        for ($i = 1; $i <= $this->PlazoMeses; $i++) {
            $interes = round($saldoCapital * $tasaMensual, 2);
            $capital = round($this->CuotaMensual - $interes, 2);
            $saldoCapital = round($saldoCapital - $capital, 2);
            if ($saldoCapital < 0) $saldoCapital = 0;

            $fechaVencimiento = date('Y-m-d', strtotime($this->FechaInicio . " +$i months"));

            $query = "INSERT INTO Cuotas 
                      (IdPlanPago, NumeroCuota, FechaVencimiento, MontoCuota, Capital, Interes, SaldoCapital)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $params = [$this->Id, $i, $fechaVencimiento, $this->CuotaMensual, $capital, $interes, $saldoCapital];

            sqlsrv_query($this->conn, $query, $params);
        }
    }

    // Listar todos los planes de un paciente
    public function listarPorPaciente($idPaciente)
    {
        $query = "SELECT pp.*, p.Nombre AS PacienteNombre, p.Cedula AS PacienteCedula,
                     m.Nombre AS MedicoNombre, pr.Descripcion AS ProcedimientoDesc
              FROM $this->table_name pp
              INNER JOIN Pacientes p ON pp.IdPaciente = p.Id
              INNER JOIN Medicos m ON pp.IdMedico = m.Id
              INNER JOIN Procedimientos pr ON pp.IdProcedimiento = pr.Id
              WHERE pp.IdPaciente = ?
              ORDER BY pp.FechaCreacion DESC";

        $stmt = sqlsrv_query($this->conn, $query, [$idPaciente]);
        if ($stmt === false) {
            error_log("Error al listar planes del paciente: " . print_r(sqlsrv_errors(), true));
            return false;
        }
        return $stmt;
    }


    // Listar cuotas de un plan
    public function listarCuotas($idPlan)
    {
        $query = "
        SELECT 
            Id,
            IdPlanPago,
            NumeroCuota,
            FechaVencimiento,
            MontoCuota,
            Capital,
            Interes,
            SaldoCapital,
            Estado,
            FechaPago
        FROM Cuotas
        WHERE IdPlanPago = ?
        ORDER BY NumeroCuota ASC
    ";

        $stmt = sqlsrv_query($this->conn, $query, [$idPlan]);

        if ($stmt === false) {
            error_log('Error al listar cuotas: ' . print_r(sqlsrv_errors(), true));
        } elseif (!sqlsrv_has_rows($stmt)) {
            error_log("No hay cuotas registradas para el plan #$idPlan");
        }

        return $stmt;
    }

    // Obtener plan por ID
    public function obtenerPorId($id)
    {
        $query = "SELECT pp.*, p.Nombre AS PacienteNombre, p.Cedula AS PacienteCedula,
                         m.Nombre AS MedicoNombre, pr.Descripcion AS ProcedimientoDesc
                  FROM $this->table_name pp
                  INNER JOIN Pacientes p ON pp.IdPaciente = p.Id
                  INNER JOIN Medicos m ON pp.IdMedico = m.Id
                  INNER JOIN Procedimientos pr ON pp.IdProcedimiento = pr.Id
                  WHERE pp.Id = ?";
        $stmt = sqlsrv_query($this->conn, $query, [$id]);
        if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
            return $row;
        }
        return false;
    }
}
?>
