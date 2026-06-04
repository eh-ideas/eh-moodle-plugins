<?php

/**
 * Version details
 *
 * @package    block_mydata
 * @copyright  2024 e-trainingsupport.com
 * @author     Sergio Aldana <sergior.aldana@me.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_simple_learning_path';  // Recommended since 2.0.2 (MDL-26035). Required since 3.0 (MDL-48494)
$plugin->version = 2026052100;  // YYYYMMDDHH (year, month, day, 24-hr time)
$plugin->requires = 2023042400; // YYYYMMDDHH (Moodle 4.1 minimum)
$plugin->maturity = MATURITY_STABLE;
$plugin->release = 'v2.0.0';