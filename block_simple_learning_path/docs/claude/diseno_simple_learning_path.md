# Diseño Técnico: `block_simple_learning_path` v2.0
> Decisiones de arquitectura, contratos de templates y sistema visual
> Documento de referencia para continuar el desarrollo

---

## 1. Arquitectura General

### 1.1 Capas del sistema

```
Capa de presentación (Alumno)
  └── block_simple_learning_path.php::get_content()
        └── templates/rutas.mustache
              └── amd/src/main.js

Capa de presentación (Admin)
  ├── index.php    → templates/simple_learning_path.mustache → amd/src/admin_list.js
  ├── edit.php     → templates/edit.mustache               → amd/src/edit_form.js
  ├── delete.php   → (redirect con notificación)
  └── report.php   → templates/report.mustache             → amd/src/report.js

Capa de datos
  ├── block_simple_learning_path (tabla principal)
  └── block_simple_learning_path_courses (relación ruta-curso)
```

### 1.2 Principios de diseño adoptados

1. **AMD puro** — Cero JavaScript inline en templates Mustache. El bloque `{{#js}}` solo contiene el `require([...])` mínimo para inicializar el módulo, pasando configuración como parámetro.

2. **Datos en atributos `data-*`** — El formulario de edición pasa la lista de cursos al AMD via `data-courses-by-category` en el `<form>`, evitando variables JS globales.

3. **CSS custom properties con fallback** — Todos los colores usan variables CSS del tema Moodle con un valor de fallback hardcodeado, garantizando que funcione en cualquier tema basado en Boost.

4. **Sin dependencias externas en el bloque** — La vista del alumno (main.js) no depende de jQuery ni Bootstrap. Las dependencias externas (Chart.js) se cargan lazy solo en la página que las necesita.

---

## 2. Contratos de Templates Mustache

### 2.1 `rutas.mustache` — Vista del alumno

```
Context pasado desde block_simple_learning_path.php::get_content()

routes[]
  id                int     ID de la ruta
  nombre_ruta       string  Nombre
  routeDesc         string  Descripción (puede ser vacía)
  hasDesc           bool
  routeImageUrl     string  URL de imagen (puede ser vacía)
  hasImage          bool
  url               string  URL destino de la ruta
  hasUrl            bool
  globalProgress    int     0-100, progreso global calculado como avg de cursos
  progressOffset    float   stroke-dasharray offset para el anillo SVG
  circunferencia    float   2π×18 = 113.097...
  hasContinue       bool    Hay al menos un curso no completado y no bloqueado
  continueUrl       string  URL del primer curso activo/pendiente
  secuencial        bool    La ruta es secuencial
  isLocked          bool    Primera ruta prereq no completada (toda la ruta bloqueada)
  completedDate     string  Fecha de completación si todos los cursos están done
  isCompleted       bool
  courses[]
    id              int
    fullname        string  Nombre del curso
    courseUrl       string  URL del curso
    progress        int     0-100
    status          string  'done' | 'active' | 'pending' | 'locked'
    isDone          bool
    isActive        bool
    isPending       bool
    isLocked        bool
    completedDate   string  Fecha si status=done
    hasDate         bool
```

### 2.2 `simple_learning_path.mustache` — Admin listado

```
Context pasado desde index.php

createurl         string
reporturl         string
reorder_url       string
sesskey           string
has_paths         bool
learning_paths[]
  id              int
  nombre_ruta     string
  descripcion     string
  hasDesc         bool
  url             string
  hasUrl          bool
  estado          int      1=publicada, 0=borrador
  estado_label    string   'Publicada' | 'Borrador'
  estado_done     bool     true si estado=1
  secuencial      bool
  criterio_label  string   Texto legible del criterio
  criterio_icon   string   Clase fa-* (ej: 'fa-users')
  criterio_color  string   Bootstrap color (primary/success/warning/info/secondary/danger)
  editurl         string
  deleteurl       string   Incluye sesskey en la URL
  cursos[]
    fullname      string
  cursos_count    int
  sortorder       int
```

### 2.3 `edit.mustache` — Admin formulario

```
Context pasado desde edit.php

action_url          string  URL POST del formulario
sesskey             string
is_edit             bool    true = edición, false = alta
path                object  Datos de la ruta (si is_edit)
  id, nombre_ruta, descripcion, imagen_url, url,
  criterio, cohortid, criterio_rol, prerequisito_rutaid,
  fecha_inicio_d, fecha_inicio_m, fecha_inicio_y,
  fecha_fin_d, fecha_fin_m, fecha_fin_y,
  estado, secuencial
courses_by_category []  Lista de categorías con sus cursos para el selector
  id, name, courses[{id, fullname, shortname}]
path_courses []     Cursos ya asociados a la ruta (si is_edit)
  id, fullname
cohorts_available []  Cohortes del sitio
  id, name, selected
roles_available []   Roles del sitio
  shortname, name, selected
otras_rutas []       Otras rutas (para selector de prerequisito)
  id, nombre_ruta, selected
criterio_*          bool     Una por cada criterio (criterio_siempre, criterio_cohorte, etc.)
uniqid              string   Para IDs únicos de elementos del form
```

### 2.4 `report.mustache` — Estadísticas

```
Context pasado desde report.php

pathName            string
pathId              int
indexUrl            string
totalStudents       int
totalCourses        int
avgProgress         int     0-100
pathSelector []
  id, nombre, selected, url
courses []
  courseName          string
  enrolledInCourse    int
  avgProgress         int
  completedCount      int
  completionPct       int   0-100
  dist0_25            int   Cantidad de alumnos en rango 0-25%
  dist26_50           int
  dist51_75           int
  dist76_100          int
hasCourses          bool
chartLabelsJson     string  JSON array de nombres de cursos
chartAvgJson        string  JSON array de promedios
chartDoneJson       string  JSON array de % completación
```

---

## 3. Sistema Visual (CSS)

### 3.1 Variables CSS

```css
:root {
  --slp-primary:   var(--primary,   #0f6cbf);
  --slp-success:   var(--success,   #28a745);
  --slp-warning:   var(--warning,   #ffc107);
  --slp-danger:    var(--danger,    #dc3545);
  --slp-secondary: var(--secondary, #6c757d);
  --slp-radius:    8px;
  --slp-shadow:    0 2px 8px rgba(0,0,0,0.08);
}
```

### 3.2 Paleta de estados del stepper

| Estado | Color nodo | Color línea | Fondo tarjeta |
|---|---|---|---|
| `done` | `--slp-success` (#28a745) | verde | `#f0fff4` |
| `active` | `--slp-primary` (#0f6cbf) | primary | `#f0f7ff` (leve highlight) |
| `pending` | `#dee2e6` (gris claro) | gris punteado | blanco |
| `locked` | `#adb5bd` (gris medio) | gris | blanco, opacidad 0.6 |

### 3.3 Anillo de progreso SVG

El anillo SVG está inline en `rutas.mustache`. Se calcula en PHP:

```php
$radio          = 18;
$circunferencia = 2 * M_PI * $radio; // ≈ 113.097
$fill           = ($globalProgress / 100) * $circunferencia;
$gap            = $circunferencia - $fill;
// → stroke-dasharray="{$fill} {$gap}"
// → stroke-dashoffset="{$circunferencia / 4}" (empezar desde arriba)
```

SVG markup:
```html
<svg viewBox="0 0 44 44" class="slp-ring">
  <circle class="slp-ring__track" cx="22" cy="22" r="18" />
  <circle class="slp-ring__fill"  cx="22" cy="22" r="18"
    stroke-dasharray="{{progressFill}} {{progressGap}}"
    stroke-dashoffset="{{progressOffset}}" />
  <text x="22" y="27" class="slp-ring__text">{{globalProgress}}%</text>
</svg>
```

### 3.4 Animación de entrada

```css
@keyframes slp-fade-up {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}

.slp-path-card {
  animation: slp-fade-up 0.4s ease both;
}

@media (prefers-reduced-motion: reduce) {
  .slp-path-card { animation: none; }
}
```

La animación se activa desde `main.js` via `IntersectionObserver` cuando la tarjeta entra al viewport:

```javascript
var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
        if (entry.isIntersecting) {
            entry.target.classList.add('slp-animate');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.1 });
```

---

## 4. Diseño del Sistema de Criterios

### 4.1 Flujo de evaluación en `user_meets_criteria()`

```
lp.criterio
  'siempre'     → return true
  'cohorte'     → cohort_members WHERE cohortid AND userid
  'cursos'      → ¿está matriculado en TODOS los cursos de la ruta?
  'curso'       → ¿está matriculado en AL MENOS UNO?
  'fecha'       → time() >= fecha_inicio AND time() <= fecha_fin
  'rol'         → role_assignments JOIN role WHERE shortname
  'prerequisito'→ user_completed_path(prerequisito_rutaid, userid)
  default       → return false
```

### 4.2 Implementación de `user_completed_path()`

```php
private function user_completed_path(int $pathid, int $userid): bool {
    // 1. Obtener cursos de la ruta prerequisito
    $courses = $DB->get_records('block_simple_learning_path_courses',
                                ['learningpathid' => $pathid]);
    if (empty($courses)) return false;

    // 2. Verificar que course_completions.timecompleted > 0 para cada curso
    foreach ($courses as $lpc) {
        $completed = $DB->record_exists_select('course_completions',
            'course = :cid AND userid = :uid AND timecompleted > 0',
            ['cid' => $lpc->courseid, 'uid' => $userid]);
        if (!$completed) return false;
    }
    return true;
}
```

### 4.3 UI de criterios en el formulario admin

El formulario tiene 4 paneles condicionales que se muestran/ocultan con `edit_form.js`:

```
select#criterio → onChange → toggleCriterioPanel(value)
  'cohorte'      → show #cohort-container
  'fecha'        → show #date-container
  'rol'          → show #rol-container
  'prerequisito' → show #prereq-container
  otros          → hide todos
```

---

## 5. Diseño del Panel de Estadísticas

### 5.1 Métricas calculadas

**Por ruta:**
- `totalStudents` — alumnos matriculados con rol `student` en al menos 1 curso de la ruta
- `avgProgress` — promedio de los promedios por curso (no es un promedio exacto por alumno — aproximación)
- `totalCourses` — cantidad de cursos en la ruta

**Por curso:**
- `enrolledInCourse` — alumnos con rol `student` matriculados en ese curso
- `avgProgress` — promedio de `(actividades completadas / total actividades con seguimiento) × 100`
- `completedCount` — alumnos con `course_completions.timecompleted IS NOT NULL`
- `completionPct` — `completedCount / enrolledInCourse × 100`
- `dist0_25`, `dist26_50`, `dist51_75`, `dist76_100` — distribución del progreso en 4 rangos

### 5.2 Limitación conocida en `avgProgress` global

La métrica global de progreso es una aproximación:

```php
// Para cada alumno, se suma el avgProgress de cada curso (no el progreso real del alumno)
// Esto subestima el progreso real cuando los alumnos tienen distribuciones asimétricas
$total_avg_progress = sum(user_pct_sum) / total_students
```

**Nota para v3:** Para precisión real, habría que calcular el progreso por alumno en cada curso y luego promediar. Esto requeriría una consulta adicional por alumno.

### 5.3 Gráficos Chart.js

| Gráfico | Tipo | Datos |
|---|---|---|
| Progreso promedio por curso | Barra horizontal (`indexAxis: 'y'`) | `chartAvgJson` |
| % de completación por curso | Dona (`cutout: '60%'`) | `chartDoneJson` |

Chart.js se carga lazy desde CDN:
```
https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js
```

---

## 6. Diseño del Drag & Drop

### 6.1 En el listado admin (`admin_list.js`)

Permite reordenar las filas de la tabla de rutas. Usa la HTML5 Drag and Drop API:

```javascript
// Datos arrastrados: 'text/plain' con el ID de la ruta
dragstart → store el elemento
dragover  → mover visualmente en el DOM
drop      → confirmar posición
dragend   → calcular nuevo orden y llamar saveOrder()
```

`saveOrder()` hace un POST a `index.php`:
```javascript
fetch(reorderUrl + '?action=reorder&sesskey=' + sesskey, {
    method: 'POST',
    body: new URLSearchParams({ 'order[]': [id1, id2, id3] })
})
```

### 6.2 En el formulario de cursos (`edit_form.js`)

Permite reordenar la lista de cursos dentro de una ruta mientras se edita. El orden final se guarda al hacer submit del form — `category_course[]` se envía en el orden del DOM, y PHP guarda el índice como `sortorder`.

---

## 7. Diseño del Sistema de Upgrade

### 7.1 Estrategia de migración

El archivo `db/upgrade.php` utiliza el sistema estándar de Moodle:

```php
function xmldb_block_simple_learning_path_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026052100) {
        // Agregar cada campo nuevo con $dbman->add_field()
        // Inicializar sortorder desde ID existente
        // Salvar savepoint
        upgrade_block_savepoint(true, 2026052100, 'simple_learning_path');
    }
    return true;
}
```

La función `upgrade_block_savepoint()` requiere el tercer parámetro sin el prefijo `block_`:
```php
upgrade_block_savepoint(true, 2026052100, 'simple_learning_path');
//                                         ↑ NO 'block_simple_learning_path'
```

### 7.2 Campos migrados con valor inicial

| Campo | Valor inicial asignado en upgrade |
|---|---|
| `estado` | `1` (todas las rutas existentes quedan publicadas) |
| `secuencial` | `0` (libre — no rompe rutas existentes) |
| `sortorder` | `id` (las rutas existentes quedan ordenadas por su ID original) |
| Cursos `sortorder` | `id` de la relación (mismo criterio) |

---

## 8. Consideraciones de Compatibilidad Moodle

| Feature | Moodle 4.1 | Moodle 4.4+ | Solución |
|---|---|---|---|
| Bootstrap 4 (jQuery modal) | ✅ | ❌ | Detectar `window.bootstrap.Modal` vs `jQuery().modal` |
| AMD `define()` | ✅ | ✅ | Usado en todos los módulos |
| Mustache `{{#js}}` | ✅ | ✅ | Solo para el `require()` mínimo |
| CSS Custom Properties | ✅ (Boost) | ✅ | Con fallback hardcodeado |
| `context_system::instance()` | ✅ | ✅ | Sin cambios |
| `render_from_template()` | ✅ | ✅ | Sin cambios |
| IntersectionObserver | ✅ (Chrome 51+) | ✅ | Con fallback: animar todo si no está disponible |

---

## 9. Strings de Idioma — Referencia Completa

Archivo: `lang/en/block_simple_learning_path.php`

```php
// Core
$string['pluginname']            = 'Learning Path';
$string['pluginmenu']            = 'Learning Paths';
$string['simple_learning_path:addinstance']   = 'Add Learning Path block';
$string['simple_learning_path:myaddinstance'] = 'Add Learning Path block to My Moodle';

// Rutas
$string['nombre_ruta']           = 'Route name';
$string['descripcion_ruta']      = 'Description';
$string['imagen_url']            = 'Cover image URL';
$string['estado_publicada']      = 'Published';
$string['estado_borrador']       = 'Draft';
$string['modo_secuencial']       = 'Sequential mode';
$string['secuencial_label']      = 'Students must complete courses in order';

// Criterios
$string['criterio']              = 'Visibility criterion';
$string['criterio_option_siempre']   = 'Always visible';
$string['criterio_option_cursos']    = 'All path courses (enrolled in all)';
$string['criterio_option_curso']     = 'Any path course (enrolled in at least one)';
$string['criterio_option_cohorte']   = 'Cohort';
$string['criterio_option_fecha']     = 'Date range';
$string['criterio_option_rol']       = 'Role';
$string['criterio_option_prereq']    = 'Prerequisite path';
$string['fecha_inicio']          = 'Start date';
$string['fecha_fin']             = 'End date';
$string['select_rol']            = 'Select role';
$string['select_prereq_ruta']    = 'Select prerequisite path';

// Vista alumno
$string['continue_route']        = 'Continue route';
$string['course_locked']         = 'Complete the previous course to unlock this one';

// Admin
$string['create_path_title']     = 'Create learning path';
$string['edit_path_title']       = 'Edit learning path';
$string['path_deleted']          = 'Learning path deleted successfully';
```

---

*Documento generado: Mayo 2026*
