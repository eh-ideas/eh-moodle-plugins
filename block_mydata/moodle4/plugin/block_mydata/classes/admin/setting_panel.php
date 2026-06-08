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
 * Generic styled admin panel: a grid of cards, each with an optional toggle
 * and optional body fields (number / text).
 *
 * Used to give the "General settings" and "Upcoming deadlines" sections the
 * same card aesthetic as the information-cards grid. Reusable across plugins.
 *
 * @package    block_mydata
 * @copyright  2024 eh! ideas Tecnología Educativa
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mydata\admin;

defined('MOODLE_INTERNAL') || die();

/**
 * Self-rendering composite admin setting for a panel of cards.
 */
class setting_panel extends \admin_setting {

    /** @var array Card definitions. */
    protected $cards;

    /**
     * Constructor.
     *
     * Each card: [
     *   'id'     => string,
     *   'icon'   => string (Font Awesome class, optional),
     *   'color'  => string (#hex, optional),
     *   'label'  => string,
     *   'desc'   => string (optional),
     *   'toggle' => ['key' => string, 'default' => 0|1] (optional),
     *   'fields' => [ ['type'=>'number'|'text','key'=>..,'label'=>..,'default'=>..,
     *                  'min'=>?, 'max'=>?, 'suffix'=>?, 'help'=>?], ... ] (optional)
     * ]
     *
     * @param string $name Unique setting name.
     * @param string $visiblename
     * @param array $cards
     */
    public function __construct($name, $visiblename, array $cards) {
        $this->cards = $cards;
        parent::__construct($name, $visiblename, '', '');
    }

    /**
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
     * Persist toggles and fields.
     *
     * @param mixed $data
     * @return string Empty on success.
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            $data = [];
        }

        foreach ($this->cards as $card) {
            if (!empty($card['toggle'])) {
                $key = $card['toggle']['key'];
                set_config($key, !empty($data[$key]) ? 1 : 0, 'block_mydata');
            }
            if (!empty($card['fields'])) {
                foreach ($card['fields'] as $field) {
                    $key = $field['key'];
                    if (!array_key_exists($key, $data)) {
                        continue;
                    }
                    if ($field['type'] === 'number') {
                        $value = (int) clean_param($data[$key], PARAM_INT);
                        if (isset($field['min']) && $value < $field['min']) {
                            $value = $field['min'];
                        }
                        if (isset($field['max']) && $value > $field['max']) {
                            $value = $field['max'];
                        }
                    } else {
                        $value = clean_param($data[$key], PARAM_TEXT);
                    }
                    set_config($key, $value, 'block_mydata');
                }
            }
        }

        return '';
    }

    /**
     * Render the panel.
     *
     * @param mixed $data
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        $name = $this->get_full_name();

        $cardshtml = '';
        foreach ($this->cards as $card) {
            $cardshtml .= $this->render_card($name, $card);
        }

        $html = styles::css() . '<div class="bm-admin-grid">' . $cardshtml . '</div>';

        return '<div class="form-item row" style="margin:0;">'
            . '<div class="form-setting col-12" style="margin:0;padding:0;">'
            . $html
            . '</div></div>';
    }

    /**
     * Render one card.
     *
     * @param string $name
     * @param array $card
     * @return string
     */
    protected function render_card($name, $card) {
        $color = !empty($card['color']) ? $card['color'] : '#64748b';
        $out = '<div class="bm-admin-card">';

        // Header.
        $out .= '<div class="bm-admin-card-head">';
        if (!empty($card['icon'])) {
            $out .= '<span class="bm-admin-ic" style="color:' . $color . ';background:' . $this->tint($color) . ';">'
                . '<i class="' . $card['icon'] . '"></i></span>';
        }
        $out .= '<div class="bm-admin-titles"><h4>' . s($card['label']) . '</h4>';
        if (!empty($card['desc'])) {
            $out .= '<p>' . s($card['desc']) . '</p>';
        }
        $out .= '</div>';

        if (!empty($card['toggle'])) {
            $key = $card['toggle']['key'];
            $current = get_config('block_mydata', $key);
            if ($current === false || $current === null || $current === '') {
                $current = $card['toggle']['default'];
            }
            $checked = ((int) $current === 1) ? ' checked' : '';
            $out .= '<label class="bm-switch">'
                . '<input type="hidden" name="' . $name . '[' . $key . ']" value="0">'
                . '<input type="checkbox" name="' . $name . '[' . $key . ']" value="1"' . $checked . '>'
                . '<span></span></label>';
        }
        $out .= '</div>'; // head.

        // Body fields.
        if (!empty($card['fields'])) {
            $out .= '<div class="bm-admin-card-body">';
            foreach ($card['fields'] as $field) {
                $out .= $this->render_field($name, $field);
            }
            $out .= '</div>';
        }

        $out .= '</div>'; // card.
        return $out;
    }

    /**
     * Render a single body field.
     *
     * @param string $name
     * @param array $field
     * @return string
     */
    protected function render_field($name, $field) {
        $key = $field['key'];
        $value = get_config('block_mydata', $key);
        if ($value === false || $value === null || $value === '') {
            $value = isset($field['default']) ? $field['default'] : '';
        }

        $full = !empty($field['help']) ? ' bm-admin-field-full' : '';
        $out = '<div class="bm-admin-field' . $full . '">';
        $out .= '<label>' . s($field['label']) . '</label>';

        if ($field['type'] === 'number') {
            $min = isset($field['min']) ? ' min="' . (int) $field['min'] . '"' : '';
            $max = isset($field['max']) ? ' max="' . (int) $field['max'] . '"' : '';
            $out .= '<span class="bm-num-wrap">'
                . '<input type="number" class="bm-num" name="' . $name . '[' . $key . ']" '
                . 'value="' . s($value) . '"' . $min . $max . '>';
            if (!empty($field['suffix'])) {
                $out .= '<span class="bm-suffix">' . s($field['suffix']) . '</span>';
            }
            $out .= '</span>';
        } else {
            $out .= '<input type="text" class="bm-text" name="' . $name . '[' . $key . ']" '
                . 'value="' . s($value) . '">';
        }

        if (!empty($field['help'])) {
            $out .= '<small>' . s($field['help']) . '</small>';
        }

        $out .= '</div>';
        return $out;
    }

    /**
     * Light tint of a hex colour.
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
