<?php
/**
 * Delete a learning path.
 *
 * @package    block_simple_learning_path
 * @copyright  2026 e-trainingsupport.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

global $DB;

$id      = required_param('id', PARAM_INT);
$context = context_system::instance();
require_capability('moodle/site:manageblocks', $context);
require_sesskey();

// Verificar existencia y eliminar.
$lp = $DB->get_record('block_simple_learning_path', ['id' => $id], '*', MUST_EXIST);

$DB->delete_records('block_simple_learning_path_courses', ['learningpathid' => $id]);
$DB->delete_records('block_simple_learning_path', ['id' => $id]);

redirect(
    new moodle_url('/blocks/simple_learning_path/index.php'),
    get_string('path_deleted', 'block_simple_learning_path'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
