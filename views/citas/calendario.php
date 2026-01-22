<?php
$page_title = "Agenda de Citas";
$page_css = "citas.css"; 

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../models/Paciente.php';
require_once '../../models/Odontologo.php';

// Cargar listas para el formulario del modal
$pacienteModel = new Paciente();
$pacientes = $pacienteModel->listarTodos();

$odoModel = new Odontologo();
$doctores = $odoModel->listarTodos();
?>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />

<main class="main-content">
    
    <?php if(isset($_GET['ok'])): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px;">Cita agendada correctamente.</div>
    <?php endif; ?>
    <?php if(isset($_GET['mensaje']) && $_GET['mensaje'] == 'AtencionRegistrada'): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px;">¡Atención médica registrada con éxito!</div>
    <?php endif; ?>

    <div class="page-header">
        <h1>Agenda de Citas</h1>
        <button class="btn-primary" onclick="mostrarModal()">+ Nueva Cita</button>
    </div>

    <div id="calendar-container" style="background: white; padding: 20px; border-radius: 8px;">
        <div id='calendar'></div>
    </div>
</main>

<div id="modalCita" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModal()">&times;</span>
        <h2>Programar Cita</h2>
        
        <form action="../../controllers/citaController.php" method="POST">
            <div class="form-group">
                <label>Paciente:</label>
                <select name="id_paciente" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach($pacientes as $p): ?>
                        <option value="<?php echo $p['id_paciente']; ?>">
                            <?php echo $p['nombres'] . ' ' . $p['apellido_paterno']; ?> (CI: <?php echo $p['ci']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Odontólogo:</label>
                <select name="id_odontologo" class="form-control" required>
                    <?php foreach($doctores as $d): ?>
                        <option value="<?php echo $d['id_odontologo']; ?>">
                            Dr. <?php echo $d['nombres'] . ' ' . $d['apellidos']; ?> (<?php echo $d['especialidad']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Fecha:</label>
                    <input type="date" name="fecha" id="fecha_input" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Hora Inicio:</label>
                    <input type="time" name="hora" required class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label>Motivo / Tratamiento:</label>
                <input type="text" name="motivo" placeholder="Ej: Dolor de muela, Limpieza..." required class="form-control">
            </div>

            <div style="margin-top: 15px; text-align: right;">
                <button type="submit" class="btn-primary">Guardar Cita</button>
            </div>
        </form>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth', // Vista mensual
            locale: 'es', // Idioma Español
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            // Carga los eventos desde la base de datos
            events: '../../controllers/citaController.php?accion=listar', 
            
            // 1. CLIC EN DÍA VACÍO -> Abrir modal para NUEVA cita
            dateClick: function(info) {
                document.getElementById('fecha_input').value = info.dateStr;
                mostrarModal();
            },

            // 2. CLIC EN CITA EXISTENTE -> Ir a ATENDER cita
            eventClick: function(info) {
                // info.event es el objeto del evento clicado
                var nombreCita = info.event.title;
                var idCita = info.event.id;

                // Preguntamos si quiere ir a la pantalla de consulta
                if(confirm("¿Deseas realizar la atención médica para: " + nombreCita + "?")) {
                    // Redireccionamos enviando el ID de la cita
                    window.location.href = 'atender.php?id_cita=' + idCita;
                }
            }
        });
        calendar.render();
    });

    // Funciones para abrir/cerrar el Modal (Ventana emergente)
    var modal = document.getElementById("modalCita");
    
    function mostrarModal() { 
        modal.style.display = "block"; 
    }
    
    function cerrarModal() { 
        modal.style.display = "none"; 
    }
    
    // Cerrar si se hace clic fuera de la cajita blanca
    window.onclick = function(event) {
        if (event.target == modal) { 
            cerrarModal(); 
        }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>