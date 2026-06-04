<?php
// This file is part of Moodle - http://moodle.org/
namespace block_coursecard\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use context_course;
use completion_info;
use moodle_url;

class main implements renderable, templatable {

    /** @var int The current user ID */
    private $userid;

    public function __construct(int $userid) {
        $this->userid = $userid;
    }

    public function export_for_template(renderer_base $output): array {
        global $DB, $SITE;

        $courses     = enrol_get_my_courses(null, 'fullname ASC');
        $coursesdata = [];

        foreach ($courses as $course) {
            if ($course->id == $SITE->id) {
                continue;
            }

            $coursecontext  = context_course::instance($course->id);
            $completioninfo = new completion_info($course);

            $courseimage = $this->get_course_image($course, $coursecontext);

            $progress = null;
            if ($completioninfo->is_enabled()) {
                $raw = \core_completion\progress::get_course_progress_percentage($course, $this->userid);
                if ($raw !== null) {
                    $progress = (int) floor($raw);
                }
            }

            [$completed, $total] = $this->get_activities_count($course, $completioninfo);

            $lastaccess = $DB->get_field(
                'user_lastaccess', 'timeaccess',
                ['userid' => $this->userid, 'courseid' => $course->id]
            );

            $status        = $this->get_course_status($progress, $completed, $total);
            $instructor    = $this->get_course_instructor($coursecontext);
            $certinfo      = $this->get_certificate_info($course, $status);
            $deadline      = $this->get_upcoming_deadline($course);
            $categoryname  = $DB->get_field('course_categories', 'name', ['id' => $course->category]);
            $fallbackclass = $this->get_fallback_class($course->category);
            $fallbackicon  = $this->get_fallback_icon($course->id);

            $coursesdata[] = [
                'id'                  => $course->id,
                'fullname'            => format_string($course->fullname),
                'courseurl'           => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(),
                'courseimage'         => $courseimage,
                'hascourseimage'      => !empty($courseimage),
                'fallbackclass'       => $fallbackclass,
                'fallbackicon'        => $fallbackicon,
                'categoryname'        => $categoryname,
                'progress'            => $progress,
                'hasprogress'         => $progress !== null,
                'progresswidth'       => $progress ?? 0,
                'iscomplete100'       => $progress !== null && $progress >= 100,
                'completedactivities' => $completed,
                'totalactivities'     => $total,
                'lastaccess'          => $lastaccess ? $this->format_last_access($lastaccess) : null,
                'haslastaccess'       => !empty($lastaccess),
                'status'              => $status,
                'isinprogress'        => $status === 'inprogress',
                'iscompleted'         => $status === 'completed',
                'isnotstarted'        => $status === 'notstarted',
                'instructor'          => $instructor,
                'hasinstructor'       => !empty($instructor),
                'hascertificate'      => $certinfo['hascertificate'],
                'nocertificate'       => !$certinfo['hascertificate'],
                'certificateurl'      => $certinfo['url'],
                'candownloadcert'     => $certinfo['candownload'],
                'deadline'            => $deadline,
                'hasdeadline'         => !empty($deadline),
            ];
        }

        return [
            'courses'    => $coursesdata,
            'hascourses' => !empty($coursesdata),
            'uniqid'     => uniqid('cc-'),
        ];
    }

    private function get_course_image($course, context_course $coursecontext): ?string {
        $fs    = get_file_storage();
        $files = $fs->get_area_files(
            $coursecontext->id, 'course', 'overviewfiles', false, 'filename', false
        );
        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                return moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    null,
                    $file->get_filepath(),
                    $file->get_filename()
                )->out();
            }
        }
        return null;
    }

    private function get_activities_count($course, completion_info $completioninfo): array {
        if (!$completioninfo->is_enabled()) {
            return [0, 0];
        }
        $modinfo   = get_fast_modinfo($course);
        $total     = 0;
        $completed = 0;
        foreach ($modinfo->get_cms() as $cm) {
            // 0 = COMPLETION_TRACKING_NONE
            if ((int)$cm->completion === 0 || $cm->deletioninprogress) {
                continue;
            }
            $total++;
            $data = $completioninfo->get_data($cm, false, $this->userid);
            // 1 = COMPLETION_COMPLETE, 2 = COMPLETION_COMPLETE_PASS
            if (in_array((int)$data->completionstate, [1, 2], true)) {
                $completed++;
            }
        }
        return [$completed, $total];
    }

    private function get_course_status(?int $progress, int $completed, int $total): string {
        if ($progress === null) {
            if ($completed === 0) {
                return 'notstarted';
            }
            return $completed >= $total ? 'completed' : 'inprogress';
        }
        if ($progress >= 100) { return 'completed'; }
        if ($progress > 0)    { return 'inprogress'; }
        return 'notstarted';
    }

    private function get_course_instructor(context_course $coursecontext): ?array {
        $roles = ['editingteacher', 'teacher'];
        foreach ($roles as $role) {
            $roleid = $this->get_role_id($role);
            if (!$roleid) { continue; }
            $teachers = get_enrolled_users($coursecontext, '', 0, 'u.id, u.firstname, u.lastname', null, 0, 1);
            $teachers = array_filter($teachers, function($u) use ($coursecontext, $roleid) {
                return user_has_role_assignment($u->id, $roleid, $coursecontext->id);
            });
            if (!empty($teachers)) {
                $t = reset($teachers);
                return [
                    'fullname' => $t->firstname . ' ' . $t->lastname,
                    'initials' => strtoupper(substr($t->firstname, 0, 1) . substr($t->lastname, 0, 1)),
                ];
            }
        }
        return null;
    }

    private function get_certificate_info($course, string $status): array {
        global $DB;
        $result = ['hascertificate' => false, 'url' => null, 'candownload' => false];
        $pluginman = \core_plugin_manager::instance();
        if (!array_key_exists('customcert', $pluginman->get_plugins_of_type('mod'))) {
            return $result;
        }
        // Use get_records + reset to avoid IGNORE_MULTIPLE constant
        $certs = $DB->get_records('customcert', ['course' => $course->id], 'id ASC', 'id', 0, 1);
        $cert  = reset($certs);
        if (!$cert) {
            return $result;
        }
        $result['hascertificate'] = true;
        if ($status === 'completed') {
            $cm = get_coursemodule_from_instance('customcert', $cert->id, $course->id);
            if ($cm) {
                $result['url']         = (new moodle_url('/mod/customcert/view.php', ['id' => $cm->id]))->out();
                $result['candownload'] = true;
            }
        }
        return $result;
    }

    private function get_upcoming_deadline($course): ?array {
        global $DB;
        $day    = 86400; // DAY_SECS
        $now    = time();
        $future = $now + (7 * $day);
        $sql = "SELECT a.name, a.duedate
                  FROM {assign} a
                  JOIN {course_modules} cm ON cm.instance = a.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                 WHERE a.course   = :courseid
                   AND a.duedate  > :now
                   AND a.duedate  < :future
                   AND cm.visible = 1
                   AND cm.deletioninprogress = 0
              ORDER BY a.duedate ASC
                 LIMIT 1";
        $assign = $DB->get_record_sql($sql, ['courseid' => $course->id, 'now' => $now, 'future' => $future]);
        if (!$assign) { return null; }
        $days = (int) ceil(($assign->duedate - $now) / $day);
        return [
            'name'         => shorten_text(format_string($assign->name), 40),
            'daysleft'     => $days,
            'dayslefttext' => $days <= 1
                ? get_string('tomorrow', 'block_coursecard')
                : get_string('daysleft', 'block_coursecard', $days),
            'isurgent'     => $days <= 2,
        ];
    }

    private function format_last_access(int $timestamp): string {
        $day  = 86400; // DAY_SECS
        $diff = time() - $timestamp;
        if ($diff < $day)          { return get_string('today',         'block_coursecard'); }
        if ($diff < 2 * $day)      { return get_string('yesterday',     'block_coursecard'); }
        $days = (int) floor($diff / $day);
        if ($days < 7)             { return get_string('daysago',       'block_coursecard', $days); }
        $weeks = (int) floor($days / 7);
        if ($weeks < 5)            { return get_string('weeksago',      'block_coursecard', $weeks); }
        return get_string('morethanmonth', 'block_coursecard');
    }

    private function get_fallback_class(int $categoryid): string {
        $classes = ['nl-fallback--blue', 'nl-fallback--green', 'nl-fallback--purple', 'nl-fallback--teal', 'nl-fallback--amber'];
        return $classes[$categoryid % count($classes)];
    }

    private function get_fallback_icon(int $courseid): string {
        $icons = [
            'fa-graduation-cap',
            'fa-book',
            'fa-code',
            'fa-flask',
            'fa-bar-chart',
            'fa-cogs',
            'fa-globe',
            'fa-pencil',
            'fa-music',
            'fa-rocket',
            'fa-lightbulb-o',
            'fa-university',
        ];
        return $icons[$courseid % count($icons)];
    }

    private function get_role_id(string $shortname): ?int {
        global $DB;
        return $DB->get_field('role', 'id', ['shortname' => $shortname]) ?: null;
    }
}
