<?php
/**
 * Statistics report for a learning path.
 *
 * Shows per-path: enrolled users, average progress, completion rate,
 * per-course breakdown and progress distribution chart.
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

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/simple_learning_path/report.php', ['id' => $id]));

// ── Selector de ruta ──────────────────────────────────────────────────────────
$all_paths = $DB->get_records('block_simple_learning_path', null, 'sortorder ASC, id ASC', 'id, nombre_ruta, estado');

if (empty($all_paths)) {
    $PAGE->set_title('Estadísticas – Sin rutas');
    $PAGE->set_heading('Estadísticas de Rutas de Aprendizaje');
    echo $OUTPUT->header();
    echo $OUTPUT->notification('No hay rutas de aprendizaje creadas todavía.', 'info');
    echo $OUTPUT->footer();
    exit;
}

// Si no se pasó ID, usar la primera ruta.
if (!$id) {
    $first = reset($all_paths);
    $id    = $first->id;
}

$lp = $DB->get_record('block_simple_learning_path', ['id' => $id], '*', MUST_EXIST);
$PAGE->set_title('Estadísticas – ' . $lp->nombre_ruta);
$PAGE->set_heading('Estadísticas de Rutas de Aprendizaje');

// ── Cursos de la ruta ─────────────────────────────────────────────────────────
$path_courses = $DB->get_records_sql('
    SELECT c.id, c.fullname, c.shortname, lpc.sortorder
      FROM {block_simple_learning_path_courses} lpc
      JOIN {course} c ON lpc.courseid = c.id
     WHERE lpc.learningpathid = :pid
  ORDER BY lpc.sortorder ASC, lpc.id ASC',
    ['pid' => $id]
);

$course_ids = array_keys($path_courses);

if (empty($course_ids)) {
    $PAGE->set_title('Estadísticas – ' . $lp->nombre_ruta);
    echo $OUTPUT->header();
    echo $OUTPUT->notification('Esta ruta no tiene cursos asociados.', 'warning');
    echo html_writer::link(
        new moodle_url('/blocks/simple_learning_path/index.php'),
        '← Volver al listado',
        ['class' => 'btn btn-secondary mt-2']
    );
    echo $OUTPUT->footer();
    exit;
}

// ── Alumnos con rol student matriculados en al menos 1 curso de la ruta ───────
list($in_sql, $in_params) = $DB->get_in_or_equal($course_ids, SQL_PARAMS_NAMED, 'c');

$enrolled_sql = "
    SELECT DISTINCT ue.userid
      FROM {enrol} e
      JOIN {user_enrolments} ue ON ue.enrolid = e.id
      JOIN {context} ctx ON ctx.instanceid = e.courseid AND ctx.contextlevel = 50
      JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ue.userid
      JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
     WHERE e.courseid {$in_sql}
";
$enrolled_users = $DB->get_fieldset_sql($enrolled_sql, $in_params);
$total_students = count($enrolled_users);

// ── Progreso por usuario por curso ────────────────────────────────────────────
$stats_per_course = [];

foreach ($path_courses as $pc) {
    // Actividades con seguimiento en el curso.
    $total_activities = $DB->count_records_select('course_modules',
        'course = :cid AND completion > 0', ['cid' => $pc->id]);

    // Completaciones por alumno.
    $completions_sql = "
        SELECT ue.userid,
               COUNT(cmc.id)         AS completed,
               cc.timecompleted      AS course_completed_time
          FROM {enrol} e
          JOIN {user_enrolments} ue ON ue.enrolid = e.id
          JOIN {context} ctx ON ctx.instanceid = e.courseid AND ctx.contextlevel = 50
          JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ue.userid
          JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
     LEFT JOIN {course_modules} cm ON cm.course = e.courseid AND cm.completion > 0
     LEFT JOIN {course_modules_completion} cmc
               ON cmc.coursemoduleid = cm.id
               AND cmc.userid = ue.userid
               AND cmc.completionstate = 1
     LEFT JOIN {course_completions} cc ON cc.course = e.courseid AND cc.userid = ue.userid
         WHERE e.courseid = :cid
      GROUP BY ue.userid, cc.timecompleted
    ";
    $user_completions = $DB->get_records_sql($completions_sql, ['cid' => $pc->id]);

    $user_progress = [];
    $sum_progress  = 0;
    $completed_count = 0;

    foreach ($user_completions as $uc) {
        $is_done = !is_null($uc->course_completed_time);
        $pct     = $is_done ? 100 : ($total_activities > 0
            ? round(($uc->completed / $total_activities) * 100)
            : 0);
        $user_progress[$uc->userid] = $pct;
        $sum_progress  += $pct;
        if ($is_done || $pct === 100) {
            $completed_count++;
        }
    }

    $n = count($user_completions) ?: 1;

    // Distribución de progreso en 4 rangos.
    $dist = [0, 0, 0, 0]; // 0-25, 26-50, 51-75, 76-100
    foreach ($user_progress as $pct) {
        if ($pct <= 25)      { $dist[0]++; }
        elseif ($pct <= 50)  { $dist[1]++; }
        elseif ($pct <= 75)  { $dist[2]++; }
        else                 { $dist[3]++; }
    }

    $stats_per_course[] = [
        'courseid'        => $pc->id,
        'courseName'      => $pc->fullname,
        'enrolledInCourse'=> count($user_completions),
        'avgProgress'     => (int) round($sum_progress / $n),
        'completedCount'  => $completed_count,
        'completionPct'   => (int) round($completed_count / max(count($user_completions), 1) * 100),
        'dist0_25'        => $dist[0],
        'dist26_50'       => $dist[1],
        'dist51_75'       => $dist[2],
        'dist76_100'      => $dist[3],
    ];
}

// ── Métricas globales de la ruta ──────────────────────────────────────────────
$total_avg_progress = 0;
$fully_completed    = 0;  // Usuarios que completaron TODOS los cursos.

if ($total_students > 0 && !empty($enrolled_users)) {
    foreach ($enrolled_users as $userid) {
        $user_pct_sum = 0;
        $courses_done = 0;
        foreach ($stats_per_course as $cs) {
            // Simplificación: usamos avgProgress como estimación per user.
            $user_pct_sum += $cs['avgProgress'];
            if ($cs['completionPct'] === 100) {
                $courses_done++;
            }
        }
        $total_avg_progress += ($user_pct_sum / max(count($stats_per_course), 1));
    }
    $total_avg_progress = (int) round($total_avg_progress / $total_students);
}

// ── Selector de rutas para el dropdown ───────────────────────────────────────
$path_selector = [];
foreach ($all_paths as $p) {
    $path_selector[] = [
        'id'       => $p->id,
        'nombre'   => $p->nombre_ruta,
        'selected' => ($p->id == $id),
        'url'      => (new moodle_url('/blocks/simple_learning_path/report.php', ['id' => $p->id]))->out(false),
    ];
}

// ── Datos para Chart.js ───────────────────────────────────────────────────────
$chart_labels    = array_column($stats_per_course, 'courseName');
$chart_avg       = array_column($stats_per_course, 'avgProgress');
$chart_completed = array_column($stats_per_course, 'completionPct');

// ── Template data ─────────────────────────────────────────────────────────────
$template_data = [
    'pathName'         => $lp->nombre_ruta,
    'pathId'           => $id,
    'indexUrl'         => (new moodle_url('/blocks/simple_learning_path/index.php'))->out(false),
    'totalStudents'    => $total_students,
    'totalCourses'     => count($path_courses),
    'avgProgress'      => $total_avg_progress,
    'pathSelector'     => $path_selector,
    'courses'          => $stats_per_course,
    'chartLabelsJson'  => json_encode(array_values($chart_labels)),
    'chartAvgJson'     => json_encode(array_values($chart_avg)),
    'chartDoneJson'    => json_encode(array_values($chart_completed)),
    'hasCourses'       => !empty($stats_per_course),
];

// ── Renderizar ────────────────────────────────────────────────────────────────
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('block_simple_learning_path/report', $template_data);
echo $OUTPUT->footer();
