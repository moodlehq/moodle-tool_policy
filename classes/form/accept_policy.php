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

use tool_policy\api;
use tool_policy\policy_version;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Represents the form for accepting a policy.
 *
 * @package     tool_policy
 * @copyright   2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class accept_policy extends \moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {
        global $PAGE;
        $mform = $this->_form;

        $userids = $this->_customdata['users'];
        $versions = $this->_customdata['versions'];
        $usernames = $this->get_users($userids);
        $policiesnames = [];
        $policies = api::list_policies();
        foreach ($versions as $versionid) {
            $version = api::get_policy_version($versionid, $policies);
            $url = new \moodle_url('/admin/tool/policy/view.php', ['versionid' => $version->id]);
            $policyname = $version->name;
            if ($version->status != policy_version::STATUS_ACTIVE) {
                $policyname .= ' ' . $version->revision;
            }
            $policiesnames[] = \html_writer::link($url, $policyname,
                ['data-action' => 'view', 'data-versionid' => $version->id]);
        }

        $mform->addElement('hidden', 'acceptforversions');
        $mform->setType('acceptforversions', PARAM_RAW);

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_LOCALURL);

        $mform->addElement('static', 'user', get_string('acceptanceusers', 'tool_policy'), join(', ', $usernames));
        $mform->addElement('static', 'policy', get_string('acceptancepolicies', 'tool_policy'),
            join(', ', $policiesnames));

        $mform->addElement('static', 'ack', '', get_string('acceptanceacknowledgement', 'tool_policy'));

        $mform->addElement('textarea', 'note', get_string('acceptancenote', 'tool_policy'));
        $mform->setType('note', PARAM_NOTAGS);

        $this->add_action_buttons(true, get_string('iagreetothepolicy', 'tool_policy'));

        foreach ($usernames as $userid => $name) {
            $mform->addElement('hidden', 'userids['.$userid.']', $userid);
            $mform->setType('userids['.$userid.']', PARAM_INT);
        }

        $this->set_data(['acceptforversions' => join(',', $versions)]);
        $PAGE->requires->js_call_amd('tool_policy/policyactions', 'init');
    }

    /**
     * Validate userids and return usernames
     *
     * @param array $userids
     * @return array (userid=>username)
     */
    protected function get_users($userids) {
        global $DB, $USER;
        $usernames = [];
        list($sql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['usercontextlevel'] = CONTEXT_USER;
        $users = $DB->get_records_sql("SELECT u.id, " . get_all_user_name_fields(true, 'u') . ", " .
                \context_helper::get_preload_record_columns_sql('ctx') .
            " FROM {user} u JOIN {context} ctx ON ctx.contextlevel=:usercontextlevel AND ctx.instanceid = u.id
            WHERE u.id " . $sql, $params);

        $acceptany = has_capability('tool/policy:acceptbehalf', \context_system::instance());
        foreach ($userids as $userid) {
            if (!isset($users[$userid])) {
                throw new dml_missing_record_exception('user', 'id=?', [$userid]);
            }
            $user = $users[$userid];
            if (isguestuser($user)) {
                throw new \moodle_exception('noguest');
            }
            if ($userid == $USER->id) {
                require_capability('tool/policy:accept', \context_system::instance());
            } else if (!$acceptany) {
                \context_helper::preload_from_record($user);
                require_capability('tool/policy:acceptbehalf', \context_user::instance($userid));
            }
            $usernames[$userid] = fullname($user);
        }
        return $usernames;
    }
}