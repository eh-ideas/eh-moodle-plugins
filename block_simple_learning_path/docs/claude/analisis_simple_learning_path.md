# Análisis Técnico: `block_simple_learning_path`
> Plugin de tipo Bloque para Moodle 4.1+
> Documento de análisis de la versión original (v1) y diagnóstico previo al desarrollo de v2.0

---

## 1. Problemas Identificados en la Versión Original

### 1.1 Base de datos insuficiente

La v1 del plugin carecía de varios campos clave en la tabla `block_simple_learning_path`:

| Campo faltante | Impacto |
|---|---|
| `descripcion` | No se podía agregar descripción a una ruta |
| `imagen_url` | No había imagen de portada por ruta |
| `estado` | No existía estado borrador/publicado — todas las rutas eran visibles |
| `secuencial` | No había modo de cursos en orden estricto |
| `sortorder` | Las rutas no tenían orden definido — el orden era por ID |
| `criterio_rol` | El criterio "rol" existía como string pero no persistía correctamente |
| `prerequisito_rutaid` | El criterio "prerequisito" existía pero sin FK real |
| `fecha_inicio / fecha_fin` | Los campos de fecha no existían en la v1 |

La tabla `block_simple_learning_path_courses` tampoco tenía `sortorder`, lo que hacía que el orden de los cursos dentro de una ruta dependiera del orden de inserción.

### 1.2 Logs de debug en producción

El archivo `block_simple_learning_path.php` tenía 6 instancias de:
```php
echo "<script>console.log('...');</script>";
```
Esto exponía datos internos en la consola del navegador de cualquier usuario.

### 1.3 Cálculo de progreso incorrecto

La consulta SQL original contaba TODOS los módulos del curso, incluyendo los que no tenían seguimiento de completación habilitado (`cm.completion = 0`). El resultado era un porcentaje artificialmente bajo.

**Corrección v2:**
```sql
-- v1 (incorrecto): cuenta todos los módulos
SELECT COUNT(*) FROM {course_modules} WHERE course = :cid

-- v2 (correcto): solo módulos con seguimiento habilitado
SELECT COUNT(*) FROM {course_modules} WHERE course = :cid AND completion > 0
```

### 1.4 Criterio "prerequisito" no funcional

El panel del criterio `prerequisito` en el formulario de edición (v1) no era mostrado ni ocultado correctamente por el JavaScript inline. El campo se guardaba en BD pero el formulario no lo gestionaba visualmente.

### 1.5 JavaScript inline en Mustache

La plantilla `edit.mustache` en v1 tenía más de 165 líneas de JavaScript inline dentro del bloque `{{#js}}`. Esto:
- Es contrario a las buenas prácticas de Moodle (AMD modules)
- No puede minificarse ni cachearse correctamente
- Mezcla lógica de presentación con comportamiento

### 1.6 Sin confirmación visual en eliminación

El botón eliminar en el panel admin usaba `confirm()` nativo del navegador, que no respeta el estilo del tema Moodle y es bloqueado en algunos contextos.

### 1.7 Sin página de estadísticas

La v1 no tenía ninguna forma de ver métricas de uso: cuántos alumnos acceden a cada ruta, cuál es su progreso promedio, qué cursos tienen mayor completación.

### 1.8 Sin seguridad CSRF en eliminación

El archivo `delete.php` de la v1 no tenía `require_sesskey()`, lo que lo hacía vulnerable a ataques CSRF desde otras páginas.

---

## 2. Inventario de Archivos — v1 vs v2

| Archivo | v1 | v2 | Nota |
|---|---|---|---|
| `block_simple_learning_path.php` | ✅ Existía | ✅ Reescrito | Eliminados console.log, criterios completos, campo estado/secuencial |
| `edit.php` | ✅ Existía | ✅ Reescrito | Nuevos campos, savepoint correcto |
| `delete.php` | ✅ Existía | ✅ Corregido | Añadido require_sesskey() |
| `index.php` | ✅ Existía | ✅ Reescrito | AJAX reorder, criterios con iconos |
| `report.php` | ❌ No existía | ✅ Nuevo | Estadísticas completas |
| `edit_form.php` | ✅ Existía | ✅ Conservado | Form de instancia del bloque (estándar Moodle) |
| `settings.php` | ✅ Existía | ✅ Conservado | Redirige a index.php |
| `styles.css` | ✅ Existía | ✅ Reescrito | BEM, CSS custom props, animaciones |
| `db/install.xml` | ✅ Existía | ✅ Ampliado | 9 campos nuevos + índices |
| `db/upgrade.php` | ❌ No existía | ✅ Nuevo | Migración desde v1 sin perder datos |
| `db/access.php` | ✅ Existía | ✅ Conservado | Sin cambios |
| `lang/en/` | ✅ Existía | ✅ Ampliado | ~25 strings nuevos |
| `templates/rutas.mustache` | ✅ Existía | ✅ Reescrito | Diseño stepper/journey |
| `templates/edit.mustache` | ✅ Existía | ✅ Reescrito | JS extraído a AMD |
| `templates/simple_learning_path.mustache` | ✅ Existía | ✅ Reescrito | Tabla con drag, modal, búsqueda |
| `templates/report.mustache` | ❌ No existía | ✅ Nuevo | KPIs + gráficos + tabla detalle |
| `amd/src/main.js` | ❌ No existía | ✅ Nuevo | Vista alumno pura JS |
| `amd/src/edit_form.js` | Parcial (inline) | ✅ Nuevo AMD | Reemplaza edit_module.js + filtercourses.js |
| `amd/src/admin_list.js` | ❌ No existía | ✅ Nuevo | Listado admin con drag y modal |
| `amd/src/report.js` | ❌ No existía | ✅ Nuevo | Chart.js lazy loading |
| `rutas_de_aprendizaje.json` | ✅ Existía | ❌ Eliminado | Residuo de versión pre-BD |
| `amd/src/edit_module.js` | ✅ Existía | ❌ Eliminado | Reemplazado por edit_form.js |
| `amd/src/filtercourses.js` | ✅ Existía | ❌ Eliminado | Absorbido en edit_form.js |

---

## 3. Análisis del Modelo de Datos

### 3.1 Tablas del core de Moodle utilizadas

#### `{course}` — Cursos
```sql
SELECT id, fullname, shortname FROM {course} WHERE id IN (...)
```

#### `{course_modules}` — Módulos/Actividades del curso
```sql
-- Para calcular progreso: solo módulos con seguimiento habilitado
SELECT COUNT(*) FROM {course_modules}
WHERE course = :cid AND completion > 0 AND deletioninprogress = 0
```

#### `{course_modules_completion}` — Estado de completación por usuario
```sql
SELECT COUNT(*) FROM {course_modules_completion} cmc
JOIN {course_modules} cm ON cmc.coursemoduleid = cm.id
WHERE cm.course = :cid AND cmc.userid = :uid
  AND cm.completion > 0 AND cmc.completionstate = 1
```
⚠️ `completionstate = 1` es "completado", `= 2` es "completado con pase".

#### `{course_completions}` — Completación del curso completo
```sql
SELECT timecompleted FROM {course_completions}
WHERE course = :cid AND userid = :uid
```
Si `timecompleted IS NOT NULL` → el curso está 100% completo aunque no todas las actividades estén marcadas.

#### `{cohort_members}` — Para criterio cohorte
```sql
SELECT id FROM {cohort_members}
WHERE cohortid = :cohortid AND userid = :uid
```

#### `{role_assignments}` y `{role}` — Para criterio rol
```sql
SELECT ra.id FROM {role_assignments} ra
JOIN {role} r ON r.id = ra.roleid
WHERE ra.userid = :uid AND r.shortname = :rol
```

#### `{enrol}`, `{user_enrolments}`, `{role_assignments}` — Para estadísticas
Para identificar alumnos matriculados en cada curso (con rol `student`):
```sql
SELECT DISTINCT ue.userid
FROM {enrol} e
JOIN {user_enrolments} ue ON ue.enrolid = e.id
JOIN {context} ctx ON ctx.instanceid = e.courseid AND ctx.contextlevel = 50
JOIN {role_assignments} ra ON ra.contextid = ctx.id AND ra.userid = ue.userid
JOIN {role} r ON r.id = ra.roleid AND r.shortname = 'student'
WHERE e.courseid IN (...)
```

### 3.2 Edge Cases en BD

| Caso | Problema | Solución implementada |
|---|---|---|
| Usuario sin actividades completadas | `$total_activities = 0` divide por cero | `max($total_activities, 1)` |
| Curso sin completación habilitada | Progreso siempre 0 aunque el alumno haya visto todo | Se muestra 0% correctamente — es una limitación de Moodle |
| `course_completions.timecompleted = NULL` | El curso figura como no completado aunque el alumno lo completó con criterio manual | Se chequea `timecompleted IS NOT NULL` |
| Ruta sin cursos asociados | No debe mostrarse al alumno | `empty($courses)` → se salta la ruta |

---

## 4. Análisis de Seguridad

### 4.1 Superficies de ataque identificadas en v1

| Vulnerabilidad | Archivo | Corrección v2 |
|---|---|---|
| Sin `require_sesskey()` en delete | `delete.php` | Añadido `confirm_sesskey()` |
| Sin `require_sesskey()` en reorder AJAX | `index.php` | Añadido `confirm_sesskey()` |
| `console.log` con datos internos | `block_simple_learning_path.php` | Eliminados los 6 `echo "<script>"` |
| Datos del bloque sin sanitizar en Mustache | `edit.mustache` | Mustache escapa `{{ }}` automáticamente |

### 4.2 Modelo de permisos

```
Alumno (student):
  → Ve rutas que pasan su criterio de visibilidad
  → Lee su propio progreso
  → No puede acceder a edit.php / delete.php / index.php admin

Administrador (manageblocks):
  → Acceso completo a todas las páginas admin
  → Puede crear, editar, eliminar y reordenar rutas
  → Puede ver estadísticas de todos los alumnos
```

### 4.3 Control de acceso en páginas admin

```php
require_login();
$context = context_system::instance();
require_capability('moodle/site:manageblocks', $context);
```

---

## 5. Análisis de Rendimiento

### 5.1 Consultas por carga del bloque

Por cada ruta que pasa el criterio de visibilidad, se ejecutan:
1. 1 consulta para `course_modules` (total actividades)
2. 1 consulta para `course_modules_completion` (actividades completadas)
3. 1 consulta para `course_completions` (completación del curso)

Para un usuario con 3 rutas de 5 cursos cada una: 3 × 5 × 3 = **45 consultas** en el peor caso.

**Recomendación futura:** Implementar caché con `cache::make('block_simple_learning_path', 'progress')` con TTL de 5 minutos para usuarios con muchas rutas.

### 5.2 Carga de Chart.js

En `report.php`, Chart.js se carga **lazy** desde CDN solo cuando el usuario visita la página de estadísticas. No afecta el tiempo de carga del bloque para alumnos.

---

## 6. Análisis de la Vista del Alumno

### 6.1 Diseño original (v1)

Lista plana de cursos con íconos de estado básicos. Sin animaciones, sin estado visual diferenciado por posición en la ruta.

### 6.2 Problemas de UX identificados

- No era claro cuál era el "próximo paso" para el alumno
- No había diferencia visual entre cursos bloqueados y pendientes
- No había un CTA ("Continuar ruta") prominente
- El progreso global de la ruta no era visible de un vistazo

### 6.3 Solución adoptada: Journey Card + Stepper

Diseño inspirado en "learning journeys" de plataformas como LinkedIn Learning y Udemy:

```
[Portada/imagen de la ruta]    [Anillo de progreso SVG]
[Nombre + descripción]         [% global]
[Botón: Continuar ruta]

── Cursos ──
○ Curso 1 ✓ (done)     → check verde, línea verde
◉ Curso 2 (active)     → punto azul, tarjeta resaltada
○ Curso 3 (pending)    → punto gris
🔒 Curso 4 (locked)    → candado, opacidad reducida
```

El **anillo SVG** se calcula en PHP con `stroke-dasharray`:
```php
$circunferencia = 2 * M_PI * 18; // radio = 18
$stroke = ($progress_pct / 100) * $circunferencia;
// → "stroke-dasharray: {$stroke} {$circunferencia}"
```

---

## 7. Decisiones de Diseño Descartadas

| Alternativa | Por qué se descartó |
|---|---|
| Usar `core/ajax` (Moodle AMD AJAX) para el reorder | Complejidad innecesaria; un POST simple con fetch es suficiente y más portable |
| Guardar criterios en una tabla separada | Sobreingeniería para el volumen de criterios actual (7 tipos) |
| Paginación del stepper de cursos | Las rutas típicamente tienen 3-15 cursos — paginación innecesaria |
| `customcert` para certificados de ruta | El plugin no gestiona certificados, solo rutas de cursos |
| React/Vue para la vista del alumno | Moodle no soporta bundlers modernos de fábrica; AMD + Mustache es la stack oficial |

---

*Documento generado: Mayo 2026*
