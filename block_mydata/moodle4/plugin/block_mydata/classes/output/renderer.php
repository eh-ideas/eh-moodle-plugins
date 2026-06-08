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
 * mydata block rendrer
 *
 * @package    block_mydata
 * @copyright  2024 e-trainingsupport.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mydata\output;

use moodle_url;
use plugin_renderer_base;

/**
 * mydata block renderer
 *
 * @package    block_mydata
 * @copyright  2024 e-trainingsupport.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Return the main content for the block mydata.
     *
     * @param mydata $mydata The mydata renderable
     * @return string HTML string
     */
    public function render_mydata(\block_mydata\output\mydata $mydata) {

        // v2 uses Font Awesome (bundled with Moodle) — no external icon font required.
        return $this->render_from_template('block_mydata/mydata', $mydata->export_for_template($this));
    }
}
