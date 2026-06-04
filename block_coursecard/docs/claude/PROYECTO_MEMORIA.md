# block_coursecard — Memoria Completa del Proyecto
> **Para Claude:** Este archivo contiene TODO el contexto necesario para continuar el desarrollo de este proyecto desde cualquier computadora. Léelo completo antes de hacer cualquier cambio.

---

## 1. Resumen del Proyecto

**Plugin:** `block_coursecard`  
**Tipo:** Bloque personalizado para Moodle (hereda `block_base`)  
**Propósito:** Reemplaza el bloque estándar "Vista general del curso" con tarjetas visuales modernas tipo dashboard  
**Sitio de producción:** https://plataforma2.ehcampus.online/my/index.php  
**Tema Moodle:** `mb2nl` (basado en Boost — requiere `!important` agresivo en CSS)  
**Versión actual:** v7 (archivo: `block_coursecard_v7.zip`)  
**Moodle mínimo:** 4.1+ (requires 2022112800)  
**Compatibilidad:** Moodle 4.1 – 4.9

---

## 2. Estructura de Archivos del Plugin

```
block_coursecard/
├── block_coursecard.php          ← Clase principal del bloque
├── version.php                   ← Metadatos del plugin
├── styles.css                    ← Todos los estilos CSS
├── classes/
│   └── output/
│       ├── main.php              ← CRÍTICO: toda la lógica de datos
│       └── renderer.php          ← Renderer que llama a main.mustache
├── templates/
│   ├── main.mustache             ← Grid contenedor de tarjetas
│   └── coursecard.mustache       ← Tarjeta individual de curso
├── lang/
│   ├── es/block_coursecard.php   ← Cadenas en español
│   └── en/block_coursecard.php   ← Cadenas en inglés
└── db/
    └── access.php                ← Capabilities del bloque
```

---

## 3. REGLA CRÍTICA — Constantes de Moodle en Namespaces

> **NUNCA usar constantes globales de Moodle dentro de código PHP con namespace.**  
> Incluso con el prefijo `\` pueden fallar con "Undefined constant" en algunos servidores.

| Constante Moodle | Reemplazar con |
|---|---|
| `\DAY_SECS` | `$day = 86400;` (variable local) |
| `\SITEID` | `global $SITE; $SITE->id` |
| `\COMPLETION_TRACKING_NONE` | `(int)$cm->completion === 0` |
| `\COMPLETION_COMPLETE` | `1` en array `[1, 2]` |
| `\COMPLETION_COMPLETE_PASS` | `2` en array `[1, 2]` |
| `\IGNORE_MULTIPLE` en `get_record()` | `$DB->get_records(..., 0, 1); $cert = reset($certs);` |

Este error causó un fatal en v5 (primer intento): `Undefined constant 'DAY_SECS'`.

---

## 4. Código Completo de Archivos Clave

### 4.1 version.php
```php
<?php
defined('MOODLE_INTERNAL') || die();
$plugin->component = 'block_coursecard';
$plugin->version   = 2024010700;
$plugin->requires  = 2022112800; // Moodle 4.1+
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.7.0';
```

### 4.2 block_coursecard.php
```php
<?php
defined('MOODLE_INTERNAL') || die();
class block_coursecard extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_coursecard');
    }
    public function get_content() {
        global $USER;
        if ($this->content !== null) { return $this->content; }
        $this->content = new stdClass();
        $renderable = new \block_coursecard\output\main($USER->id);
        $renderer   = $this->page->get_renderer('block_coursecard');
        $this->content->text   = $renderer->render($renderable);
        $this->content->footer = '';
        return $this->content;
    }
    public function applicable_formats() {
        return ['my' => true, 'site-index' => false, 'course-view' => false];
    }
}
```

### 4.3 classes/output/renderer.php
```php
<?php
namespace block_coursecard\output;
defined('MOODLE_INTERNAL') || die();
use plugin_renderer_base;
class renderer extends plugin_renderer_base {
    public function render_main(main $page): string {
        $data = $page->export_for_template($this);
        return $this->render_from_template('block_coursecard/main', $data);
    }
}
```

### 4.4 classes/output/main.php (COMPLETO — versión correcta sin constantes)
```php
<?php
namespace block_coursecard\output;
defined('MOODLE_INTERNAL') || die();
use renderable; use renderer_base; use templatable;
use context_course; use completion_info; use moodle_url;

class main implements renderable, templatable {
    private $userid;
    public function __construct(int $userid) { $this->userid = $userid; }

    public function export_for_template(renderer_base $output): array {
        global $DB, $SITE;
        $courses     = enrol_get_my_courses(null, 'fullname ASC');
        $coursesdata = [];
        foreach ($courses as $course) {
            if ($course->id == $SITE->id) { continue; }
            $coursecontext  = context_course::instance($course->id);
            $completioninfo = new completion_info($course);
            $courseimage    = $this->get_course_image($course, $coursecontext);
            $progress = null;
            if ($completioninfo->is_enabled()) {
                $raw = \core_completion\progress::get_course_progress_percentage($course, $this->userid);
                if ($raw !== null) { $progress = (int) floor($raw); }
            }
            [$completed, $total] = $this->get_activities_count($course, $completioninfo);
            $lastaccess    = $DB->get_field('user_lastaccess', 'timeaccess',
                ['userid' => $this->userid, 'courseid' => $course->id]);
            $status        = $this->get_course_status($progress, $completed, $total);
            $instructor    = $this->get_course_instructor($coursecontext);
            $certinfo      = $this->get_certificate_info($course, $status);
            $deadline      = $this->get_upcoming_deadline($course);
            $categoryname  = $DB->get_field('course_categories', 'name', ['id' => $course->category]);
            $fallbackclass = $this->get_fallback_class($course->category);
            $fallbackicon  = $this->get_fallback_icon($course->id);
            $coursesdata[] = [
                'id'              => $course->id,
                'fullname'        => format_string($course->fullname),
                'courseurl'       => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(),
                'courseimage'     => $courseimage,
                'hascourseimage'  => !empty($courseimage),
                'fallbackclass'   => $fallbackclass,
                'fallbackicon'    => $fallbackicon,
                'categoryname'    => $categoryname,
                'progress'        => $progress,
                'hasprogress'     => $progress !== null,
                'progresswidth'   => $progress ?? 0,
                'iscomplete100'   => $progress !== null && $progress >= 100,
                'completedactivities' => $completed,
                'totalactivities'     => $total,
                'lastaccess'      => $lastaccess ? $this->format_last_access($lastaccess) : null,
                'haslastaccess'   => !empty($lastaccess),
                'status'          => $status,
                'isinprogress'    => $status === 'inprogress',
                'iscompleted'     => $status === 'completed',
                'isnotstarted'    => $status === 'notstarted',
                'instructor'      => $instructor,
                'hasinstructor'   => !empty($instructor),
                'hascertificate'  => $certinfo['hascertificate'],
                'nocertificate'   => !$certinfo['hascertificate'],
                'certificateurl'  => $certinfo['url'],
                'candownloadcert' => $certinfo['candownload'],
                'deadline'        => $deadline,
                'hasdeadline'     => !empty($deadline),
            ];
        }
        return ['courses' => $coursesdata, 'hascourses' => !empty($coursesdata), 'uniqid' => uniqid('cc-')];
    }

    private function get_course_image($course, context_course $coursecontext): ?string {
        $fs    = get_file_storage();
        $files = $fs->get_area_files($coursecontext->id, 'course', 'overviewfiles', false, 'filename', false);
        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                return moodle_url::make_pluginfile_url(
                    $file->get_contextid(), $file->get_component(), $file->get_filearea(),
                    null, $file->get_filepath(), $file->get_filename())->out();
            }
        }
        return null;
    }

    private function get_activities_count($course, completion_info $completioninfo): array {
        if (!$completioninfo->is_enabled()) { return [0, 0]; }
        $modinfo = get_fast_modinfo($course);
        $total = 0; $completed = 0;
        foreach ($modinfo->get_cms() as $cm) {
            if ((int)$cm->completion === 0 || $cm->deletioninprogress) { continue; }
            $total++;
            $data = $completioninfo->get_data($cm, false, $this->userid);
            if (in_array((int)$data->completionstate, [1, 2], true)) { $completed++; }
        }
        return [$completed, $total];
    }

    private function get_course_status(?int $progress, int $completed, int $total): string {
        if ($progress === null) {
            if ($completed === 0) { return 'notstarted'; }
            return $completed >= $total ? 'completed' : 'inprogress';
        }
        if ($progress >= 100) { return 'completed'; }
        if ($progress > 0)    { return 'inprogress'; }
        return 'notstarted';
    }

    private function get_course_instructor(context_course $coursecontext): ?array {
        $roles = ['editingteacher', 'teacher'];
        foreach ($roles as $role) {
            $roleid = $this->get_role_id($role);
            if (!$roleid) { continue; }
            $teachers = get_enrolled_users($coursecontext, '', 0, 'u.id, u.firstname, u.lastname', null, 0, 1);
            $teachers = array_filter($teachers, function($u) use ($coursecontext, $roleid) {
                return user_has_role_assignment($u->id, $roleid, $coursecontext->id);
            });
            if (!empty($teachers)) {
                $t = reset($teachers);
                return ['fullname' => $t->firstname . ' ' . $t->lastname,
                        'initials' => strtoupper(substr($t->firstname, 0, 1) . substr($t->lastname, 0, 1))];
            }
        }
        return null;
    }

    private function get_certificate_info($course, string $status): array {
        global $DB;
        $result = ['hascertificate' => false, 'url' => null, 'candownload' => false];
        $pluginman = \core_plugin_manager::instance();
        if (!array_key_exists('customcert', $pluginman->get_plugins_of_type('mod'))) { return $result; }
        $certs = $DB->get_records('customcert', ['course' => $course->id], 'id ASC', 'id', 0, 1);
        $cert  = reset($certs);
        if (!$cert) { return $result; }
        $result['hascertificate'] = true;
        if ($status === 'completed') {
            $cm = get_coursemodule_from_instance('customcert', $cert->id, $course->id);
            if ($cm) {
                $result['url']         = (new moodle_url('/mod/customcert/view.php', ['id' => $cm->id]))->out();
                $result['candownload'] = true;
            }
        }
        return $result;
    }

    private function get_upcoming_deadline($course): ?array {
        global $DB;
        $day    = 86400;
        $now    = time();
        $future = $now + (7 * $day);
        $sql = "SELECT a.name, a.duedate FROM {assign} a
                  JOIN {course_modules} cm ON cm.instance = a.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                 WHERE a.course = :courseid AND a.duedate > :now AND a.duedate < :future
                   AND cm.visible = 1 AND cm.deletioninprogress = 0
              ORDER BY a.duedate ASC LIMIT 1";
        $assign = $DB->get_record_sql($sql, ['courseid' => $course->id, 'now' => $now, 'future' => $future]);
        if (!$assign) { return null; }
        $days = (int) ceil(($assign->duedate - $now) / $day);
        return [
            'name'         => shorten_text(format_string($assign->name), 40),
            'daysleft'     => $days,
            'dayslefttext' => $days <= 1 ? get_string('tomorrow', 'block_coursecard')
                                         : get_string('daysleft', 'block_coursecard', $days),
            'isurgent'     => $days <= 2,
        ];
    }

    private function format_last_access(int $timestamp): string {
        $day  = 86400;
        $diff = time() - $timestamp;
        if ($diff < $day)     { return get_string('today',         'block_coursecard'); }
        if ($diff < 2 * $day) { return get_string('yesterday',     'block_coursecard'); }
        $days = (int) floor($diff / $day);
        if ($days < 7)        { return get_string('daysago',       'block_coursecard', $days); }
        $weeks = (int) floor($days / 7);
        if ($weeks < 5)       { return get_string('weeksago',      'block_coursecard', $weeks); }
        return get_string('morethanmonth', 'block_coursecard');
    }

    private function get_fallback_class(int $categoryid): string {
        $classes = ['nl-fallback--blue','nl-fallback--green','nl-fallback--purple','nl-fallback--teal','nl-fallback--amber'];
        return $classes[$categoryid % count($classes)];
    }

    private function get_fallback_icon(int $courseid): string {
        $icons = ['fa-graduation-cap','fa-book','fa-code','fa-flask','fa-bar-chart','fa-cogs',
                  'fa-globe','fa-pencil','fa-music','fa-rocket','fa-lightbulb-o','fa-university'];
        return $icons[$courseid % count($icons)];
    }

    private function get_role_id(string $shortname): ?int {
        global $DB;
        return $DB->get_field('role', 'id', ['shortname' => $shortname]) ?: null;
    }
}
```

### 4.5 templates/main.mustache
```mustache
{{! @template block_coursecard/main — Grid raíz }}
<div class="block-coursecard">
    {{#hascourses}}
    <div class="nl-grid" id="{{uniqid}}">
        {{#courses}}
        {{> block_coursecard/coursecard }}
        {{/courses}}
    </div>
    {{/hascourses}}
    {{^hascourses}}
    <div class="nl-empty">
        <p>{{#str}}nocourses, block_coursecard{{/str}}</p>
    </div>
    {{/hascourses}}
</div>
```

### 4.6 templates/coursecard.mustache (completo — v7)
```mustache
{{! @template block_coursecard/coursecard }}
<div class="nl-card nl-card--{{status}}">
    <a href="{{courseurl}}"
       class="nl-card__banner {{^hascourseimage}}nl-card__banner--fallback {{fallbackclass}}{{/hascourseimage}}"
       {{#hascourseimage}}style="background-image: url('{{courseimage}}');"{{/hascourseimage}}
       tabindex="-1" aria-hidden="true">
        <div class="nl-card__banner-overlay"></div>
        {{^hascourseimage}}
        <div class="nl-card__banner-icon" aria-hidden="true">
            <i class="fa {{fallbackicon}}"></i>
        </div>
        {{/hascourseimage}}
        <div class="nl-card__banner-badge">
            {{#isinprogress}}
            <span class="nl-badge nl-badge--inprogress">
                <i class="fa fa-clock-o" aria-hidden="true"></i>
                {{#str}}inprogress, block_coursecard{{/str}}
            </span>
            {{/isinprogress}}
            {{#iscompleted}}
            <span class="nl-badge nl-badge--completed">
                <i class="fa fa-check-circle" aria-hidden="true"></i>
                {{#str}}completed, block_coursecard{{/str}}
            </span>
            {{/iscompleted}}
            {{#isnotstarted}}
            <span class="nl-badge nl-badge--notstarted">
                <i class="fa fa-circle-o" aria-hidden="true"></i>
                {{#str}}notstarted, block_coursecard{{/str}}
            </span>
            {{/isnotstarted}}
        </div>
    </a>
    <div class="nl-card__body">
        <h3 class="nl-card__title">
            <a href="{{courseurl}}">{{fullname}}</a>
        </h3>
        <div class="nl-card__meta">
            <span class="nl-card__category">
                <i class="fa fa-folder-o" aria-hidden="true"></i>
                {{categoryname}}
            </span>
            {{#hascertificate}}
            <span class="nl-cert-pill nl-cert-pill--yes">
                <i class="fa fa-certificate" aria-hidden="true"></i>
                {{#str}}certificate, block_coursecard{{/str}}
            </span>
            {{/hascertificate}}
            {{#nocertificate}}
            <span class="nl-cert-pill nl-cert-pill--no">
                <i class="fa fa-ban" aria-hidden="true"></i>
                {{#str}}nocertificate, block_coursecard{{/str}}
            </span>
            {{/nocertificate}}
        </div>
        {{#hasinstructor}}
        <div class="nl-card__instructor">
            <div class="nl-avatar" aria-hidden="true">{{instructor.initials}}</div>
            <span>{{instructor.fullname}}</span>
        </div>
        {{/hasinstructor}}
        {{#hasprogress}}
        <div class="nl-progress">
            <div class="nl-progress__header">
                <span class="nl-progress__label">{{#str}}progress, block_coursecard{{/str}}</span>
                <span class="nl-progress__pct {{#iscomplete100}}nl-progress__pct--complete{{/iscomplete100}}">
                    {{progress}}%
                </span>
            </div>
            <div class="nl-progress__track" role="progressbar"
                 aria-valuenow="{{progress}}" aria-valuemin="0" aria-valuemax="100">
                <div class="nl-progress__fill {{#iscomplete100}}nl-progress__fill--complete{{/iscomplete100}}"
                     style="width: {{progresswidth}}%;"></div>
            </div>
        </div>
        {{/hasprogress}}
        {{#hasdeadline}}
        <div class="nl-strip {{#deadline.isurgent}}nl-strip--urgent{{/deadline.isurgent}}">
            <i class="fa fa-calendar-times-o" aria-hidden="true"></i>
            <span>{{#str}}deadline, block_coursecard{{/str}}:
                <strong>{{deadline.name}}</strong> — {{deadline.dayslefttext}}</span>
        </div>
        {{/hasdeadline}}
        {{#iscompleted}}
            {{#candownloadcert}}
            <div class="nl-strip nl-strip--cert">
                <i class="fa fa-certificate" aria-hidden="true"></i>
                <span>{{#str}}certready, block_coursecard{{/str}}</span>
            </div>
            <a href="{{courseurl}}" class="nl-btn nl-btn--completed" style="margin-bottom:6px;">
                <i class="fa fa-eye" aria-hidden="true"></i>
                {{#str}}reviewcontent, block_coursecard{{/str}}
            </a>
            <a href="{{certificateurl}}" class="nl-btn nl-btn--certificate" target="_blank" rel="noopener noreferrer">
                <i class="fa fa-download" aria-hidden="true"></i>
                {{#str}}downloadcertificate, block_coursecard{{/str}}
            </a>
            {{/candownloadcert}}
            {{^candownloadcert}}
            <div class="nl-strip nl-strip--success">
                <i class="fa fa-trophy" aria-hidden="true"></i>
                <span>{{#str}}congratulations, block_coursecard{{/str}}</span>
            </div>
            <a href="{{courseurl}}" class="nl-btn nl-btn--completed">
                <i class="fa fa-eye" aria-hidden="true"></i>
                {{#str}}reviewcontent, block_coursecard{{/str}}
            </a>
            {{/candownloadcert}}
        {{/iscompleted}}
        {{#isinprogress}}
        <a href="{{courseurl}}" class="nl-btn nl-btn--inprogress">
            {{#str}}continuecourse, block_coursecard{{/str}}
        </a>
        {{/isinprogress}}
        {{#isnotstarted}}
        <a href="{{courseurl}}" class="nl-btn nl-btn--notstarted">
            {{#str}}startcourse, block_coursecard{{/str}}
        </a>
        {{/isnotstarted}}
    </div>
</div>
```

---

## 5. Sistema de Colores (v7)

| Estado | Borde lateral | Badge texto | Barra de progreso | % color | Botón CTA |
|---|---|---|---|---|---|
| En progreso | `#E07B00` naranja | `#FFD08A` | `#E07B00` | `#E07B00` | `#E07B00` naranja |
| Completado | `#1D9E75` verde | `#9FE1CB` | `#1D9E75` | `#0F6E56` | `#0F6E56` verde oscuro |
| Sin iniciar | `#6366F1` índigo | `#C7C5FF` | `#6366F1` | `#4F46E5` | `#4F46E5` índigo |
| Certificado (botón) | — | — | — | — | `#534AB7` violeta |

**Track de barra:** siempre `#dde1e7` (gris claro, forzado con `!important` para ignorar dark mode del sistema)

---

## 6. Historial Completo de Versiones

| Versión | Fecha | Cambios principales |
|---|---|---|
| v1-v3 | 2024 | Estructura base, primer diseño funcional |
| v4 | 2024 | Rediseño completo: banner, badges, progreso, instructor, deadline, cert |
| v5 (fallido) | 2024 | Fatal: `Undefined constant 'DAY_SECS'` por uso de constantes Moodle en namespace |
| v5 (fix) | 2024 | Reescritura main.php sin constantes. Añade: título 17px, indigo para notstarted, badges opacos, íconos fallback |
| v6 | 2025 | Botones "Ver curso" + "Descargar cert" en completados. Quita chip de último acceso. Título 20px |
| v7 | Mayo 2026 | Sistema de colores naranja/verde/índigo. Quita chip actividades. Título 23px. Track de barra gris claro. % con color por estado |

---

## 7. Errores Conocidos y Sus Soluciones

### Error: `Undefined constant 'DAY_SECS'`
- **Causa:** Constantes globales de Moodle no disponibles en namespace PHP
- **Solución:** Usar `$day = 86400;` como variable local
- **Dónde puede aparecer:** `get_upcoming_deadline()` y `format_last_access()` en `main.php`

### Error: ZIP no se puede sobrescribir en el mismo directorio
- **Causa:** `zip` no puede escribir en un archivo que ya existe en la carpeta actual
- **Solución:** Construir en `/tmp/` y luego copiar:
  ```bash
  cd /ruta/outputs && zip -r /tmp/block_coursecard_vX.zip block_coursecard/ && cp /tmp/block_coursecard_vX.zip .
  ```

### CSS ignorado por el tema mb2nl
- **Causa:** El tema Boost y mb2nl tienen alta especificidad CSS
- **Solución:** Usar `!important` en prácticamente todas las reglas de layout y color

### Dark mode aplica estilos no deseados
- **Síntoma:** El track de la barra de progreso aparece negro en lugar de gris claro
- **Solución:** Agregar `!important` al `background` del `.nl-progress__track` para anular el `@media (prefers-color-scheme: dark)`

---

## 8. Cadenas de Idioma Requeridas

Archivo: `lang/es/block_coursecard.php`

```php
$string['pluginname']          = 'Mis Cursos';
$string['coursecard:addinstance'] = 'Agregar bloque Mis Cursos';
$string['coursecard:myaddinstance'] = 'Agregar bloque Mis Cursos al dashboard';
$string['nocourses']           = 'No estas inscrito en ningun curso.';
$string['inprogress']          = 'En progreso';
$string['completed']           = 'Completado';
$string['notstarted']          = 'Sin iniciar';
$string['progress']            = 'Progreso del curso';
$string['activities']          = 'actividades';
$string['lastaccess']          = 'Ultimo acceso';
$string['today']               = 'Hoy';
$string['yesterday']           = 'Ayer';
$string['daysago']             = 'Hace {$a} dias';
$string['weeksago']            = 'Hace {$a} semanas';
$string['morethanmonth']       = 'Hace mas de un mes';
$string['certificate']         = 'Certificado';
$string['nocertificate']       = 'Sin certificado';
$string['hascertificate']      = 'Este curso otorga certificado';
$string['certready']           = 'Tu certificado esta listo para descargar';
$string['downloadcertificate'] = 'Descargar certificado';
$string['reviewcontent']       = 'Revisar contenido';
$string['continuecourse']      = 'Continuar curso';
$string['startcourse']         = 'Comenzar curso';
$string['congratulations']     = 'Felicitaciones! Completaste este curso.';
$string['deadline']            = 'Entrega proxima';
$string['tomorrow']            = 'Manana';
$string['daysleft']            = 'En {$a} dias';
```

---

## 9. Cómo Empaquetar el ZIP

```bash
# Desde la carpeta outputs (donde está block_coursecard/)
cd /ruta/a/outputs
zip -r /tmp/block_coursecard_v8.zip block_coursecard/
cp /tmp/block_coursecard_v8.zip .
```

El ZIP debe contener `block_coursecard/` como raíz — no su contenido directamente.

---

## 10. Mejoras Futuras Pendientes

- [ ] Filtros por estado (tabs: Todos / En progreso / Completados / Sin iniciar)
- [ ] Ordenamiento configurable (por acceso, por progreso, por nombre)
- [ ] Configuración del bloque (número de columnas, campos visibles/ocultos)
- [ ] Soporte para campos personalizados de curso (custom fields)
- [ ] Paginación para usuarios con muchos cursos (>20)
- [ ] Vista compacta (lista) vs vista de tarjetas
- [ ] Icono personalizado por categoría (en lugar de determinístico por ID)
- [ ] Mostrar nota/calificación del curso en la tarjeta

---

## 11. Comandos Útiles para Desarrollo

```bash
# Verificar que no haya constantes de Moodle en main.php
grep -n "\\\\DAY_SECS\|\\\\SITEID\|\\\\COMPLETION_\|\\\\IGNORE_MULTIPLE" main.php
# → debe devolver vacío (CLEAN)

# Construir ZIP
cd outputs && zip -r /tmp/block_coursecard_v8.zip block_coursecard/ && cp /tmp/block_coursecard_v8.zip .

# Ver estructura del ZIP
unzip -l block_coursecard_v8.zip | head -30
```

---

*Generado: Mayo 2026 — Última versión activa: v7*
