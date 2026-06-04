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
 * Full-page academic history view for block_academichistory.
 *
 * Displays a paginated table of all courses completed by the current user,
 * including completion date, final grade, and certificate download link.
 *
 * @package   block_academichistory
 * @copyright 2024 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use block_academichistory\output\history_table;

// Force login: this page is only for authenticated users.
require_login();

// Set up page context at system level (data is personal, not course-scoped).
$context = context_system::instance();

// Verify the user has the capability to view the academic history.
require_capability('block/academichistory:view', $context);

// Pagination parameter: current page number (0-indexed).
$page    = optional_param('page', 0, PARAM_INT);
$perpage = 20;

// --- Page setup -----------------------------------------------------------

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/academichistory/view.php', ['page' => $page]));
$PAGE->set_title(get_string('pagetitle', 'block_academichistory'));
$PAGE->set_heading($SITE->fullname);
// Use 'base' layout: removes the navigation sidebar and gives the table full page width.
$PAGE->set_pagelayout('base');

// --- Output ---------------------------------------------------------------

echo $OUTPUT->header();

// Instantiate the renderable/templatable data object.
$table = new history_table($USER->id, $page, $perpage);

// Render the Mustache template with the exported data array.
echo $OUTPUT->render_from_template(
    'block_academichistory/history_table',
    $table->export_for_template($OUTPUT)
);

echo $OUTPUT->footer();
