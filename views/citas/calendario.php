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
        <div class="alert alert-success" style="padding: 10px; margin-bottom: 10px; background: #d4edda; color: #155724; border-radius: 5px;">
            <i class="fas fa-check-circle"></i> 
            <?php 
                if($_GET['ok'] == 'creado') echo 'Cita creada exitosamente.';
                elseif($_GET['ok'] == 'editado') echo 'Cita actualizada correctamente.';
                elseif($_GET['ok'] == 'cancelada') echo 'Cita cancelada.';
                elseif($_GET['ok'] == 'movida') echo 'Cita reagendada exitosamente.';
            ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger" style="padding: 10px; margin-bottom: 10px; background: #f8d7da; color: #721c24; border-radius: 5px;">
            <i class="fas fa-exclamation-triangle"></i> 
            <?php 
                if($_GET['error'] == 'ocupado') echo '<strong>Horario no disponible.</strong> El doctor ya tiene una cita en ese horario.';
                elseif($_GET['error'] == 'datos_incompletos') echo 'Por favor complete todos los campos obligatorios.';
                else echo 'Error al procesar la solicitud.';
            ?>
        </div>
    <?php endif; ?>

    <div class="page-header" style="flex-wrap: wrap; gap: 10px;">
        <h1 style="margin-right: auto;"><i class="far fa-calendar-alt"></i> Agenda de Citas</h1>
        
        <div style="display: flex; align-items: center; gap: 8px;">
            <label style="font-weight: bold; color: #555; font-size: 0.9rem;">Filtrar por Doctor:</label>
            <select id="filtroDoctor" class="form-control" style="width: 200px; padding: 6px;" onchange="filtrarCalendario()">
                <option value="">Todos los Doctores</option>
                <?php foreach($doctores as $d): ?>
                    <option value="<?php echo $d['id_odontologo']; ?>">Dr. <?php echo $d['nombres'] . ' ' . $d['apellidos']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="btn-primary" onclick="abrirModalCrear()">
            <i class="fas fa-plus"></i> Nueva Cita
        </button>
    </div>

    <div style="display: flex; gap: 15px; margin-bottom: 10px; font-size: 0.85rem; color: #666;">
        <div style="display: flex; align-items: center; gap: 4px;">
            <div style="width: 14px; height: 14px; background: #3498db; border-radius: 2px;"></div> Programada
        </div>
        <div style="display: flex; align-items: center; gap: 4px;">
            <div style="width: 14px; height: 14px; background: #2ecc71; border-radius: 2px;"></div> Atendida
        </div>
        <div style="display: flex; align-items: center; gap: 4px;">
            <div style="width: 14px; height: 14px; background: #e74c3c; border-radius: 2px;"></div> Cancelada
        </div>
    </div>

    <div id="calendar-container">
        <div id='calendar'></div>
    </div>
</main>

<!-- MODAL MEJORADO PARA CREAR/EDITAR CITA -->
<div id="modalCita" class="modal">
    <div class="modal-content" style="max-width: 650px;">
        <div class="modal-header">
            <h2 id="modalTitulo">Nueva Cita Médica</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form action="../../controllers/citaController.php" method="POST" id="formCita">
                <input type="hidden" name="id_cita" id="id_cita">

                <!-- PASO 1: INFORMACIÓN DEL PACIENTE -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 15px 0; color: #2c3e50; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user-injured"></i> Datos del Paciente
                    </h4>
                    
                    <div class="form-group">
                        <label>Paciente: <span style="color: red;">*</span></label>
                        <select name="id_paciente" id="id_paciente" class="form-control" required onchange="mostrarInfoPaciente()">
                            <option value="">-- Seleccione un paciente --</option>
                            <?php foreach($pacientes as $p): ?>
                                <option value="<?php echo $p['id_paciente']; ?>" 
                                        data-ci="<?php echo $p['ci']; ?>"
                                        data-telefono="<?php echo $p['telefono']; ?>"
                                        data-edad="<?php 
                                            if($p['fecha_nacimiento']){
                                                $nac = new DateTime($p['fecha_nacimiento']);
                                                $hoy = new DateTime();
                                                echo $hoy->diff($nac)->y;
                                            } else {
                                                echo 'N/A';
                                            }
                                        ?>">
                                    <?php echo $p['nombres'] . ' ' . $p['apellido_paterno'] . ' ' . $p['apellido_materno']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> Si el paciente no está en la lista, debe registrarlo primero en "Pacientes"
                        </small>
                    </div>

                    <!-- Información adicional del paciente (se muestra dinámicamente) -->
                    <div id="infoPaciente" style="display: none; margin-top: 15px; padding: 12px; background: white; border-left: 3px solid #3498db; border-radius: 4px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; font-size: 0.9rem;">
                            <div>
                                <strong style="color: #555;">CI:</strong><br>
                                <span id="pacienteCI" style="color: #2c3e50;">-</span>
                            </div>
                            <div>
                                <strong style="color: #555;">Edad:</strong><br>
                                <span id="pacienteEdad" style="color: #2c3e50;">-</span>
                            </div>
                            <div>
                                <strong style="color: #555;">Teléfono:</strong><br>
                                <span id="pacienteTelefono" style="color: #2c3e50;">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASO 2: ASIGNACIÓN DE PROFESIONAL -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 15px 0; color: #2c3e50; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user-md"></i> Asignación de Profesional
                    </h4>
                    
                    <div class="form-group">
                        <label>Odontólogo: <span style="color: red;">*</span></label>
                        <select name="id_odontologo" id="id_odontologo" class="form-control" required style="font-size: 1.05rem;">
                            <option value="">-- Seleccione un doctor --</option>
                            <?php foreach($doctores as $d): ?>
                                <option value="<?php echo $d['id_odontologo']; ?>">
                                    Dr(a). <?php echo $d['nombres'] . ' ' . $d['apellidos']; ?>
                                    <?php if($d['especialidad']): ?> 
                                        <span style="color: #7f8c8d;">- <?php echo $d['especialidad']; ?></span>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- PASO 3: FECHA Y HORA -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 15px 0; color: #2c3e50; display: flex; align-items: center; gap: 8px;">
                        <i class="far fa-clock"></i> Programación
                    </h4>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Fecha de la Cita: <span style="color: red;">*</span></label>
                            <input type="date" name="fecha" id="fecha" required class="form-control" 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   style="font-size: 1.05rem;">
                            <small style="color: #666; display: block; margin-top: 4px;">
                                <i class="fas fa-calendar-alt"></i> Solo fechas futuras
                            </small>
                        </div>
                        <div class="form-group">
                            <label>Hora de Inicio: <span style="color: red;">*</span></label>
                            <select name="hora" id="hora" required class="form-control" style="font-size: 1.05rem;">
                                <option value="">-- Seleccione --</option>
                                <?php 
                                // Generar horarios de 8:00 AM a 7:30 PM cada 30 minutos
                                for($h = 8; $h < 20; $h++) {
                                    foreach(['00', '30'] as $m) {
                                        $tiempo = sprintf("%02d:%s", $h, $m);
                                        $formato12 = date('g:i A', strtotime($tiempo));
                                        echo "<option value='$tiempo'>$formato12</option>";
                                    }
                                }
                                ?>
                            </select>
                            <small style="color: #666; display: block; margin-top: 4px;">
                                <i class="fas fa-info-circle"></i> Duración estimada: 30 min
                            </small>
                        </div>
                    </div>

                    <!-- Indicador visual de disponibilidad -->
                    <div id="disponibilidadIndicador" style="display: none; margin-top: 12px; padding: 10px; border-radius: 5px; font-size: 0.9rem;">
                        <!-- Se llenará dinámicamente con JavaScript -->
                    </div>
                </div>

                <!-- PASO 4: MOTIVO DE CONSULTA -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 15px 0; color: #2c3e50; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-notes-medical"></i> Motivo de Consulta
                    </h4>
                    
                    <div class="form-group">
                        <label>Descripción del Motivo: <span style="color: red;">*</span></label>
                        <textarea name="motivo" id="motivo" class="form-control" rows="4" 
                                  placeholder="Ej: Dolor en muela superior derecha, Limpieza dental periódica, Revisión de ortodoncia..."
                                  required style="resize: vertical; font-size: 0.95rem;"></textarea>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            <i class="fas fa-lightbulb"></i> Sea específico para que el doctor pueda prepararse mejor
                        </small>
                    </div>

                    <!-- Motivos comunes (botones rápidos) -->
                    <div style="margin-top: 10px;">
                        <small style="color: #666; margin-bottom: 5px; display: block;">Motivos frecuentes (clic para usar):</small>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <button type="button" class="btn-motivo-rapido" onclick="setMotivo('Dolor dental agudo')">
                                Dolor dental
                            </button>
                            <button type="button" class="btn-motivo-rapido" onclick="setMotivo('Limpieza dental')">
                                Limpieza
                            </button>
                            <button type="button" class="btn-motivo-rapido" onclick="setMotivo('Revisión general')">
                                Revisión
                            </button>
                            <button type="button" class="btn-motivo-rapido" onclick="setMotivo('Extracción de muela')">
                                Extracción
                            </button>
                            <button type="button" class="btn-motivo-rapido" onclick="setMotivo('Ortodoncia - Control')">
                                Ortodoncia
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Información de estado (solo para edición) -->
                <div id="infoEstado" style="display: none; background: #e8f4f8; padding: 12px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #3498db;">
                    <strong style="color: #2c3e50;">Estado actual:</strong> 
                    <span id="estadoTexto" style="font-weight: bold;"></span>
                </div>

                <!-- BOTONES DE ACCIÓN -->
                <div style="margin-top: 25px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; border-top: 2px solid #e9ecef; padding-top: 20px;">
                    
                    <button type="button" id="btnCancelar" onclick="confirmarCancelacion()" 
                            style="display:none; background: #e74c3c; color: white; border: none; padding: 10px 18px; border-radius: 5px; cursor: pointer; font-size: 0.95rem; transition: background 0.3s;">
                        <i class="fas fa-ban"></i> Cancelar Cita
                    </button>

                    <div style="display: flex; gap: 10px; margin-left: auto;">
                        <a id="btnHistorial" href="#" target="_blank" 
                           style="display: none; background: #34495e; color: white; padding: 10px 18px; border-radius: 5px; text-decoration: none; font-size: 0.95rem; transition: background 0.3s;">
                            <i class="fas fa-file-medical"></i> Ver Historial
                        </a>
                        <a id="btnAtender" href="#" 
                           style="display: none; background: #2ecc71; color: white; padding: 10px 18px; border-radius: 5px; text-decoration: none; font-size: 0.95rem; transition: background 0.3s;">
                            <i class="fas fa-user-md"></i> Atender Ahora
                        </a>
                        <button type="button" onclick="cerrarModal()" 
                                style="background: #95a5a6; color: white; border: none; padding: 10px 18px; border-radius: 5px; cursor: pointer; font-size: 0.95rem;">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-primary" id="btnGuardar" 
                                style="padding: 10px 25px; font-size: 0.95rem;">
                            <i class="fas fa-save"></i> Guardar Cita
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Estilos adicionales para el modal mejorado */
.btn-motivo-rapido {
    background: white;
    border: 1px solid #ddd;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    color: #555;
}

.btn-motivo-rapido:hover {
    background: #3498db;
    color: white;
    border-color: #3498db;
    transform: translateY(-1px);
}

#btnCancelar:hover {
    background: #c0392b;
}

#btnHistorial:hover {
    background: #2c3e50;
}

#btnAtender:hover {
    background: #27ae60;
}
</style>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script>

<script>
    var calendar;
    var doctorFiltrado = '';

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            height: '100%',
            contentHeight: 'auto', 
            aspectRatio: 1.8,
            
            slotMinTime: '07:00:00',
            slotMaxTime: '21:00:00',
            allDaySlot: false,
            slotDuration: '00:30:00',

            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            
            businessHours: {
                daysOfWeek: [1, 2, 3, 4, 5, 6],
                startTime: '08:00',
                endTime: '20:00'
            },

            editable: true,
            droppable: false,
            
            events: function(fetchInfo, successCallback, failureCallback) {
                fetch('../../controllers/citaController.php?accion=listar&id_odontologo=' + doctorFiltrado)
                    .then(response => response.json())
                    .then(data => successCallback(data))
                    .catch(error => {
                        console.error('Error al cargar citas:', error);
                        failureCallback(error);
                    });
            },
            
            dateClick: function(info) {
                abrirModalCrear(info.dateStr);
            },

            eventClick: function(info) {
                info.jsEvent.preventDefault();
                var id = info.event.id;
                
                fetch('../../controllers/citaController.php?accion=obtener&id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        if(data) {
                            abrirModalEditar(data);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al cargar los datos de la cita');
                    });
            },

            eventDrop: function(info) {
                if(!confirm('¿Confirmar reagendación de la cita?')) {
                    info.revert();
                    return;
                }

                var formData = new FormData();
                formData.append('accion', 'mover');
                formData.append('id_cita', info.event.id);
                formData.append('start', formatearFechaISO(info.event.start));
                formData.append('end', formatearFechaISO(info.event.end || info.event.start));

                fetch('../../controllers/citaController.php', { 
                    method: 'POST', 
                    body: formData 
                })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'error') {
                        alert('Error: ' + data.message);
                        info.revert();
                    } else {
                        window.location.href = 'calendario.php?ok=movida';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al mover la cita');
                    info.revert();
                });
            },

            eventResize: function(info) {
                info.revert();
                alert('No se puede cambiar la duración. Las citas tienen duración fija de 30 minutos.');
            }
        });
        
        calendar.render();
    });

    function filtrarCalendario() {
        doctorFiltrado = document.getElementById('filtroDoctor').value;
        calendar.refetchEvents();
    }

    var modal = document.getElementById("modalCita");
    var form = document.getElementById("formCita");

    // Función para mostrar información del paciente seleccionado
    function mostrarInfoPaciente() {
        var select = document.getElementById('id_paciente');
        var opcionSeleccionada = select.options[select.selectedIndex];
        
        if(select.value) {
            document.getElementById('infoPaciente').style.display = 'block';
            document.getElementById('pacienteCI').textContent = opcionSeleccionada.dataset.ci;
            document.getElementById('pacienteEdad').textContent = opcionSeleccionada.dataset.edad + ' años';
            document.getElementById('pacienteTelefono').textContent = opcionSeleccionada.dataset.telefono || 'No registrado';
        } else {
            document.getElementById('infoPaciente').style.display = 'none';
        }
    }

    // Función para establecer motivo rápido
    function setMotivo(texto) {
        document.getElementById('motivo').value = texto;
    }

    function abrirModalCrear(fechaPreseleccionada) {
        form.reset();
        document.getElementById('id_cita').value = "";
        document.getElementById('modalTitulo').innerText = "Nueva Cita Médica";
        
        document.getElementById('btnCancelar').style.display = "none";
        document.getElementById('btnAtender').style.display = "none";
        document.getElementById('btnHistorial').style.display = "none";
        document.getElementById('infoEstado').style.display = "none";
        document.getElementById('infoPaciente').style.display = "none";
        document.getElementById('btnGuardar').style.display = "inline-block";
        
        if(fechaPreseleccionada) {
            if(fechaPreseleccionada.indexOf('T') === -1) {
                document.getElementById('fecha').value = fechaPreseleccionada;
                document.getElementById('hora').value = "09:00";
            } else {
                let partes = fechaPreseleccionada.split('T');
                document.getElementById('fecha').value = partes[0];
                document.getElementById('hora').value = partes[1].substring(0, 5);
            }
        } else {
            document.getElementById('fecha').value = '';
            document.getElementById('hora').value = '09:00';
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

        document.getElementById('modalTitulo').innerText = "Modificar Cita";
        
        // Mostrar info del paciente
        mostrarInfoPaciente();
        
        // Mostrar estado
        document.getElementById('infoEstado').style.display = 'block';
        document.getElementById('estadoTexto').innerText = data.estado;
        document.getElementById('estadoTexto').style.color = 
            data.estado == 'PROGRAMADA' ? '#3498db' :
            data.estado == 'ATENDIDA' ? '#2ecc71' :
            data.estado == 'CANCELADA' ? '#e74c3c' : '#666';

        // Mostrar botones según estado
        if(data.estado == 'PROGRAMADA') {
            document.getElementById('btnCancelar').style.display = "inline-block";
            document.getElementById('btnAtender').style.display = "inline-block";
            document.getElementById('btnAtender').href = "atender.php?id_cita=" + data.id_cita;
            document.getElementById('btnGuardar').style.display = "inline-block";
        } else if(data.estado == 'ATENDIDA') {
            document.getElementById('btnCancelar').style.display = "none";
            document.getElementById('btnAtender').style.display = "none";
            document.getElementById('btnGuardar').style.display = "none";
        } else if(data.estado == 'CANCELADA') {
            document.getElementById('btnCancelar').style.display = "none";
            document.getElementById('btnAtender').style.display = "none";
            document.getElementById('btnGuardar').style.display = "none";
        }
        
        document.getElementById('btnHistorial').style.display = "inline-block";
        document.getElementById('btnHistorial').href = "../pacientes/historia.php?buscar_ci=" + data.ci;

        modal.style.display = "block";
    }

    function confirmarCancelacion() {
        if(!confirm('¿Está seguro de cancelar esta cita?\n\nEsta acción quedará registrada en el sistema.')) {
            return;
        }
        
        var id = document.getElementById('id_cita').value;
        var formData = new FormData();
        formData.append('accion', 'cancelar');
        formData.append('id_cita', id);

        fetch('../../controllers/citaController.php', { 
            method: 'POST', 
            body: formData 
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                window.location.href = 'calendario.php?ok=cancelada';
            } else {
                alert('Error al cancelar la cita.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la cancelación');
        });
    }

    function cerrarModal() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            cerrarModal();
        }
    }

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