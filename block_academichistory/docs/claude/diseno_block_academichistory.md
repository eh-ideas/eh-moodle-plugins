# Diseño Técnico: `block_academichistory`
> Contratos de datos, flujos y responsabilidades por archivo  
> Documento de diseño previo al desarrollo — Versión 1.0

---

## 1. Flujo General de la Aplicación

```
Dashboard del usuario (/my)
│
├── [Bloque visible] block_academichistory.php
│     └── get_content()
│           ├── Cuenta cursos completados del $USER->id  (1 query liviana)
│           └── Renderiza: "Cursos finalizados: X" + botón → view.php
│
└── [Clic en botón] /blocks/academichistory/view.php
      ├── require_login()
      ├── Setup de página ($PAGE)
      ├── Instancia history_table ($userid = $USER->id)
      │     ├── Query 1: cursos + fechas + notas
      │     ├── Query 2 (condicional): certificados por curso
      │     └── export_for_template() → array de datos
      └── $OUTPUT->render_from_template('block_academichistory/history_table', $data)
            └── history_table.mustache → HTML final
```

---

## 2. Contratos de Datos por Archivo

---

### 2.1 `version.php`

**Responsabilidad:** Metadatos del plugin. Sin lógica.

**Variables que define:**

```
$plugin->version    = 2024010100        (int)   YYYYMMDDVV
$plugin->requires   = 2023100900        (int)   Moodle 4.3 mínimo
$plugin->component  = 'block_academichistory'  (string)
$plugin->maturity   = MATURITY_STABLE   (const)
$plugin->release    = '1.0.0'           (string)
```

**Dependencias declaradas:**
- Ninguna obligatoria.
- `customcert` es opcional → NO va en `$plugin->dependencies` (causaría error si no está instalado).

---

### 2.2 `db/access.php`

**Responsabilidad:** Declarar las capacidades del plugin.

**Estructura del array `$capabilities`:**

```
block/academichistory:myaddinstance
  captype:      'write'
  contextlevel: CONTEXT_SYSTEM
  archetypes:   student => CAP_ALLOW
                user    => CAP_ALLOW

block/academichistory:addinstance
  captype:      'write'
  contextlevel: CONTEXT_BLOCK
  archetypes:   editingteacher => CAP_ALLOW
                manager        => CAP_ALLOW

block/academichistory:view
  captype:      'read'
  contextlevel: CONTEXT_SYSTEM
  archetypes:   student => CAP_ALLOW
                user    => CAP_ALLOW
```

---

### 2.3 `lang/en/block_academichistory.php` y `lang/es/block_academichistory.php`

**Responsabilidad:** Strings de idioma. Sin lógica.

**Inventario completo de strings necesarios:**

| Clave (`$string[key]`) | EN | ES |
|---|---|---|
| `pluginname` | Academic History | Historial Académico |
| `academichistory:view` | View academic history | Ver historial académico |
| `academichistory:addinstance` | Add a new Academic History block | Agregar bloque de Historial Académico |
| `academichistory:myaddinstance` | Add Academic History to my dashboard | Agregar Historial Académico a mi panel |
| `viewfullhistory` | View full history | Ver historial completo |
| `completedcourses` | Completed courses | Cursos finalizados |
| `nocompletedcourses` | You have no completed courses yet. | Aún no tienes cursos finalizados. |
| `course` | Course | Curso |
| `completiondate` | Completion Date | Fecha de Finalización |
| `finalgrade` | Final Grade | Calificación Final |
| `certificate` | Certificate | Certificado |
| `download` | Download | Descargar |
| `na` | N/A | N/D |
| `gradenotavailable` | Not graded | Sin calificación |
| `gradescale` | Scale | Escala |
| `certificateissued` | Download certificate | Descargar certificado |
| `certificateavailable` | Certificate available | Certificado disponible |
| `certificatenotissued` | Not obtained | No obtenido |
| `pagetitle` | Academic History | Historial Académico |
| `privacy:metadata` | The Academic History block does not store any personal data. It only displays data managed by Moodle core. | El bloque Historial Académico no almacena datos personales propios. Solo muestra datos gestionados por el núcleo de Moodle. |

---

### 2.4 `block_academichistory.php`

**Responsabilidad:** Clase principal del bloque. Muestra el resumen en el widget lateral.

**Clase:** `block_academichistory extends block_base`

**Métodos a implementar:**

```
init()
  → $this->title = get_string('pluginname', 'block_academichistory')
  → Sin retorno

applicable_formats() : array
  → Retorna: ['my' => true]
  → Limita el bloque solo al Dashboard personal

get_content() : stdClass|null
  → Guarda en $this->content (caché de Moodle)
  → Si isguestuser() || !isloggedin() → return null
  → Hace 1 query COUNT para obtener total de cursos completados
  → Construye $this->content->text con:
      - Párrafo: "Cursos finalizados: {n}"
      - Botón enlace → view.php
  → $this->content->footer = ''
  → Retorna $this->content
```

**Query liviana del bloque (COUNT):**

```sql
SELECT COUNT(cc.id)
FROM {course_completions} cc
JOIN {course} c ON c.id = cc.course
WHERE cc.userid = :userid
  AND cc.timecompleted IS NOT NULL
  AND cc.timecompleted > 0
  AND c.visible = 1
```

**URL del botón:**
```
new moodle_url('/blocks/academichistory/view.php')
```
(No necesita parámetros en v1.0 porque solo se muestra el historial del $USER actual)

---

### 2.5 `view.php`

**Responsabilidad:** Página de pantalla completa. Solo setup de página + instanciar la clase + renderizar.

**Flujo de ejecución:**

```
1. require('../../../config.php')
2. require_login()                          — fuerza autenticación
3. $context = context_system::instance()
4. require_capability('block/academichistory:view', $context)
5. $PAGE->set_context($context)
6. $PAGE->set_url(new moodle_url('/blocks/academichistory/view.php'))
7. $PAGE->set_title(get_string('pagetitle', 'block_academichistory'))
8. $PAGE->set_heading($SITE->fullname)
9. $PAGE->set_pagelayout('standard')
10. $page   = optional_param('page', 0, PARAM_INT)    — para paginación
11. $perpage = 20                                       — constante
12. $table  = new \block_academichistory\output\history_table($USER->id, $page, $perpage)
13. echo $OUTPUT->header()
14. echo $OUTPUT->heading(get_string('pagetitle', 'block_academichistory'))
15. echo $OUTPUT->render_from_template(
        'block_academichistory/history_table',
        $table->export_for_template($OUTPUT)
    )
16. echo $OUTPUT->footer()
```

**Parámetros GET aceptados:**

| Parámetro | Tipo | Default | Uso |
|---|---|---|---|
| `page` | PARAM_INT | 0 | Página actual para paginación |

---

### 2.6 `classes/output/history_table.php`

**Responsabilidad:** Toda la lógica de negocio: consultas SQL, transformación de datos, preparación para Mustache.

**Namespace:** `block_academichistory\output`

**Clase:** `history_table implements \renderable, \templatable`

**Constructor:**

```
__construct(int $userid, int $page = 0, int $perpage = 20)
  → Almacena: $this->userid, $this->page, $this->perpage
  → NO ejecuta queries aquí (lazy loading)
```

**Métodos privados:**

```
get_completions() : array
  → Ejecuta Query Principal (ver sección 3.1)
  → Retorna array de stdClass con campos:
      courseid, coursename, courseurl, timecompleted,
      finalgrade, grademax, scaleid

get_certificates(array $courseids) : array
  → Si customcert no instalado → retorna []
  → Ejecuta Query de Certificados (ver sección 3.2)
  → Retorna array indexado por courseid:
      [courseid => ['cmid' => int, 'issued' => bool]]

format_grade(float|null $finalgrade, float $grademax, int|null $scaleid) : string
  → Si $scaleid != null → return get_string('gradescale', 'block_academichistory')
  → Si $finalgrade === null → return get_string('gradenotavailable', 'block_academichistory')
  → Calcula porcentaje: round(($finalgrade / $grademax) * 100, 1) . '%'
  → Retorna string formateado

format_certificate(int $courseid, array $certs, renderer_base $output) : array
  → Si no hay certificado para $courseid →
      return ['label' => get_string('na', ...), 'has_link' => false, 'link' => '']
  → Si hay certificado pero no emitido →
      return ['label' => get_string('certificatenotissued', ...), 'has_link' => false, 'link' => '']
  → Si hay certificado emitido →
      $url = new moodle_url('/mod/customcert/view.php', ['id' => $certs[$courseid]['cmid']])
      return ['label' => get_string('download', ...), 'has_link' => true, 'link' => $url->out(false)]
```

**Método público principal:**

```
export_for_template(\renderer_base $output) : array
  → Llama get_completions() con LIMIT/OFFSET según $page y $perpage
  → Extrae array de courseids del resultado
  → Llama get_certificates($courseids)
  → Itera sobre completions y construye array $rows:
      [
        'coursename'      => string (nombre del curso),
        'courseurl'       => string (URL al curso),
        'completiondate'  => string (fecha formateada d/m/Y),
        'grade'           => string (resultado de format_grade()),
        'certificate'     => array  (resultado de format_certificate()),
      ]
  → Obtiene total de registros para paginación (query COUNT separado)
  → Construye paginación con $OUTPUT->paging_bar()
  → Retorna array completo (ver sección 3.3)
```

---

### 2.7 `privacy/provider.php`

**Responsabilidad:** Declarar que el plugin no almacena datos personales propios.

**Clase:** `provider implements \core_privacy\local\metadata\null_provider`

**Un solo método:**
```
get_reason() : string
  → return 'privacy:metadata'
```

---

### 2.8 `lib.php`

**Responsabilidad:** Archivo de hooks globales. Vacío en v1.0, presente por convención.

---

## 3. Definición de las Queries SQL

### 3.1 Query Principal: Completions + Notas

**Propósito:** Obtener todos los cursos completados del usuario con su fecha y calificación.

**Tablas:** `course_completions`, `course`, `grade_items`, `grade_grades`

```sql
SELECT
    c.id            AS courseid,
    c.fullname      AS coursename,
    cc.timecompleted,
    gg.finalgrade,
    gi.grademax,
    gi.scaleid

FROM {course_completions} cc

JOIN {course} c
    ON  c.id = cc.course
    AND c.visible = 1

LEFT JOIN {grade_items} gi
    ON  gi.courseid  = c.id
    AND gi.itemtype  = 'course'
    AND gi.itemnumber = 0

LEFT JOIN {grade_grades} gg
    ON  gg.itemid = gi.id
    AND gg.userid = cc.userid

WHERE cc.userid          = :userid
  AND cc.timecompleted  IS NOT NULL
  AND cc.timecompleted   > 0

ORDER BY cc.timecompleted DESC

LIMIT  :limit
OFFSET :offset
```

**Parámetros:** `['userid' => $userid, 'limit' => $perpage, 'offset' => $page * $perpage]`

**Resultado:** Array de `stdClass`, un objeto por curso completado.

**Notas importantes sobre esta query:**
- `LEFT JOIN` en `grade_items` y `grade_grades` → un curso puede no tener notas configuradas.
- `gi.itemnumber = 0` → garantiza que tomamos solo el ítem de nota del curso completo, no de actividades individuales.
- `LIMIT/OFFSET` para paginación.

---

### 3.2 Query de Certificados (Condicional)

**Propósito:** Obtener el `cmid` de customcert por curso y si el usuario ya lo tiene emitido.

**Pre-condición:** Solo se ejecuta si `$DB->get_manager()->table_exists('customcert')` retorna `true`.

**Tablas:** `course_modules`, `modules`, `customcert`, `customcert_issues`

```sql
SELECT
    cm.course       AS courseid,
    cm.id           AS cmid,
    cert.id         AS certid,
    cert.name       AS certname,
    ci.id           AS issueid

FROM {course_modules} cm

JOIN {modules} m
    ON  m.id   = cm.module
    AND m.name = 'customcert'

JOIN {customcert} cert
    ON  cert.id = cm.instance

LEFT JOIN {customcert_issues} ci
    ON  ci.customcertid = cert.id
    AND ci.userid       = :userid

WHERE cm.course IN (:courseids)
  AND cm.visible = 1

ORDER BY cm.id ASC
```

**Parámetros:** `['userid' => $userid]` + lista de courseids via `$DB->get_in_or_equal()`

**Procesamiento del resultado en PHP:**
```
Para cada fila del resultado:
  Si el courseid ya existe en el array resultado:
    → Ignorar (solo usamos el primer certificado por curso)
  Si no existe:
    → $certs[$row->courseid] = [
          'cmid'   => $row->cmid,
          'issued' => ($row->issueid !== null),
      ]
```

**Resultado:** Array indexado por `courseid`.

---

### 3.3 Contrato de Datos para Mustache (`export_for_template` → plantilla)

Este es el array exacto que recibirá `history_table.mustache`:

```json
{
  "pagetitle":       "Historial Académico",
  "has_courses":     true,
  "courses": [
    {
      "coursename":     "Introducción a la Programación",
      "courseurl":      "https://moodle.ejemplo.com/course/view.php?id=5",
      "completiondate": "15/03/2024",
      "grade":          "87.5%",
      "certificate": {
        "has_link": true,
        "label":    "Descargar",
        "link":     "https://moodle.ejemplo.com/mod/customcert/view.php?id=23"
      }
    },
    {
      "coursename":     "Diseño Web",
      "courseurl":      "https://moodle.ejemplo.com/course/view.php?id=8",
      "completiondate": "02/11/2023",
      "grade":          "Sin calificación",
      "certificate": {
        "has_link": false,
        "label":    "N/D",
        "link":     ""
      }
    }
  ],
  "paging_bar":      "<div class='paging...'>...</div>",
  "str_course":      "Curso",
  "str_date":        "Fecha de Finalización",
  "str_grade":       "Calificación Final",
  "str_certificate": "Certificado",
  "str_no_courses":  "Aún no tienes cursos finalizados."
}
```

**Tipos de datos en el contrato:**

| Campo | Tipo | Nullable | Notas |
|---|---|---|---|
| `pagetitle` | string | No | Ya traducido |
| `has_courses` | bool | No | Controla sección vacía en Mustache |
| `courses` | array | No | Vacío si no hay cursos |
| `courses[].coursename` | string | No | Nombre completo |
| `courses[].courseurl` | string | No | URL absoluta, ya generada con `moodle_url` |
| `courses[].completiondate` | string | No | Formateado `d/m/Y` en PHP |
| `courses[].grade` | string | No | Ya formateado: porcentaje, "Sin calificación", "Escala" |
| `courses[].certificate` | object | No | Siempre presente, `has_link` determina si hay URL |
| `courses[].certificate.has_link` | bool | No | true = mostrar botón con enlace |
| `courses[].certificate.label` | string | No | Texto del botón/celda |
| `courses[].certificate.link` | string | Sí | URL absoluta o cadena vacía |
| `paging_bar` | string | No | HTML preformateado por `$OUTPUT->paging_bar()` |
| `str_*` | string | No | Strings de idioma ya traducidos |

---

## 4. Diseño de la Plantilla Mustache

### 4.1 Estructura lógica del template

```
history_table.mustache
│
├── {{^has_courses}}
│     <div class="alert alert-info">{{str_no_courses}}</div>
│   {{/has_courses}}
│
└── {{#has_courses}}
      <div class="table-responsive">
        <table class="table table-striped table-hover generaltable">
          <thead>
            <tr>
              <th>{{str_course}}</th>
              <th>{{str_date}}</th>
              <th>{{str_grade}}</th>
              <th>{{str_certificate}}</th>
            </tr>
          </thead>
          <tbody>
            {{#courses}}
            <tr>
              <td><a href="{{courseurl}}">{{coursename}}</a></td>
              <td>{{completiondate}}</td>
              <td>{{grade}}</td>
              <td>
                {{#certificate.has_link}}
                  <a href="{{{certificate.link}}}" class="btn btn-sm btn-primary" target="_blank">
                    {{certificate.label}}
                  </a>
                {{/certificate.has_link}}
                {{^certificate.has_link}}
                  {{certificate.label}}
                {{/certificate.has_link}}
              </td>
            </tr>
            {{/courses}}
          </tbody>
        </table>
      </div>
      {{{paging_bar}}}
    {{/has_courses}}
```

**Nota sobre triple bigote `{{{ }}}` vs doble `{{ }}`:**
- `{{courseurl}}` y `{{certificate.link}}` → URLs: usar `{{ }}` (se escapa igual, las URLs no tienen HTML).
- `{{{paging_bar}}}` → HTML preformateado por Moodle: **obligatorio** triple bigote para que se renderice el HTML.
- Todos los textos y nombres → `{{ }}` (escapado automático, protege contra XSS).

---

## 5. Diagrama de Dependencias entre Archivos

```
version.php
    ↓ (declara componente)
db/access.php
    ↓ (define capacidades usadas por)
block_academichistory.php  ←──────────── lang/[en|es]/block_academichistory.php
    ↓ (enlaza a)                                     ↑ (provee strings a)
view.php ────────────────────────────────────────────┘
    ↓ (instancia)
classes/output/history_table.php
    ↓ (usa strings de lang + DB)
    ↓ (devuelve array a)
templates/history_table.mustache
    ↑
privacy/provider.php  (independiente, solo implementa interfaz)
lib.php               (independiente, vacío)
```

---

## 6. Checklist de Validación Pre-Desarrollo

Antes de escribir el código, verificar que se cumple:

- [ ] La estructura de directorios está definida completamente.
- [ ] Todos los strings de idioma están inventariados (no agregar más durante el desarrollo).
- [ ] El contrato de datos de `export_for_template()` está fijo (no cambiar estructura durante desarrollo).
- [ ] Las queries SQL fueron revisadas con los nombres exactos de tablas de Moodle (prefijo `{}`).
- [ ] El manejo de `customcert` opcional está contemplado en todos los archivos que lo tocan.
- [ ] Los edge cases de `finalgrade NULL` y `scaleid NOT NULL` tienen su camino de código definido.
- [ ] La paginación está diseñada desde el inicio (no agregarla después).

---

*Documento aprobado → proceder al desarrollo del código.*
