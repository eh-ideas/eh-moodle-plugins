<?php

class block_simple_learning_path_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        // Agregar un encabezado según el archivo de idioma.
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        // Campo de texto para el título del bloque con un valor por defecto.
        $mform->addElement('text', 'config_title', get_string('simple_learning_path:blocktitle', 'block_simple_learning_path'));
        $mform->setDefault('config_title', 'default value');
        $mform->setType('config_title', PARAM_TEXT);

        // Checkbox para mostrar cursos del usuario.
        $mform->addElement('advcheckbox', 'config_show_my_courses', get_string('simple_learning_path:showmycourses', 'block_simple_learning_path'));
        $mform->setDefault('config_show_my_courses', 0); // Usar 0 como valor por defecto (no marcado).
        $mform->setType('config_show_my_courses', PARAM_BOOL);

        // Checkbox para mostrar rutas de aprendizaje.
        $mform->addElement('advcheckbox', 'config_show_my_learning_paths', get_string('simple_learning_path:showmylearningpaths', 'block_simple_learning_path'));
        $mform->setDefault('config_show_my_learning_paths', 0); // Usar 0 como valor por defecto (no marcado).
        $mform->setType('config_show_my_learning_paths', PARAM_BOOL);
    }

    public function definition_after_data() {
        $mform =& $this->_form;

        if (!isset($this->block->config)) {
            $this->block->config = new stdClass();
        }

        // Comprueba si el elemento 'config_show_my_courses' existe antes de obtener su valor.
        if ($mform->elementExists('config_show_my_courses')) {
            $value = $mform->getElementValue('config_show_my_courses');
            $this->block->config->show_my_courses = $value ? $value : 0;
        } else {
            $this->block->config->show_my_courses = 0;
        }

        // Repite para 'config_show_my_learning_paths'
        if ($mform->elementExists('config_show_my_learning_paths')) {
            $value = $mform->getElementValue('config_show_my_learning_paths');
            $this->block->config->show_my_learning_paths = $value ? $value : 0;
        } else {
            $this->block->config->show_my_learning_paths = 0;
        }
    }
}