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
