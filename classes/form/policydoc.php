<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Provides {@link tool_policy\output\renderer} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\form;

use html_writer;
use moodleform;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the form for editing a policy document version.
 *
 * @copyright 2018 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class policydoc extends moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {

        $mform = $this->_form;
        $formdata = $this->_customdata['formdata'];

        $mform->addElement('text', 'name', get_string('policydocname', 'tool_policy'), ['maxlength' => 1333]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');

        $mform->addElement('text', 'revision', get_string('policydocrevision', 'tool_policy'), ['maxlength' => 1333]);
        $mform->setType('revision', PARAM_TEXT);
        $mform->addRule('revision', null, 'required', null, 'client');
        $mform->addRule('revision', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');

        $mform->addElement('editor', 'content_editor', get_string('policydoccontent', 'tool_policy'));
        $mform->addRule('content_editor', null, 'required', null, 'client');

        if (!empty($formdata->versionid)) {
            // We are editing an existing version.
            $saveasnewoptions = [
                $mform->createElement('radio', 'saveasnew', '', get_string('saveasnew1', 'tool_policy'), 1),
                $mform->createElement('radio', 'saveasnew', '', get_string('saveasnew0', 'tool_policy'), 0),
            ];

            $mform->addGroup($saveasnewoptions, 'saveasnewgrp', get_string('saveasnew', 'tool_policy'),
                [html_writer::empty_tag('br')], false);

            if ($formdata->versionid == $formdata->currentversionid) {
                // We are editing the current version.
                $mform->setDefault('saveasnew', 1);
            } else {
                // We are editing a draft version.
                $mform->setDefault('saveasnew', 0);
            }
        }

        $this->add_action_buttons();
    }
}
