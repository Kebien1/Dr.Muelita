<?php
require_once __DIR__ . '/../config/db.php';

class Cita {
    private $conn;
    private $table = "citas";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Listar citas para el calendario (devuelve JSON structure luego)
    public function listarParaCalendario() {
    // IMPORTANTE: Debe llamar a vw_agenda, no a la tabla citas directamente
    $query = "SELECT id_cita, fecha_hora_inicio as start, fecha_hora_fin as end, 
                     motivo as title, estado, odontologo, paciente 
              FROM vw_agenda"; 
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    // Crear nueva cita
    public function crear($datos) {
        $query = "INSERT INTO " . $this->table . " 
                  (id_paciente, id_odontologo, fecha_hora_inicio, fecha_hora_fin, motivo, estado, creada_por) 
                  VALUES (:pac, :odoc, :inicio, :fin, :motivo, 'PROGRAMADA', :creador)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":pac", $datos['id_paciente']);
        $stmt->bindParam(":odoc", $datos['id_odontologo']);
        $stmt->bindParam(":inicio", $datos['inicio']);
        $stmt->bindParam(":fin", $datos['fin']);
        $stmt->bindParam(":motivo", $datos['motivo']);
        $stmt->bindParam(":creador", $datos['id_usuario']);

        return $stmt->execute();
    }

    // REGLA DE NEGOCIO: Anular citas sin confirmar 12 horas antes
    public function anularVencidas() {
        // Si falta menos de 12 horas para la cita y sigue "PROGRAMADA" (no confirmada/pagada), se anula?
        // Nota: En tu requerimiento decía "confirmadas hasta 12 hrs antes".
        // Aquí simulamos esa lógica: Si estamos a menos de 12 horas del inicio y el estado es PROGRAMADA, pasamos a CANCELADA.
        
        $sql = "UPDATE citas SET estado = 'CANCELADA' 
                WHERE estado = 'PROGRAMADA' 
                AND fecha_hora_inicio < DATE_ADD(NOW(), INTERVAL 12 HOUR) 
                AND fecha_hora_inicio > NOW()";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
    }
}
?>