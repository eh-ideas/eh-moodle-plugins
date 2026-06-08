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

namespace block_mydata\output;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/badgeslib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/course/lib.php');

use renderable;
use renderer_base;
use templatable;
use user_picture;
use core_message;
use moodle_url;

/**
 * Class containing data for the mydata block (v2).
 *
 * v2 introduces a dynamic card registry: each card can be enabled/disabled and
 * recoloured from the admin settings, and several new cards were added
 * (progress, deadlines, overdue, streak, forums, time online).
 *
 * @package    block_mydata
 * @copyright  2024 e-trainingsupport.com / eh!ideas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mydata implements renderable, templatable {

    /** @var object Block instance configuration. */
    protected $config;

    /** @var array Cached enrolled courses keyed by id. */
    protected $courses = null;

    /**
     * Constructor.
     *
     * @param object $config Block instance config.
     */
    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * Static registry describing every available card.
     *
     * zone: 'main' (large cards) or 'secondary' (small cards).
     * default: whether the card is shown out of the box.
     * icon: Font Awesome 6 class.
     * color: default accent colour (admin can override).
     *
     * @return array
     */
    public static function get_card_registry() {
        return [
            // Main (large) cards.
            'pending' => [
                'zone' => 'main', 'default' => 1,
                'icon' => 'fa-solid fa-clock', 'color' => '#ea580c',
            ],
            'completed' => [
                'zone' => 'main', 'default' => 1,
                'icon' => 'fa-solid fa-circle-check', 'color' => '#16a34a',
            ],
            'courses' => [
                'zone' => 'main', 'default' => 1,
                'icon' => 'fa-solid fa-graduation-cap', 'color' => '#2563eb',
            ],
            // Secondary (small) cards.
            'messages' => [
                'zone' => 'secondary', 'default' => 1,
                'icon' => 'fa-solid fa-comment-dots', 'color' => '#7c3aed',
            ],
            'badges' => [
                'zone' => 'secondary', 'default' => 1,
                'icon' => 'fa-solid fa-award', 'color' => '#db2777',
            ],
            'certificates' => [
                'zone' => 'secondary', 'default' => 1,
                'icon' => 'fa-solid fa-file-lines', 'color' => '#d97706',
            ],
            'streak' => [
                'zone' => 'secondary', 'default' => 0,
                'icon' => 'fa-solid fa-fire-flame-curved', 'color' => '#0891b2',
                'heavy' => true,
            ],
            'forums' => [
                'zone' => 'secondary', 'default' => 0,
                'icon' => 'fa-solid fa-comments', 'color' => '#059669',
                'heavy' => true,
            ],
            'timeonline' => [
                'zone' => 'secondary', 'default' => 0,
                'icon' => 'fa-solid fa-hourglass-half', 'color' => '#6366f1',
                'heavy' => true,
            ],
        ];
    }

    /**
     * Export all data for the template.
     *
     * @param renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        $data = new \stdClass();

        $this->populate_user_data($USER, $data);

        // Gather the heavy course-derived stats once.
        $stats = $this->get_block_stats($USER);

        $this->populate_progress($data, $stats);
        $this->populate_cards($USER, $data, $stats);
        $this->populate_deadlines($USER, $data);

        return $data;
    }

    /**
     * Read a boolean show/hide config flag, falling back to the registry default.
     *
     * @param string $key config name (e.g. 'show_pending')
     * @param int $default
     * @return bool
     */
    protected function get_flag($key, $default) {
        $val = get_config('block_mydata', $key);
        if ($val === false || $val === null || $val === '') {
            return (bool) $default;
        }
        return (bool) (int) $val;
    }

    /**
     * Read the accent colour for a card, falling back to the registry default.
     *
     * @param string $id card id
     * @param string $default
     * @return string
     */
    protected function get_color($id, $default) {
        $val = get_config('block_mydata', 'color_' . $id);
        // Only allow validated hex colours into the inline styles (defence in depth).
        if (!empty($val) && preg_match('/^#[0-9a-fA-F]{6}$/', $val)) {
            return $val;
        }
        return $default;
    }

    // ------------------------------------------------------------------
    // User profile.
    // ------------------------------------------------------------------

    /**
     * Populate the user profile header.
     *
     * @param object $user
     * @param \stdClass $data
     */
    protected function populate_user_data($user, $data) {
        global $PAGE;

        if ($this->get_flag('display_picture', 1)) {
            $picture = new user_picture($user);
            $picture->size = 120;
            $data->userpicture_url = $picture->get_url($PAGE)->out(false);
            $initials = '';
            if (!empty($user->firstname)) {
                $initials .= \core_text::strtoupper(\core_text::substr($user->firstname, 0, 1));
            }
            if (!empty($user->lastname)) {
                $initials .= \core_text::strtoupper(\core_text::substr($user->lastname, 0, 1));
            }
            $data->user_initials = $initials;
        }

        $data->userfullname = fullname($user);
        $data->userfirstname = $user->firstname;

        if ($this->get_flag('display_country', 1) && !empty($user->country)) {
            $countries = get_string_manager()->get_list_of_countries(true);
            if (isset($countries[$user->country])) {
                $data->usercountry = $countries[$user->country];
            }
        }

        if ($this->get_flag('display_city', 1) && !empty($user->city)) {
            $data->usercity = $user->city;
        }

        if ($this->get_flag('display_email', 1) && !empty($user->email)) {
            $data->useremail = $user->email;
        }

        if ($this->get_flag('display_position', 1)) {
            \profile_load_custom_fields($user);
            if (!empty($user->profile['puesto'])) {
                // Plain value; the Mustache {{position}} placeholder escapes it.
                $data->position = $user->profile['puesto'];
            }
        }
    }

    // ------------------------------------------------------------------
    // Progress bar.
    // ------------------------------------------------------------------

    /**
     * Populate the global progress bar.
     *
     * @param \stdClass $data
     * @param array $stats
     */
    protected function populate_progress($data, $stats) {
        $data->showprogress = $this->get_flag('show_progress', 1);
        $data->progresspct = $stats['progressavg'];
    }

    // ------------------------------------------------------------------
    // Cards.
    // ------------------------------------------------------------------

    /**
     * Build the main and secondary card arrays for the template.
     *
     * @param object $user
     * @param \stdClass $data
     * @param array $stats
     */
    protected function populate_cards($user, $data, $stats) {
        $registry = self::get_card_registry();
        $values = $this->compute_card_values($user, $stats, $registry);

        $maincards = [];
        $secondarycards = [];

        foreach ($registry as $id => $def) {
            if (!$this->get_flag('show_' . $id, $def['default'])) {
                continue;
            }
            $v = $values[$id];
            $url = $v['url'];

            $card = [
                'id'        => $id,
                'value'     => $v['value'],
                'sub'       => isset($v['sub']) ? $v['sub'] : null,
                'label'     => $v['label'],
                'icon'      => $def['icon'],
                'iconcolor' => $this->get_color($id, $def['color']),
                'hasurl'    => !empty($url),
                'url'       => !empty($url) ? $url : '',
                'badge'     => !empty($v['badge']),
                'badgetext' => isset($v['badgetext']) ? $v['badgetext'] : '',
                'isnew'     => !empty($v['isnew']),
            ];

            $card['iconbg'] = $this->hex_to_rgba($card['iconcolor'], 0.12);

            if ($def['zone'] === 'main') {
                $maincards[] = $card;
            } else {
                $secondarycards[] = $card;
            }
        }

        $data->maincards = $maincards;
        $data->hasmaincards = !empty($maincards);
        $data->secondarycards = $secondarycards;
        $data->hassecondarycards = !empty($secondarycards);
    }

    /**
     * Compute the displayed value, label and link for each registered card.
     *
     * Heavy values are only computed if their card is enabled.
     *
     * @param object $user
     * @param array $stats
     * @param array $registry
     * @return array keyed by card id
     */
    protected function compute_card_values($user, $stats, $registry) {
        $enabled = function($id) use ($registry) {
            return $this->get_flag('show_' . $id, $registry[$id]['default']);
        };

        $values = [];

        // Pending activities (with overdue badge).
        $values['pending'] = [
            'value' => $stats['activitiesdue'],
            'label' => get_string('pending_activities', 'block_mydata'),
            'url'   => (new moodle_url('/calendar/view.php', ['view' => 'upcoming']))->out(false),
        ];
        if ($enabled('pending')) {
            $overdue = $this->get_overdue_count($user, $stats);
            if ($overdue > 0) {
                $values['pending']['badge'] = true;
                $values['pending']['badgetext'] = get_string('overdue_badge', 'block_mydata', $overdue);
            }
        }

        $values['completed'] = [
            'value' => $stats['activitiescompleted'],
            'label' => get_string('completed_activities', 'block_mydata'),
            'url'   => (new moodle_url('/my/courses.php'))->out(false),
        ];

        $values['courses'] = [
            'value' => $stats['coursescompleted'],
            'sub'   => $stats['coursesenrolled'],
            'label' => get_string('completed_courses', 'block_mydata'),
            'url'   => (new moodle_url('/my/courses.php'))->out(false),
        ];

        $values['messages'] = [
            'value' => $enabled('messages') ? core_message\api::count_unread_conversations($user) : 0,
            'label' => get_string('unread_messages', 'block_mydata'),
            'url'   => (new moodle_url('/message/index.php'))->out(false),
        ];

        $values['badges'] = [
            'value' => $enabled('badges') ? count(badges_get_user_badges($user->id)) : 0,
            'label' => get_string('badgesreceived', 'block_mydata'),
            'url'   => (new moodle_url('/badges/mybadges.php'))->out(false),
        ];

        $certurl = get_config('block_mydata', 'certurl');
        $values['certificates'] = [
            'value' => $enabled('certificates') ? $this->get_certificates_received_count($user->id) : 0,
            'label' => get_string('certificatesreceived', 'block_mydata'),
            'url'   => !empty($certurl) ? $certurl : '',
        ];

        $values['streak'] = [
            'value' => $enabled('streak') ? get_string('streak_value', 'block_mydata', $this->get_login_streak($user->id)) : 0,
            'label' => get_string('streak_label', 'block_mydata'),
            'url'   => '',
            'isnew' => true,
        ];

        $values['forums'] = [
            'value' => $enabled('forums') ? $this->get_forum_posts_count($user->id) : 0,
            'label' => get_string('forums_label', 'block_mydata'),
            'url'   => '',
            'isnew' => true,
        ];

        $values['timeonline'] = [
            'value' => $enabled('timeonline') ? get_string('timeonline_value', 'block_mydata', $this->get_time_online_hours($user->id)) : 0,
            'label' => get_string('timeonline_label', 'block_mydata'),
            'url'   => '',
            'isnew' => true,
        ];

        return $values;
    }

    // ------------------------------------------------------------------
    // Stats helpers.
    // ------------------------------------------------------------------

    /**
     * Return the user's enrolled active courses (cached).
     *
     * @param object $user
     * @return array
     */
    protected function get_courses($user) {
        if ($this->courses === null) {
            $this->courses = enrol_get_users_courses($user->id, true);
        }
        return $this->courses;
    }

    /**
     * Compute course/activity counters and average progress in a single pass.
     *
     * @param object $user
     * @return array
     */
    protected function get_block_stats($user) {
        $stats = [
            'coursesenrolled' => 0,
            'coursescompleted' => 0,
            'activitiescompleted' => 0,
            'activitiesdue' => 0,
            'progressavg' => 0,
        ];

        $courses = $this->get_courses($user);
        $progresssum = 0;
        $progresscount = 0;

        foreach ($courses as $course) {
            $stats['coursesenrolled']++;
            $completion = new \completion_info($course);

            if (!$completion->is_enabled()) {
                continue;
            }

            $percent = \core_completion\progress::get_course_progress_percentage($course, $user->id);
            if ($percent !== null) {
                $progresssum += $percent;
                $progresscount++;
                if ((int) $percent === 100) {
                    $stats['coursescompleted']++;
                }
            }

            $modules = $completion->get_activities();
            foreach ($modules as $module) {
                $moduledata = $completion->get_data($module, false, $user->id);
                if ($moduledata->completionstate == COMPLETION_INCOMPLETE) {
                    $stats['activitiesdue']++;
                } else {
                    $stats['activitiescompleted']++;
                }
            }
        }

        $stats['progressavg'] = $progresscount ? (int) round($progresssum / $progresscount) : 0;

        return $stats;
    }

    /**
     * Count certificates issued to the user.
     *
     * @param int $userid
     * @return int
     */
    protected function get_certificates_received_count($userid) {
        global $DB;
        if (!$DB->get_manager()->table_exists('customcert_issues')) {
            return 0;
        }
        return $DB->count_records('customcert_issues', ['userid' => $userid]);
    }

    /**
     * Count activities whose due date has passed (last 30 days) and are not complete.
     *
     * @param object $user
     * @param array $stats
     * @return int
     */
    protected function get_overdue_count($user, $stats) {
        global $DB;

        $courses = $this->get_courses($user);
        if (empty($courses)) {
            return 0;
        }
        $courseids = array_keys($courses);
        list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'c');

        $now = time();
        $params['from'] = $now - (30 * DAYSECS);
        $params['to'] = $now;

        $sql = "SELECT id, courseid, modulename, instance
                  FROM {event}
                 WHERE timestart >= :from AND timestart < :to
                   AND eventtype IN ('due', 'close')
                   AND courseid $insql";

        $events = $DB->get_records_sql($sql, $params);
        if (empty($events)) {
            return 0;
        }

        $overdue = 0;
        foreach ($events as $event) {
            if (empty($event->modulename) || empty($event->instance)) {
                continue;
            }
            $cm = get_coursemodule_from_instance($event->modulename, $event->instance, $event->courseid, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $completion = new \completion_info($courses[$event->courseid]);
            if (!$completion->is_enabled($cm)) {
                $overdue++; // No completion tracking: count the passed deadline as pending.
                continue;
            }
            $cdata = $completion->get_data($cm, false, $user->id);
            if ($cdata->completionstate == COMPLETION_INCOMPLETE) {
                $overdue++;
            }
        }

        return $overdue;
    }

    /**
     * Number of consecutive days (ending today) with activity in the platform.
     *
     * @param int $userid
     * @return int
     */
    protected function get_login_streak($userid) {
        global $DB;

        $since = time() - (60 * DAYSECS);
        $sql = "SELECT DISTINCT FLOOR(timecreated / 86400) AS dayno
                  FROM {logstore_standard_log}
                 WHERE userid = :userid AND timecreated >= :since
              ORDER BY dayno DESC";
        $days = $DB->get_fieldset_sql($sql, ['userid' => $userid, 'since' => $since]);
        if (empty($days)) {
            return 0;
        }

        $today = (int) floor(time() / 86400);
        $streak = 0;
        $expected = $today;
        foreach ($days as $dayno) {
            $dayno = (int) $dayno;
            if ($dayno === $expected || ($streak === 0 && $dayno === $today - 1)) {
                $streak++;
                $expected = $dayno - 1;
            } else if ($dayno < $expected) {
                break;
            }
        }

        return $streak;
    }

    /**
     * Count forum posts authored by the user in the current calendar month.
     *
     * @param int $userid
     * @return int
     */
    protected function get_forum_posts_count($userid) {
        global $DB;
        if (!$DB->get_manager()->table_exists('forum_posts')) {
            return 0;
        }
        $since = make_timestamp((int) date('Y'), (int) date('n'), 1, 0, 0, 0);
        return $DB->count_records_select('forum_posts', 'userid = :userid AND created >= :since',
            ['userid' => $userid, 'since' => $since]);
    }

    /**
     * Rough estimate of active hours this month, derived from log timestamps.
     *
     * Consecutive log entries less than 30 minutes apart are treated as one
     * continuous session; the gaps are summed and capped per step.
     *
     * @param int $userid
     * @return int hours (rounded)
     */
    protected function get_time_online_hours($userid) {
        global $DB;

        $since = make_timestamp((int) date('Y'), (int) date('n'), 1, 0, 0, 0);
        $sql = "SELECT id, timecreated
                  FROM {logstore_standard_log}
                 WHERE userid = :userid AND timecreated >= :since
              ORDER BY timecreated ASC";
        $rows = $DB->get_records_sql($sql, ['userid' => $userid, 'since' => $since], 0, 5000);
        if (count($rows) < 2) {
            return 0;
        }

        $gapcap = 30 * MINSECS;
        $seconds = 0;
        $prev = null;
        foreach ($rows as $row) {
            if ($prev !== null) {
                $delta = $row->timecreated - $prev;
                $seconds += min($delta, $gapcap);
            }
            $prev = $row->timecreated;
        }

        return (int) round($seconds / HOURSECS);
    }

    // ------------------------------------------------------------------
    // Deadlines section.
    // ------------------------------------------------------------------

    /**
     * Populate the upcoming deadlines list.
     *
     * @param object $user
     * @param \stdClass $data
     */
    protected function populate_deadlines($user, $data) {
        global $DB;

        $data->showdeadlines = $this->get_flag('show_deadlines', 1);
        $data->deadlines = [];
        $data->hasdeadlines = false;
        if (!$data->showdeadlines) {
            return;
        }

        $daysahead = (int) get_config('block_mydata', 'deadlines_days');
        if ($daysahead <= 0) {
            $daysahead = 7;
        }
        $maxitems = (int) get_config('block_mydata', 'deadlines_max');
        if ($maxitems <= 0) {
            $maxitems = 3;
        }

        $courses = $this->get_courses($user);
        if (empty($courses)) {
            return;
        }
        $courseids = array_keys($courses);
        list($insql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'c');

        $now = time();
        $params['from'] = $now;
        $params['to'] = $now + ($daysahead * DAYSECS);

        $sql = "SELECT id, name, courseid, modulename, instance, timestart
                  FROM {event}
                 WHERE timestart >= :from AND timestart <= :to
                   AND eventtype IN ('due', 'close')
                   AND courseid $insql
              ORDER BY timestart ASC";
        $events = $DB->get_records_sql($sql, $params, 0, $maxitems * 3);

        $deadlines = [];
        foreach ($events as $event) {
            if (count($deadlines) >= $maxitems) {
                break;
            }
            $url = '';
            if (!empty($event->modulename) && !empty($event->instance)) {
                $cm = get_coursemodule_from_instance($event->modulename, $event->instance, $event->courseid, false, IGNORE_MISSING);
                if ($cm) {
                    $url = (new moodle_url('/mod/' . $event->modulename . '/view.php', ['id' => $cm->id]))->out(false);
                }
            }

            $daysleft = (int) ceil(($event->timestart - $now) / DAYSECS);
            if ($daysleft <= 0) {
                $when = get_string('due_today', 'block_mydata');
                $urgency = 'urgent';
            } else if ($daysleft == 1) {
                $when = get_string('due_tomorrow', 'block_mydata');
                $urgency = 'urgent';
            } else {
                $when = get_string('due_in_days', 'block_mydata', $daysleft);
                $urgency = ($daysleft <= 4) ? 'soon' : 'ok';
            }

            $coursename = isset($courses[$event->courseid]->fullname)
                ? format_string($courses[$event->courseid]->fullname) : '';

            $deadlines[] = [
                'title'    => format_string($event->name),
                'course'   => $coursename,
                'url'      => $url,
                'hasurl'   => !empty($url),
                'when'     => $when,
                'urgency'  => $urgency,
            ];
        }

        $data->deadlines = $deadlines;
        $data->hasdeadlines = !empty($deadlines);
    }

    // ------------------------------------------------------------------
    // Utilities.
    // ------------------------------------------------------------------

    /**
     * Convert a #rrggbb colour to an rgba() string with the given alpha.
     *
     * @param string $hex
     * @param float $alpha
     * @return string
     */
    protected function hex_to_rgba($hex, $alpha) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return 'rgba(0,0,0,' . $alpha . ')';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "rgba($r,$g,$b,$alpha)";
    }
}
