<?php
/**
 * Block simple_learning_path main class.
 *
 * @package    block_simple_learning_path
 * @copyright  2026 e-trainingsupport.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

class block_simple_learning_path extends block_base {

    public function has_config() {
        return true;
    }

    public function applicable_formats() {
        return ['all' => true];
    }

    public function init() {
        $this->title = get_string('simple_learning_path', 'block_simple_learning_path');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $USER;

        $uniqueid  = 'slp' . uniqid();
        $rutas     = $this->get_learning_paths();
        $mis_cursos = $this->get_courses_progress();

        $data = [
            'uniqueid' => $uniqueid,
            'routes'   => [],
        ];

        $routes_found = false;
        $isFirstRoute = true;

        foreach ($rutas as $ruta) {
            if (!$this->user_meets_criteria($ruta, $mis_cursos)) {
                continue;
            }

            $routes_found = true;
            $coursesData  = [];
            $totalProgress = 0;
            $courseCount   = 0;

            foreach ($ruta['cursos'] as $curso) {
                $courseDetails      = $this->get_course_details($curso['shortname']);
                $courseProgressData = $mis_cursos[$curso['shortname']] ?? null;

                $courseProgress = $courseProgressData['progress']      ?? 0;
                $isCompleted    = $courseProgressData['is_completed']  ?? false;
                $isInProgress   = !$isCompleted && $courseProgress > 0;
                $isPending      = !$isCompleted && !$isInProgress;

                // En rutas secuenciales, bloquear cursos si el anterior no está completo.
                $isLocked = false;
                if ($ruta['secuencial'] && $courseCount > 0 && !$coursesData[$courseCount - 1]['isCompleted']) {
                    $isLocked     = true;
                    $isInProgress = false;
                    $isPending    = false;
                }

                $completedDate = '';
                if ($isCompleted && !empty($courseProgressData['course_completion_time'])) {
                    $completedDate = userdate($courseProgressData['course_completion_time'], get_string('strftimedate', 'langconfig'));
                }

                $coursesData[] = [
                    'courseName'        => $courseDetails['fullname'],
                    'courseDescription' => $courseDetails['summary'],
                    'courseProgress'    => (int) $courseProgress,
                    'isCompleted'       => $isCompleted,
                    'isInProgress'      => $isInProgress,
                    'isPending'         => $isPending,
                    'isLocked'          => $isLocked,
                    'completedDate'     => $completedDate,
                    'courseUrl'         => $isLocked ? '#' : (new moodle_url('/course/view.php', ['id' => $courseDetails['id']]))->out(),
                    'courseCoverUrl'    => $courseDetails['cover_image_url'],
                    'sortorder'         => $curso['sortorder'],
                ];

                $totalProgress += $courseProgress;
                $courseCount++;
            }

            $routeProgress    = $courseCount > 0 ? (int) round($totalProgress / $courseCount) : 0;
            $coursesCompleted = count(array_filter($coursesData, fn($c) => $c['isCompleted']));

            // Curso "continuar": primer curso en progreso o el primero pendiente.
            $continueUrl = '';
            foreach ($coursesData as $cd) {
                if ($cd['isInProgress'] && !$cd['isLocked']) {
                    $continueUrl = $cd['courseUrl'];
                    break;
                }
            }
            if (!$continueUrl) {
                foreach ($coursesData as $cd) {
                    if ($cd['isPending'] && !$cd['isLocked']) {
                        $continueUrl = $cd['courseUrl'];
                        break;
                    }
                }
            }

            $data['routes'][] = [
                'id'              => $ruta['id'],
                'routeName'       => $ruta['nombre'],
                'routeDesc'       => $ruta['descripcion'] ?? '',
                'routeImageUrl'   => $ruta['imagen_url'] ?? '',
                'hasImage'        => !empty($ruta['imagen_url']),
                'secuencial'      => (bool) $ruta['secuencial'],
                'courses'         => $coursesData,
                'coursesCompleted'=> $coursesCompleted,
                'totalCourses'    => $courseCount,
                'routeProgress'   => $routeProgress,
                'isCompleted'     => ($routeProgress === 100),
                'firstRoute'      => $isFirstRoute,
                'ariaExpanded'    => $isFirstRoute ? 'true' : 'false',
                'continueUrl'     => $continueUrl,
                'hasContinue'     => !empty($continueUrl) && $routeProgress < 100,
            ];

            $isFirstRoute = false;
        }

        $this->content       = new stdClass();
        $this->content->text = '';

        if (!$routes_found) {
            $this->content->text = get_string('no_routes_available', 'block_simple_learning_path');
            return $this->content;
        }

        $renderer = $this->page->get_renderer('core');
        $this->content->text = $renderer->render_from_template('block_simple_learning_path/rutas', $data);

        return $this->content;
    }

    // ── Criterios de visibilidad ──────────────────────────────────────────────

    private function user_meets_criteria(array $ruta, array $mis_cursos): bool {
        global $USER, $DB;

        $criterio = $ruta['criterio'] ?? '';

        // Ruta en borrador: solo la ve un admin/manager.
        if (empty($ruta['estado'])) {
            return has_capability('moodle/site:manageblocks', context_system::instance());
        }

        switch ($criterio) {

            case 'siempre':
                // Visible para cualquier usuario autenticado.
                return isloggedin() && !isguestuser();

            case 'cohorte':
                if (empty($ruta['cohortid'])) {
                    return false;
                }
                return $DB->record_exists('cohort_members', [
                    'cohortid' => $ruta['cohortid'],
                    'userid'   => $USER->id,
                ]);

            case 'cursos':
                // El alumno debe estar matriculado en TODOS los cursos de la ruta.
                foreach ($ruta['cursos'] as $curso) {
                    if (empty($mis_cursos[$curso['shortname']])) {
                        return false;
                    }
                }
                return !empty($ruta['cursos']);

            case 'curso':
                // El alumno debe estar matriculado en AL MENOS UN curso de la ruta.
                foreach ($ruta['cursos'] as $curso) {
                    if (!empty($mis_cursos[$curso['shortname']])) {
                        return true;
                    }
                }
                return false;

            case 'fecha':
                // Visible dentro del rango de fechas configurado.
                $now = time();
                $from = $ruta['fecha_inicio'] ?? 0;
                $to   = $ruta['fecha_fin']   ?? 0;
                if ($from && $now < $from) {
                    return false;
                }
                if ($to && $now > $to) {
                    return false;
                }
                return true;

            case 'rol':
                // El usuario debe tener el rol indicado en algún contexto de la plataforma.
                if (empty($ruta['criterio_rol'])) {
                    return false;
                }
                $rolerecord = $DB->get_record('role', ['shortname' => $ruta['criterio_rol']], 'id');
                if (!$rolerecord) {
                    return false;
                }
                return $DB->record_exists('role_assignments', [
                    'userid' => $USER->id,
                    'roleid' => $rolerecord->id,
                ]);

            case 'prerequisito':
                // El usuario debe haber completado otra ruta primero.
                if (empty($ruta['prerequisito_rutaid'])) {
                    return false;
                }
                return $this->user_completed_path((int) $ruta['prerequisito_rutaid'], $mis_cursos);

            default:
                // Sin criterio definido: visible para todos los matriculados.
                return true;
        }
    }

    /**
     * Comprueba si el usuario completó el 100% de una ruta.
     */
    private function user_completed_path(int $pathid, array $mis_cursos): bool {
        global $DB;

        $cursos = $DB->get_records_sql('
            SELECT c.shortname
              FROM {block_simple_learning_path_courses} lpc
              JOIN {course} c ON lpc.courseid = c.id
             WHERE lpc.learningpathid = ?
          ORDER BY lpc.sortorder ASC', [$pathid]);

        if (empty($cursos)) {
            return false;
        }

        foreach ($cursos as $curso) {
            $progress = $mis_cursos[$curso->shortname] ?? null;
            if (!$progress || !$progress['is_completed']) {
                return false;
            }
        }

        return true;
    }

    // ── Obtención de datos ────────────────────────────────────────────────────

    /**
     * Retorna el progreso del usuario en sus cursos (rol estudiante).
     */
    private function get_courses_progress(): array {
        global $USER, $DB;

        $sql = "
            SELECT
                c.id              AS courseid,
                c.shortname,
                c.fullname,
                COUNT(cmc.id)     AS completed_activities,
                COUNT(cm.id)      AS total_activities,
                cc.timecompleted  AS course_completed_time
            FROM {course} c
            JOIN {enrol} e               ON e.courseid  = c.id
            JOIN {user_enrolments} ue    ON ue.enrolid  = e.id
            LEFT JOIN {course_modules} cm
                ON cm.course = c.id AND cm.completion > 0
            LEFT JOIN {course_modules_completion} cmc
                ON cmc.coursemoduleid = cm.id
                AND cmc.userid = ue.userid
                AND cmc.completionstate = 1
            LEFT JOIN {course_completions} cc
                ON cc.course = c.id AND cc.userid = ue.userid
            JOIN {context} ctx
                ON ctx.instanceid = c.id AND ctx.contextlevel = 50
            JOIN {role_assignments} ra
                ON ra.contextid = ctx.id AND ra.userid = ue.userid
            JOIN {role} r
                ON r.id = ra.roleid AND r.shortname = 'student'
            WHERE ue.userid = :userid
            GROUP BY c.id, c.shortname, c.fullname, cc.timecompleted
        ";

        $records = $DB->get_records_sql($sql, ['userid' => $USER->id]);
        $result  = [];

        foreach ($records as $record) {
            $activityRate = ($record->total_activities > 0)
                ? round(($record->completed_activities / $record->total_activities) * 100, 2)
                : 0;

            $isCompleted   = !is_null($record->course_completed_time);
            $courseProgress = $isCompleted ? 100 : $activityRate;

            $result[$record->shortname] = [
                'id'                   => $record->courseid,
                'shortname'            => $record->shortname,
                'fullname'             => $record->fullname,
                'activity_completion_rate' => $activityRate,
                'course_completion_time'   => $record->course_completed_time,
                'progress'             => $courseProgress,
                'is_completed'         => $isCompleted,
            ];
        }

        return $result;
    }

    /**
     * Retorna los detalles visuales de un curso por shortname.
     */
    private function get_course_details(string $shortname): array {
        global $DB, $OUTPUT;

        $course = $DB->get_record('course', ['shortname' => $shortname],
            'id, shortname, fullname, summary, summaryformat');

        if (!$course) {
            return [
                'id'              => null,
                'shortname'       => $shortname,
                'fullname'        => $shortname,
                'summary'         => '',
                'cover_image_url' => $OUTPUT->image_url('placeholder', 'block_simple_learning_path')->out(),
            ];
        }

        $context = context_course::instance($course->id);
        $summary = file_rewrite_pluginfile_urls(
            $course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null
        );
        $summary = format_text($summary, $course->summaryformat, ['context' => $context]);

        $coverUrl = \core_course\external\course_summary_exporter::get_course_image($course);
        if (!$coverUrl) {
            $coverUrl = $OUTPUT->image_url('placeholder', 'block_simple_learning_path')->out();
        }

        return [
            'id'              => $course->id,
            'shortname'       => $course->shortname,
            'fullname'        => $course->fullname,
            'summary'         => $summary,
            'cover_image_url' => $coverUrl,
        ];
    }

    /**
     * Retorna todas las rutas publicadas con sus cursos, ordenadas por sortorder.
     */
    public function get_learning_paths(): array {
        global $DB;

        $paths = $DB->get_records('block_simple_learning_path', null, 'sortorder ASC, id ASC');
        $rutas = [];

        foreach ($paths as $path) {
            $cursos_db = $DB->get_records_sql('
                SELECT c.id, c.shortname, lpc.sortorder
                  FROM {block_simple_learning_path_courses} lpc
                  JOIN {course} c ON lpc.courseid = c.id
                 WHERE lpc.learningpathid = :pathid
              ORDER BY lpc.sortorder ASC, lpc.id ASC',
                ['pathid' => $path->id]
            );

            $cursos = [];
            foreach ($cursos_db as $curso) {
                $cursos[] = [
                    'id'        => $curso->id,
                    'shortname' => $curso->shortname,
                    'sortorder' => $curso->sortorder,
                ];
            }

            $rutas[] = [
                'id'                => $path->id,
                'nombre'            => $path->nombre_ruta,
                'descripcion'       => $path->descripcion ?? '',
                'url'               => $path->url,
                'imagen_url'        => $path->imagen_url ?? '',
                'criterio'          => $path->criterio ?? '',
                'cohortid'          => $path->cohortid ?? null,
                'criterio_rol'      => $path->criterio_rol ?? '',
                'prerequisito_rutaid' => $path->prerequisito_rutaid ?? null,
                'fecha_inicio'      => $path->fecha_inicio ?? null,
                'fecha_fin'         => $path->fecha_fin   ?? null,
                'estado'            => $path->estado ?? 1,
                'secuencial'        => $path->secuencial ?? 0,
                'sortorder'         => $path->sortorder ?? 0,
                'cursos'            => $cursos,
            ];
        }

        return $rutas;
    }
}
