<?php
// This file is part of Moodle - http://moodle.org/
defined('MOODLE_INTERNAL') || die();

class block_coursecard extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_coursecard');
    }

    public function get_content() {
        global $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        $renderable = new \block_coursecard\output\main($USER->id);
        $renderer   = $this->page->get_renderer('block_coursecard');

        $this->content->text = $renderer->render($renderable);

        return $this->content;
    }

    public function applicable_formats() {
        return ['my' => true, 'site-index' => false, 'course' => false];
    }

    public function has_config() {
        return false;
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function hide_header() {
        return false;
    }
}
