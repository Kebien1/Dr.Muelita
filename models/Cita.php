<?php
require_once __DIR__ . '/../config/db.php';

class Cita {
    private $conn;
    private $table = "citas";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // LISTAR PARA CALENDARIO (Con Filtro Opcional)
    public function listarParaCalendario($id_odontologo = null) {
        // Hacemos JOIN directo para asegurar los datos y poder filtrar
        $sql = "SELECT 
                    c.id_cita, 
                    c.fecha_hora_inicio as start, 
                    c.fecha_hora_fin as end, 
                    CONCAT(p.nombres, ' ', p.apellido_paterno) as title,
                    c.estado,
                    c.motivo
                FROM citas c
                INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                WHERE c.estado != 'CANCELADA'";

        // Si mandaron un ID de doctor, filtramos
        if ($id_odontologo) {
            $sql .= " AND c.id_odontologo = :odo";
        }

        $stmt = $this->conn->prepare($sql);
        
        if ($id_odontologo) {
            $stmt->bindParam(":odo", $id_odontologo);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // OBTENER UNA CITA (Para editar y ver historial)
    public function obtenerPorId($id) {
        // Traemos también el CI del paciente para el enlace al historial
        $query = "SELECT c.*, p.ci 
                  FROM " . $this->table . " c
                  INNER JOIN pacientes p ON c.id_paciente = p.id_paciente
                  WHERE c.id_cita = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Verificar Disponibilidad
    public function verificarDisponibilidad($id_odontologo, $inicio, $fin, $id_cita_excluir = null) {
        $sql = "SELECT COUNT(*) as total FROM " . $this->table . " 
                WHERE id_odontologo = :odo 
                AND estado != 'CANCELADA'
                AND fecha_hora_inicio < :fin 
                AND fecha_hora_fin > :inicio";
        
        if ($id_cita_excluir) {
            $sql .= " AND id_cita != :exc";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(":odo", $id_odontologo);
        $stmt->bindParam(":inicio", $inicio);
        $stmt->bindParam(":fin", $fin);
        
        if ($id_cita_excluir) {
            $stmt->bindParam(":exc", $id_cita_excluir);
        }

        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] == 0); 
    }

    // Crear
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

    // Actualizar
    public function actualizar($datos) {
        $query = "UPDATE " . $this->table . " SET 
                  id_paciente = :pac, 
                  id_odontologo = :odoc, 
                  fecha_hora_inicio = :inicio, 
                  fecha_hora_fin = :fin, 
                  motivo = :motivo
                  WHERE id_cita = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":pac", $datos['id_paciente']);
        $stmt->bindParam(":odoc", $datos['id_odontologo']);
        $stmt->bindParam(":inicio", $datos['inicio']);
        $stmt->bindParam(":fin", $datos['fin']);
        $stmt->bindParam(":motivo", $datos['motivo']);
        $stmt->bindParam(":id", $datos['id_cita']);
        return $stmt->execute();
    }
    
    // Mover (Drag & Drop)
    public function mover($id_cita, $inicio, $fin) {
        $query = "UPDATE " . $this->table . " SET fecha_hora_inicio = :inicio, fecha_hora_fin = :fin WHERE id_cita = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":inicio", $inicio);
        $stmt->bindParam(":fin", $fin);
        $stmt->bindParam(":id", $id_cita);
        return $stmt->execute();
    }

    // Cancelar Cita
    public function cancelar($id_cita) {
        $query = "UPDATE " . $this->table . " SET estado = 'CANCELADA' WHERE id_cita = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id_cita);
        return $stmt->execute();
    }

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