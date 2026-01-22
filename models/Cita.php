<?php
require_once __DIR__ . '/../config/db.php';

class Cita {
    private $conn;
    private $table = "citas";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Listar citas para el calendario
    public function listarParaCalendario() {
        $query = "SELECT id_cita, fecha_hora_inicio as start, fecha_hora_fin as end, 
                         motivo as title, estado, odontologo, paciente 
                  FROM vw_agenda"; 
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- NUEVO: Verificar Disponibilidad ---
    public function verificarDisponibilidad($id_odontologo, $inicio, $fin) {
        // Busca si hay alguna cita que se solape en horario y que NO esté cancelada
        $sql = "SELECT COUNT(*) as total FROM " . $this->table . " 
                WHERE id_odontologo = :odo 
                AND estado != 'CANCELADA'
                AND fecha_hora_inicio < :fin 
                AND fecha_hora_fin > :inicio";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":odo", $id_odontologo);
        $stmt->bindParam(":inicio", $inicio);
        $stmt->bindParam(":fin", $fin);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si total es 0, significa que está libre (TRUE). Si hay citas, devuelve FALSE.
        return ($resultado['total'] == 0); 
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

    // Anular citas sin confirmar (Regla de negocio opcional)
    public function anularVencidas() {
        $sql = "UPDATE citas SET estado = 'CANCELADA' 
                WHERE estado = 'PROGRAMADA' 
                AND fecha_hora_inicio < DATE_ADD(NOW(), INTERVAL 12 HOUR) 
                AND fecha_hora_inicio > NOW()";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
    }
}
?>