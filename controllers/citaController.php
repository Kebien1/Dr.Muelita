<?php
session_start();
require_once __DIR__ . '/../models/Cita.php';

$citaModel = new Cita();

// 1. Si piden la lista para el calendario (AJAX)
if (isset($_GET['accion']) && $_GET['accion'] == 'listar') {
    $citas = $citaModel->listarParaCalendario();
    
    $eventos = [];
    foreach($citas as $cita) {
        $color = '#3498db'; // Azul (Programada)
        if($cita['estado'] == 'CANCELADA') $color = '#e74c3c';
        if($cita['estado'] == 'ATENDIDA') $color = '#2ecc71'; 

        $eventos[] = [
            'id' => $cita['id_cita'],
            'title' => $cita['paciente'] . ' - ' . $cita['title'],
            'start' => $cita['start'],
            'end' => $cita['end'],
            'color' => $color
        ];
    }
    echo json_encode($eventos);
    exit; 
}

// 2. Si envían el formulario para guardar
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Calculamos hora fin (30 min de duración fija)
    $inicio = $_POST['fecha'] . ' ' . $_POST['hora'];
    $duracion = 30; 
    $fin = date('Y-m-d H:i:s', strtotime($inicio . " +$duracion minutes"));
    $id_odontologo = $_POST['id_odontologo'];

    // --- VALIDACIÓN DE DISPONIBILIDAD ---
    if (!$citaModel->verificarDisponibilidad($id_odontologo, $inicio, $fin)) {
        // Si el modelo dice que NO hay espacio, redirigimos con error
        header("Location: ../views/citas/calendario.php?error=ocupado");
        exit;
    }
    // ------------------------------------

    $datos = [
        'id_paciente' => $_POST['id_paciente'],
        'id_odontologo' => $id_odontologo,
        'inicio' => $inicio,
        'fin' => $fin,
        'motivo' => $_POST['motivo'],
        'id_usuario' => $_SESSION['id_usuario']
    ];

    if ($citaModel->crear($datos)) {
        header("Location: ../views/citas/calendario.php?ok=1");
    } else {
        header("Location: ../views/citas/calendario.php?error=1");
    }
}
?>