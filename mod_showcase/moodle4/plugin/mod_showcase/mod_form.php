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
 * The main mod_showcase configuration form.
 *
 * @package     mod_showcase
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_showcase
 * @copyright  2024 Your Name <you@example.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_showcase_mod_form extends moodleform_mod {

    /**
     * Defines the form fields.
     */
    public function definition(): void {
        global $CFG;

        $mform = $this->_form;

        // ── 1. GENERAL SECTION ───────────────────────────────────────────────
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Internal name (not shown to students).
        $mform->addElement('text', 'name', get_string('showcasename', 'mod_showcase'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'showcasename', 'mod_showcase');

        // Standard intro/description editor (used by Moodle search and as
        // the "description" text in hero / video / download templates).
        $this->standard_intro_elements();

        // ── 2. SHOWCASE SETTINGS ─────────────────────────────────────────────
        $mform->addElement('header', 'showcasefieldset', get_string('showcasefieldset', 'mod_showcase'));

        // ── Layout type selector ─────────────────────────────────────────────
        $layoutoptions = [
            'hero'       => get_string('layout_hero',       'mod_showcase'),
            'video'      => get_string('layout_video',      'mod_showcase'),
            'download'   => get_string('layout_download',   'mod_showcase'),
            'image_only' => get_string('layout_image_only', 'mod_showcase'),
        ];
        $mform->addElement('select', 'layout_type', get_string('layout_type', 'mod_showcase'), $layoutoptions);
        $mform->setDefault('layout_type', 'hero');
        $mform->addHelpButton('layout_type', 'layout_type', 'mod_showcase');

        // ════════════════════════════════════════════════════════════════════
        // HERO LAYOUT FIELDS
        // ════════════════════════════════════════════════════════════════════

        // Background image (single file, stored in File API).
        $mform->addElement(
            'filepicker',
            'heroimage',
            get_string('heroimage', 'mod_showcase'),
            null,
            $this->file_options(['accepted_types' => ['.jpg', '.jpeg', '.png', '.webp', '.gif']])
        );
        $mform->addHelpButton('heroimage', 'heroimage', 'mod_showcase');
        $mform->hideIf('heroimage', 'layout_type', 'neq', 'hero');

        // Overlay colour — plain text (hex). A CSS color-picker JS enhancement
        // can be added later without touching this field definition.
        $mform->addElement(
            'text',
            'overlay_color',
            get_string('overlay_color', 'mod_showcase'),
            ['size' => '10', 'placeholder' => '#000000']
        );
        $mform->setType('overlay_color', PARAM_TEXT);
        $mform->setDefault('overlay_color', '#000000');
        $mform->addHelpButton('overlay_color', 'overlay_color', 'mod_showcase');
        $mform->hideIf('overlay_color', 'layout_type', 'neq', 'hero');

        // Overlay opacity (integer 0–100).
        $mform->addElement(
            'text',
            'overlay_opacity',
            get_string('overlay_opacity', 'mod_showcase'),
            ['size' => '4']
        );
        $mform->setType('overlay_opacity', PARAM_INT);
        $mform->setDefault('overlay_opacity', 40);
        $mform->addHelpButton('overlay_opacity', 'overlay_opacity', 'mod_showcase');
        $mform->hideIf('overlay_opacity', 'layout_type', 'neq', 'hero');

        // ════════════════════════════════════════════════════════════════════
        // SHARED FIELDS: subtitle (hero + download)
        // ════════════════════════════════════════════════════════════════════

        $mform->addElement('text', 'subtitle', get_string('subtitle', 'mod_showcase'), ['size' => '64']);
        $mform->setType('subtitle', PARAM_TEXT);
        $mform->addHelpButton('subtitle', 'subtitle', 'mod_showcase');
        // Visible for hero and download; hidden for video and image_only.
        $mform->hideIf('subtitle', 'layout_type', 'eq', 'video');
        $mform->hideIf('subtitle', 'layout_type', 'eq', 'image_only');

        // ════════════════════════════════════════════════════════════════════
        // SHARED FIELDS: button_text + button_url (hero + download)
        // ════════════════════════════════════════════════════════════════════

        $mform->addElement('text', 'button_text', get_string('button_text', 'mod_showcase'), ['size' => '48']);
        $mform->setType('button_text', PARAM_TEXT);
        $mform->addHelpButton('button_text', 'button_text', 'mod_showcase');
        $mform->hideIf('button_text', 'layout_type', 'eq', 'video');
        $mform->hideIf('button_text', 'layout_type', 'eq', 'image_only');

        // Button URL: only meaningful for hero (download uses the file URL directly).
        $mform->addElement('text', 'button_url', get_string('button_url', 'mod_showcase'), ['size' => '64']);
        $mform->setType('button_url', PARAM_URL);
        $mform->addHelpButton('button_url', 'button_url', 'mod_showcase');
        $mform->hideIf('button_url', 'layout_type', 'neq', 'hero');

        // ════════════════════════════════════════════════════════════════════
        // VIDEO LAYOUT FIELDS
        // ════════════════════════════════════════════════════════════════════

        $mform->addElement(
            'text',
            'video_url',
            get_string('video_url', 'mod_showcase'),
            ['size' => '64', 'placeholder' => 'https://www.youtube.com/watch?v=...']
        );
        $mform->setType('video_url', PARAM_URL);
        $mform->addHelpButton('video_url', 'video_url', 'mod_showcase');
        $mform->hideIf('video_url', 'layout_type', 'neq', 'video');

        // ════════════════════════════════════════════════════════════════════
        // DOWNLOAD LAYOUT FIELDS
        // ════════════════════════════════════════════════════════════════════

        // Thumbnail / icon image.
        $mform->addElement(
            'filepicker',
            'downloadimage',
            get_string('downloadimage', 'mod_showcase'),
            null,
            $this->file_options(['accepted_types' => ['.jpg', '.jpeg', '.png', '.webp', '.svg']])
        );
        $mform->addHelpButton('downloadimage', 'downloadimage', 'mod_showcase');
        $mform->hideIf('downloadimage', 'layout_type', 'neq', 'download');

        // Downloadable file (filemanager allows the file to be backed up).
        $mform->addElement(
            'filemanager',
            'downloadfile',
            get_string('downloadfile', 'mod_showcase'),
            null,
            $this->file_options(['maxfiles' => 1, 'accepted_types' => '*', 'subdirs' => 0])
        );
        $mform->addHelpButton('downloadfile', 'downloadfile', 'mod_showcase');
        $mform->hideIf('downloadfile', 'layout_type', 'neq', 'download');

        // ════════════════════════════════════════════════════════════════════
        // IMAGE-ONLY LAYOUT FIELDS
        // ════════════════════════════════════════════════════════════════════

        $mform->addElement(
            'filepicker',
            'showcaseimage',
            get_string('image', 'mod_showcase'),
            null,
            $this->file_options(['accepted_types' => ['.jpg', '.jpeg', '.png', '.webp', '.gif']])
        );
        $mform->addHelpButton('showcaseimage', 'image', 'mod_showcase');
        $mform->hideIf('showcaseimage', 'layout_type', 'neq', 'image_only');

        // ── 3. STANDARD MOODLE MODULE ELEMENTS ──────────────────────────────
        // Adds: visible, ID number, grade, completion conditions, etc.
        $this->standard_coursemodule_elements();

        // ── 4. ACTION BUTTONS ────────────────────────────────────────────────
        $this->add_action_buttons();
    }

    // ═════════════════════════════════════════════════════════════════════════
    // DATA PRE-PROCESSING
    // Loads existing files from File API storage into draft areas so the
    // filepicker / filemanager elements display them when editing an instance.
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Pre-process form data to populate draft file areas from the File API.
     *
     * @param array $defaultvalues Passed by reference; contains DB record values.
     */
    public function data_preprocessing(&$defaultvalues): void {
        parent::data_preprocessing($defaultvalues);

        // Context id for file storage. For a new instance there is no module
        // context yet, so we pass null — file_prepare_draft_area() handles it
        // gracefully by creating an empty draft area.
        $contextid = ($this->current->instance) ? $this->context->id : null;

        // Hero background image.
        $draftitemid = file_get_submitted_draft_itemid('heroimage');
        file_prepare_draft_area(
            $draftitemid,
            $contextid,
            'mod_showcase',
            'heroimage',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
        $defaultvalues['heroimage'] = $draftitemid;

        // Download card thumbnail image.
        $draftitemid = file_get_submitted_draft_itemid('downloadimage');
        file_prepare_draft_area(
            $draftitemid,
            $contextid,
            'mod_showcase',
            'downloadimage',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
        $defaultvalues['downloadimage'] = $draftitemid;

        // Downloadable file (PDF, etc.).
        $draftitemid = file_get_submitted_draft_itemid('downloadfile');
        file_prepare_draft_area(
            $draftitemid,
            $contextid,
            'mod_showcase',
            'downloadfile',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
        $defaultvalues['downloadfile'] = $draftitemid;

        // Full-width image (image_only layout).
        $draftitemid = file_get_submitted_draft_itemid('showcaseimage');
        file_prepare_draft_area(
            $draftitemid,
            $contextid,
            'mod_showcase',
            'showcaseimage',
            0,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
        $defaultvalues['showcaseimage'] = $draftitemid;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // SERVER-SIDE VALIDATION
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Custom validation rules applied after the standard form validation.
     *
     * @param array $data  Form field values.
     * @param array $files Uploaded files (not used directly here; handled by File API).
     * @return array       Associative array of field_name => error_string.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        switch ($data['layout_type']) {

            case 'hero':
                // Opacity must be an integer between 0 and 100.
                if (
                    !is_numeric($data['overlay_opacity']) ||
                    (int)$data['overlay_opacity'] < 0 ||
                    (int)$data['overlay_opacity'] > 100
                ) {
                    $errors['overlay_opacity'] = get_string('error_opacity_range', 'mod_showcase');
                }
                break;

            case 'video':
                // Video URL is required and must be a valid YouTube/Vimeo link.
                if (empty(trim($data['video_url']))) {
                    $errors['video_url'] = get_string('required');
                } elseif (!self::is_valid_video_url($data['video_url'])) {
                    $errors['video_url'] = get_string('error_invalid_video_url', 'mod_showcase');
                }
                break;
        }

        return $errors;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // HELPERS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Returns a merged file options array.
     * Applies site-wide maxbytes as the base and merges any overrides.
     *
     * @param  array $overrides Options to merge/override.
     * @return array
     */
    private function file_options(array $overrides = []): array {
        global $CFG;

        $defaults = [
            'maxbytes' => $CFG->maxbytes ?? 0,
            'subdirs'  => 0,
            'maxfiles' => 1,
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Returns true when $url is a recognisable YouTube or Vimeo URL.
     * Accepts both watch URLs and short/embed variants.
     *
     * @param  string $url
     * @return bool
     */
    private static function is_valid_video_url(string $url): bool {
        $pattern = '/^https?:\/\/(www\.)?(
            youtube\.com\/(watch\?.*v=|embed\/|shorts\/) |
            youtu\.be\/                                   |
            vimeo\.com\/
        )/xi';

        return (bool) preg_match($pattern, trim($url));
    }
}
