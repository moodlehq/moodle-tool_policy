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
 * Plugin version and other meta-data are defined here.
 *
 * @package     tool_policy
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \tool_policy\validateminor_helper;

/**
 * Extends the user preferences page
 *
 * @param navigation_node $usersetting
 * @param stdClass $user
 * @param context_user $usercontext
 * @param stdClass $course
 * @param context_course $coursecontext
 */
function tool_policy_extend_navigation_user_settings(navigation_node $usersetting, $user, context_user $usercontext,
        $course, context_course $coursecontext) {
    global $CFG;

    $userpolicysettings = $usersetting->add(get_string('userpolicysettings', 'tool_policy'), null,
        navigation_node::TYPE_CONTAINER, null, 'tool_policy-userpolicysettings');

    // TODO link to a page that provides details on all policies that the user has accepted, when etc.
    $userpolicysettings->add('Policies and agreements', '');

    // TODO here will links generated from the actual list of policy documents.
    $userpolicysettings->add('Site policy', '');
    $userpolicysettings->add('Privacy policy', '');
    $userpolicysettings->add('Personal data sharing and processing', '');
    $userpolicysettings->add('Intellectual property policy', '');
}

/**
 * Load policy message for guests.
 *
 * @return string The HTML code to insert before the head.
 */
function tool_policy_before_standard_html_head() {
    global $PAGE, $USER;

    $message = null;
    if (empty($USER->policyagreed) and isguestuser()) {
        $output = $PAGE->get_renderer('tool_policy');
        $page = new \tool_policy\output\guestconsent();

        $message = $output->render($page);
    }

    return $message;
}

/**
 * Hooks redirections to digital minor validation and policy acceptance pages before sign up.
 */
function tool_policy_pre_signup_requests() {
    global $SESSION;

    if (!isset($SESSION->tool_policy->minor)) {  // Digital minor check hasn't been done.
        redirect(new moodle_url('/admin/tool/policy/validateminor.php'));
    } else { // Digital minor check has been done.
        if (!validateminor_helper::is_valid_minor_session()) { // Minor session is no longer valid.
            validateminor_helper::destroy_minor_session();
            redirect(new moodle_url('/admin/tool/policy/validateminor.php'));
        }
        $is_minor = validateminor_helper::get_minor_session_status();
        validateminor_helper::redirect($is_minor);
    }
}
