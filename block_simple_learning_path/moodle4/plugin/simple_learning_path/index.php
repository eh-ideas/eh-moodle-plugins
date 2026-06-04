<?php
/**
 * Admin index: list of learning paths.
 *
 * @package    block_simple_learning_path
 * @copyright  2026 e-trainingsupport.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

global $DB, $OUTPUT, $PAGE;

$context = context_system::instance();
require_capability('moodle/site:manageblocks', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/simple_learning_path/index.php'));
$PAGE->set_title(get_string('pluginmenu', 'block_simple_learning_path'));
$PAGE->set_heading(get_string('pluginmenu', 'block_simple_learning_path'));

// ── Acción AJAX: reordenar rutas ──────────────────────────────────────────────
// POST con action=reorder&order[]=id1&order[]=id2&order[]=id3
if (optional_param('action', '', PARAM_ALPHA) === 'reorder' && confirm_sesskey()) {
    $order = required_param_array('order', PARAM_INT);
    foreach ($order as $sortorder => $pathid) {
        $DB->set_field('block_simple_learning_path', 'sortorder', $sortorder, ['id' => $pathid]);
    }
    $response = ['success' => true];
    header('Content-Type: application/json');
    echo json_encode($response);
    die();
}

// ── Obtener rutas ordenadas ───────────────────────────────────────────────────
$learningpaths = $DB->get_records('block_simple_learning_path', null, 'sortorder ASC, id ASC');

// ── Mapa de etiquetas de criterio ─────────────────────────────────────────────
function get_criterio_label(stdClass $lp, moodle_database $DB): array {
    switch ($lp->criterio ?? '') {
        case 'siempre':
            return ['label' => 'Siempre visible', 'icon' => 'fa-globe',       'color' => 'success'];
        case 'cursos':
            return ['label' => 'Todos los cursos', 'icon' => 'fa-check-square-o', 'color' => 'primary'];
        case 'curso':
            return ['label' => 'Algún curso',     'icon' => 'fa-square-o',    'color' => 'primary'];
        case 'cohorte':
            $cohort = $DB->get_record('cohort', ['id' => $lp->cohortid ?? 0], 'name', IGNORE_MISSING);
            $name   = $cohort ? $cohort->name : 'Cohorte no encontrada';
            return ['label' => 'Cohorte: ' . $name, 'icon' => 'fa-users', 'color' => 'warning'];
        case 'fecha':
            $desde = !empty($lp->fecha_inicio) ? date('d/m/Y', $lp->fecha_inicio) : '–';
            $hasta = !empty($lp->fecha_fin)    ? date('d/m/Y', $lp->fecha_fin)    : '–';
            return ['label' => "Fecha: {$desde} → {$hasta}", 'icon' => 'fa-calendar', 'color' => 'info'];
        case 'rol':
            return ['label' => 'Rol: ' . ($lp->criterio_rol ?? '–'), 'icon' => 'fa-id-badge', 'color' => 'secondary'];
        case 'prerequisito':
            $prereq = !empty($lp->prerequisito_rutaid)
                ? $DB->get_field('block_simple_learning_path', 'nombre_ruta', ['id' => $lp->prerequisito_rutaid])
                : null;
            return ['label' => 'Prereq: ' . ($prereq ?: '–'), 'icon' => 'fa-lock', 'color' => 'danger'];
        default:
            return ['label' => 'Sin criterio', 'icon' => 'fa-question-circle', 'color' => 'secondary'];
    }
}

// ── Preparar datos para la plantilla ─────────────────────────────────────────
$template_data = [
    'createurl'      => (new moodle_url('/blocks/simple_learning_path/edit.php'))->out(false),
    'reporturl'      => (new moodle_url('/blocks/simple_learning_path/report.php'))->out(false),
    'reorder_url'    => (new moodle_url('/blocks/simple_learning_path/index.php'))->out(false),
    'sesskey'        => sesskey(),
    'has_paths'      => !empty($learningpaths),
    'learning_paths' => [],
];

foreach ($learningpaths as $lp) {

    $criterio_info = get_criterio_label($lp, $DB);

    // Cursos asociados (ordenados por sortorder).
    $courses = $DB->get_records_sql('
        SELECT c.id, c.fullname
          FROM {block_simple_learning_path_courses} lpc
          JOIN {course} c ON lpc.courseid = c.id
         WHERE lpc.learningpathid = :pid
      ORDER BY lpc.sortorder ASC, lpc.id ASC',
        ['pid' => $lp->id]
    );

    $cursos = [];
    foreach ($courses as $course) {
        $cursos[] = ['fullname' => $course->fullname];
    }

    $estado      = (int) ($lp->estado ?? 1);
    $secuencial  = (int) ($lp->secuencial ?? 0);

    $template_data['learning_paths'][] = [
        'id'              => $lp->id,
        'nombre_ruta'     => $lp->nombre_ruta,
        'descripcion'     => $lp->descripcion ?? '',
        'hasDesc'         => !empty($lp->descripcion),
        'url'             => $lp->url ?? '#',
        'hasUrl'          => ($lp->url ?? '#') !== '#' && !empty($lp->url),
        'estado'          => $estado,
        'estado_label'    => $estado ? 'Publicada' : 'Borrador',
        'estado_done'     => (bool) $estado,
        'secuencial'      => (bool) $secuencial,
        'criterio_label'  => $criterio_info['label'],
        'criterio_icon'   => $criterio_info['icon'],
        'criterio_color'  => $criterio_info['color'],
        'editurl'         => (new moodle_url('/blocks/simple_learning_path/edit.php', ['id' => $lp->id]))->out(false),
        'deleteurl'       => (new moodle_url('/blocks/simple_learning_path/delete.php', [
            'id'      => $lp->id,
            'sesskey' => sesskey(),
        ]))->out(false),
        'cursos'          => $cursos,
        'cursos_count'    => count($cursos),
        'sortorder'       => $lp->sortorder ?? 0,
    ];
}

// ── Renderizar ────────────────────────────────────────────────────────────────
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('block_simple_learning_path/simple_learning_path', $template_data);
echo $OUTPUT->footer();
