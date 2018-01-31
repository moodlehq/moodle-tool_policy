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
 * Provides {@link tool_policy\output\page_validateminor} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 Mihail Geshoski <mihail@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

use renderable;
use renderer_base;
use templatable;

/**
 * Represents a management page with the list of versions of the given policy document.
 *
 * @copyright 2018 Mihail Geshoski <mihail@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_validateminor extends \moodleform implements renderable, templatable {

    /**
     * Defines the form fields.
     */
    public function definition() {

        $mform = $this->_form;

        $mform->addElement('date_selector', 'dateofbirth', get_string('dateofbirth',
            'tool_policy'), array('optional'  => false));
        $mform->addRule('dateofbirth', null, 'required', null, 'client');

        $countries = get_string_manager()->get_list_of_countries();
        $default_country[''] = get_string('selectacountry');
        $countries = array_merge($default_country, $countries);
        $mform->addElement('select', 'country', get_string('countryofresidence',
            'tool_policy'), $countries);
        $mform->addRule('country', null, 'required', null, 'client');

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
        // Validate date of birth.
        $t = time();
        if ($t < $data['dateofbirth']) {
            $errors['dateofbirth'] = get_string('invaliddateofbirth', 'tool_policy');
        }

        return $errors;
    }

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $SITE;

        ob_start();
        $this->display();
        $formhtml = ob_get_contents();
        ob_end_clean();
        $context = [
            'sitename' => $SITE->fullname,
            'formhtml' => $formhtml
        ];

        return $context;
    }
}
