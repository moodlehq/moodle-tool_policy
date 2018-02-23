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
use tool_policy\api;

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

        $mform->addElement('header', 'hdr_policy', get_string('policydochdrpolicy', 'tool_policy'));

        $mform->addElement('text', 'name', get_string('policydocname', 'tool_policy'), ['maxlength' => 1333]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addHelpButton('name', 'policydocname', 'tool_policy');
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');

        $mform->addElement('text', 'description', get_string('policydocdesc', 'tool_policy'), ['maxlength' => 1333]);
        $mform->addHelpButton('description', 'policydocdesc', 'tool_policy');
        $mform->setType('description', PARAM_TEXT);
        $mform->addRule('description', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');

        $mform->addElement('select', 'audience', get_string('policydocaudience', 'tool_policy'), [
            api::AUDIENCE_ALL => get_string('policydocaudience_all', 'tool_policy'),
            api::AUDIENCE_LOGGEDIN => get_string('policydocaudience_loggedin', 'tool_policy'),
            api::AUDIENCE_GUESTS => get_string('policydocaudience_guests', 'tool_policy'),
        ]);
        $mform->addHelpButton('audience', 'policydocaudience', 'tool_policy');
        $mform->setDefault('audience', api::AUDIENCE_ALL);

        $mform->addElement('header', 'hdr_version', get_string('policydochdrversion', 'tool_policy'));

        $mform->addElement('text', 'revision', get_string('policydocrevision', 'tool_policy'), ['maxlength' => 1333]);
        $mform->addHelpButton('revision', 'policydocrevision', 'tool_policy');
        $mform->setType('revision', PARAM_TEXT);
        $mform->addRule('revision', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');

        $mform->addElement('editor', 'summary_editor', get_string('policydocsummary', 'tool_policy'), ['rows' => 7],
            api::policy_summary_field_options());
        $mform->addHelpButton('summary_editor', 'policydocsummary', 'tool_policy');
        $mform->addRule('summary_editor', null, 'required', null, 'client');

        $mform->addElement('editor', 'content_editor', get_string('policydoccontent', 'tool_policy'), null,
            api::policy_content_field_options());
        $mform->addHelpButton('content_editor', 'policydoccontent', 'tool_policy');
        $mform->addRule('content_editor', null, 'required', null, 'client');

        if (!empty($formdata->versionid)) {
            // We are editing an existing version.
            $saveasnewoptions = [
                $mform->createElement('radio', 'saveasnew', '', get_string('yes', 'core'), 1),
                $mform->createElement('radio', 'saveasnew', '', get_string('no', 'core'), 0),
            ];

            $mform->addGroup($saveasnewoptions, 'saveasnewgrp', get_string('saveasnew', 'tool_policy'),
                [html_writer::empty_tag('br')], false);
            $mform->addHelpButton('saveasnewgrp', 'saveasnew', 'tool_policy');

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

    /**
     * Data validation.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data.
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array $errors array of "element_name"=>"error_description", if there are errors.
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $formdata = $this->_customdata['formdata'];
        // If it's a new version, versionid will be different. So we must to ignore it.
        $versionid = $data['saveasnew']? null : $formdata->versionid;
        if (api::policy_revision_exists($data['revision'], $formdata->policyid, $versionid)) {
            // Validate that revision is unique for this policy.
            $errors['revision'] = get_string('revisionunique', 'tool_policy');
        }

        return $errors;
    }
}
