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
 * English language strings for block_academichistory.
 *
 * @package   block_academichistory
 * @copyright 2024 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Core plugin strings.
$string['pluginname']                        = 'Academic History';
$string['academichistory:view']              = 'View academic history';
$string['academichistory:addinstance']       = 'Add a new Academic History block';
$string['academichistory:myaddinstance']     = 'Add Academic History to my dashboard';

// Block widget strings.
$string['completedcourses']                  = 'Completed courses';
$string['viewfullhistory']                   = 'View full history';

// Navigation and summary strings.
$string['backtodashboard']                   = 'Back to dashboard';
$string['summarytitle']                      = 'Academic summary';
$string['summarysubtitle']                   = 'Courses you have successfully completed';

// Page and table strings.
$string['pagetitle']                         = 'Academic History';
$string['nocompletedcourses']                = 'You have no completed courses yet.';
$string['course']                            = 'Course';
$string['completiondate']                    = 'Completion Date';
$string['finalgrade']                        = 'Final Grade';
$string['statusgrade']                       = 'Status / Grade';
$string['certificate']                       = 'Certificate';

// Grade and completion status strings.
$string['gradenotavailable']                 = 'Not graded';
$string['gradescale']                        = 'Scale';
$string['coursecompleted']                   = 'Completed';

// Certificate display strings.
$string['download']                          = 'Download';
$string['na']                                = 'N/A';
$string['certificatenotissued']              = 'Not obtained';
$string['certificatereadytoissue']           = 'Ready to issue';

// Privacy API.
$string['privacy:metadata']                  = 'The Academic History block does not store any personal data. '
                                               . 'It only displays data already managed by Moodle core '
                                               . '(course completions and grades).';
