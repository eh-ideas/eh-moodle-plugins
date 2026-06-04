<?php

defined('MOODLE_INTERNAL') || die();

// Verifica que el usuario tiene permisos de administrador del sitio
if ($hassiteconfig) {
    // Verificamos si la sección de configuración actual es la de nuestro bloque
    if (optional_param('section', '', PARAM_TEXT) === 'blocksettingsimple_learning_path') {
        // Redirigimos solo si estamos en la sección de configuración de nuestro bloque
        redirect(new moodle_url('/blocks/simple_learning_path/index.php'));
    }
}