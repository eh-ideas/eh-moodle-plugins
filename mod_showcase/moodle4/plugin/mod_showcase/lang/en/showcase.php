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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     mod_showcase
 * @category    string
 * @copyright   2024 Your Name <you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// ─── Core module strings ──────────────────────────────────────────────────────

$string['modulename']           = 'Showcase';
$string['modulenameplural']     = 'Showcases';
$string['modulename_help']      = 'The Showcase activity lets teachers display rich visual content (hero banners, featured videos, download cards, or full-width images) directly inline on the course page — no click required from students.';
$string['pluginname']           = 'Showcase';
$string['pluginadministration'] = 'Showcase administration';
$string['showcasefieldset']     = 'Showcase settings';
$string['showcasename']         = 'Showcase name';
$string['showcasename_help']    = 'An internal name for this showcase instance. It is not displayed to students.';

// ─── Layout type ──────────────────────────────────────────────────────────────

$string['layout_type']          = 'Layout type';
$string['layout_type_help']     = 'Choose the visual layout for this showcase. Each option shows a different set of fields below.';
$string['layout_hero']          = 'Hero Banner';
$string['layout_video']         = 'Featured Video';
$string['layout_download']      = 'Download Card';
$string['layout_image_only']    = 'Full-width Image';

// ─── Hero layout ──────────────────────────────────────────────────────────────

$string['heroimage']            = 'Background image';
$string['heroimage_help']       = 'Upload a high-resolution image as the hero background. Recommended size: 1920 × 600 px (JPG or WebP).';
$string['subtitle']             = 'Subtitle';
$string['subtitle_help']        = 'Optional subtitle displayed below the main title.';
$string['overlay_color']        = 'Overlay colour';
$string['overlay_color_help']   = 'A colour layer placed over the background image to improve text readability.';
$string['overlay_opacity']      = 'Overlay opacity (%)';
$string['overlay_opacity_help'] = 'Opacity of the colour overlay (0 = fully transparent · 100 = fully opaque). 40–60 works best for dark images.';
$string['button_text']          = 'Button label';
$string['button_text_help']     = 'Text displayed on the call-to-action button. Leave empty to hide the button.';
$string['button_url']           = 'Button URL';
$string['button_url_help']      = 'Destination URL when the button is clicked.';

// ─── Video layout ─────────────────────────────────────────────────────────────

$string['video_url']            = 'Video URL';
$string['video_url_help']       = 'Paste a YouTube or Vimeo URL (e.g. https://www.youtube.com/watch?v=xxxxx). The video is embedded in a responsive 16:9 iframe.';

// ─── Download layout ──────────────────────────────────────────────────────────

$string['downloadimage']        = 'Thumbnail / icon';
$string['downloadimage_help']   = 'Upload a small image or icon that represents the downloadable file (PNG or SVG recommended).';
$string['downloadfile']         = 'File to download';
$string['downloadfile_help']    = 'Upload the file (PDF, DOCX, ZIP, etc.) students will download. It is included in course backups automatically.';

// ─── Image-only layout ────────────────────────────────────────────────────────

$string['image']                = 'Image';
$string['image_help']           = 'Upload a full-width responsive image. It scales to fit the course content column.';

// ─── Capabilities ─────────────────────────────────────────────────────────────

$string['showcase:addinstance'] = 'Add a new Showcase';
$string['showcase:view']        = 'View Showcase';

// ─── Privacy ──────────────────────────────────────────────────────────────────

$string['privacy:metadata']     = 'The Showcase activity plugin does not store any personal data about users.';

// ─── Validation errors ────────────────────────────────────────────────────────

$string['error_invalid_video_url'] = 'Please enter a valid YouTube or Vimeo URL.';
$string['error_no_downloadfile']   = 'A downloadable file is required for the Download Card layout.';
$string['error_opacity_range']     = 'Opacity must be a whole number between 0 and 100.';
