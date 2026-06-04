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
 * Showcase view page.
 *
 * Because showcase_supports(FEATURE_NO_VIEW_LINK) returns true, Moodle
 * renders the content directly on the course page and never normally
 * navigates here. This file exists only to satisfy Moodle's module
 * conventions and to provide a graceful fallback (redirect to the course).
 *
 * @package     mod_showcase
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// ── Resolve the course-module from the query string ──────────────────────────

$id = optional_param('id', 0, PARAM_INT);   // course_modules.id

if ($id) {
    $cm       = get_coursemodule_from_id('showcase', $id, 0, false, MUST_EXIST);
    $course   = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $showcase = $DB->get_record('showcase', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    throw new moodle_exception('invalidcoursemodule', 'error');
}

// ── Authentication & context ──────────────────────────────────────────────────

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/showcase:view', $context);

// ── Redirect to the course page ───────────────────────────────────────────────
// Content is displayed inline; there is no standalone "view" for this module.

$courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
redirect($courseurl);
