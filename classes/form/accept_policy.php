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
 * Provides {@link tool_policy\form\accept_policy} class.
 *
 * @package     tool_policy
 * @copyright   2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\form;

global $CFG;
require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Provides {@link tool_policy\form\accept_policy} class.
 *
 * @package     tool_policy
 * @copyright   2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class accept_policy extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $user = $this->_customdata['user'];
        $policy = $this->_customdata['policy'];

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'acceptforversion');
        $mform->setType('acceptforversion', PARAM_INT);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $mform->addElement('static', 'user', 'User', fullname($user)); // TODO string, cap
        $mform->addElement('static', 'policy', 'Policy', format_string($policy->name.', '.$policy->revision)); // TODO string, cap

        $mform->addElement('textarea', 'note', 'Remark'); // tODO strings
        $mform->setType('note', PARAM_NOTAGS);

        $this->add_action_buttons(true, 'Accept on behalf of the user'); // TODO

        $this->set_data(['userid' => $user->id, 'acceptforversion' => $policy->versionid]);
    }
}