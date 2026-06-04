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
 * Spanish language strings for block_academichistory.
 *
 * @package   block_academichistory
 * @copyright 2024 Your Name <your@email.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Strings principales del plugin.
$string['pluginname']                        = 'Historial Académico';
$string['academichistory:view']              = 'Ver historial académico';
$string['academichistory:addinstance']       = 'Agregar un nuevo bloque de Historial Académico';
$string['academichistory:myaddinstance']     = 'Agregar Historial Académico a mi panel';

// Strings del widget del bloque.
$string['completedcourses']                  = 'Cursos finalizados';
$string['viewfullhistory']                   = 'Ver historial completo';

// Strings de navegación y resumen.
$string['backtodashboard']                   = 'Volver al panel';
$string['summarytitle']                      = 'Resumen académico';
$string['summarysubtitle']                   = 'Cursos que has completado exitosamente';

// Strings de la página y la tabla.
$string['pagetitle']                         = 'Historial Académico';
$string['nocompletedcourses']                = 'Aún no tienes cursos finalizados.';
$string['course']                            = 'Curso';
$string['completiondate']                    = 'Fecha de Finalización';
$string['finalgrade']                        = 'Calificación Final';
$string['statusgrade']                       = 'Estado / Calificación';
$string['certificate']                       = 'Certificado';

// Strings de calificación y estado de completitud.
$string['gradenotavailable']                 = 'Sin calificación';
$string['gradescale']                        = 'Escala';
$string['coursecompleted']                   = 'Completado';

// Strings de certificado.
$string['download']                          = 'Descargar';
$string['na']                                = 'N/D';
$string['certificatenotissued']              = 'No obtenido';
$string['certificatereadytoissue']           = 'Listo para emitir';

// API de privacidad.
$string['privacy:metadata']                  = 'El bloque Historial Académico no almacena datos personales propios. '
                                               . 'Solo muestra datos ya gestionados por el núcleo de Moodle '
                                               . '(finalizaciones de cursos y calificaciones).';
