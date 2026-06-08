# Changelog — block_mydata

## [2.0.0] — 2026-06-07 — Estable (producción)

### Nuevo
- Panel de administración para activar/desactivar tarjetas individualmente
- Reordenamiento de tarjetas con drag & drop en la administración
- 6 nuevas tarjetas de estadísticas (ver diseño en `docs/claude/design_v2.md`)
- Diseño visual renovado con jerarquía visual mejorada
- Barra de progreso por curso
- Sección "Próximos vencimientos"
- Modo compacto configurable

### Cambiado
- Tarjetas ahora tienen show/hide configurable por el administrador
- Iconos migrados de Simple Line Icons a Font Awesome (ya incluido en Moodle)
- Estructura de plantilla Mustache reorganizada en componentes parciales

### Seguridad
- Auditoría de seguridad y estándares Moodle pre-producción (ver `docs/claude/security_audit.md`)
- Agregadas las cadenas de idioma de las capabilities (faltaban desde v1)
- Validación hex de colores en la salida (defensa en profundidad) y fix de doble-escapado
- Aviso de "consumo elevado" en las tarjetas que consultan los registros del sitio

### Corregido
- Bug en `settings.php`: tarjeta 5 referenciaba claves de color de tarjeta 4
- El bloque no aparecía en el listado "Agregar..." por tener título vacío; ahora
  usa el nombre del plugin y oculta la cabecera con `hide_header()`

---

## [1.0.0] — 2024-05-27

- Versión inicial estable
- 6 tarjetas: actividades pendientes, completadas, mensajes, insignias, certificados, cursos
- Personalización de colores por tarjeta
- Perfil de usuario con avatar generado por iniciales
- Soporte español / inglés
