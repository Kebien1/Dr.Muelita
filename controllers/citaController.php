<?php
session_start();
require_once __DIR__ . '/../models/Cita.php';

$citaModel = new Cita();

// --- API JSON ---

// 1. Listar (Con filtro de doctor opcional)
if (isset($_GET['accion']) && $_GET['accion'] == 'listar') {
    
    // ¿Pidieron filtrar por doctor?
    $id_filtro = isset($_GET['id_odontologo']) && !empty($_GET['id_odontologo']) 
                 ? $_GET['id_odontologo'] 
                 : null;

    $citas = $citaModel->listarParaCalendario($id_filtro);
    
    $eventos = [];
    foreach($citas as $cita) {
        $color = '#3498db'; // Azul (Por defecto)
        if($cita['estado'] == 'ATENDIDA') $color = '#2ecc71'; // Verde
        // Las canceladas ya no las traemos del modelo, pero por seguridad:
        if($cita['estado'] == 'CANCELADA') $color = '#e74c3c'; 

        $eventos[] = [
            'id' => $cita['id_cita'],
            'title' => $cita['title'], // Nombre del paciente
            'start' => $cita['start'],
            'end' => $cita['end'],
            'color' => $color,
            'extendedProps' => [
                'estado' => $cita['estado'],
                'motivo' => $cita['motivo']
            ]
        ];
    }
    echo json_encode($eventos);
    exit; 
}

// 2. Obtener UNA cita
if (isset($_GET['accion']) && $_GET['accion'] == 'obtener') {
    $id = $_GET['id'];
    $cita = $citaModel->obtenerPorId($id);
    echo json_encode($cita);
    exit;
}

// 3. Mover Cita
if (isset($_POST['accion']) && $_POST['accion'] == 'mover') {
    $id = $_POST['id_cita'];
    $inicio = $_POST['start'];
    $fin = $_POST['end'];
    $citaOriginal = $citaModel->obtenerPorId($id);
    
    if ($citaModel->verificarDisponibilidad($citaOriginal['id_odontologo'], $inicio, $fin, $id)) {
        $citaModel->mover($id, $inicio, $fin);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Horario ocupado']);
    }
    exit;
}

// 4. Cancelar Cita
if (isset($_POST['accion']) && $_POST['accion'] == 'cancelar') {
    $id = $_POST['id_cita'];
    if ($citaModel->cancelar($id)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// --- FORMULARIO POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $inicio = $_POST['fecha'] . ' ' . $_POST['hora'];
    $duracion = 30; 
    $fin = date('Y-m-d H:i:s', strtotime($inicio . " +$duracion minutes"));
    $id_odontologo = $_POST['id_odontologo'];
    
    $id_cita = isset($_POST['id_cita']) && !empty($_POST['id_cita']) ? $_POST['id_cita'] : null;

    if (!$citaModel->verificarDisponibilidad($id_odontologo, $inicio, $fin, $id_cita)) {
        header("Location: ../views/citas/calendario.php?error=ocupado");
        exit;
    }

    $datos = [
        'id_paciente' => $_POST['id_paciente'],
        'id_odontologo' => $id_odontologo,
        'inicio' => $inicio,
        'fin' => $fin,
        'motivo' => $_POST['motivo'],
        'id_usuario' => $_SESSION['id_usuario']
    ];

    if ($id_cita) {
        $datos['id_cita'] = $id_cita;
        if ($citaModel->actualizar($datos)) {
            header("Location: ../views/citas/calendario.php?ok=editado");
        } else {
            header("Location: ../views/citas/calendario.php?error=1");
        }
    } else {
        if ($citaModel->crear($datos)) {
            header("Location: ../views/citas/calendario.php?ok=creado");
        } else {
            header("Location: ../views/citas/calendario.php?error=1");
        }
    }
}
?>