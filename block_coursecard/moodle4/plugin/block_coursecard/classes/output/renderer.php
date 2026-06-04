<?php
// This file is part of Moodle - http://moodle.org/
namespace block_coursecard\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    /**
     * Render the main block output.
     */
    public function render_main(main $main): string {
        return $this->render_from_template(
            'block_coursecard/main',
            $main->export_for_template($this)
        );
    }
}
