<?php
// get_notes.php

header('Content-Type: application/json'); // Indicar que la respuesta es JSON

// Definir la ruta al archivo JSON de datos
$data_file = 'data.json';

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
    return is_array($notes) ? $notes : [];
}

$notes = read_notes();

$events = [];
foreach ($notes as $note) {
    // Asegurarse de que 'fecha' y 'hora' existen antes de usarlas
    $fecha = $note['fecha'] ?? '';
    $hora = $note['hora'] ?? '';

    // Si la fecha o la hora no están definidas (aunque no deberían con el nuevo index.php),
    // no creamos el evento para evitar errores.
    if (!empty($fecha) && !empty($hora)) {
        $events[] = [
            'id' => $note['id'],
            'title' => ($note['titulo'] ?? 'Sin título') . ' (' . $hora . ')',
            'start' => $fecha . 'T' . $hora,
            'description' => $note['contenido'] ?? '' // Puedes añadir más datos aquí si los necesitas en el frontend
        ];
    }
}

echo json_encode($events);

?>