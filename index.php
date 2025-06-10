<?php

// Definir la ruta al archivo JSON de datos
$data_file = 'data.json';

// --- Funciones para manejar el archivo JSON ---

/**
 * Lee las notas desde el archivo JSON.
 * @return array Un array de notas. Si el archivo no existe o está vacío/inválido, devuelve un array vacío.
 */
function read_notes() {
    global $data_file;
    if (!file_exists($data_file) || filesize($data_file) == 0) {
        return [];
    }
    $json_data = file_get_contents($data_file);
    $notes = json_decode($json_data, true);
    return is_array($notes) ? $notes : []; // Asegurarse de que siempre devuelva un array
}

/**
 * Escribe las notas en el archivo JSON.
 * @param array $notes El array de notas a guardar.
 */
function write_notes($notes) {
    global $data_file;
    file_put_contents($data_file, json_encode($notes, JSON_PRETTY_PRINT));
}

// --- Variables y datos predefinidos ---
$dias_semana = [
    'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'
];

// Mensajes para el usuario
$message = '';
$error_message = '';

// Cargar todas las notas al inicio
$notes = read_notes();

// Lógica para agregar o modificar una nota
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que la clave 'action' existe antes de usarla
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add_note' || $action === 'edit_note') {
            // Usar el operador de coalescencia nula (?? '') para evitar "Undefined array key"
            // Si la clave no existe, se le asigna un string vacío.
            $titulo = trim($_POST['titulo'] ?? '');
            $contenido = trim($_POST['contenido'] ?? '');
            $dia_semana_nombre = $_POST['dia_semana'] ?? ''; // Corregido aquí
            $fecha = $_POST['fecha'] ?? '';                 // Corregido aquí
            $hora = $_POST['hora'] ?? '';
            $id = $_POST['id'] ?? null; // Para edición, el ID puede estar presente

            if (empty($titulo) || empty($contenido) || empty($dia_semana_nombre) || empty($fecha) || empty($hora)) {
                $error_message = 'Todos los campos son obligatorios.';
            } else {
                // Validación básica de fecha
                if (!DateTime::createFromFormat('Y-m-d', $fecha)) {
                    $error_message = 'Formato de fecha inválido. Use AAAA-MM-DD.';
                } else {
                    if ($action === 'add_note') {
                        // Generar un ID único para la nueva nota
                        $id = uniqid();
                        $new_note = [
                            'id' => $id,
                            'titulo' => $titulo,
                            'contenido' => $contenido,
                            'dia_semana' => $dia_semana_nombre, // Guardamos el nombre del día
                            'fecha' => $fecha, // Guardamos la fecha completa
                            'hora' => $hora,
                            'timestamp' => date('Y-m-d H:i:s')
                        ];
                        $notes[] = $new_note;
                        $message = 'Nota agregada exitosamente.';
                    } elseif ($action === 'edit_note' && $id !== null) { // Asegurarse de que el ID está presente
                        $note_id_to_edit = $id;
                        foreach ($notes as &$note) { // Usar & para modificar el array original
                            if ($note['id'] === $note_id_to_edit) {
                                $note['titulo'] = $titulo;
                                $note['contenido'] = $contenido;
                                $note['dia_semana'] = $dia_semana_nombre;
                                $note['fecha'] = $fecha;
                                $note['hora'] = $hora;
                                $note['timestamp'] = date('Y-m-d H:i:s'); // Actualizar timestamp
                                $message = 'Nota modificada exitosamente.';
                                break;
                            }
                        }
                        unset($note); // Romper la referencia
                    }
                    write_notes($notes); // Guardar los cambios
                }
            }
        } elseif ($action === 'delete_note' && isset($_POST['id'])) {
            $note_id_to_delete = $_POST['id'];
            $initial_count = count($notes);
            $notes = array_filter($notes, function($note) use ($note_id_to_delete) {
                return ($note['id'] ?? null) !== $note_id_to_delete; // También seguro al filtrar
            });
            $notes = array_values($notes); // Reindexar el array
            if (count($notes) < $initial_count) {
                write_notes($notes); // Guardar los cambios
                $message = 'Nota eliminada exitosamente.';
            } else {
                $error_message = 'No se encontró la nota para eliminar.';
            }
        }
    }
}

// Lógica para pre-cargar datos en el formulario de edición
$edit_note = null;
// Verificar que las claves 'action' e 'id' existen en $_GET
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $note_id_to_edit = $_GET['id'];
    foreach ($notes as $note) {
        if (($note['id'] ?? null) === $note_id_to_edit) { // Acceso seguro al ID de la nota
            $edit_note = $note;
            break;
        }
    }
    if (!$edit_note) {
        $error_message = 'Nota no encontrada para edición.';
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Notas con Calendario</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />
</head>
<body>
    <div class="container">
        <h1>Gestión de Notas con Calendario</h1>

        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <h2><?php echo $edit_note ? 'Editar Nota' : 'Agregar Nueva Nota'; ?></h2>
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="<?php echo $edit_note ? 'edit_note' : 'add_note'; ?>">
            <?php if ($edit_note): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_note['id']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="titulo">Título:</label>
                <input type="text" id="titulo" name="titulo" value="<?php echo $edit_note ? htmlspecialchars($edit_note['titulo']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="contenido">Contenido:</label>
                <textarea id="contenido" name="contenido" required><?php echo $edit_note ? htmlspecialchars($edit_note['contenido']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="dia_semana">Día de la Semana:</label>
                <select id="dia_semana" name="dia_semana" required>
                    <option value="">Seleccione un día</option>
                    <?php foreach ($dias_semana as $dia_opcion): ?>
                        <option value="<?php echo htmlspecialchars($dia_opcion); ?>"
                            <?php echo ($edit_note && ($edit_note['dia_semana'] ?? '') === $dia_opcion) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dia_opcion); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="fecha">Fecha (AAAA-MM-DD):</label>
                <input type="date" id="fecha" name="fecha" value="<?php echo $edit_note ? htmlspecialchars($edit_note['fecha'] ?? '') : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="hora">Hora:</label>
                <input type="time" id="hora" name="hora" value="<?php echo $edit_note ? htmlspecialchars($edit_note['hora'] ?? '') : ''; ?>" required>
            </div>

            <button type="submit"><?php echo $edit_note ? 'Guardar Cambios' : 'Agregar Nota'; ?></button>
        </form>
    </div>

    <div id="calendar-container" class="container">
        <h2>Calendario de Notas</h2>
        <div id="calendar"></div>
    </div>

    <div class="container note-list-container">
        <h2>Listado de Notas</h2>
        <div class="note-list">
            <?php if (empty($notes)): ?>
                <p class="no-notes">No hay notas agregadas aún.</p>
            <?php else: ?>
                <?php
                // Opcional: Ordenar las notas por fecha y luego por hora
                usort($notes, function($a, $b) {
                    $datetime_a = strtotime(($a['fecha'] ?? '') . ' ' . ($a['hora'] ?? ''));
                    $datetime_b = strtotime(($b['fecha'] ?? '') . ' ' . ($b['hora'] ?? ''));
                    // Manejar casos donde la fecha/hora podría ser inválida
                    if ($datetime_a === false) return 1;
                    if ($datetime_b === false) return -1;
                    return $datetime_a - $datetime_b;
                });
                ?>
                <?php foreach ($notes as $note): ?>
                    <div class="note-card">
                        <h3><?php echo htmlspecialchars($note['titulo'] ?? 'Sin título'); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($note['contenido'] ?? '')); ?></p>
                        <div class="details">
                            <p><strong>Día:</strong> <?php echo htmlspecialchars($note['dia_semana'] ?? ''); ?></p>
                            <p><strong>Fecha:</strong> <?php echo htmlspecialchars($note['fecha'] ?? ''); ?></p>
                            <p><strong>Hora:</strong> <?php echo htmlspecialchars($note['hora'] ?? ''); ?></p>
                            <p><em>Última actualización: <?php echo htmlspecialchars($note['timestamp'] ?? ''); ?></em></p>
                        </div>
                        <div class="actions">
                            <a href="index.php?action=edit&id=<?php echo htmlspecialchars($note['id'] ?? ''); ?>">Editar</a>
                            <form action="index.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete_note">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($note['id'] ?? ''); ?>">
                                <button type="submit" onclick="return confirm('¿Está seguro de que desea eliminar esta nota?');">Eliminar</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar;

            function initializeCalendar() {
                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth', // Vista inicial: mes
                    locale: 'es', // Idioma español
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay' // Vistas disponibles
                    },
                    events: 'get_notes.php', // URL desde donde FullCalendar obtendrá los eventos (nuestras notas)
                    eventClick: function(info) {
                        // Al hacer clic en un evento, podemos redirigir a la edición
                        // o mostrar un modal con los detalles.
                        // Para este ejemplo, redirigimos a la página de edición.
                        window.location.href = 'index.php?action=edit&id=' + info.event.id;
                    },
                    eventDidMount: function(info) {
                        // Opcional: Mostrar el contenido de la nota en el tooltip
                        // Puedes usar una librería de tooltips como Tippy.js o crear la tuya.
                        // Por simplicidad, aquí solo cambiaremos el título del evento.
                        if (info.event.extendedProps.description) {
                            info.el.setAttribute('title', info.event.extendedProps.description);
                        }
                    }
                });
                calendar.render();
            }

            initializeCalendar();

            // Función para recargar los eventos del calendario
            // Llama a esta función después de agregar, modificar o eliminar una nota
            window.refreshCalendarEvents = function() {
                if (calendar) {
                    calendar.refetchEvents();
                }
            };

            // Detectar si hubo una acción POST (agregar/modificar/eliminar)
            // Y si es así, recargar los eventos del calendario.
            // Esto es crucial para que los cambios se vean sin recargar toda la página.
            // Aunque estamos haciendo un POST completo que recarga la página,
            // si usaras AJAX para las operaciones, esta función sería clave.
            // Como PHP recarga la página, el calendario se reconstruye con los nuevos datos.
        });
    </script>
</body>
</html>