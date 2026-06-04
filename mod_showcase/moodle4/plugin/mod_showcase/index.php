<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Lists all Showcase instances in a given course.
 *
 * Moodle requires this file to exist for every activity module.
 * It is accessible from Administration > Course > Activity report.
 *
 * @package     mod_showcase
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// ── Resolve the course ────────────────────────────────────────────────────────

$id = required_param('id', PARAM_INT);  // course.id

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_login($course);
$PAGE->set_pagelayout('incourse');

// ── Page setup ────────────────────────────────────────────────────────────────

$PAGE->set_url('/mod/showcase/index.php', ['id' => $course->id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

// ── Heading ───────────────────────────────────────────────────────────────────

$modulenameplural = get_string('modulenameplural', 'mod_showcase');
echo $OUTPUT->heading($modulenameplural);

// ── Fetch all showcase instances in this course ───────────────────────────────

$showcases = $DB->get_records('showcase', ['course' => $course->id], 'name ASC');

if (empty($showcases)) {
    notice(get_string('thereareno', 'moodle', $modulenameplural), new moodle_url('/course/view.php', ['id' => $course->id]));
}

// ── Render as a simple table ──────────────────────────────────────────────────

$table                    = new html_table();
$table->head              = [
    get_string('name'),
    get_string('layout_type', 'mod_showcase'),
    get_string('lastmodified'),
];
$table->attributes['class'] = 'generaltable';

foreach ($showcases as $showcase) {
    $cm = get_coursemodule_from_instance('showcase', $showcase->id, $course->id, false, MUST_EXIST);

    $link        = html_writer::link(
        new moodle_url('/mod/showcase/view.php', ['id' => $cm->id]),
        format_string($showcase->name)
    );
    $layoutlabel = get_string('layout_' . $showcase->layout_type, 'mod_showcase');
    $modified    = userdate($showcase->timemodified);

    $table->data[] = [$link, $layoutlabel, $modified];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
