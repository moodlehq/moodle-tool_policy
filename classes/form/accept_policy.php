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

        $users = $this->_customdata['users'];
        $policies = $this->_customdata['policies'];
        $usernames = [];
        foreach ($users as $user) {
            $usernames[] = fullname($user);
        }
        $policiesnames = [];
        foreach ($policies as $policy) {
            $url = new \moodle_url('/admin/tool/policy/view.php', ['versionid' => $policy->versionid]);
            $policyname = format_string($policy->name);
            if ($policy->currentversionid != $policy->versionid) {
                $policyname .= ' ' . format_string($policy->revision);
            }
            $policiesnames[] = \html_writer::link($url, $policyname);
        }

        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'acceptforversion');
        $mform->setType('acceptforversion', PARAM_INT);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $mform->addElement('static', 'user', get_string('acceptanceusers', 'tool_policy'), join(', ', $usernames));
        $mform->addElement('static', 'policy', get_string('acceptancepolicies', 'tool_policy'),
            join(', ', $policiesnames));

        $mform->addElement('static', 'ack', '', get_string('acceptanceacknowledgement', 'tool_policy'));

        $mform->addElement('textarea', 'note', get_string('acceptancenote', 'tool_policy'));
        $mform->setType('note', PARAM_NOTAGS);

        $this->add_action_buttons(true, get_string('iagreetothepolicy', 'tool_policy'));

        $this->set_data(['userid' => $user->id, 'acceptforversion' => $policy->versionid]);
    }
}