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
 * Strings for component 'block_mydata', language 'es'.
 *
 * @package    block_mydata
 * @copyright  2024 e-trainingsupport.com / eh!ideas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Mi Panel';
$string['welcomemessage'] = '¡Hola, {$a}, bienvenido/a!';

// Capabilities.
$string['mydata:addinstance'] = 'Agregar un nuevo bloque Mi Panel';
$string['mydata:myaddinstance'] = 'Agregar un nuevo bloque Mi Panel al Área personal';

// Admin panel branding.
$string['company'] = 'eh! ideas Tecnología Educativa';
$string['settings_tagline'] = 'Panel personal del estudiante con sus estadísticas de aprendizaje.';
$string['settings_intro'] = 'Elegí qué tarjetas se muestran, ajustá sus colores y configurá la información del perfil. Los cambios se aplican a todos los usuarios.';
$string['credits_by'] = 'Desarrollado por eh! ideas Tecnología Educativa';
$string['credits_rights'] = 'Todos los derechos reservados.';

// Card grid (admin).
$string['card_visible'] = 'Mostrar u ocultar esta tarjeta';
$string['card_accent'] = 'Color de acento';
$string['card_link'] = 'Enlace al hacer clic';
$string['card_link_ph'] = 'https://...  (opcional)';
$string['card_link_help'] = 'A dónde lleva la tarjeta al hacer clic. Si lo dejás vacío se usa el destino por defecto (o ninguno, si esa tarjeta no tiene una página lógica en Moodle).';
$string['card_heavy'] = 'Consumo elevado: esta tarjeta consulta los registros del sitio. En plataformas con mucho tráfico puede afectar el rendimiento. Actívala solo si la necesitás.';
$string['zone_main'] = 'Tarjeta principal';
$string['zone_secondary'] = 'Tarjeta secundaria';

// General settings.
$string['general_heading'] = 'Configuración general';
$string['general_heading_desc'] = 'Datos del perfil que se muestran en la cabecera del bloque.';
$string['display_picture'] = 'Mostrar foto de perfil';
$string['display_picture_desc'] = 'Muestra la foto del usuario (o sus iniciales si no tiene foto).';
$string['display_country'] = 'Mostrar país';
$string['display_country_desc'] = 'Muestra el país del usuario.';
$string['display_city'] = 'Mostrar ciudad';
$string['display_city_desc'] = 'Muestra la ciudad del usuario.';
$string['display_email'] = 'Mostrar email';
$string['display_email_desc'] = 'Muestra la dirección de correo del usuario.';
$string['display_position'] = 'Mostrar cargo';
$string['display_position_desc'] = 'Muestra el campo de perfil personalizado «puesto».';

// Progress bar.
$string['progress_heading'] = 'Barra de progreso';
$string['show_progress'] = 'Mostrar barra de progreso';
$string['show_progress_desc'] = 'Muestra el progreso promedio de todos los cursos activos del usuario.';
$string['progress_label'] = 'Progreso promedio en tus cursos activos';
$string['progress_tooltip'] = 'Promedio del porcentaje de completitud en todos tus cursos activos. Solo considera actividades con seguimiento de finalización habilitado.';

// Cards section.
$string['cards_heading'] = 'Tarjetas de información';
$string['cards_heading_desc'] = 'Activa o desactiva cada tarjeta y elige su color de acento. Las tarjetas marcadas como nuevas vienen desactivadas por defecto.';
$string['card_color_desc'] = 'Color de acento del icono de la tarjeta (formato HEX).';

$string['card_pending'] = 'Actividades pendientes';
$string['card_pending_desc'] = 'Tarjeta con el número de actividades sin completar.';
$string['card_pending_color'] = 'Color — Actividades pendientes';
$string['card_completed'] = 'Actividades completadas';
$string['card_completed_desc'] = 'Tarjeta con el número de actividades terminadas.';
$string['card_completed_color'] = 'Color — Actividades completadas';
$string['card_courses'] = 'Cursos completados';
$string['card_courses_desc'] = 'Tarjeta con los cursos completados sobre el total de cursos.';
$string['card_courses_color'] = 'Color — Cursos completados';
$string['card_messages'] = 'Mensajes sin leer';
$string['card_messages_desc'] = 'Tarjeta con el número de conversaciones no leídas.';
$string['card_messages_color'] = 'Color — Mensajes sin leer';
$string['card_badges'] = 'Insignias recibidas';
$string['card_badges_desc'] = 'Tarjeta con el total de insignias del usuario.';
$string['card_badges_color'] = 'Color — Insignias recibidas';
$string['card_certificates'] = 'Certificados recibidos';
$string['card_certificates_desc'] = 'Tarjeta con los certificados emitidos (requiere mod_customcert).';
$string['card_certificates_color'] = 'Color — Certificados recibidos';
$string['card_streak'] = 'Días de racha (nuevo)';
$string['card_streak_desc'] = 'Tarjeta con los días consecutivos de actividad en la plataforma.';
$string['card_streak_color'] = 'Color — Días de racha';
$string['card_forums'] = 'Participación en foros (nuevo)';
$string['card_forums_desc'] = 'Tarjeta con el número de mensajes en foros publicados este mes.';
$string['card_forums_color'] = 'Color — Participación en foros';
$string['card_timeonline'] = 'Tiempo en la plataforma (nuevo)';
$string['card_timeonline_desc'] = 'Tarjeta con una estimación de horas de actividad este mes.';
$string['card_timeonline_color'] = 'Color — Tiempo en la plataforma';

$string['certurl'] = 'URL de certificados';
$string['certurl_desc'] = 'Dirección a la que enlaza la tarjeta de certificados (p. ej. tu página «Mis Certificados»). Si se deja vacío, la tarjeta no será clicable.';

// Deadlines section.
$string['deadlines_heading'] = 'Próximos vencimientos';
$string['deadlines_heading_desc'] = 'Lista de actividades con fecha de entrega cercana.';
$string['show_deadlines'] = 'Mostrar próximos vencimientos';
$string['show_deadlines_desc'] = 'Muestra una lista con las actividades próximas a vencer.';
$string['deadlines_days'] = 'Días de anticipación';
$string['deadlines_days_desc'] = 'Cuántos días hacia adelante se buscan vencimientos.';
$string['days_suffix'] = 'días';
$string['deadlines_max'] = 'Cantidad máxima a mostrar';
$string['deadlines_max_desc'] = 'Número máximo de vencimientos que se listan.';
$string['deadlines_title'] = 'Próximos vencimientos';
$string['deadlines_empty'] = 'No tienes vencimientos próximos. ¡Buen trabajo!';

// Card labels (front-end).
$string['pending_activities'] = 'Actividades pendientes';
$string['completed_activities'] = 'Actividades completadas';
$string['completed_courses'] = 'Cursos completados';
$string['unread_messages'] = 'Mensajes sin leer';
$string['badgesreceived'] = 'Insignias';
$string['certificatesreceived'] = 'Certificados';
$string['streak_label'] = 'Días de racha';
$string['streak_value'] = '{$a}';
$string['forums_label'] = 'Posts en foros';
$string['timeonline_label'] = 'Tiempo este mes';
$string['timeonline_value'] = '{$a}h';
$string['overdue_badge'] = '{$a} vencidas';

// Deadline relative dates.
$string['due_today'] = 'Vence hoy';
$string['due_tomorrow'] = 'Vence mañana';
$string['due_in_days'] = 'Vence en {$a} días';

// Privacy.
$string['privacy:metadata'] = 'El bloque Mi Panel solo muestra datos existentes del usuario; no almacena información personal propia.';
