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
 * Library of interface functions and constants for mod_showcase.
 *
 * All the core Moodle functions — add/update/delete instance, file serving,
 * and inline course-page rendering — live here.
 *
 * @package     mod_showcase
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// ═════════════════════════════════════════════════════════════════════════════
// MODULE API — REQUIRED FUNCTIONS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Returns the features this module supports.
 *
 * FEATURE_NO_VIEW_LINK is the "magic flag" that makes Moodle render the
 * module inline on the course page (same behaviour as mod_label) instead of
 * showing a clickable link that takes students to view.php.
 *
 * @param  string $feature  FEATURE_* constant from lib/moodlelib.php.
 * @return bool|null        True/false if supported, null if unknown.
 */
function showcase_supports(string $feature): ?bool {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;  // Shown under "Resources" in the activity picker.
        case FEATURE_MOD_INTRO:
            return true;                    // Enable the standard intro/description field.
        case FEATURE_SHOW_DESCRIPTION:
            return true;                    // Allow the intro to be shown on the course page.
        case FEATURE_NO_VIEW_LINK:
            return true;                    // ← KEY: display content inline, no link to view.php.
        case FEATURE_BACKUP_MOODLE2:
            return true;                    // Enable Backup & Restore support.
        case FEATURE_IDNUMBER:
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        default:
            return null;
    }
}

/**
 * Creates a new showcase instance and persists its uploaded files.
 *
 * Moodle calls this function after the teacher submits the add-instance form.
 * The $data object already contains the draft item IDs for each file area
 * (populated by the filepicker/filemanager form elements); we use
 * file_save_draft_area_files() to move them from the draft area to permanent
 * storage tied to this module context.
 *
 * @param  stdClass                  $data  Form data (includes coursemodule id).
 * @param  mod_showcase_mod_form|null $mform The form object (not used here but
 *                                          required by the Moodle API signature).
 * @return int  The id of the newly created record.
 */
function showcase_add_instance(stdClass $data, ?mod_showcase_mod_form $mform = null): int {
    global $DB;

    $data->timecreated  = time();
    $data->timemodified = time();

    // Insert the record first so we have an ID (needed by the File API below).
    $data->id = $DB->insert_record('showcase', $data);

    // The coursemodule record already exists at this point; we can get its context.
    $context = context_module::instance($data->coursemodule);

    showcase_save_files($data, $context);

    return $data->id;
}

/**
 * Updates an existing showcase instance.
 *
 * @param  stdClass                  $data  Form data.
 * @param  mod_showcase_mod_form|null $mform
 * @return bool  True on success.
 */
function showcase_update_instance(stdClass $data, ?mod_showcase_mod_form $mform = null): bool {
    global $DB;

    $data->id           = $data->instance;  // Moodle passes the old id as $data->instance.
    $data->timemodified = time();

    $DB->update_record('showcase', $data);

    $context = context_module::instance($data->coursemodule);

    showcase_save_files($data, $context);

    return true;
}

/**
 * Deletes a showcase instance and all its associated files.
 *
 * @param  int  $id  The instance id (mdl_showcase.id).
 * @return bool True on success.
 */
function showcase_delete_instance(int $id): bool {
    global $DB;

    if (!$DB->record_exists('showcase', ['id' => $id])) {
        return false;
    }

    // Retrieve the course module so we can get its context for file deletion.
    $cm = get_coursemodule_from_instance('showcase', $id);
    if ($cm) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        // Delete ALL files stored under this module context (all file areas).
        $fs->delete_area_files($context->id, 'mod_showcase');
    }

    $DB->delete_records('showcase', ['id' => $id]);

    return true;
}

// ═════════════════════════════════════════════════════════════════════════════
// INLINE COURSE-PAGE RENDERING
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Returns the content to render inline on the course page.
 *
 * Because showcase_supports(FEATURE_NO_VIEW_LINK) returns true, Moodle calls
 * this function and injects the returned $info->content directly into the
 * course page HTML — no link or redirect to view.php needed.
 *
 * The rendered HTML is cached by Moodle's course-module cache. The cache is
 * invalidated automatically whenever the instance is updated.
 *
 * @param  stdClass $coursemodule  The course_modules row (contains ->instance).
 * @return cached_cm_info|null
 */
function showcase_get_coursemodule_info(stdClass $coursemodule): ?cached_cm_info {
    global $DB, $OUTPUT;

    $showcase = $DB->get_record('showcase', ['id' => $coursemodule->instance], '*', MUST_EXIST);

    $info       = new cached_cm_info();
    $info->name = $showcase->name;

    $context = context_module::instance($coursemodule->id);

    // Build the data array for the Mustache template.
    // We pass $coursemodule->id explicitly so format_module_intro() can
    // resolve the correct context for text filters.
    $templatedata = showcase_build_template_data($showcase, $context, $coursemodule->id);

    // Map layout_type to its template name (templates/ directory).
    $validlayouts = ['hero', 'video', 'download', 'image_only'];
    $layout = in_array($showcase->layout_type, $validlayouts) ? $showcase->layout_type : 'hero';

    // Render the Mustache template and store its output as the inline content.
    $info->content = $OUTPUT->render_from_template('mod_showcase/' . $layout, $templatedata);

    return $info;
}

// ═════════════════════════════════════════════════════════════════════════════
// FILE SERVING
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Serves files stored in mod_showcase file areas through pluginfile.php.
 *
 * Moodle routes requests for /pluginfile.php/.../mod_showcase/... to this
 * function. We verify the user has permission to view the module, then let
 * Moodle stream the file.
 *
 * @param  stdClass $course     Course record.
 * @param  stdClass $cm         Course-module record.
 * @param  context  $context    Module context.
 * @param  string   $filearea   One of: heroimage | downloadimage | downloadfile | showcaseimage.
 * @param  array    $args       Remaining URL path segments [itemid, filename].
 * @param  bool     $forcedownload  Whether to force a file download.
 * @param  array    $options    Additional options.
 * @return bool  False if file not found (Moodle handles the 404).
 */
function showcase_pluginfile(
    stdClass $course,
    stdClass $cm,
    context $context,
    string $filearea,
    array $args,
    bool $forcedownload,
    array $options = []
): bool {
    // Only serve files from a module context.
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }

    // Validate the file area.
    $allowedareas = ['heroimage', 'downloadimage', 'downloadfile', 'showcaseimage', 'intro'];
    if (!in_array($filearea, $allowedareas)) {
        return false;
    }

    // Require the user to be logged in and able to view the course.
    require_login($course, true, $cm);

    // Serve the file. The 'downloadfile' area forces a download dialog;
    // images are served inline.
    $forcedownload = ($filearea === 'downloadfile') ? true : $forcedownload;

    $itemid   = (int) array_shift($args);   // Usually 0 for our single-file areas.
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs   = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_showcase', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    // Stream the file to the browser.
    send_stored_file($file, null, 0, $forcedownload, $options);

    return true;
}

// ═════════════════════════════════════════════════════════════════════════════
// INTERNAL HELPERS  (prefixed with showcase_ to avoid global namespace clashes)
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Persists all uploaded files from their draft areas to permanent storage.
 *
 * Called by both showcase_add_instance() and showcase_update_instance().
 * file_save_draft_area_files() is idempotent: if no file was uploaded for a
 * given area, it simply does nothing (or deletes existing files if the teacher
 * cleared the picker, which is the expected behaviour on update).
 *
 * @param stdClass $data     Form data containing draft item IDs.
 * @param context  $context  Module context for permanent storage.
 */
function showcase_save_files(stdClass $data, context $context): void {
    $fileoptions = ['subdirs' => 0, 'maxfiles' => 1];

    // Hero background image.
    if (isset($data->heroimage)) {
        file_save_draft_area_files(
            $data->heroimage,
            $context->id,
            'mod_showcase',
            'heroimage',
            0,
            $fileoptions
        );
    }

    // Download card thumbnail.
    if (isset($data->downloadimage)) {
        file_save_draft_area_files(
            $data->downloadimage,
            $context->id,
            'mod_showcase',
            'downloadimage',
            0,
            $fileoptions
        );
    }

    // Downloadable file (PDF, etc.).
    if (isset($data->downloadfile)) {
        file_save_draft_area_files(
            $data->downloadfile,
            $context->id,
            'mod_showcase',
            'downloadfile',
            0,
            $fileoptions
        );
    }

    // Full-width image (image_only layout).
    if (isset($data->showcaseimage)) {
        file_save_draft_area_files(
            $data->showcaseimage,
            $context->id,
            'mod_showcase',
            'showcaseimage',
            0,
            $fileoptions
        );
    }
}

/**
 * Builds the data array that is passed to the Mustache template.
 *
 * Resolves pluginfile URLs for stored images/files and computes derived
 * values (e.g. overlay opacity as a CSS decimal, embed URL for videos).
 *
 * @param  stdClass $showcase  DB record.
 * @param  context  $context   Module context (used to build pluginfile URLs).
 * @return array    Flat associative array suitable for render_from_template().
 */
function showcase_build_template_data(stdClass $showcase, context $context, int $cmid = 0): array {
    // Description / intro text formatted as HTML.
    // $cmid is required by format_module_intro() to resolve the filter context.
    $description = !empty($showcase->intro)
        ? format_module_intro('showcase', $showcase, $cmid, false)
        : '';

    $data = [
        'title'       => format_string($showcase->name),
        'subtitle'    => format_string($showcase->subtitle ?? ''),
        'description' => $description,
        'button_text' => format_string($showcase->button_text ?? ''),
        'button_url'  => $showcase->button_url ?? '',
        'layout_type' => $showcase->layout_type,
    ];

    switch ($showcase->layout_type) {

        case 'hero':
            $data['heroimage_url']      = showcase_get_file_url($context, 'heroimage');
            $data['overlay_color']      = $showcase->overlay_color ?: '#000000';
            // Convert integer percent to CSS decimal (e.g. 40 → 0.4).
            $data['overlay_opacity_css'] = number_format((int)$showcase->overlay_opacity / 100, 2, '.', '');
            $data['has_button']          = (!empty($showcase->button_text) && !empty($showcase->button_url));
            break;

        case 'video':
            $data['embed_url'] = showcase_build_embed_url($showcase->video_url ?? '');
            break;

        case 'download':
            $data['downloadimage_url'] = showcase_get_file_url($context, 'downloadimage');
            $data['downloadfile_url']  = showcase_get_file_url($context, 'downloadfile', true);
            $data['has_button']        = !empty($showcase->button_text);
            break;

        case 'image_only':
            $data['showcaseimage_url'] = showcase_get_file_url($context, 'showcaseimage');
            break;
    }

    return $data;
}

/**
 * Returns the pluginfile.php URL for the first file in a given file area,
 * or an empty string if no file has been uploaded.
 *
 * @param  context $context      Module context.
 * @param  string  $filearea     File area name (e.g. 'heroimage').
 * @param  bool    $forcedownload  Whether the URL should force a download.
 * @return string  Absolute URL or empty string.
 */
function showcase_get_file_url(context $context, string $filearea, bool $forcedownload = false): string {
    $fs    = get_file_storage();
    $files = $fs->get_area_files(
        $context->id,
        'mod_showcase',
        $filearea,
        0,
        'id DESC',
        false    // Exclude directories.
    );

    if (empty($files)) {
        return '';
    }

    $file = reset($files);

    return moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename(),
        $forcedownload
    )->out(false);
}

/**
 * Converts a public YouTube or Vimeo URL to its embeddable iframe src.
 *
 * Handles:
 *   youtube.com/watch?v=ID  →  youtube.com/embed/ID
 *   youtu.be/ID             →  youtube.com/embed/ID
 *   youtube.com/shorts/ID   →  youtube.com/embed/ID
 *   vimeo.com/ID            →  player.vimeo.com/video/ID
 *
 * @param  string $url  Raw URL as entered by the teacher.
 * @return string  Embed URL, or the original URL if no pattern matches.
 */
function showcase_build_embed_url(string $url): string {
    $url = trim($url);

    // YouTube — standard watch URL.
    if (preg_match('/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]+)/i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }

    // YouTube — short URL (youtu.be/ID).
    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }

    // YouTube — Shorts.
    if (preg_match('/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }

    // Vimeo — numeric video ID.
    if (preg_match('/vimeo\.com\/(\d+)/i', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }

    // Unknown format — return as-is (the template will still try to embed it).
    return $url;
}
