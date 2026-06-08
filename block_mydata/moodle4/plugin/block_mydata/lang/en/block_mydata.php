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
 * Strings for component 'block_mydata', language 'en'.
 *
 * @package    block_mydata
 * @copyright  2024 e-trainingsupport.com / eh!ideas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'My Dashboard';
$string['welcomemessage'] = 'Hi, {$a}, welcome!';

// Capabilities.
$string['mydata:addinstance'] = 'Add a new My Dashboard block';
$string['mydata:myaddinstance'] = 'Add a new My Dashboard block to the Dashboard';

// Admin panel branding.
$string['company'] = 'eh! ideas Tecnología Educativa';
$string['settings_tagline'] = 'Personal student dashboard with their learning statistics.';
$string['settings_intro'] = 'Choose which cards are shown, adjust their colours and configure the profile information. Changes apply to all users.';
$string['credits_by'] = 'Developed by eh! ideas Tecnología Educativa';
$string['credits_rights'] = 'All rights reserved.';

// Card grid (admin).
$string['card_visible'] = 'Show or hide this card';
$string['card_accent'] = 'Accent colour';
$string['card_link'] = 'Link on click';
$string['card_link_ph'] = 'https://...  (optional)';
$string['card_link_help'] = 'Where the card goes when clicked. Leave empty to use the built-in default (or none, if that card has no logical page in Moodle).';
$string['card_heavy'] = 'High load: this card queries the site logs. On high-traffic platforms it may affect performance. Enable it only if you need it.';
$string['zone_main'] = 'Main card';
$string['zone_secondary'] = 'Secondary card';

// General settings.
$string['general_heading'] = 'General settings';
$string['general_heading_desc'] = 'Profile fields shown in the block header.';
$string['display_picture'] = 'Show profile picture';
$string['display_picture_desc'] = 'Show the user picture (or their initials when there is no picture).';
$string['display_country'] = 'Show country';
$string['display_country_desc'] = 'Show the user country.';
$string['display_city'] = 'Show city';
$string['display_city_desc'] = 'Show the user city.';
$string['display_email'] = 'Show email';
$string['display_email_desc'] = 'Show the user email address.';
$string['display_position'] = 'Show position';
$string['display_position_desc'] = 'Show the custom profile field "puesto" (position).';

// Progress bar.
$string['progress_heading'] = 'Progress bar';
$string['show_progress'] = 'Show progress bar';
$string['show_progress_desc'] = 'Show the average progress across all of the user active courses.';
$string['progress_label'] = 'Average progress across your active courses';
$string['progress_tooltip'] = 'Average completion percentage across all your active courses. Only activities with completion tracking enabled are counted.';

// Cards section.
$string['cards_heading'] = 'Information cards';
$string['cards_heading_desc'] = 'Enable or disable each card and choose its accent colour. Cards marked as new are disabled by default.';
$string['card_color_desc'] = 'Accent colour for the card icon (HEX format).';

$string['card_pending'] = 'Pending activities';
$string['card_pending_desc'] = 'Card showing the number of incomplete activities.';
$string['card_pending_color'] = 'Colour — Pending activities';
$string['card_completed'] = 'Completed activities';
$string['card_completed_desc'] = 'Card showing the number of finished activities.';
$string['card_completed_color'] = 'Colour — Completed activities';
$string['card_courses'] = 'Completed courses';
$string['card_courses_desc'] = 'Card showing completed courses out of the total.';
$string['card_courses_color'] = 'Colour — Completed courses';
$string['card_messages'] = 'Unread messages';
$string['card_messages_desc'] = 'Card showing the number of unread conversations.';
$string['card_messages_color'] = 'Colour — Unread messages';
$string['card_badges'] = 'Badges received';
$string['card_badges_desc'] = 'Card showing the total badges of the user.';
$string['card_badges_color'] = 'Colour — Badges received';
$string['card_certificates'] = 'Certificates received';
$string['card_certificates_desc'] = 'Card showing issued certificates (requires mod_customcert).';
$string['card_certificates_color'] = 'Colour — Certificates received';
$string['card_streak'] = 'Login streak (new)';
$string['card_streak_desc'] = 'Card showing consecutive days of activity on the platform.';
$string['card_streak_color'] = 'Colour — Login streak';
$string['card_forums'] = 'Forum activity (new)';
$string['card_forums_desc'] = 'Card showing the number of forum posts made this month.';
$string['card_forums_color'] = 'Colour — Forum activity';
$string['card_timeonline'] = 'Time on platform (new)';
$string['card_timeonline_desc'] = 'Card showing an estimate of active hours this month.';
$string['card_timeonline_color'] = 'Colour — Time on platform';

$string['certurl'] = 'Certificates URL';
$string['certurl_desc'] = 'Destination the certificates card links to (e.g. your "My Certificates" page). Leave empty to make the card non-clickable.';

// Deadlines section.
$string['deadlines_heading'] = 'Upcoming deadlines';
$string['deadlines_heading_desc'] = 'List of activities with an upcoming due date.';
$string['show_deadlines'] = 'Show upcoming deadlines';
$string['show_deadlines_desc'] = 'Show a list of activities that are due soon.';
$string['deadlines_days'] = 'Days ahead';
$string['deadlines_days_desc'] = 'How many days ahead to look for deadlines.';
$string['days_suffix'] = 'days';
$string['deadlines_max'] = 'Maximum items to show';
$string['deadlines_max_desc'] = 'Maximum number of deadlines to list.';
$string['deadlines_title'] = 'Upcoming deadlines';
$string['deadlines_empty'] = 'You have no upcoming deadlines. Well done!';

// Card labels (front-end).
$string['pending_activities'] = 'Pending activities';
$string['completed_activities'] = 'Completed activities';
$string['completed_courses'] = 'Completed courses';
$string['unread_messages'] = 'Unread messages';
$string['badgesreceived'] = 'Badges';
$string['certificatesreceived'] = 'Certificates';
$string['streak_label'] = 'Day streak';
$string['streak_value'] = '{$a}';
$string['forums_label'] = 'Forum posts';
$string['timeonline_label'] = 'Time this month';
$string['timeonline_value'] = '{$a}h';
$string['overdue_badge'] = '{$a} overdue';

// Deadline relative dates.
$string['due_today'] = 'Due today';
$string['due_tomorrow'] = 'Due tomorrow';
$string['due_in_days'] = 'Due in {$a} days';

// Privacy.
$string['privacy:metadata'] = 'The My Dashboard block only displays existing user data; it does not store any personal data of its own.';
