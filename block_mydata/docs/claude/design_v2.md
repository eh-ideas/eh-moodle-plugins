# block_mydata v2 — Diseño Técnico y Propuesta Visual

**Estado:** Propuesta inicial  
**Fecha:** 2026-06-05  
**Versión objetivo:** 2.0.0 para Moodle 4.1+

---

## 1. Problema que resuelve v2

La v1 tiene estas limitaciones:
- Las 6 tarjetas son fijas: no se pueden ocultar ni reordenar desde la administración
- Diseño plano uniforme: todas las tarjetas tienen el mismo peso visual, sin jerarquía
- Iconos de librería externa (Simple Line Icons) que requiere fonts propios
- No hay tarjetas para datos relevantes como progreso, vencimientos o racha de aprendizaje

---

## 2. Nuevas tarjetas propuestas

### Tarjetas existentes (v1) — mantener

| ID | Nombre | Dato |
|----|--------|------|
| `pending_activities` | Actividades pendientes | Conteo de actividades sin completar |
| `completed_activities` | Actividades completadas | Conteo de actividades terminadas |
| `unread_messages` | Mensajes sin leer | Conversaciones no leídas |
| `badges` | Insignias recibidas | Total de badges del usuario |
| `certificates` | Certificados recibidos | Certificados emitidos (mod_customcert) |
| `completed_courses` | Cursos completados | X de Y cursos completados |

### Tarjetas nuevas propuestas

| ID | Nombre | Dato | Fuente Moodle | Prioridad |
|----|--------|------|---------------|-----------|
| `course_progress` | Progreso promedio | % de avance promedio en todos los cursos activos | `core_completion\progress` | Alta |
| `upcoming_deadlines` | Próximos vencimientos | N° de actividades con fecha de entrega en los próximos 7 días | `core_calendar` / `assign` due dates | Alta |
| `login_streak` | Racha de acceso | Días consecutivos con actividad en la plataforma | `{logstore_standard_log}` | Media |
| `forum_activity` | Participación en foros | N° de posts/respuestas del usuario en el período actual | `{forum_posts}` | Media |
| `time_online` | Tiempo en la plataforma | Horas de sesión acumuladas (último mes) | `{logstore_standard_log}` | Media |
| `overdue_activities` | Actividades vencidas | Actividades con fecha pasada sin completar | `assign` / `quiz` due dates | Alta |
| `last_course` | Último curso visitado | Nombre y progreso del último curso accedido | `{user_lastaccess}` | Baja |
| `enrolled_courses` | Cursos matriculados | Total de cursos activos en los que está inscrito | `enrol_get_users_courses()` | Baja |

---

## 3. Propuesta de layout visual

### Concepto: "Dashboard jerarquizado"

En lugar de 6 tarjetas idénticas en grilla, se propone una jerarquía visual de 3 niveles:

```
┌─────────────────────────────────────────────────────────────────┐
│  CABECERA — Perfil del usuario (nombre, avatar, país, cargo)     │
│  + Barra de progreso global (% promedio de todos los cursos)     │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  TARJETA     │  │  TARJETA     │  │  TARJETA     │  ← FILA 1: Métricas
│  GRANDE      │  │  GRANDE      │  │  GRANDE      │    principales (3 col)
│  Pend. Act.  │  │  Comp. Act.  │  │  Cursos      │
│     16       │  │     12       │  │    2/10      │
└──────────────┘  └──────────────┘  └──────────────┘

┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐
│Msgs  │  │Insig │  │Certs │  │Venci │  │Racha │  │Foros │  ← FILA 2: Métricas
│  0   │  │  1   │  │  28  │  │  3   │  │  7d  │  │  5   │    secundarias (6 col)
└──────┘  └──────┘  └──────┘  └──────┘  └──────┘  └──────┘

┌─────────────────────────────────────────────────────────────────┐
│  SECCIÓN: Próximos vencimientos (lista de las 3 más urgentes)   │  ← Opcional
│  ▸ Quiz: Evaluación Final — vence en 2 días                     │
│  ▸ Tarea: Informe — vence en 5 días                             │
└─────────────────────────────────────────────────────────────────┘
```

### Variante compacta (para columna lateral estrecha)

```
┌────────────────────────┐
│  Avatar + Nombre       │
│  Progreso: ████░░ 65%  │
├────────────────────────┤
│ 🕐 16  Pendientes      │
│ ✓  12  Completadas     │
│ 🎓 2/10 Cursos         │
├────────────────────────┤
│ ✉  0   Mensajes        │
│ 🏅 1   Insignias       │
│ 📄 28  Certificados    │
└────────────────────────┘
```

---

## 4. Panel de administración — diseño propuesto

### 4.1 Ajustes globales del bloque

```
Sección: Configuración general
  - Título del bloque: [campo texto] (default: "Mi Panel")
  - Mostrar información del perfil: [sí/no]
  - Mostrar campos del perfil: [checkboxes: país, ciudad, email, cargo, teléfono...]
  - Layout de tarjetas: [Normal | Compacto]

Sección: Tarjetas — activar / desactivar
  ┌────────────────────────────────────────────────────┐
  │  ☑  Actividades pendientes     [Color] [TextColor] │
  │  ☑  Actividades completadas    [Color] [TextColor] │
  │  ☑  Cursos completados         [Color] [TextColor] │
  │  ☑  Mensajes sin leer          [Color] [TextColor] │
  │  ☑  Insignias recibidas        [Color] [TextColor] │
  │  ☑  Certificados recibidos     [Color] [TextColor] │
  │  ☐  Progreso promedio          [Color] [TextColor] │
  │  ☐  Próximos vencimientos      [Color] [TextColor] │
  │  ☐  Actividades vencidas       [Color] [TextColor] │
  │  ☐  Racha de acceso            [Color] [TextColor] │
  │  ☐  Participación en foros     [Color] [TextColor] │
  │  ☐  Tiempo en plataforma       [Color] [TextColor] │
  └────────────────────────────────────────────────────┘

Sección: Tarjeta "Próximos vencimientos"
  - Días de anticipación: [7] días
  - Cantidad máxima a mostrar: [3]
```

### 4.2 Implementación técnica del panel

Cada tarjeta se mapea a un par de ajustes en `settings.php`:
- `show_card_{id}` → `admin_setting_configcheckbox` (visible/oculta)
- `cardcolor_{id}` → `admin_setting_configcolourpicker`
- `textcolor_{id}` → `admin_setting_configcolourpicker`

En `classes/output/mydata.php`, `export_for_template()` lee `show_card_{id}` antes de incluir la tarjeta en el array de datos. Si está en false, no se pasa al template y Mustache simplemente no la renderiza.

---

## 5. Cambios técnicos respecto a v1

### 5.1 Iconos
- **v1:** Simple Line Icons (fuente externa en `/styles/fonts/`)
- **v2:** Font Awesome 6 (ya incluido en Moodle 4.x como `fa-*`)
- Beneficio: elimina 5 archivos de fuentes del plugin, reduce tamaño del zip ~80KB

### 5.2 Estructura de plantilla Mustache
- **v1:** un único `mydata.mustache` con todas las tarjetas hardcodeadas
- **v2:** plantilla principal + loop sobre array de tarjetas activas:
  ```mustache
  {{#cards}}
    {{> block_mydata/card}}
  {{/cards}}
  ```
  Permite agregar/quitar tarjetas sin tocar el template.

### 5.3 Clase de datos
- **v1:** métodos fijos que siempre calculan todos los datos
- **v2:** cada tarjeta tiene su propio método `get_{id}_data()` que solo se llama si la tarjeta está activa → mejor performance cuando hay tarjetas desactivadas

### 5.4 Corrección de bug v1
- `settings.php` líneas 109-129: duplicaba claves `cardcolor4`/`textcolor4` para la tarjeta 5
- En v2 se unifica con el nuevo sistema de claves dinámicas `cardcolor_{id}`

---

## 6. Tarjetas "Nice to have" para versiones futuras

| Tarjeta | Complejidad | Requiere plugin externo |
|---------|-------------|------------------------|
| Ranking del estudiante (gamificación) | Alta | mod_game o similar |
| Horas de video vistas | Media | mod_hvp / Panopto |
| Próximas clases programadas | Media | mod_zoom / BBB |
| Logros desbloqueados esta semana | Alta | Sistema de logros custom |

---

## 7. Plan de desarrollo sugerido

1. **Sprint 1:** Estructura base v2, panel admin con show/hide de tarjetas existentes
2. **Sprint 2:** Layout nuevo (jerarquía 3 niveles), migración a FA icons
3. **Sprint 3:** Tarjetas nuevas: `course_progress`, `upcoming_deadlines`, `overdue_activities`
4. **Sprint 4:** Tarjetas opcionales: `login_streak`, `forum_activity`, `time_online`
5. **Sprint 5:** QA, pruebas en Moodle 4.1 / 4.3 / 4.5, release zip

---

## 8. Archivos a crear/modificar en v2

```
moodle4/plugin/block_mydata/
├── block_mydata.php              ~ sin cambios estructurales
├── version.php                   ~ bump a 2024010100 / v2.0.0
├── settings.php                  ★ reescribir completo (sistema de tarjetas dinámico)
├── styles.css                    ★ actualizar para nuevo layout
├── classes/
│   └── output/
│       ├── mydata.php            ★ refactorizar con métodos por tarjeta
│       └── renderer.php          ~ sin cambios
├── db/
│   └── access.php                ~ sin cambios
├── lang/
│   ├── en/block_mydata.php       ★ agregar strings para tarjetas nuevas
│   └── es/block_mydata.php       ★ agregar strings para tarjetas nuevas
└── templates/
    ├── mydata.mustache           ★ reescribir con loop de tarjetas
    └── card.mustache             ★ nuevo: template parcial de tarjeta individual
```
