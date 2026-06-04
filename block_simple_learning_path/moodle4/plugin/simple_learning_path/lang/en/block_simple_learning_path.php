<?php
/**
 * Language strings for block_simple_learning_path.
 *
 * @package    block_simple_learning_path
 * @copyright  2026 e-trainingsupport.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// ── Plugin meta ───────────────────────────────────────────────────────────────
$string['pluginname']    = 'Rutas de aprendizaje (simple)';
$string['headerconfig']  = 'Configuraciones';
$string['descconfig']    = 'Configuraciones generales para las rutas de aprendizaje.';
$string['defaulttitle']  = 'CURSOS Y RUTAS DE APRENDIZAJE';

// Block title & capabilities.
$string['simple_learning_path']              = 'Rutas de aprendizaje';
$string['simple_learning_path:addinstance']  = 'Agregar un nuevo bloque de Rutas de Aprendizaje';
$string['simple_learning_path:myaddinstance']= 'Agregar un bloque de Rutas de Aprendizaje a Mi Moodle';
$string['simple_learning_path:blocktitle']   = 'Título del bloque';
$string['simple_learning_path:showmycourses']      = 'Mostrar mis cursos';
$string['simple_learning_path:showmylearningpaths']= 'Mostrar mis rutas en proceso';

// ── Admin – listado y formulario ──────────────────────────────────────────────
$string['pluginmenu']        = 'Rutas de aprendizaje';
$string['create_path_title'] = 'Crear Nueva Ruta de Aprendizaje';
$string['edit_path_title']   = 'Editar Ruta de Aprendizaje';

// Campos del formulario – v1.
$string['nombre_ruta']             = 'Nombre de la Ruta';
$string['nombre_ruta_placeholder'] = 'Ej: Programa de Liderazgo';
$string['courses_help']            = 'Selecciona en orden los cursos que formarán parte de esta ruta de aprendizaje.';
$string['courses']                 = 'Cursos';
$string['url']                     = 'URL de la ruta';
$string['url_help']                = 'Introduce la URL de la página con información de la ruta, o deja "#" para no vincular.';

// Campos del formulario – v2 (nuevos).
$string['descripcion_ruta']             = 'Descripción de la ruta';
$string['descripcion_ruta_placeholder'] = 'Breve descripción visible para el alumno (máx. 1000 caracteres)';
$string['descripcion_ruta_help']        = 'Esta descripción se mostrará al alumno bajo el nombre de la ruta.';
$string['imagen_url']                   = 'Imagen de portada (URL)';
$string['imagen_url_help']              = 'URL de una imagen que represente la ruta. Formato recomendado: 800×400 px.';
$string['estado_ruta']                  = 'Estado';
$string['estado_publicada']             = 'Publicada';
$string['estado_borrador']              = 'Borrador';
$string['modo_secuencial']              = 'Modo de cursado';
$string['secuencial_label']             = 'Ruta secuencial (el alumno debe completar cada curso en orden)';
$string['secuencial_help']              = 'En modo secuencial, los cursos posteriores aparecen bloqueados hasta completar el anterior.';
$string['drag_to_reorder']              = 'Arrastrá las filas para definir el orden de los cursos en la ruta.';

// Selección de cursos.
$string['choosecategory']          = 'Selecciona una categoría';
$string['select_category']         = 'Selecciona una categoría';
$string['choosecourse']            = 'Selecciona un curso';
$string['add_course']              = 'Añadir curso';
$string['remove_course']           = 'Eliminar curso';
$string['remove']                  = 'Eliminar';
$string['select_course']           = 'Selecciona un curso';
$string['removecourse']            = 'Eliminar curso';
$string['search_course_placeholder']= 'Buscar curso';
$string['searchcourse']            = 'Buscar';
$string['reset_search']            = 'Limpiar búsqueda';
$string['added_courses']           = 'Cursos agregados';
$string['general_information']     = 'Información general';
$string['course_selection']        = 'Selección de cursos';

// ── Criterios de visualización ────────────────────────────────────────────────
$string['display_criteria']     = 'Criterio de visualización';
$string['select_criteria']      = 'Seleccioná el criterio por el cual se mostrará esta ruta al usuario:';

// Criterios – v1.
$string['criteria_option_cursos']  = 'Estar matriculado en TODOS los cursos de la ruta';
$string['criteria_option_curso']   = 'Estar matriculado en AL MENOS UN curso de la ruta';
$string['criteria_option_cohorte'] = 'Pertenecer a una cohorte específica';

// Criterios – v2 (nuevos).
$string['criteria_option_siempre'] = 'Siempre visible (cualquier usuario autenticado)';
$string['criteria_option_fecha']   = 'Visible en un rango de fechas';
$string['criteria_option_rol']     = 'Tener un rol específico en la plataforma';
$string['criteria_option_prereq']  = 'Haber completado otra ruta (prerequisito)';

// Cohorte.
$string['select_cohort']        = 'Seleccioná la cohorte';
$string['select_cohort_option'] = 'Seleccioná una cohorte';

// Fechas.
$string['fecha_inicio'] = 'Fecha de inicio';
$string['fecha_fin']    = 'Fecha de fin';

// Rol.
$string['select_rol']        = 'Seleccioná el rol requerido';
$string['select_rol_option'] = 'Seleccioná un rol';

// Prerequisito de ruta.
$string['select_prereq_ruta']  = 'Ruta que debe completarse primero';
$string['select_prereq_option']= 'Seleccioná una ruta';
$string['prereq_help']         = 'El alumno verá esta ruta solo cuando haya completado el 100% de la ruta elegida.';

// Criterios de selección del curso para criterio.
$string['select_course_for_criteria'] = 'Selecciona un curso para el criterio';
$string['select_course_option']       = 'Selecciona un curso';

// ── Errores y validaciones ────────────────────────────────────────────────────
$string['errornamerouteexists'] = 'Ya existe una ruta de aprendizaje con el nombre "{$a}". Por favor, elegí un nombre diferente.';
$string['invalidurl']           = 'La URL proporcionada no es válida: {$a}';
$string['invalidcriterio']      = 'Criterio de visualización inválido.';
$string['nocohortid']           = 'Debés seleccionar una cohorte cuando el criterio es "Cohorte".';

// ── Mensajes de éxito ─────────────────────────────────────────────────────────
$string['changes_saved']             = 'Cambios guardados correctamente.';
$string['new_learning_path_created'] = 'Nueva ruta de aprendizaje creada correctamente.';
$string['path_deleted']              = 'Ruta de aprendizaje eliminada.';

// ── Vista del alumno (bloque) ─────────────────────────────────────────────────
$string['no_routes_available'] = 'No hay rutas de aprendizaje disponibles para vos en este momento.';
$string['no_date']             = 'Sin fecha';
$string['route_completed']     = 'Ruta completada';
$string['route_in_progress']   = 'En progreso';
$string['course_locked']       = 'Completá el curso anterior para desbloquear';
$string['continue_route']      = 'Continuar';
$string['completed_on']        = 'Completado el {$a}';
$string['progress_label']      = 'Progreso';
$string['courses_count']       = '{$a->done} / {$a->total} cursos';

// ── Configuración heredada ────────────────────────────────────────────────────
$string['labellearning_path_json_definition'] = 'Definición JSON (obsoleto)';
$string['desclearning_path_json_definition']  = 'No se usa en v2.';
$string['simple_learning_path:rutajsonlabel'] = 'JSON de la Ruta';
