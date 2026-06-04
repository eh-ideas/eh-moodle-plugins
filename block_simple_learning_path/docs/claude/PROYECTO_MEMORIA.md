# block_simple_learning_path — Memoria Completa del Proyecto
> **Para Claude:** Este archivo contiene TODO el contexto necesario para continuar el desarrollo de este plugin desde cualquier computadora o sesión nueva. Léelo completo antes de hacer cualquier cambio.

---

## 1. Resumen del Proyecto

**Plugin:** `block_simple_learning_path`
**Tipo:** Bloque personalizado para Moodle (hereda `block_base`)
**Propósito:** Agrupa cursos en rutas de aprendizaje visuales. El alumno ve un stepper/timeline con su progreso en cada curso de la ruta. El administrador gestiona las rutas desde un panel propio.
**Sitio de producción:** https://plataforma2.ehcampus.online/
**Tema Moodle:** `mb2nl` (basado en Boost)
**Versión actual:** v2.0.0 (archivo: `releases/simple_learning_path_v2.zip`)
**Stamp de versión:** `2026052100`
**Moodle mínimo:** 4.1 (requires `2023042400`)
**Compatibilidad PHP:** 7.4 – 8.3

---

## 2. Estructura de Archivos del Plugin

```
simple_learning_path/
├── block_simple_learning_path.php   ← Clase principal del bloque (lógica de datos y rendering)
├── version.php                      ← Metadatos y versión
├── edit.php                         ← Formulario de alta/edición de ruta (admin)
├── delete.php                       ← Eliminación de ruta con confirmación (admin)
├── index.php                        ← Panel listado de rutas (admin)
├── report.php                       ← Página de estadísticas por ruta (admin)
├── edit_form.php                    ← Form de configuración de instancia del bloque (Moodle estándar)
├── settings.php                     ← Redirige desde admin de plugins a index.php
├── styles.css                       ← Todos los estilos (~350 líneas, BEM, CSS custom props)
├── amd/
│   ├── src/
│   │   ├── main.js                  ← Vista alumno: collapse, progress animation, locked courses
│   │   ├── edit_form.js             ← Panel admin: filtro, search, drag&drop cursos, criterios
│   │   ├── admin_list.js            ← Listado admin: search, delete modal, drag&drop filas
│   │   └── report.js                ← Estadísticas: carga Chart.js lazy, renderiza gráficos
│   └── build/
│       ├── main.min.js
│       ├── edit_form.min.js
│       ├── admin_list.min.js
│       └── report.min.js
├── db/
│   ├── install.xml                  ← Schema de BD (tablas + campos + índices)
│   ├── upgrade.php                  ← Migraciones desde v1
│   └── access.php                   ← Capabilities del bloque
├── lang/
│   └── en/
│       └── block_simple_learning_path.php  ← ~40 cadenas de idioma
├── templates/
│   ├── rutas.mustache               ← Vista alumno: journey card + stepper de cursos
│   ├── edit.mustache                ← Formulario admin alta/edición de ruta
│   ├── simple_learning_path.mustache ← Panel admin listado de rutas
│   └── report.mustache             ← Página de estadísticas
└── pix/
    └── placeholder.svg             ← Imagen placeholder para rutas sin imagen
```

---

## 3. Base de Datos

### Tabla: `block_simple_learning_path`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT | PK autoincrement |
| `nombre_ruta` | VARCHAR(255) | Nombre de la ruta |
| `descripcion` | TEXT | Descripción opcional |
| `imagen_url` | VARCHAR(1000) | URL de imagen de portada |
| `url` | VARCHAR(500) | URL destino de la ruta |
| `criterio` | VARCHAR(50) | Criterio de visibilidad (ver abajo) |
| `cohortid` | INT | ID de cohorte (si criterio=cohorte) |
| `criterio_rol` | VARCHAR(50) | Shortname del rol (si criterio=rol) |
| `prerequisito_rutaid` | INT | ID de ruta prerequisito (si criterio=prerequisito) |
| `fecha_inicio` | INT | Timestamp inicio (si criterio=fecha) |
| `fecha_fin` | INT | Timestamp fin (si criterio=fecha) |
| `estado` | INT(1) | 1=publicada, 0=borrador |
| `secuencial` | INT(1) | 1=cursos en orden estricto, 0=libre |
| `sortorder` | INT(10) | Orden de aparición en el bloque |

### Tabla: `block_simple_learning_path_courses`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT | PK autoincrement |
| `learningpathid` | INT | FK → `block_simple_learning_path.id` |
| `courseid` | INT | FK → `course.id` |
| `sortorder` | INT(10) | Orden dentro de la ruta |

### Criterios de visibilidad disponibles

| Valor | Descripción |
|---|---|
| `siempre` | Siempre visible para todos |
| `cursos` | Solo si el usuario está matriculado en TODOS los cursos de la ruta |
| `curso` | Solo si el usuario está matriculado en AL MENOS UNO |
| `cohorte` | Solo si el usuario pertenece al cohortid especificado |
| `fecha` | Solo si la fecha actual está entre fecha_inicio y fecha_fin |
| `rol` | Solo si el usuario tiene el rol especificado (shortname) en el contexto del sitio |
| `prerequisito` | Solo si el usuario completó el 100% de la ruta prerequisito_rutaid |

---

## 4. Arquitectura de la Vista del Alumno

### Flujo de datos en `block_simple_learning_path.php`

```
get_content()
  └── get_learning_paths()            ← todas las rutas publicadas, ordenadas por sortorder
        └── user_meets_criteria()     ← filtra por criterio de visibilidad
              └── (si pasa) get_courses_progress()  ← progreso por curso del usuario
                    └── render_from_template('rutas')
```

### Cálculo de progreso por curso

```php
// Solo cuenta módulos con seguimiento de completación habilitado (cm.completion > 0)
$total_activities = COUNT(course_modules WHERE completion > 0)
$completed_activities = COUNT(course_modules_completion WHERE completionstate = 1)
$progress_pct = round(completed / total * 100)
// Si el curso tiene course_completions.timecompleted → progreso = 100
```

### Estados de curso en el stepper

| Estado | Condición | Clase CSS |
|---|---|---|
| `done` | `$progress >= 100` | `slp-step--done` |
| `active` | Es el primer curso con `progress > 0 && < 100` | `slp-step--active` |
| `locked` | Secuencial y el curso anterior no completado | `slp-step--locked` |
| `pending` | Resto de cursos | `slp-step--pending` |

### Botón "Continuar ruta"

Se genera `continueUrl` apuntando al primer curso que NO sea `done` y NO esté `locked`.

---

## 5. Sistema CSS (styles.css)

- **Nomenclatura:** BEM estricto — `.slp-[bloque]__[elemento]--[modificador]`
- **Variables:** CSS custom properties con fallback a variables del tema Moodle

```css
--slp-primary:    var(--primary, #0f6cbf)
--slp-success:    var(--success, #28a745)
--slp-warning:    var(--warning, #ffc107)
--slp-danger:     var(--danger,  #dc3545)
```

- **Animaciones:** `@keyframes slp-fade-up` activado por `IntersectionObserver` en main.js
- **Accesibilidad:** `@media (prefers-reduced-motion: reduce)` desactiva todas las animaciones
- **Nodos del stepper:** Los estados `done/active/pending/locked` se controlan exclusivamente con clases CSS
- **Anillo SVG de progreso:** Inline en `rutas.mustache`, radio=18, `stroke-dasharray` calculado en PHP

---

## 6. Módulos AMD

### `main.js` — Vista alumno
- `togglePanel(id)` — colapsa/expande la ruta
- `animateProgressBars()` — activa animación con IntersectionObserver
- `initLockedCourses()` — bloquea clicks en cursos locked
- `initContinueButtons()` — scroll suave al primer curso activo
- Sin dependencias de Bootstrap ni jQuery

### `edit_form.js` — Formulario admin
- `filterCoursesByCategory(catId)` — filtra el selector de cursos
- `addCourse(id, name)` / `removeCourse(btn)` — agrega/quita cursos de la ruta
- `initDragDrop()` — drag & drop HTML5 para reordenar cursos en el form
- `toggleCriterioPanel(value)` — muestra/oculta paneles: `#cohort-container`, `#date-container`, `#rol-container`, `#prereq-container`
- Datos de cursos pasados via `data-courses-by-category` en el `<form>`

### `admin_list.js` — Listado admin
- `filterRows(query)` — búsqueda live, actualiza contador
- `initDeleteModal()` — Bootstrap 5 → Bootstrap 4 → `confirm()` nativo (fallback)
- `initDragSort()` — drag & drop de filas de tabla para reordenar rutas
- `saveOrder(order)` — POST fetch a `index.php?action=reorder&sesskey=...`
- `showSaveToast()` — toast de confirmación de guardado (no requiere Bootstrap)

### `report.js` — Estadísticas
- Carga Chart.js 4.4.1 lazy desde CDN `cdnjs.cloudflare.com`
- `renderProgressChart()` — barra horizontal: progreso promedio por curso
- `renderCompletionChart()` — dona: % de completación por curso
- Fallback elegante si falla la carga de CDN

---

## 7. Panel de Administración

### Flujo de páginas admin

```
index.php (listado) → edit.php (alta/edición) → index.php (con mensaje éxito)
                     → delete.php → index.php (con mensaje éxito)
                     → report.php (estadísticas)
```

### AJAX en `index.php`

```
POST /blocks/simple_learning_path/index.php
  action=reorder
  sesskey=XXXX
  order[]=id1&order[]=id2&order[]=id3
→ JSON { success: true }
```

### `edit.php` — campos del formulario

```php
POST: nombre_ruta, descripcion, imagen_url, url, criterio,
      cohortid, criterio_rol, prerequisito_rutaid,
      fecha_inicio_d/m/y, fecha_fin_d/m/y,
      estado, secuencial,
      category_course[]   ← array de IDs de cursos en orden
```

El `sortorder` de cada curso se guarda como el índice del array `category_course`.

---

## 8. Seguridad

| Punto | Implementación |
|---|---|
| Autenticación | `require_login()` en todos los `.php` admin |
| Autorización | `require_capability('moodle/site:manageblocks', $context)` |
| CSRF en acciones POST | `confirm_sesskey()` en `index.php` (reorder), `delete.php`, `edit.php` |
| CSRF en modal delete | `sesskey` pasado via `data-url` al AMD module |
| Parámetros de entrada | `required_param` / `optional_param` con `PARAM_INT`, `PARAM_TEXT`, etc. |
| Sin `console.log` en producción | Eliminados todos los `echo "<script>console.log..."` del bloque |

---

## 9. Historial de Versiones

| Versión | Fecha | Cambios |
|---|---|---|
| v1.0 | 2025 | Versión original: rutas en JSON, sin BD robusta, sin criterios completos |
| v2.0.0 | Mayo 2026 | Reescritura completa: BD con 9 campos nuevos, stepper visual, 7 criterios, panel admin completo, estadísticas con Chart.js, módulos AMD, upgrade.php |

---

## 10. Errores Conocidos y Sus Soluciones

### `edit_form.js` — panel `prereq-container` no se mostraba
- **Causa:** El panel `#prereq-container` no tenía su bloque `display` manejado en `toggleCriterioPanel()`
- **Solución:** Agregar explícitamente en el switch de `toggleCriterioPanel`:
```javascript
var prereqContainer = document.getElementById('prereq-container');
if (prereqContainer) {
    prereqContainer.style.display = (value === 'prerequisito') ? 'block' : 'none';
}
```

### Bootstrap Modal — compatibilidad 4 vs 5
- **Causa:** Moodle 4.1-4.3 usa Bootstrap 4, Moodle 4.4+ usa Bootstrap 5
- **Solución en `admin_list.js`:**
```javascript
if (window.bootstrap && window.bootstrap.Modal) {
    new window.bootstrap.Modal(modalEl).show(); // BS5
} else if (window.jQuery) {
    window.jQuery('#' + cfg.modalId).modal('show'); // BS4
} else {
    if (confirm('¿Eliminar "' + name + '"?')) { window.location.href = url; } // fallback
}
```

### ZIP no se puede crear directamente en la carpeta montada
- **Causa:** Restricción de permisos en el sandbox de Claude Cowork
- **Solución:** Usar Python `zipfile` en lugar de la CLI `zip`:
```python
import zipfile, os
with zipfile.ZipFile(out_path, 'w', zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk(src):
        for file in files:
            ...
```

### Progreso incorrecto por contar módulos sin seguimiento
- **Causa:** La versión anterior contaba TODOS los módulos del curso
- **Solución:** Filtrar solo módulos con `cm.completion > 0` en la consulta SQL

---

## 11. Cómo Hacer el ZIP para Moodle

```python
# Desde Python (recomendado por restricciones de sandbox)
import zipfile, os
src = '/ruta/a/plugin/simple_learning_path'
out = '/ruta/de/salida/simple_learning_path_v2.zip'
with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk(src):
        for file in files:
            if file.startswith('.'): continue
            filepath = os.path.join(root, file)
            arcname = os.path.relpath(filepath, os.path.dirname(src))
            zf.write(filepath, arcname)
```

El ZIP debe contener `simple_learning_path/` como carpeta raíz.
Instalación en Moodle: **Administración del sitio → Plugins → Instalar plugins → subir ZIP**.
Si el plugin ya está instalado, Moodle detecta el cambio de versión y corre `upgrade.php` automáticamente.

---

## 12. Mejoras Futuras Pendientes

- [ ] Idioma español (`lang/es/`) — actualmente solo existe `lang/en/`
- [ ] Vista de progreso global del alumno (todas sus rutas en un solo panel)
- [ ] Notificaciones por email cuando se completa una ruta
- [ ] Soporte para imagen de portada subida via File Manager de Moodle (no solo URL)
- [ ] Configuración por instancia del bloque (qué rutas mostrar, cuántas)
- [ ] Exportación CSV del reporte de estadísticas
- [ ] Cache con `cache::make()` para `get_courses_progress()` (usuarios con muchos cursos)
- [ ] Paginación en el listado admin con muchas rutas (>50)
- [ ] Soporte multi-idioma para `nombre_ruta` y `descripcion`

---

## 13. Comandos Útiles para Desarrollo

```bash
# Verificar que no haya console.log en PHP
grep -rn "console.log" blocks/simple_learning_path/

# Compilar AMD con uglify-js (instalado localmente)
cd /ruta/outputs && npm install uglify-js
./node_modules/.bin/uglifyjs amd/src/main.js -o amd/build/main.min.js
./node_modules/.bin/uglifyjs amd/src/edit_form.js -o amd/build/edit_form.min.js
./node_modules/.bin/uglifyjs amd/src/admin_list.js -o amd/build/admin_list.min.js
./node_modules/.bin/uglifyjs amd/src/report.js -o amd/build/report.min.js

# Verificar structure del ZIP
python3 -c "import zipfile; [print(f.filename) for f in zipfile.ZipFile('simple_learning_path_v2.zip').infolist()]"

# Forzar purga de caché AMD en Moodle (desde admin)
# Administración del sitio → Desarrollo → Purgar caché
```

---

*Generado: Mayo 2026 — Versión activa: v2.0.0*
