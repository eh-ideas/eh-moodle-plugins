<?php
/**
 * Upgrade script for block_simple_learning_path.
 *
 * @package    block_simple_learning_path
 * @copyright  2026 e-trainingsupport.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin from an old version to the current one.
 *
 * @param int $oldversion Version we are upgrading from.
 * @return bool Always true.
 */
function xmldb_block_simple_learning_path_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026052100) {

        // ── Tabla: block_simple_learning_path ────────────────────────────────

        $table = new xmldb_table('block_simple_learning_path');

        // Campo: descripcion (texto libre de la ruta).
        $field = new xmldb_field('descripcion', XMLDB_TYPE_TEXT, null, null, null, null, null, 'nombre_ruta');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Campo: imagen_url (portada de la ruta).
        $field = new xmldb_field('imagen_url', XMLDB_TYPE_CHAR, '1000', null, null, null, null, 'url');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Cambiar el tipo de campo 'criterio' de TEXT a CHAR(50).
        // Primero verificamos si ya es CHAR; si no, lo alteramos.
        $field = new xmldb_field('criterio', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'cohortid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }

        // Campo: criterio_rol.
        $field = new xmldb_field('criterio_rol', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'cohortid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Campo: prerequisito_rutaid.
        $field = new xmldb_field('prerequisito_rutaid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'criterio_rol');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Campo: fecha_inicio.
        $field = new xmldb_field('fecha_inicio', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'prerequisito_rutaid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Campo: fecha_fin.
        $field = new xmldb_field('fecha_fin', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'fecha_inicio');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Campo: estado (0=borrador, 1=publicada). Las rutas existentes quedan publicadas.
        $field = new xmldb_field('estado', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'fecha_fin');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Campo: secuencial (0=libre, 1=secuencial).
        $field = new xmldb_field('secuencial', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'estado');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Campo: sortorder.
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'secuencial');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Índice: estado + sortorder para queries de visualización.
        $index = new xmldb_index('estado_sortorder', XMLDB_INDEX_NOTUNIQUE, ['estado', 'sortorder']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Inicializar sortorder con el ID de la ruta para mantener orden original.
        $DB->execute("UPDATE {block_simple_learning_path} SET sortorder = id WHERE sortorder = 0");

        // ── Tabla: block_simple_learning_path_courses ────────────────────────

        $table_courses = new xmldb_table('block_simple_learning_path_courses');

        // Campo: sortorder dentro de la ruta.
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'courseid');
        if (!$dbman->field_exists($table_courses, $field)) {
            $dbman->add_field($table_courses, $field);
        }

        // Índice: learningpathid + sortorder para ordenar cursos de una ruta.
        $index = new xmldb_index('learningpath_sortorder', XMLDB_INDEX_NOTUNIQUE, ['learningpathid', 'sortorder']);
        if (!$dbman->index_exists($table_courses, $index)) {
            $dbman->add_index($table_courses, $index);
        }

        // Inicializar sortorder de cursos con su ID para mantener orden de inserción.
        $DB->execute("UPDATE {block_simple_learning_path_courses} SET sortorder = id WHERE sortorder = 0");

        // Marcar el upgrade como completado.
        upgrade_block_savepoint(true, 2026052100, 'simple_learning_path');
    }

    return true;
}
