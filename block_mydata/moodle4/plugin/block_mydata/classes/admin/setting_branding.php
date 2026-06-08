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
 * A display-only admin setting that renders raw, styled HTML.
 *
 * Unlike admin_setting_heading, the markup is NOT passed through the text
 * cleaner, so full inline CSS (flexbox, gradients, shadows) survives. Used to
 * render the branded header/footer of the settings page. Reusable: copy this
 * class into any eh!ideas plugin (or move it to a shared local_ehideas lib).
 *
 * @package    block_mydata
 * @copyright  2024 eh!ideas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mydata\admin;

defined('MOODLE_INTERNAL') || die();

/**
 * Renders arbitrary HTML inside the admin settings tree without sanitising it.
 */
class setting_branding extends \admin_setting {

    /** @var string Raw HTML to render. */
    protected $html;

    /**
     * Constructor.
     *
     * @param string $name Unique setting name (e.g. 'block_mydata/brandheader').
     * @param string $html Raw HTML block to output.
     */
    public function __construct($name, $html) {
        $this->nosave = true;
        $this->html = $html;
        parent::__construct($name, '', '', '');
    }

    /**
     * Always "set" — nothing is stored.
     *
     * @return true
     */
    public function get_setting() {
        return true;
    }

    /**
     * No default to apply.
     *
     * @return true
     */
    public function get_defaultsetting() {
        return true;
    }

    /**
     * Nothing is written.
     *
     * @param mixed $data
     * @return string empty (no error)
     */
    public function write_setting($data) {
        return '';
    }

    /**
     * Output the raw HTML, full width (no label column).
     *
     * @param mixed $data
     * @param string $query
     * @return string
     */
    public function output_html($data, $query = '') {
        return '<div class="form-item row" style="margin:0;">'
            . '<div class="form-setting col-12" style="margin:0;padding:0;">'
            . $this->html
            . '</div></div>';
    }
}
