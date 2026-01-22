<?php
$page_title = "Agenda de Citas";
$page_css = "citas.css"; 

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once '../../models/Paciente.php';
require_once '../../models/Odontologo.php';

$pacienteModel = new Paciente();
$pacientes = $pacienteModel->listarTodos();
$odoModel = new Odontologo();
$doctores = $odoModel->listarTodos();
?>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />

<main class="main-content">
    
    <?php if(isset($_GET['ok'])): ?>
        <div class="alert alert-success" style="padding: 10px; margin-bottom: 10px; font-size: 0.9rem;">
            <i class="fas fa-check-circle"></i> Acción realizada con éxito.
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['error']) && $_GET['error'] == 'ocupado'): ?>
        <div class="alert alert-danger" style="padding: 10px; margin-bottom: 10px; font-size: 0.9rem;">
            <i class="fas fa-exclamation-triangle"></i> <strong>Horario ocupado.</strong>
        </div>
    <?php endif; ?>

    <div class="page-header" style="flex-wrap: wrap; gap: 10px;">
        <h1 style="margin-right: auto;"><i class="far fa-calendar-alt"></i> Agenda</h1>
        
        <div style="display: flex; align-items: center; gap: 8px;">
            <label style="font-weight: bold; color: #555; font-size: 0.9rem;">Ver:</label>
            <select id="filtroDoctor" class="form-control" style="width: 180px; padding: 6px;" onchange="filtrarCalendario()">
                <option value="">Todos los Doctores</option>
                <?php foreach($doctores as $d): ?>
                    <option value="<?php echo $d['id_odontologo']; ?>">Dr. <?php echo $d['nombres']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="btn-primary" onclick="abrirModalCrear()">
            <i class="fas fa-plus"></i> Cita
        </button>
    </div>

    <div style="display: flex; gap: 15px; margin-bottom: 10px; font-size: 0.8rem; color: #666;">
        <div style="display: flex; align-items: center; gap: 4px;">
            <div style="width: 12px; height: 12px; background: #3498db; border-radius: 2px;"></div> Programada
        </div>
        <div style="display: flex; align-items: center; gap: 4px;">
            <div style="width: 12px; height: 12px; background: #2ecc71; border-radius: 2px;"></div> Atendida
        </div>
    </div>

    <div id="calendar-container">
        <div id='calendar'></div>
    </div>
</main>

<div id="modalCita" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitulo">Programar</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form action="../../controllers/citaController.php" method="POST" id="formCita">
                <input type="hidden" name="id_cita" id="id_cita">

                <div class="form-group">
                    <label>Paciente:</label>
                    <select name="id_paciente" id="id_paciente" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach($pacientes as $p): ?>
                            <option value="<?php echo $p['id_paciente']; ?>"><?php echo $p['nombres'] . ' ' . $p['apellido_paterno']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Odontólogo:</label>
                    <select name="id_odontologo" id="id_odontologo" class="form-control" required>
                        <?php foreach($doctores as $d): ?>
                            <option value="<?php echo $d['id_odontologo']; ?>">Dr. <?php echo $d['nombres']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label>Fecha:</label>
                        <input type="date" name="fecha" id="fecha" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Hora:</label>
                        <input type="time" name="hora" id="hora" required class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label>Motivo:</label>
                    <input type="text" name="motivo" id="motivo" placeholder="Ej: Dolor..." required class="form-control">
                </div>

                <div style="margin-top: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; border-top: 1px solid #eee; padding-top: 15px;">
                    <button type="button" id="btnCancelar" onclick="cancelarCita()" style="display:none; background: #e74c3c; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">
                        <i class="fas fa-trash-alt"></i>
                    </button>

                    <div style="display: flex; gap: 5px; margin-left: auto;">
                        <a id="btnHistorial" href="#" target="_blank" style="display: none; background: #34495e; color: white; padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 0.85rem;">
                            <i class="fas fa-file-medical"></i>
                        </a>
                        <a id="btnAtender" href="#" style="display: none; background: #2ecc71; color: white; padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 0.85rem;">
                            <i class="fas fa-user-md"></i> Atender
                        </a>
                        <button type="submit" class="btn-primary" style="padding: 8px 15px;">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script>

<script>
    var calendar; 

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            height: '100%', // Se adapta al contenedor CSS que limitamos
            contentHeight: 'auto', 
            aspectRatio: 1.8, // Hace el calendario más "achatado" (menos alto)
            
            // LÍMITES DE HORARIO (Para vistas Semana/Día)
            slotMinTime: '07:00:00', // Empieza a las 7 AM
            slotMaxTime: '21:00:00', // Termina a las 9 PM
            allDaySlot: false, // Quita la fila "Todo el día" para ahorrar espacio

            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            editable: true, 
            events: {
                url: '../../controllers/citaController.php?accion=listar',
                extraParams: function() {
                    return { id_odontologo: document.getElementById('filtroDoctor').value };
                }
            },
            
            dateClick: function(info) { abrirModalCrear(info.dateStr); },

            eventClick: function(info) {
                var id = info.event.id;
                fetch('../../controllers/citaController.php?accion=obtener&id=' + id)
                    .then(response => response.json())
                    .then(data => { abrirModalEditar(data); });
            },

            eventDrop: function(info) {
                var formData = new FormData();
                formData.append('accion', 'mover');
                formData.append('id_cita', info.event.id);
                formData.append('start', formatearFechaISO(info.event.start));
                formData.append('end', formatearFechaISO(info.event.end));

                fetch('../../controllers/citaController.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'error') {
                        alert("¡Error! " + data.message);
                        info.revert();
                    }
                });
            }
        });
        calendar.render();
    });

    function filtrarCalendario() { calendar.refetchEvents(); }

    var modal = document.getElementById("modalCita");
    var form = document.getElementById("formCita");
    var btnCancelar = document.getElementById('btnCancelar');
    var btnAtender = document.getElementById('btnAtender');
    var btnHistorial = document.getElementById('btnHistorial');

    function abrirModalCrear(fechaPreseleccionada) {
        form.reset(); 
        document.getElementById('id_cita').value = ""; 
        document.getElementById('modalTitulo').innerText = "Programar";
        btnAtender.style.display = "none"; 
        btnHistorial.style.display = "none";
        btnCancelar.style.display = "none";
        
        if(fechaPreseleccionada) {
            // Si clicaste en la vista Mes (solo fecha), pon la fecha y una hora por defecto
            if(fechaPreseleccionada.indexOf('T') === -1) {
                document.getElementById('fecha').value = fechaPreseleccionada;
                document.getElementById('hora').value = "09:00"; 
            } else {
                // Si clicaste en Semana/Día (fecha y hora), separa ambas
                let partes = fechaPreseleccionada.split('T');
                document.getElementById('fecha').value = partes[0];
                document.getElementById('hora').value = partes[1].substring(0, 5);
            }
        }
        modal.style.display = "block";
    }

    function abrirModalEditar(data) {
        document.getElementById('id_cita').value = data.id_cita;
        document.getElementById('id_paciente').value = data.id_paciente;
        document.getElementById('id_odontologo').value = data.id_odontologo;
        document.getElementById('motivo').value = data.motivo;
        
        var partes = data.fecha_hora_inicio.split(' ');
        document.getElementById('fecha').value = partes[0];
        document.getElementById('hora').value = partes[1].substring(0, 5);

        document.getElementById('modalTitulo').innerText = "Editar";
        btnAtender.style.display = "inline-block";
        btnAtender.href = "atender.php?id_cita=" + data.id_cita;
        btnHistorial.style.display = "inline-block";
        btnHistorial.href = "../pacientes/historia.php?buscar_ci=" + data.ci;
        btnCancelar.style.display = "inline-block"; 

        modal.style.display = "block";
    }

    function cancelarCita() {
        if(!confirm("¿Cancelar cita?")) return;
        var id = document.getElementById('id_cita').value;
        var formData = new FormData();
        formData.append('accion', 'cancelar');
        formData.append('id_cita', id);

        fetch('../../controllers/citaController.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                cerrarModal();
                calendar.refetchEvents();
            } else {
                alert("Error.");
            }
        });
    }

    function cerrarModal() { modal.style.display = "none"; }
    window.onclick = function(event) { if (event.target == modal) { cerrarModal(); } }

    function formatearFechaISO(date) {
        var year = date.getFullYear();
        var month = ('0' + (date.getMonth() + 1)).slice(-2);
        var day = ('0' + date.getDate()).slice(-2);
        var hours = ('0' + date.getHours()).slice(-2);
        var minutes = ('0' + date.getMinutes()).slice(-2);
        var seconds = ('0' + date.getSeconds()).slice(-2);
        return year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds;
    }
</script>

<?php require_once '../../includes/footer.php'; ?>