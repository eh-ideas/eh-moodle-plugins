<?php
/**
 * Create / edit a learning path.
 *
 * @package    block_simple_learning_path
 * @copyright  2026 e-trainingsupport.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

global $DB, $OUTPUT, $PAGE;

$id      = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();
require_capability('moodle/site:manageblocks', $context);

$PAGE->set_url(new moodle_url('/blocks/simple_learning_path/edit.php', ['id' => $id]));
$PAGE->set_context($context);
$PAGE->set_title($id ? get_string('edit_path_title', 'block_simple_learning_path')
                     : get_string('create_path_title', 'block_simple_learning_path'));
$PAGE->set_heading($PAGE->title);

$uniqid = uniqid('slp-edit-');

// ── Valores por defecto ───────────────────────────────────────────────────────

$template_data = [
    'id'               => $id,
    'is_editing'       => $id > 0,
    'action_url'       => (new moodle_url('/blocks/simple_learning_path/edit.php', ['id' => $id]))->out(false),
    'cancel_url'       => (new moodle_url('/blocks/simple_learning_path/index.php'))->out(false),
    'sesskey'          => sesskey(),
    'nombre_ruta'      => '',
    'descripcion'      => '',
    'url'              => '#',
    'imagen_url'       => '',
    'estado'           => 1,
    'secuencial'       => 0,
    'criterio'         => '',
    'cohortid'         => 0,
    'criterio_rol'     => '',
    'prerequisito_rutaid' => 0,
    'fecha_inicio_str' => '',
    'fecha_fin_str'    => '',
    'categories'       => [],
    'courses_by_category' => [],
    'selected_courses' => [],
    'cohorts'          => [],
    'roles_available'  => [],
    'otras_rutas'      => [],
    'uniqid'           => $uniqid,
];

// ── Cargar datos existentes al editar ─────────────────────────────────────────

if ($id > 0) {
    $lp = $DB->get_record('block_simple_learning_path', ['id' => $id], '*', MUST_EXIST);

    $template_data['nombre_ruta']      = $lp->nombre_ruta;
    $template_data['descripcion']      = $lp->descripcion ?? '';
    $template_data['url']              = $lp->url;
    $template_data['imagen_url']       = $lp->imagen_url ?? '';
    $template_data['estado']           = (int) ($lp->estado ?? 1);
    $template_data['secuencial']       = (int) ($lp->secuencial ?? 0);
    $template_data['criterio']         = $lp->criterio ?? '';
    $template_data['cohortid']         = (int) ($lp->cohortid ?? 0);
    $template_data['criterio_rol']     = $lp->criterio_rol ?? '';
    $template_data['prerequisito_rutaid'] = (int) ($lp->prerequisito_rutaid ?? 0);

    if (!empty($lp->fecha_inicio)) {
        $template_data['fecha_inicio_str'] = date('Y-m-d', $lp->fecha_inicio);
    }
    if (!empty($lp->fecha_fin)) {
        $template_data['fecha_fin_str'] = date('Y-m-d', $lp->fecha_fin);
    }

    // Cursos asociados ordenados por sortorder.
    $associated = $DB->get_records_sql('
        SELECT c.id, c.fullname, cc.name AS categoryname
          FROM {block_simple_learning_path_courses} lpc
          JOIN {course} c  ON lpc.courseid  = c.id
          JOIN {course_categories} cc ON c.category = cc.id
         WHERE lpc.learningpathid = :pathid
      ORDER BY lpc.sortorder ASC, lpc.id ASC',
        ['pathid' => $id]
    );

    foreach ($associated as $course) {
        $template_data['selected_courses'][] = [
            'id'           => $course->id,
            'fullname'     => $course->fullname,
            'categoryname' => $course->categoryname,
        ];
    }
}

// ── Categorías y cursos ───────────────────────────────────────────────────────

$categories = $DB->get_records_menu('course_categories', null, 'name ASC', 'id, name');
$courses    = $DB->get_records('course', null, 'fullname ASC', 'id, fullname, category');

$category_names = [];
foreach ($categories as $cat_id => $cat_name) {
    $category_names[$cat_id] = $cat_name;
    $template_data['categories'][] = ['id' => $cat_id, 'name' => $cat_name];
}

foreach ($courses as $course) {
    if ($course->id == SITEID) {
        continue; // Excluir sitio raíz.
    }
    $cat_name = $category_names[$course->category] ?? '';
    $template_data['courses_by_category'][$course->category][] = [
        'id'           => $course->id,
        'fullname'     => $course->fullname,
        'categoryname' => $cat_name,
    ];
}

// ── Cohortes ──────────────────────────────────────────────────────────────────

$cohorts = $DB->get_records('cohort', null, 'name ASC', 'id, name');
foreach ($cohorts as $cohort) {
    $template_data['cohorts'][] = [
        'id'       => $cohort->id,
        'name'     => $cohort->name,
        'selected' => ($cohort->id == $template_data['cohortid']),
    ];
}

// ── Roles disponibles ─────────────────────────────────────────────────────────

$roles = $DB->get_records('role', null, 'sortorder ASC', 'id, shortname, name');
$role_labels = [
    'student'        => get_string('student',        'role'),
    'teacher'        => get_string('teacher',        'role'),
    'editingteacher' => get_string('editingteacher', 'role'),
    'manager'        => get_string('manager',        'role'),
    'coursecreator'  => get_string('coursecreator',  'role'),
];
foreach ($roles as $role) {
    $label = $role_labels[$role->shortname] ?? ($role->name ?: $role->shortname);
    $template_data['roles_available'][] = [
        'shortname' => $role->shortname,
        'name'      => $label,
        'selected'  => ($role->shortname === $template_data['criterio_rol']),
    ];
}

// ── Otras rutas (para prerequisito) ──────────────────────────────────────────

$otras = $DB->get_records('block_simple_learning_path', null, 'sortorder ASC, id ASC', 'id, nombre_ruta');
foreach ($otras as $ruta) {
    if ($ruta->id == $id) {
        continue; // No permitir que una ruta sea prerequisito de sí misma.
    }
    $template_data['otras_rutas'][] = [
        'id'       => $ruta->id,
        'nombre'   => $ruta->nombre_ruta,
        'selected' => ($ruta->id == $template_data['prerequisito_rutaid']),
    ];
}

// ── Checkeo de criterio seleccionado ─────────────────────────────────────────

$criterio = $template_data['criterio'];
$template_data['criterio_siempre_checked'] = ($criterio === 'siempre')      ? 'checked' : '';
$template_data['criterio_cursos_checked']  = ($criterio === 'cursos')       ? 'checked' : '';
$template_data['criterio_curso_checked']   = ($criterio === 'curso')        ? 'checked' : '';
$template_data['criterio_cohorte_checked'] = ($criterio === 'cohorte')      ? 'checked' : '';
$template_data['criterio_fecha_checked']   = ($criterio === 'fecha')        ? 'checked' : '';
$template_data['criterio_rol_checked']     = ($criterio === 'rol')          ? 'checked' : '';
$template_data['criterio_prereq_checked']  = ($criterio === 'prerequisito') ? 'checked' : '';

// JSON para el módulo AMD.
$template_data['courses_by_category_json'] = json_encode(
    $template_data['courses_by_category'],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
);

// ── Procesar envío del formulario ─────────────────────────────────────────────

$data = data_submitted();
if ($data && confirm_sesskey()) {

    // Validar URL.
    if (isset($data->url) && $data->url !== '#' && !empty($data->url)) {
        if (!filter_var($data->url, FILTER_VALIDATE_URL)) {
            throw new moodle_exception('invalidurl', 'block_simple_learning_path', '', $data->url);
        }
    }

    // Validar criterio.
    $valid_criteria = ['siempre', 'cursos', 'curso', 'cohorte', 'fecha', 'rol', 'prerequisito'];
    $criterio_submitted = $data->criterio ?? '';
    if (!in_array($criterio_submitted, $valid_criteria)) {
        throw new moodle_exception('invalidcriterio', 'block_simple_learning_path');
    }

    // Cohort ID solo aplica si el criterio es 'cohorte'.
    $cohortid = null;
    if ($criterio_submitted === 'cohorte') {
        if (empty($data->cohortid)) {
            throw new moodle_exception('nocohortid', 'block_simple_learning_path');
        }
        $cohortid = (int) $data->cohortid;
    }

    // Fechas.
    $fecha_inicio = null;
    $fecha_fin    = null;
    if ($criterio_submitted === 'fecha') {
        if (!empty($data->fecha_inicio)) {
            $fecha_inicio = strtotime($data->fecha_inicio . ' 00:00:00');
        }
        if (!empty($data->fecha_fin)) {
            $fecha_fin = strtotime($data->fecha_fin . ' 23:59:59');
        }
    }

    // Rol.
    $criterio_rol = null;
    if ($criterio_submitted === 'rol' && !empty($data->criterio_rol)) {
        $criterio_rol = clean_param($data->criterio_rol, PARAM_ALPHANUMEXT);
    }

    // Prerequisito.
    $prerequisito_rutaid = null;
    if ($criterio_submitted === 'prerequisito' && !empty($data->prerequisito_rutaid)) {
        $prerequisito_rutaid = (int) $data->prerequisito_rutaid;
    }

    // Estado y modo secuencial.
    $estado    = isset($data->estado)    ? (int) $data->estado    : 1;
    $secuencial = isset($data->secuencial) ? 1 : 0;

    // Construir objeto base.
    $record = new stdClass();
    $record->nombre_ruta         = clean_param($data->nombre_ruta ?? '', PARAM_TEXT);
    $record->descripcion         = clean_param($data->descripcion ?? '', PARAM_CLEANHTML);
    $record->url                 = clean_param($data->url ?? '#',        PARAM_URL);
    $record->imagen_url          = clean_param($data->imagen_url ?? '',  PARAM_URL);
    $record->criterio            = $criterio_submitted;
    $record->cohortid            = $cohortid;
    $record->criterio_rol        = $criterio_rol;
    $record->prerequisito_rutaid = $prerequisito_rutaid;
    $record->fecha_inicio        = $fecha_inicio;
    $record->fecha_fin           = $fecha_fin;
    $record->estado              = $estado;
    $record->secuencial          = $secuencial;
    $record->timemodified        = time();

    // Cursos seleccionados (array de IDs).
    $course_ids = isset($data->category_course) ? array_map('intval', (array) $data->category_course) : [];

    if (!empty($data->id) && (int) $data->id > 0) {
        // EDITAR ruta existente.
        $record->id = (int) $data->id;
        $DB->update_record('block_simple_learning_path', $record);

        // Reconstruir relaciones con cursos respetando el orden enviado.
        $DB->delete_records('block_simple_learning_path_courses', ['learningpathid' => $record->id]);
        foreach ($course_ids as $sortorder => $courseid) {
            $DB->insert_record('block_simple_learning_path_courses', [
                'learningpathid' => $record->id,
                'courseid'       => $courseid,
                'sortorder'      => $sortorder,
            ]);
        }

        redirect(
            new moodle_url('/blocks/simple_learning_path/index.php'),
            get_string('changes_saved', 'block_simple_learning_path'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );

    } else {
        // CREAR nueva ruta.
        $record->timecreated = time();
        $record->sortorder   = $DB->count_records('block_simple_learning_path'); // Al final de la lista.
        $newid = $DB->insert_record('block_simple_learning_path', $record);

        foreach ($course_ids as $sortorder => $courseid) {
            $DB->insert_record('block_simple_learning_path_courses', [
                'learningpathid' => $newid,
                'courseid'       => $courseid,
                'sortorder'      => $sortorder,
            ]);
        }

        redirect(
            new moodle_url('/blocks/simple_learning_path/index.php'),
            get_string('new_learning_path_created', 'block_simple_learning_path'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

// ── Renderizar ────────────────────────────────────────────────────────────────

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('block_simple_learning_path/edit', $template_data);
echo $OUTPUT->footer();
