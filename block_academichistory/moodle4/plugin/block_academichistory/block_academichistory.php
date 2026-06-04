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
 * Main block class for block_academichistory.
 *
 * Displays a summary of completed courses for the current user
 * and a link to the full history page (view.php).
 *
 * @package   block_academichistory
 * @copyright 2024 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Academic History block class.
 */
class block_academichistory extends block_base {

    /**
     * Initialises the block.
     *
     * Sets the block title from the language string.
     *
     * @return void
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_academichistory');
    }

    /**
     * Returns the pages where this block can be added.
     *
     * Restricted to the personal Dashboard (/my) only, so the context
     * is always context_user and access control remains simple.
     *
     * @return array Associative array of page formats and their availability.
     */
    public function applicable_formats(): array {
        return ['my' => true];
    }

    /**
     * Indicates that this block has no per-instance configuration form.
     *
     * @return bool
     */
    public function instance_allow_config(): bool {
        return false;
    }

    /**
     * Builds and returns the block content.
     *
     * Executes a lightweight COUNT query to retrieve the number of completed
     * courses, then renders the block_content Mustache template.
     *
     * @return stdClass|null The block content object, or null if not applicable.
     */
    public function get_content(): ?stdClass {
        global $DB, $USER, $OUTPUT;

        // Return cached content if already built.
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         = new stdClass();
        $this->content->footer = '';

        // Do not render for guests or unauthenticated users.
        if (!isloggedin() || isguestuser()) {
            $this->content->text = '';
            return $this->content;
        }

        // Lightweight COUNT query: only completed, visible courses.
        $sql = 'SELECT COUNT(cc.id)
                  FROM {course_completions} cc
                  JOIN {course} c ON c.id = cc.course AND c.visible = 1
                 WHERE cc.userid          = :userid
                   AND cc.timecompleted  IS NOT NULL
                   AND cc.timecompleted   > 0';

        $count = (int) $DB->count_records_sql($sql, ['userid' => $USER->id]);

        // Build the template data array.
        $data = [
            'count'                 => $count,
            'has_courses'           => ($count > 0),
            'view_url'              => (new moodle_url('/blocks/academichistory/view.php'))->out(false),
            'str_completed_courses' => get_string('completedcourses', 'block_academichistory'),
            'str_view_history'      => get_string('viewfullhistory', 'block_academichistory'),
            'str_no_courses'        => get_string('nocompletedcourses', 'block_academichistory'),
        ];

        // Render via Mustache template (Moodle 4.x best practice for blocks).
        $this->content->text = $OUTPUT->render_from_template(
            'block_academichistory/block_content',
            $data
        );

        return $this->content;
    }
}
