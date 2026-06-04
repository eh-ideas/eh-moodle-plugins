# Análisis Técnico: `block_academichistory`
> Plugin de tipo Bloque para Moodle 4.3+  
> Documento de análisis previo al desarrollo — Versión 1.0

---

## 1. Evaluación de la Arquitectura Propuesta

### 1.1 Tipo de Plugin: `block` ✅ Correcto

Un bloque (`block`) es la elección correcta porque:
- Se instala en cualquier página del dashboard o de curso.
- Puede mostrarse en el **Dashboard** (`/my`) del estudiante, que es el contexto natural para un historial personal.
- La separación **bloque liviano (resumen) + `view.php` (pantalla completa)** es el patrón estándar de Moodle (lo usan `block_recentlyaccessedcourses`, `block_timeline`, etc.).

### 1.2 Punto Crítico: Contexto del Bloque

El bloque debe definir explícitamente en qué páginas puede añadirse. Si solo se añade en el Dashboard (`/my`), el contexto es `context_user`. Si se permite en páginas de curso, el contexto es `context_course`. Esta distinción afecta directamente al control de acceso.

**Recomendación:** Limitar el bloque al Dashboard del usuario (`my-index`) para que el contexto sea siempre el del usuario autenticado. Esto simplifica la seguridad enormemente.

```php
// En block_academichistory.php
public function applicable_formats(): array {
    return ['my' => true, 'site' => false, 'course' => false];
}
```

---

## 2. Inventario Completo de Archivos

### 2.1 Archivos Mencionados en el Diseño Inicial

| Archivo | Estado | Observación |
|---|---|---|
| `version.php` | ✅ Necesario | Correcto |
| `block_academichistory.php` | ✅ Necesario | Correcto |
| `lang/en/block_academichistory.php` | ✅ Necesario | Correcto |
| `lang/es/block_academichistory.php` | ✅ Necesario | Correcto |
| `view.php` | ✅ Necesario | Correcto |
| `templates/history_table.mustache` | ✅ Necesario | Correcto |

### 2.2 Archivos FALTANTES (Obligatorios para Moodle 4.3)

| Archivo | Urgencia | Motivo |
|---|---|---|
| `db/access.php` | 🔴 CRÍTICO | Sin este archivo el plugin no puede definir capacidades. Moodle lo exige. |
| `privacy/provider.php` | 🔴 CRÍTICO | Obligatorio desde Moodle 3.3 (RGPD). Sin él, el plugin falla en la verificación de privacidad. |
| `classes/output/history_table.php` | 🟡 IMPORTANTE | Clase `renderable`+`templatable`. Es la forma correcta de pasar datos a Mustache en Moodle 4.x. |
| `lib.php` | 🟡 IMPORTANTE | Puede estar vacío pero es convención tenerlo para hooks futuros. |
| `db/install.xml` | 🟢 OPCIONAL | Solo si el plugin crea sus propias tablas. En este diseño, no es necesario. |
| `settings.php` | 🟢 OPCIONAL | Para configuración de admin (ej: elegir qué columnas mostrar). Recomendado para el futuro. |

### 2.3 Estructura Final de Archivos

```
blocks/academichistory/
├── block_academichistory.php       # Clase principal del bloque
├── version.php                      # Metadatos y versión
├── view.php                         # Página de historial completo
├── lib.php                          # Funciones globales del plugin (puede estar vacío)
├── db/
│   └── access.php                   # Definición de capacidades
├── lang/
│   ├── en/
│   │   └── block_academichistory.php
│   └── es/
│       └── block_academichistory.php
├── classes/
│   └── output/
│       └── history_table.php        # Clase renderable/templatable
├── templates/
│   └── history_table.mustache       # Plantilla de la tabla
└── privacy/
    └── provider.php                 # API de privacidad (RGPD)
```

---

## 3. Análisis del Modelo de Datos

### 3.1 Tablas Involucradas

#### `{course_completions}` — Fuente principal
| Campo | Tipo | Uso |
|---|---|---|
| `userid` | INT | FK → usuario |
| `course` | INT | FK → `{course}.id` |
| `timecompleted` | INT | Timestamp Unix de finalización (**puede ser NULL** si la finalización fue manual sin fecha) |
| `timeenrolled` | INT | Fecha de inscripción |
| `timestarted` | INT | Fecha de inicio |

⚠️ **Problema identificado:** `timecompleted` puede ser `NULL` o `0` incluso en registros marcados como completados. Hay que filtrar con `timecompleted > 0` o con el campo `status` de la tabla `{course_completion_criteria_compl}`.

La forma más segura de saber si un curso está completado es verificar `timecompleted IS NOT NULL AND timecompleted > 0`.

#### `{course}` — Datos del curso
| Campo | Tipo | Uso |
|---|---|---|
| `id` | INT | PK |
| `fullname` | VARCHAR | Nombre completo del curso |
| `shortname` | VARCHAR | Nombre corto |
| `visible` | TINYINT | ¿El curso está visible? |

⚠️ **Problema identificado:** Se deben mostrar solo cursos con `visible = 1` a menos que el usuario tenga la capacidad `moodle/course:viewhiddencourses`.

#### `{grade_items}` — Ítems de calificación
| Campo | Tipo | Uso |
|---|---|---|
| `id` | INT | PK |
| `courseid` | INT | FK → `{course}.id` |
| `itemtype` | VARCHAR | Filtrar por `'course'` para obtener la nota final |
| `itemnumber` | INT | Debe ser `0` para el ítem de curso (la nota raíz) |
| `grademax` | DECIMAL | Nota máxima (generalmente 100) |
| `grademin` | DECIMAL | Nota mínima (generalmente 0) |
| `scaleid` | INT | Si usa escala en vez de número |

⚠️ **Problema identificado:** Si `scaleid` no es NULL, la nota es cualitativa (escala). No se puede mostrar como número directamente; hay que resolver el valor de la escala en `{scale}`.

#### `{grade_grades}` — Calificaciones del usuario
| Campo | Tipo | Uso |
|---|---|---|
| `itemid` | INT | FK → `{grade_items}.id` |
| `userid` | INT | FK → usuario |
| `finalgrade` | DECIMAL | **Nota efectiva** (puede ser NULL si no hay nota) |
| `rawgrade` | DECIMAL | Nota bruta antes de ajuste |
| `overridden` | INT | Si la nota fue sobreescrita manualmente |
| `excluded` | INT | Si la nota está excluida del promedio |

⚠️ **Problema identificado:** `finalgrade` puede ser `NULL`. Mostrar como "—" o "Sin calificación" en ese caso.

#### `{modules}` — Registro de módulos instalados
| Campo | Tipo | Uso |
|---|---|---|
| `id` | INT | PK |
| `name` | VARCHAR | Ej: `'customcert'`, `'quiz'`, `'assign'` |

Usado para obtener el `id` del módulo `customcert` y hacer el JOIN con `{course_modules}`.

#### `{course_modules}` — Instancias de módulos en cursos
| Campo | Tipo | Uso |
|---|---|---|
| `id` | INT | PK = **cmid** (el que va en la URL) |
| `course` | INT | FK → `{course}.id` |
| `module` | INT | FK → `{modules}.id` |
| `instance` | INT | ID del registro en la tabla del módulo (ej: `{customcert}.id`) |
| `visible` | TINYINT | Si el módulo está visible |

#### `{customcert}` — Instancias del plugin customcert
| Campo | Tipo | Uso |
|---|---|---|
| `id` | INT | PK |
| `course` | INT | FK → `{course}.id` |
| `name` | VARCHAR | Nombre del certificado |

⚠️ **Problema crítico:** Esta tabla **puede no existir** si el plugin `customcert` no está instalado. Toda consulta a esta tabla debe estar protegida por una verificación previa.

#### `{customcert_issues}` — Certificados emitidos
| Campo | Tipo | Uso |
|---|---|---|
| `id` | INT | PK |
| `userid` | INT | FK → usuario |
| `customcertid` | INT | FK → `{customcert}.id` |
| `timecreated` | INT | Cuándo se emitió |

⚠️ **Mismo problema:** Tabla condicional, solo existe si `customcert` está instalado.

---

## 4. Análisis de la Consulta SQL Principal

### 4.1 Consulta Propuesta (Problema del Diseño Original)

El diseño original propone una **sola consulta SQL** que une todas las tablas, incluyendo `customcert`. Esto tiene un problema grave:

> Si `customcert` no está instalado, la tabla `{customcert}` no existe y la consulta fallará con un error SQL fatal.

### 4.2 Estrategia Correcta: Consulta Condicional

**Paso 1:** Verificar si `customcert` está instalado antes de construir la consulta.

```php
$customcert_installed = $DB->get_manager()->table_exists('customcert') 
    && $DB->get_manager()->table_exists('customcert_issues');
```

**Paso 2:** Construir la consulta base (siempre se ejecuta):

```sql
SELECT
    c.id          AS courseid,
    c.fullname    AS coursename,
    cc.timecompleted,
    gg.finalgrade,
    gi.grademax,
    gi.scaleid
FROM {course_completions} cc
JOIN {course} c
    ON c.id = cc.course
LEFT JOIN {grade_items} gi
    ON gi.courseid = c.id
    AND gi.itemtype = 'course'
    AND gi.itemnumber = 0
LEFT JOIN {grade_grades} gg
    ON gg.itemid = gi.id
    AND gg.userid = cc.userid
WHERE cc.userid = :userid
  AND cc.timecompleted IS NOT NULL
  AND cc.timecompleted > 0
  AND c.visible = 1
ORDER BY cc.timecompleted DESC
```

**Paso 3:** Si `customcert` está instalado, hacer una **segunda consulta separada** para obtener los cmid de certificados por curso:

```sql
SELECT
    cm.course    AS courseid,
    cm.id        AS cmid,
    ci.id        AS issueid
FROM {course_modules} cm
JOIN {modules} m
    ON m.id = cm.module AND m.name = 'customcert'
JOIN {customcert} cert
    ON cert.id = cm.instance
LEFT JOIN {customcert_issues} ci
    ON ci.customcertid = cert.id AND ci.userid = :userid
WHERE cm.course IN (/* lista de courseids del paso 2 */)
  AND cm.visible = 1
```

Esta separación en dos consultas es más segura y más fácil de mantener.

### 4.3 Gestión de Escalas de Calificación

Si `gi.scaleid IS NOT NULL`, la calificación usa una escala cualitativa. La nota en `gg.finalgrade` es el índice (número entero) en la tabla `{scale}`. Para mostrar el valor textual:

```sql
LEFT JOIN {scale} sc ON sc.id = gi.scaleid
```

Y el campo `sc.scale` contiene una cadena separada por comas como `"Insuficiente,Suficiente,Bien,Notable,Sobresaliente"`. Se necesita PHP para extraer el valor por índice.

**Recomendación:** Para la v1.0, mostrar `"Escala"` o el índice numérico. Implementar la resolución completa en v1.1.

---

## 5. Análisis de Seguridad

### 5.1 En `view.php`

```php
require_login();           // Obliga autenticación
require_sesskey();         // SOLO si hay acciones POST (no aplica aquí, es lectura)
```

El `$userid` que se consulta **debe** ser siempre `$USER->id` a menos que:
- Se pase por parámetro GET: `$userid = optional_param('userid', $USER->id, PARAM_INT);`
- Y se verifique la capacidad: `require_capability('moodle/user:viewalldetails', $context)` para que solo admins puedan ver el historial de otros usuarios.

Para la v1.0, usar solo `$USER->id` y no admitir `userid` por parámetro.

### 5.2 Capacidades a Definir en `db/access.php`

```php
// Capacidad para que el bloque pueda añadirse al Dashboard personal
'block/academichistory:myaddinstance' => [
    'captype'      => 'write',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes'   => ['user' => CAP_ALLOW],
],
// Capacidad para que administradores puedan añadirlo a páginas de curso
'block/academichistory:addinstance' => [
    'captype'      => 'write',
    'contextlevel' => CONTEXT_BLOCK,
    'archetypes'   => [
        'editingteacher' => CAP_ALLOW,
        'manager'        => CAP_ALLOW,
    ],
],
// Capacidad para ver el historial propio
'block/academichistory:view' => [
    'captype'      => 'read',
    'contextlevel' => CONTEXT_SYSTEM,
    'archetypes'   => ['student' => CAP_ALLOW, 'user' => CAP_ALLOW],
],
```

### 5.3 En el Bloque `get_content()`

Verificar que el contexto es correcto antes de mostrar datos:

```php
if (!isloggedin() || isguestuser()) {
    return null; // No mostrar nada a usuarios no autenticados
}
```

---

## 6. Privacy API (Obligatorio en Moodle 4.x)

Este plugin **no almacena datos propios**, solo los lee de tablas de core y de `customcert`. Por eso, el `provider.php` implementa la interfaz `null_provider` (la más simple), documentando que los datos se gestionan por el core de Moodle:

```php
namespace block_academichistory\privacy;
use core_privacy\local\metadata\null_provider;

class provider implements null_provider {
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
```

Y en el archivo de idioma agregar la cadena:
```php
$string['privacy:metadata'] = 'El bloque Historial Académico no almacena datos personales propios. Solo muestra datos gestionados por el núcleo de Moodle.';
```

---

## 7. Patrón Renderable/Templatable (Moodle 4.x Best Practice)

En lugar de preparar el array de datos directamente en `view.php`, la forma correcta es crear una clase en `classes/output/history_table.php`:

```
Flujo de datos:
view.php
  → instancia history_table (renderable)
    → $OUTPUT->render_from_template('block_academichistory/history_table', $data)
      → history_table.mustache
```

La clase `history_table` implementa:
- `\renderable` — indica que puede ser renderizada por el output renderer.
- `\templatable` — requiere el método `export_for_template(renderer_base $output)` que devuelve el array de datos para Mustache.

Este patrón tiene varias ventajas:
- La lógica de preparación de datos queda encapsulada y es testeable.
- `view.php` queda limpio y solo maneja el setup de la página.
- Si en el futuro se necesita un renderer personalizado, solo se modifica la clase.

---

## 8. Análisis de la Plantilla Mustache

### 8.1 Clases Bootstrap Correctas para Moodle 4.3

Moodle 4.x usa Bootstrap 4 (no Bootstrap 5). Las clases correctas son:

```html
<table class="table table-striped table-hover table-bordered table-sm generaltable">
```

La clase `generaltable` es CSS nativo de Moodle y garantiza la integración visual correcta.

Para que la tabla sea responsiva en móviles:
```html
<div class="table-responsive">
    <table class="table table-striped table-hover generaltable">
```

### 8.2 Internacionalización en Mustache

Los strings de idioma se deben renderizar **desde PHP** antes de pasar a Mustache (no usar `{{#str}}` en tablas complejas ya que requiere el helper de strings que puede no estar disponible en todos los contextos). Se recomienda pasar los strings ya traducidos como parte del objeto de datos:

```php
// En export_for_template()
'str_course'     => get_string('course'),
'str_date'       => get_string('completiondate', 'block_academichistory'),
'str_grade'      => get_string('grade'),
'str_certificate'=> get_string('certificate', 'block_academichistory'),
'str_download'   => get_string('download'),
'str_na'         => get_string('na', 'block_academichistory'),
```

### 8.3 Sanitización de Datos en Mustache

- `{{ variable }}` — escapa HTML automáticamente (para texto plano: nombres, fechas).
- `{{{ variable }}}` — NO escapa HTML (para enlaces HTML preformateados).

Se recomienda generar el HTML de los enlaces (`<a href="...">`) en PHP usando `html_writer::link()` y `moodle_url`, y pasarlos a Mustache con triple bigote `{{{ }}}`.

---

## 9. Edge Cases Identificados

| Caso | Comportamiento esperado | Solución técnica |
|---|---|---|
| Usuario sin cursos completados | Mostrar mensaje vacío amigable | Condición `{{#has_courses}}` / `{{^has_courses}}` en Mustache |
| `timecompleted` es NULL o 0 | No mostrar ese registro | Filtro `WHERE cc.timecompleted > 0` en SQL |
| `finalgrade` es NULL | Mostrar "—" | Lógica en `export_for_template()` |
| Nota con escala cualitativa | Mostrar indicador "Escala" | Verificar `scaleid IS NOT NULL` |
| `customcert` no instalado | Columna "Certificado" muestra "N/A" | `$DB->get_manager()->table_exists()` |
| Curso tiene >1 instancia customcert | Mostrar el primer cmid activo | `LIMIT 1` o `ORDER BY cm.id ASC LIMIT 1` en la subconsulta |
| Usuario con muchos cursos (>50) | Tiempo de carga aceptable | Paginación (recomendada desde v1.0) |
| Curso completado pero ya no visible | No mostrarlo | `AND c.visible = 1` en SQL |
| Usuario invitado (guest) | No mostrar el bloque | `isguestuser()` check en `get_content()` |
| Error de BD (tabla inexistente) | No romper la página | `try/catch` en la clase de output |

---

## 10. Consideraciones de Rendimiento

La consulta principal involucra múltiples JOINs. Para un usuario típico con 5-20 cursos completados, el rendimiento será excelente. Sin embargo:

- Los campos `course_completions.userid`, `grade_grades.userid`, `grade_grades.itemid` ya tienen índices en Moodle core → la consulta es eficiente.
- Si se implementa la consulta de `customcert`, agregar `WHERE cm.course IN (...)` con los IDs obtenidos en el primer paso evita un full table scan.
- **No se recomienda caché en v1.0** (innecesaria para la escala esperada), pero se puede agregar en v2.0 con `cache::make('block_academichistory', 'completions')` y un `ttl` de 5-10 minutos.

---

## 11. Gaps y Mejoras al Diseño Original

### Problemas Técnicos a Corregir

1. **Falta `db/access.php`** → Sin capacidades definidas, el bloque no puede añadirse.
2. **Falta `privacy/provider.php`** → El plugin fallará la verificación de cumplimiento de privacidad.
3. **Consulta SQL con `customcert` directamente** → Debe ser condicional.
4. **No se filtra `timecompleted > 0`** → Puede mostrar cursos "completados" sin fecha.
5. **No se verifica `c.visible = 1`** → Puede mostrar cursos ocultos.
6. **No se maneja `scaleid`** → Cursos con notas de escala mostrarán un número sin sentido.

### Mejoras Recomendadas para v1.0

1. **Paginación:** Usar `$PAGE->perpage` o un parámetro fijo de 20 registros con `LIMIT/OFFSET`.
2. **Ordenamiento:** Permitir ordenar por fecha o por calificación (parámetro GET `sort`).
3. **Columna de Categoría:** Agregar `{course_categories}.name` al JOIN para mostrar la categoría del curso.
4. **Estado del certificado:** Diferenciar entre "Certificado disponible pero no obtenido" vs "No tiene certificado".

### Mejoras para v2.0

- Soporte para múltiples idiomas en el nombre del curso (tablas de traducción de Moodle).
- Exportación a PDF del historial usando `moodlelib.php` o una librería externa.
- Configuración de admin para elegir qué columnas mostrar.
- Vista para profesores/administradores con selector de estudiante.

---

## 12. Versión y Compatibilidad

```php
// version.php
$plugin->version   = 2024010100;       // YYYYMMDDVV
$plugin->requires  = 2023100900;       // Moodle 4.3 (build date)
$plugin->component = 'block_academichistory';
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '1.0.0';
```

**Tabla de compatibilidad:**

| Moodle | Versión interna | PHP mínimo | Soporte |
|---|---|---|---|
| 4.3 | 2023100900 | PHP 8.0 | ✅ Target |
| 4.4 | 2024042200 | PHP 8.1 | ✅ |
| 4.5 | 2024100700 | PHP 8.1 | ✅ |
| 5.0 | 2025040000 | PHP 8.2 | ✅ (verificar deprecaciones) |

---

## 13. Resumen de Decisiones de Diseño

| Decisión | Opción Elegida | Alternativa Descartada | Motivo |
|---|---|---|---|
| Datos de certificado | 2 consultas separadas | 1 sola consulta con JOIN | Seguridad ante `customcert` no instalado |
| Patrón de renderizado | `renderable`+`templatable` | Array directo en `view.php` | Estándar Moodle 4.x, testeable |
| Strings en Mustache | Pasar strings desde PHP | Usar `{{#str}}` helper | Mayor compatibilidad y control |
| Contexto del bloque | Solo Dashboard (`/my`) | Dashboard + páginas de curso | Simplifica seguridad y contexto |
| Privacy API | `null_provider` | `plugin\provider` completo | El plugin no genera datos propios |
| Notas en escala | Mostrar "Escala" (v1.0) | Resolver valor textual | Complejidad diferida a v2.0 |
| Paginación | Implementada en v1.0 | Sin paginación | Robustez con muchos cursos |

---

*Documento generado como base para el desarrollo del plugin. Pendiente validación por el desarrollador antes de proceder al diseño de contratos de datos.*
