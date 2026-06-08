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
 * Admin settings for block_mydata v2.
 *
 * The settings let the administrator decide which cards are visible and
 * recolour each one, plus configure the profile header, progress bar and
 * the upcoming-deadlines section.
 *
 * @package    block_mydata
 * @copyright  2024 e-trainingsupport.com / eh!ideas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $release = get_config('block_mydata', 'release');
    if (empty($release)) {
        $release = 'v2.0.0-dev';
    }

    // ------------------------------------------------------------------
    // Branded header.
    // ------------------------------------------------------------------
    $brandheader =
        '<div style="display:flex;align-items:center;gap:20px;'
        . 'background:linear-gradient(135deg,#142a4d 0%,#1f4f8f 100%);color:#fff;'
        . 'padding:20px 24px;border-radius:14px;margin:4px 0 20px;'
        . 'box-shadow:0 6px 22px rgba(20,42,77,.25);">'
            . '<div style="font-size:26px;font-weight:800;letter-spacing:-.5px;line-height:1;flex-shrink:0;">'
                . 'eh<span style="color:#4da3ff;">!</span> ideas</div>'
            . '<div style="flex:1;border-left:1px solid rgba(255,255,255,.22);padding-left:20px;">'
                . '<div style="font-size:17px;font-weight:700;">' . get_string('pluginname', 'block_mydata')
                    . ' <span style="opacity:.6;font-weight:500;">· block_mydata</span></div>'
                . '<div style="font-size:13px;opacity:.82;margin-top:3px;">'
                    . get_string('settings_tagline', 'block_mydata') . '</div>'
            . '</div>'
            . '<div style="font-size:12px;font-weight:700;background:rgba(255,255,255,.16);'
                . 'padding:6px 14px;border-radius:20px;white-space:nowrap;flex-shrink:0;">' . s($release) . '</div>'
        . '</div>'
        . '<div style="font-size:13px;color:#5a6b82;margin:0 2px 14px;">'
            . get_string('settings_intro', 'block_mydata') . '</div>';

    $settings->add(new \block_mydata\admin\setting_branding('block_mydata/brandheader', $brandheader));

    // ------------------------------------------------------------------
    // General settings (styled card panel).
    // ------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'block_mydata/general_heading',
        get_string('general_heading', 'block_mydata'),
        get_string('general_heading_desc', 'block_mydata')
    ));

    $generalcards = [
        ['id' => 'display_picture', 'icon' => 'fa-solid fa-user', 'color' => '#2563eb',
            'label' => get_string('display_picture', 'block_mydata'),
            'desc' => get_string('display_picture_desc', 'block_mydata'),
            'toggle' => ['key' => 'display_picture', 'default' => 1]],
        ['id' => 'display_country', 'icon' => 'fa-solid fa-earth-americas', 'color' => '#2563eb',
            'label' => get_string('display_country', 'block_mydata'),
            'desc' => get_string('display_country_desc', 'block_mydata'),
            'toggle' => ['key' => 'display_country', 'default' => 1]],
        ['id' => 'display_city', 'icon' => 'fa-solid fa-city', 'color' => '#2563eb',
            'label' => get_string('display_city', 'block_mydata'),
            'desc' => get_string('display_city_desc', 'block_mydata'),
            'toggle' => ['key' => 'display_city', 'default' => 0]],
        ['id' => 'display_email', 'icon' => 'fa-solid fa-envelope', 'color' => '#2563eb',
            'label' => get_string('display_email', 'block_mydata'),
            'desc' => get_string('display_email_desc', 'block_mydata'),
            'toggle' => ['key' => 'display_email', 'default' => 1]],
        ['id' => 'display_position', 'icon' => 'fa-solid fa-briefcase', 'color' => '#2563eb',
            'label' => get_string('display_position', 'block_mydata'),
            'desc' => get_string('display_position_desc', 'block_mydata'),
            'toggle' => ['key' => 'display_position', 'default' => 1]],
        ['id' => 'show_progress', 'icon' => 'fa-solid fa-chart-line', 'color' => '#0ea5e9',
            'label' => get_string('progress_heading', 'block_mydata'),
            'desc' => get_string('show_progress_desc', 'block_mydata'),
            'toggle' => ['key' => 'show_progress', 'default' => 1]],
    ];

    $settings->add(new \block_mydata\admin\setting_panel(
        'block_mydata/generalpanel',
        get_string('general_heading', 'block_mydata'),
        $generalcards
    ));

    // ------------------------------------------------------------------
    // Cards — one styled box per resource (visible toggle + colour + extras).
    // ------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'block_mydata/cards_heading',
        get_string('cards_heading', 'block_mydata'),
        get_string('cards_heading_desc', 'block_mydata')
    ));

    $settings->add(new \block_mydata\admin\setting_cards_grid(
        'block_mydata/cardsgrid',
        \block_mydata\output\mydata::get_card_registry()
    ));

    // ------------------------------------------------------------------
    // Deadlines section (styled card panel).
    // ------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'block_mydata/deadlines_heading',
        get_string('deadlines_heading', 'block_mydata'),
        get_string('deadlines_heading_desc', 'block_mydata')
    ));

    $deadlinescards = [
        ['id' => 'deadlines', 'icon' => 'fa-solid fa-triangle-exclamation', 'color' => '#ef4444',
            'label' => get_string('deadlines_title', 'block_mydata'),
            'desc' => get_string('show_deadlines_desc', 'block_mydata'),
            'toggle' => ['key' => 'show_deadlines', 'default' => 1],
            'fields' => [
                ['type' => 'number', 'key' => 'deadlines_days',
                    'label' => get_string('deadlines_days', 'block_mydata'),
                    'default' => 7, 'min' => 1, 'max' => 60,
                    'suffix' => get_string('days_suffix', 'block_mydata')],
                ['type' => 'number', 'key' => 'deadlines_max',
                    'label' => get_string('deadlines_max', 'block_mydata'),
                    'default' => 3, 'min' => 1, 'max' => 10],
            ]],
    ];

    $settings->add(new \block_mydata\admin\setting_panel(
        'block_mydata/deadlinespanel',
        get_string('deadlines_heading', 'block_mydata'),
        $deadlinescards
    ));

    // ------------------------------------------------------------------
    // Branded footer / credits.
    // ------------------------------------------------------------------
    $brandfooter =
        '<div style="text-align:center;margin:30px 0 6px;padding:18px 16px;'
        . 'border-top:1px solid #e2e8f0;color:#7a8aa0;font-size:12px;line-height:1.7;">'
            . '<div style="font-size:16px;font-weight:800;color:#142a4d;margin-bottom:4px;">'
                . 'eh<span style="color:#2b7fff;">!</span> ideas</div>'
            . '<div>' . get_string('credits_by', 'block_mydata') . '</div>'
            . '<div style="margin-top:4px;">&copy; ' . userdate(time(), '%Y')
                . ' ' . get_string('company', 'block_mydata') . '. '
                . get_string('credits_rights', 'block_mydata')
                . ' &middot; ' . s($release) . '</div>'
        . '</div>';

    $settings->add(new \block_mydata\admin\setting_branding('block_mydata/brandfooter', $brandfooter));
}
