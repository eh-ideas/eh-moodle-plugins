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
 * Version details
 *
 * @package    block_mydata
 * @copyright  2024 e-trainingsupport.com
 * @author     Sergio Aldana <sergior.aldana@me.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_mydata';
$plugin->version = 2026060800; // YYYYMMDDXX.
$plugin->requires = 2022112800; // Requiere Moodle 4.1.
$plugin->maturity = MATURITY_STABLE;
$plugin->release = 'v2.0.1';
$plugin->dependencies = array(
    'mod_customcert' => 2022041910,
);
