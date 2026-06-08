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
 * Custom admin setting: one styled card per resource.
 *
 * Renders the whole "information cards" section as a responsive grid of boxes.
 * Each box groups every variable of that resource (visible toggle, accent
 * colour and any resource-specific extras such as the certificates URL) and
 * writes the matching block_mydata config keys (show_<id>, color_<id>, ...).
 *
 * @package    block_mydata
 * @copyright  2024 eh! ideas Tecnología Educativa
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mydata\admin;

defined('MOODLE_INTERNAL') || die();

/**
 * Composite, self-rendering admin setting for the per-card configuration.
 */
class setting_cards_grid extends \admin_setting {

    /** @var array Card registry (id => meta). */
    protected $registry;

    /**
     * Constructor.
     *
     * @param string $name Unique setting name (e.g. 'block_mydata/cardsgrid').
     * @param array $registry Output from mydata::get_card_registry().
     */
    public function __construct($name, array $registry) {
        $this->registry = $registry;
        parent::__construct($name, get_string('cards_heading', 'block_mydata'),
            get_string('cards_heading_desc', 'block_mydata'), '');
    }

    /**
     * Current stored values are read directly in output_html.
     *
     * @return true
     */
    public function get_setting() {
        return true;
    }

    /**
     * @return true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Persist every card's values.
     *
     * @param mixed $data Array posted under this setting's name.
     * @return string Empty string on success.
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            $data = [];
        }

        foreach ($this->registry as $id => $meta) {
            $show = !empty($data['show_' . $id]) ? 1 : 0;
            set_config('show_' . $id, $show, 'block_mydata');

            if (isset($data['color_' . $id])) {
                $color = $this->clean_colour($data['color_' . $id], $meta['color']);
                set_config('color_' . $id, $color, 'block_mydata');
            }
        }

        // Resource-specific extra: certificates destination URL.
        if (isset($data['certurl'])) {
            set_config('certurl', clean_param($data['certurl'], PARAM_URL), 'block_mydata');
        }

        return '';
    }

    /**
     * Validate a hex colour, falling back to a default.
     *
     * @param string $value
     * @param string $default
     * @return string
     */
    protected function clean_colour($value, $default) {
        $value = trim($value);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            return $value;
        }
        return $default;
    }

    /**
     * Render the card grid.
     *
     * @param mixed $data
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        $name = $this->get_full_name();

        $cards = '';
        foreach ($this->registry as $id => $meta) {
            $cards .= $this->render_card($name, $id, $meta);
        }

        $html = styles::css()
            . '<div class="bm-admin-grid">' . $cards . '</div>';

        return '<div class="form-item row" style="margin:0;">'
            . '<div class="form-setting col-12" style="margin:0;padding:0;">'
            . $html
            . '</div></div>';
    }

    /**
     * Render a single resource card.
     *
     * @param string $name Setting full name (input prefix).
     * @param string $id Card id.
     * @param array $meta Registry meta.
     * @return string
     */
    protected function render_card($name, $id, $meta) {
        $label = get_string('card_' . $id, 'block_mydata');
        $desc = get_string('card_' . $id . '_desc', 'block_mydata');

        $showval = get_config('block_mydata', 'show_' . $id);
        if ($showval === false || $showval === null || $showval === '') {
            $showval = $meta['default'];
        }
        $checked = ((int) $showval === 1) ? ' checked' : '';

        $color = get_config('block_mydata', 'color_' . $id);
        if (empty($color)) {
            $color = $meta['color'];
        }
        // Defence in depth: never emit a non-hex colour into the HTML attributes/CSS.
        $color = $this->clean_colour($color, $meta['color']);

        $zonekey = ($meta['zone'] === 'main') ? 'zone_main' : 'zone_secondary';
        $zone = get_string($zonekey, 'block_mydata');
        $tint = $this->tint($color);

        $out = '<div class="bm-admin-card">';

        // Header: icon + titles + visibility toggle.
        $out .= '<div class="bm-admin-card-head">';
        $out .= '<span class="bm-admin-ic" style="color:' . $color . ';background:' . $tint . ';">'
            . '<i class="' . $meta['icon'] . '"></i></span>';
        $out .= '<div class="bm-admin-titles">'
            . '<div class="bm-admin-zone">' . $zone . '</div>'
            . '<h4>' . s($label) . '</h4>'
            . '<p>' . s($desc) . '</p>'
            . '</div>';
        // Visibility toggle (hidden 0 + checkbox 1).
        $out .= '<label class="bm-switch" title="' . s(get_string('card_visible', 'block_mydata')) . '">'
            . '<input type="hidden" name="' . $name . '[show_' . $id . ']" value="0">'
            . '<input type="checkbox" name="' . $name . '[show_' . $id . ']" value="1"' . $checked . '>'
            . '<span></span></label>';
        $out .= '</div>'; // head.

        // Body: accent colour + extras.
        $out .= '<div class="bm-admin-card-body">';
        $out .= '<div class="bm-admin-field">'
            . '<label>' . get_string('card_accent', 'block_mydata') . '</label>'
            . '<span class="bm-color-wrap">'
            . '<input type="color" class="bm-color" name="' . $name . '[color_' . $id . ']" value="' . $color . '">'
            . '<code>' . s($color) . '</code>'
            . '</span>'
            . '</div>';

        // Performance warning for cards that query the site logs.
        if (!empty($meta['heavy'])) {
            $out .= '<div class="bm-admin-warn">'
                . '<i class="fa-solid fa-gauge-high"></i>'
                . '<span>' . get_string('card_heavy', 'block_mydata') . '</span>'
                . '</div>';
        }

        // Resource-specific extra variable: certificates URL.
        if ($id === 'certificates') {
            $certurl = get_config('block_mydata', 'certurl');
            $out .= '<div class="bm-admin-field bm-admin-field-full">'
                . '<label>' . get_string('certurl', 'block_mydata') . '</label>'
                . '<input type="text" inputmode="url" class="bm-text" name="' . $name . '[certurl]" '
                . 'placeholder="https://...  (opcional)" value="' . s($certurl) . '">'
                . '<small>' . get_string('certurl_desc', 'block_mydata') . '</small>'
                . '</div>';
        }

        $out .= '</div>'; // body.
        $out .= '</div>'; // card.

        return $out;
    }

    /**
     * Light tint of a hex colour for icon backgrounds.
     *
     * @param string $hex
     * @return string rgba()
     */
    protected function tint($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return 'rgba(0,0,0,.08)';
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "rgba($r,$g,$b,.13)";
    }
}
