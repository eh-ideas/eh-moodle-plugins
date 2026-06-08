# Auditoría de seguridad y estándares — block_mydata v2

**Fecha:** 2026-06-07
**Versión auditada:** 2026060712 (v2.0.0 — STABLE)
**Resultado:** ✅ Aprobado para producción · liberado como estable

---

## 1. Seguridad

| Área | Resultado | Detalle |
|------|-----------|---------|
| **Inyección SQL** | ✅ OK | Todas las consultas usan placeholders nombrados y `get_in_or_equal()`. No hay concatenación de variables en SQL. |
| **XSS (salida)** | ✅ OK | Plantilla Mustache autoescapa `{{ }}`. Se corrigió doble-escapado en `puesto` y se pasó a `{{{ }}}` los nombres ya saneados con `format_string()`. |
| **XSS (estilos inline)** | ✅ OK | Los colores se validan como hex (`/^#[0-9a-fA-F]{6}$/`) tanto al guardar como al imprimir (defensa en profundidad). |
| **Capabilities / IDOR** | ✅ OK | El bloque solo muestra datos del `$USER` actual. No accede a datos de otros usuarios. |
| **CSRF** | ✅ OK | Los ajustes se guardan por el flujo estándar de admin de Moodle (verificación de `sesskey` automática). |
| **Validación de entrada** | ✅ OK | `PARAM_INT` + clamp en días/máximo, `PARAM_URL` en URL de certificados, hex en colores, 0/1 en toggles. |
| **Privacidad (GDPR)** | ✅ OK | `null_provider`: el bloque no almacena datos personales propios. |
| **Datos sensibles** | ✅ OK | `get_config_for_external()` no expone secretos. |

### Correcciones aplicadas en esta auditoría
1. **Doble-escapado** del campo "puesto" y de nombres de actividad/curso (cosmético, no vulnerabilidad).
2. **Endurecimiento de colores**: validación hex en la salida del panel admin y del front-end.

---

## 2. Estándares de desarrollo Moodle

| Requisito | Resultado |
|-----------|-----------|
| Cabecera GPL v3 en todos los archivos | ✅ |
| Sin etiqueta `?>` de cierre | ✅ |
| `defined('MOODLE_INTERNAL') || die();` donde corresponde | ✅ (clases de solo-declaración exentas, correcto) |
| Nomenclatura de componente `block_mydata` | ✅ |
| `version.php` completo (component, version, requires, maturity, release) | ✅ |
| `db/access.php` con capabilities | ✅ |
| **Strings de capabilities** (`mydata:addinstance`, `mydata:myaddinstance`) | ✅ **(corregido — faltaban desde v1)** |
| Cobertura de strings EN/ES (sin claves faltantes) | ✅ 100% (87/87) |
| `lang/en` presente (idioma obligatorio) | ✅ |
| Provider de privacidad | ✅ |
| Plantilla Mustache documentada (`@template` + ejemplo) | ✅ |
| Autoloading PSR-ish bajo `classes/` | ✅ |
| APIs usadas no deprecadas | ✅ (completion, message, badges, calendar, user_picture) |

### Corrección aplicada
- **Se agregaron las cadenas de las dos capabilities** en EN y ES. Sin ellas, la página de definición de roles mostraba las claves crudas (lo marca el codechecker oficial).

---

## 3. Recomendaciones no bloqueantes (para tener en cuenta)

1. **Dependencia de `mod_customcert`** — actualmente es dependencia **dura** en `version.php`, pero la funcionalidad es opcional (el código ya verifica `table_exists`). Para mayor portabilidad se podría volver **blanda** (quitarla de `dependencies`). *No afecta a esta plataforma, que sí tiene customcert.*
2. **Madurez** — está en `MATURITY_BETA`. Al cerrar el desarrollo, pasar a `MATURITY_STABLE` y `release` de `v2.0.0-dev` → `v2.0.0` (desaparece la advertencia de instalación).
3. **Rendimiento a escala** — las tarjetas "racha" y "tiempo en plataforma" consultan `logstore_standard_log`. Sólo corren si están activas (lo están desactivadas por defecto). Para sitios con mucho tráfico, considerar cachear con MUC si se activan masivamente.
4. **Limpieza menor** — quedan strings de color de la v1 sin usar (`card_*_color`, `card_color_desc`). Inofensivos; se pueden eliminar en una pasada de limpieza.

---

## 4. Conclusión

El plugin **cumple con los requerimientos de seguridad y los estándares de desarrollo de Moodle** para despliegue en producción. Las correcciones obligatorias (strings de capabilities) y las de robustez (escapado/validación) fueron aplicadas.

**Liberado como estable:** versión `2026060712`, `release v2.0.0`, `MATURITY_STABLE`.
Zip de producción: `moodle4/releases/block_mydata_v2.0.0.zip`.
