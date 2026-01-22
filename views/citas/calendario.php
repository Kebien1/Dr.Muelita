<?php
$page_title = "Agenda de Citas";
$page_css = "citas.css"; 

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../models/Paciente.php';
require_once '../../models/Odontologo.php';

// Cargar listas
$pacienteModel = new Paciente();
$pacientes = $pacienteModel->listarTodos();
$odoModel = new Odontologo();
$doctores = $odoModel->listarTodos();
?>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />

<main class="main-content">
    
    <?php if(isset($_GET['ok'])): ?>
        <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
            <i class="fas fa-check-circle"></i> Cita agendada correctamente.
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['error']) && $_GET['error'] == 'ocupado'): ?>
        <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #f5c6cb;">
            <i class="fas fa-exclamation-triangle"></i> <strong>¡Conflicto de Horario!</strong> El odontólogo ya tiene una cita en ese horario. Por favor elige otro.
        </div>
    <?php endif; ?>
    <div class="page-header">
        <h1><i class="far fa-calendar-alt"></i> Agenda de Citas</h1>
        <button class="btn-primary" onclick="mostrarModal()">
            <i class="fas fa-plus"></i> Nueva Cita
        </button>
    </div>

    <div id="calendar-container">
        <div id='calendar'></div>
    </div>
</main>

<div id="modalCita" class="modal">
    <div class="modal-content">
        
        <div class="modal-header">
            <h2>Programar Cita</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        
        <div class="modal-body">
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
                                Dr. <?php echo $d['nombres']; ?> (<?php echo $d['especialidad']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
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
                    <input type="text" name="motivo" placeholder="Ej: Dolor, Limpieza..." required class="form-control">
                </div>

                <div style="margin-top: 20px; text-align: right;">
                    <button type="submit" class="btn-primary" style="width: 100%;">
                        Guardar Cita
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: '../../controllers/citaController.php?accion=listar', 
            
            dateClick: function(info) {
                document.getElementById('fecha_input').value = info.dateStr;
                mostrarModal();
            },

            eventClick: function(info) {
                var nombreCita = info.event.title;
                var idCita = info.event.id;
                
                if(confirm("GESTIONAR CITA:\n" + nombreCita + "\n\n¿Deseas ir a la pantalla de ATENCIÓN MÉDICA?")) {
                    window.location.href = 'atender.php?id_cita=' + idCita;
                }
            }
        });
        calendar.render();
    });

    var modal = document.getElementById("modalCita");
    function mostrarModal() { modal.style.display = "block"; }
    function cerrarModal() { modal.style.display = "none"; }
    window.onclick = function(event) {
        if (event.target == modal) { cerrarModal(); }
    }
</script>

<?php require_once '../../includes/footer.php'; ?>