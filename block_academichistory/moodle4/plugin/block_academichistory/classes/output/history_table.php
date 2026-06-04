<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Renderable/templatable class for the academic history table.
 *
 * Encapsulates all SQL queries and data transformation logic.
 * Produces a plain array ready for the Mustache template.
 *
 * @package   block_academichistory
 * @copyright 2024 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_academichistory\output;

use moodle_url;
use renderer_base;
use stdClass;

/**
 * Data object for the history table template.
 *
 * Implements renderable and templatable so it can be used with
 * $OUTPUT->render_from_template() in view.php.
 */
class history_table implements \renderable, \templatable {

    /** @var int The user ID whose history is being displayed. */
    protected int $userid;

    /** @var int Current page number (0-indexed) for pagination. */
    protected int $page;

    /** @var int Number of records per page. */
    protected int $perpage;

    /**
     * Constructor.
     *
     * @param int $userid  The user whose history to display.
     * @param int $page    Current page (0-indexed). Default 0.
     * @param int $perpage Records per page. Default 20.
     */
    public function __construct(int $userid, int $page = 0, int $perpage = 20) {
        $this->userid  = $userid;
        $this->page    = $page;
        $this->perpage = $perpage;
    }

    // -------------------------------------------------------------------------
    // Private: SQL helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the total count of completed courses for the user.
     *
     * Used to build the paging bar in export_for_template().
     *
     * @return int Total number of completed visible courses.
     */
    private function count_completions(): int {
        global $DB;

        $sql = 'SELECT COUNT(cc.id)
                  FROM {course_completions} cc
                  JOIN {course} c ON c.id = cc.course AND c.visible = 1
                 WHERE cc.userid         = :userid
                   AND cc.timecompleted IS NOT NULL
                   AND cc.timecompleted  > 0';

        return (int) $DB->count_records_sql($sql, ['userid' => $this->userid]);
    }

    /**
     * Retrieves a paginated list of completed courses with grades.
     *
     * Joins course_completions with course (for the name) and performs
     * LEFT JOINs on grade_items and grade_grades so that courses without
     * a configured grade item are still included (with NULL grade values).
     *
     * @return stdClass[] Array of result rows, one per completed course.
     */
    private function get_completions(): array {
        global $DB;

        $sql = 'SELECT c.id           AS courseid,
                       c.fullname     AS coursename,
                       cc.timecompleted,
                       gg.finalgrade,
                       gi.grademax,
                       gi.scaleid
                  FROM {course_completions} cc
                  JOIN {course} c
                    ON c.id      = cc.course
                   AND c.visible = 1
             LEFT JOIN {grade_items} gi
                    ON gi.courseid   = c.id
                   AND gi.itemtype   = :itemtype
                   AND gi.itemnumber = 0
             LEFT JOIN {grade_grades} gg
                    ON gg.itemid = gi.id
                   AND gg.userid = cc.userid
                 WHERE cc.userid         = :userid
                   AND cc.timecompleted IS NOT NULL
                   AND cc.timecompleted  > 0
              ORDER BY cc.timecompleted DESC';

        $params = [
            'userid'   => $this->userid,
            'itemtype' => 'course',
        ];

        return array_values(
            $DB->get_records_sql($sql, $params, $this->page * $this->perpage, $this->perpage)
        );
    }

    /**
     * Retrieves customcert data for a list of course IDs.
     *
     * Checks whether the customcert plugin is installed before querying.
     * Returns an array indexed by courseid. Each entry contains:
     *   - cmid   (int)  : The course-module ID for the customcert instance.
     *   - issued (bool) : Whether the current user has been issued a certificate.
     *
     * If customcert is not installed, returns an empty array so that all
     * certificate cells will display "N/A" without any errors.
     *
     * @param  int[] $courseids List of course IDs to check.
     * @return array            Certificate data indexed by courseid.
     */
    private function get_certificates(array $courseids): array {
        global $DB;

        if (empty($courseids)) {
            return [];
        }

        // Guard: customcert may not be installed on this Moodle instance.
        if (!$DB->get_manager()->table_exists('customcert')
                || !$DB->get_manager()->table_exists('customcert_issues')) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');

        $sql = "SELECT cm.course    AS courseid,
                       cm.id        AS cmid,
                       ci.id        AS issueid
                  FROM {course_modules} cm
                  JOIN {modules} m
                    ON m.id   = cm.module
                   AND m.name = 'customcert'
                  JOIN {customcert} cert
                    ON cert.id = cm.instance
             LEFT JOIN {customcert_issues} ci
                    ON ci.customcertid = cert.id
                   AND ci.userid       = :userid
                 WHERE cm.course $insql
                   AND cm.visible = 1
              ORDER BY cm.id ASC";

        $params           = array_merge(['userid' => $this->userid], $inparams);
        $rows             = $DB->get_records_sql($sql, $params);
        $certsbycourse    = [];

        foreach ($rows as $row) {
            // Keep only the first customcert instance per course.
            if (isset($certsbycourse[$row->courseid])) {
                continue;
            }
            $certsbycourse[$row->courseid] = [
                'cmid'   => (int) $row->cmid,
                'issued' => ($row->issueid !== null),
            ];
        }

        return $certsbycourse;
    }

    // -------------------------------------------------------------------------
    // Private: formatters
    // -------------------------------------------------------------------------

    /**
     * Formats the grade/status data for a single course row.
     *
     * Returns an array consumed by the Mustache template with two exclusive states:
     *  - has_grade=true  → a numeric or scale grade exists and is shown as text.
     *  - is_completed=true → no grade exists; a "Completed" badge is shown instead.
     *
     * Rules:
     *  - Scale grade (scaleid set) → has_grade=true, value="Scale".
     *  - NULL finalgrade           → is_completed=true (Opción B: completion badge).
     *  - Numeric grade             → has_grade=true, value="XX.X%".
     *
     * @param  float|null $finalgrade The raw final grade value (may be null).
     * @param  float      $grademax   The maximum grade for the item.
     * @param  int|null   $scaleid    If set, this item uses a qualitative scale.
     * @return array                  Template-ready grade/status data.
     */
    private function format_grade(?float $finalgrade, float $grademax, ?int $scaleid): array {
        // Scale-based grade: show a label, no percentage possible in v1.
        if ($scaleid !== null) {
            return [
                'has_grade'    => true,
                'value'        => get_string('gradescale', 'block_academichistory'),
                'is_completed' => false,
            ];
        }

        // No grade recorded: show the "Completed" badge instead (Opción B).
        if ($finalgrade === null) {
            return [
                'has_grade'    => false,
                'value'        => '',
                'is_completed' => true,
            ];
        }

        // Avoid division by zero for misconfigured grade items.
        if ($grademax <= 0) {
            return [
                'has_grade'    => true,
                'value'        => number_format($finalgrade, 1),
                'is_completed' => false,
            ];
        }

        $percentage = round(($finalgrade / $grademax) * 100, 1);
        return [
            'has_grade'    => true,
            'value'        => $percentage . '%',
            'is_completed' => false,
        ];
    }

    /**
     * Builds the certificate data array for a single course row.
     *
     * Three possible states, each with a distinct label and button style:
     *  1. No customcert in course        → plain text "N/A", no link.
     *  2. customcert exists, NOT issued  → "Ready to issue" button (warning),
     *                                      links to the activity so the student
     *                                      can generate their certificate.
     *  3. customcert exists, IS issued   → "Download" button (success),
     *                                      links to the activity to retrieve it.
     *
     * Returns an associative array consumed by the Mustache template:
     *   - has_link  (bool)   : Whether to render a clickable button/link.
     *   - label     (string) : Button or cell text.
     *   - link      (string) : Absolute URL string, or empty string.
     *   - css_class (string) : Full Bootstrap button classes for the link element.
     *
     * @param  int   $courseid The course ID for this row.
     * @param  array $certs    Certificate data indexed by courseid (from get_certificates()).
     * @return array           Template-ready certificate data.
     */
    private function format_certificate(int $courseid, array $certs): array {
        // No customcert instance in this course.
        if (!isset($certs[$courseid])) {
            return [
                'has_link'  => false,
                'label'     => get_string('na', 'block_academichistory'),
                'link'      => '',
                'css_class' => '',
            ];
        }

        $url = new moodle_url('/mod/customcert/view.php', ['id' => $certs[$courseid]['cmid']]);

        // customcert exists but the user has not generated their certificate yet.
        // Show "Ready to issue" with a warning-style button so the student knows
        // they can go to the activity and obtain it.
        if (!$certs[$courseid]['issued']) {
            return [
                'has_link'  => true,
                'label'     => get_string('certificatereadytoissue', 'block_academichistory'),
                'link'      => $url->out(false),
                'css_class' => 'btn btn-sm btn-warning',
            ];
        }

        // Certificate has already been issued: provide the download link.
        return [
            'has_link'  => true,
            'label'     => get_string('download', 'block_academichistory'),
            'link'      => $url->out(false),
            'css_class' => 'btn btn-sm btn-success',
        ];
    }

    // -------------------------------------------------------------------------
    // Public: templatable interface
    // -------------------------------------------------------------------------

    /**
     * Exports all data needed by the history_table Mustache template.
     *
     * @param  renderer_base $output The current page renderer.
     * @return array                 Template context data.
     */
    public function export_for_template(renderer_base $output): array {
        global $OUTPUT;

        // Fetch paginated completions.
        $completions = $this->get_completions();
        $total       = $this->count_completions();

        // Collect course IDs to query certificates in a single round-trip.
        $courseids = array_column($completions, 'courseid');
        $certs     = $this->get_certificates($courseids);

        // Build the rows array for the template.
        $rows = [];
        foreach ($completions as $row) {
            $courseurl = new moodle_url('/course/view.php', ['id' => $row->courseid]);

            $rows[] = [
                'coursename'     => format_string($row->coursename),
                'courseurl'      => $courseurl->out(false),
                'completiondate' => userdate($row->timecompleted, get_string('strftimedate', 'langconfig')),
                'grade'          => $this->format_grade(
                                        isset($row->finalgrade) ? (float) $row->finalgrade : null,
                                        isset($row->grademax)   ? (float) $row->grademax   : 100.0,
                                        isset($row->scaleid)    ? (int)   $row->scaleid    : null
                                    ),
                'certificate'    => $this->format_certificate((int) $row->courseid, $certs),
            ];
        }

        // Build the paging bar HTML (empty string when no paging needed).
        $pagingbar = '';
        if ($total > $this->perpage) {
            $pagingurl = new moodle_url('/blocks/academichistory/view.php');
            $pagingbar = $OUTPUT->paging_bar($total, $this->page, $this->perpage, $pagingurl);
        }

        // Build the back-to-dashboard URL.
        $backurl = new moodle_url('/my');

        return [
            // Summary header data.
            'total_courses'       => $total,
            'back_url'            => $backurl->out(false),

            // Table data.
            'has_courses'         => !empty($rows),
            'courses'             => $rows,
            'paging_bar'          => $pagingbar,

            // Pre-translated strings (avoids using {{#str}} helper in the template).
            'str_back'            => get_string('backtodashboard', 'block_academichistory'),
            'str_summary_title'   => get_string('summarytitle', 'block_academichistory'),
            'str_summary_sub'     => get_string('summarysubtitle', 'block_academichistory'),
            'str_completed_label' => get_string('completedcourses', 'block_academichistory'),
            'str_course'          => get_string('course', 'block_academichistory'),
            'str_date'            => get_string('completiondate', 'block_academichistory'),
            'str_grade'           => get_string('statusgrade', 'block_academichistory'),
            'str_completed'       => get_string('coursecompleted', 'block_academichistory'),
            'str_certificate'     => get_string('certificate', 'block_academichistory'),
            'str_no_courses'      => get_string('nocompletedcourses', 'block_academichistory'),
        ];
    }
}
