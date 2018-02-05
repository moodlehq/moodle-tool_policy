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
 * Provides {@link tool_policy\form\agelocationverification} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 Mihail Geshoski <mihail@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\form;

require_once($CFG->libdir.'/formslib.php');

use moodleform;

defined('MOODLE_INTERNAL') || die();


/**
 * Defines the form for verifying user's age and location.
 *
 * @copyright 2018 Mihail Geshoski <mihail@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class agelocationverification extends moodleform {
    /**
     * Defines the form fields.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('text', 'age', get_string('howoldareyou',
            'tool_policy'), array('optional'  => false));
        $mform->setType('age', PARAM_RAW);
        $mform->addRule('age', null, 'required', null, 'client');
        $mform->addRule('age', null, 'numeric', null, 'client');

        $countries = get_string_manager()->get_list_of_countries();
        $default_country[''] = get_string('selectacountry');
        $countries = array_merge($default_country, $countries);
        $mform->addElement('select', 'country', get_string('wheredoyoulive',
            'tool_policy'), $countries);
        $mform->addRule('country', null, 'required', null, 'client');
        $mform->setDefault('country', $CFG->country);

        // buttons
        $this->add_action_buttons(true, get_string('proceed', 'tool_policy'));
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
        // Validate age.
        if ($data['age'] < 0) {
            $errors['age'] = get_string('invalidage', 'tool_policy');
        }

        return $errors;
    }

}
