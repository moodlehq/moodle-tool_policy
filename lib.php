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

use tool_policy\api;
use tool_policy\validateminor_helper;

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
    global $CFG, $PAGE;

    // Do nothing if we are not set as the site policies handler.
    if (empty($CFG->sitepolicyhandler) || $CFG->sitepolicyhandler !== 'tool_policy') {
        return;
    }

    $userpolicysettings = $usersetting->add(get_string('userpolicysettings', 'tool_policy'), null,
        navigation_node::TYPE_CONTAINER, null, 'tool_policy-userpolicysettings');

    $userpolicysettings->add(get_string('policiesagreements', 'tool_policy'),
        new moodle_url('/admin/tool/policy/user.php', ['userid' => $user->id]));

    foreach (api::list_policies(null, true, api::AUDIENCE_LOGGEDIN) as $policy) {
        $userpolicysettings->add(format_string($policy->name), new moodle_url('/admin/tool/policy/view.php', [
            'policyid' => $policy->id,
            'versionid' => $policy->currentversionid,
            'returnurl' => $PAGE->url,
        ]));
    }
}

/**
 * Load policy message for guests.
 *
 * @return string The HTML code to insert before the head.
 */
function tool_policy_before_standard_html_head() {
    global $CFG, $PAGE, $USER;

    $message = null;
    if (!empty($CFG->sitepolicyhandler) && $CFG->sitepolicyhandler == 'tool_policy' && empty($USER->policyagreed) && isguestuser()) {
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
    global $CFG;

    // Do nothing if we are not set as the site policies handler.
    if (empty($CFG->sitepolicyhandler) || $CFG->sitepolicyhandler !== 'tool_policy') {
        return;
    }

    if (!validateminor_helper::minor_session_exists()) {  // Digital minor check hasn't been done.
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

/**
 * Serve the embedded files.
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function tool_policy_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG;

    // Do not allow access to files if we are not set as the site policy handler.
    if (empty($CFG->sitepolicyhandler) || $CFG->sitepolicyhandler !== 'tool_policy') {
        return false;
    }

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    if ($filearea !== 'policydocumentsummary' && $filearea !== 'policydocumentcontent') {
        return false;
    }

    $itemid = array_shift($args);

    $policy = api::get_policy_version(null, $itemid);

    if (!api::is_public($policy)) {
        require_login();
    }

    if (!api::can_user_view_policy_version($policy)) {
        return false;
    }

    $filename = array_pop($args);

    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $args).'/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'tool_policy', $filearea, $itemid, $filepath, $filename);

    if (!$file) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Map icons for font-awesome themes.
 */
function tool_policy_get_fontawesome_icon_map() {
    return [
        'tool_policy:agreedno' => 'fa-times text-danger',
        'tool_policy:agreedyes' => 'fa-check text-success',
        'tool_policy:agreedyesonbehalf' => 'fa-check text-info'
    ];
}

/**
 * Check if current user has to accept some site policies.
 *
 * @return moodle_url|string The URL to a script where the user should accept the policies or empty if
 * the user can continue using the site without being redirected.
 */
function tool_policy_site_policy_handler() {
    global $CFG;
    if (!isguestuser()) {
        return $CFG->wwwroot . '/' . $CFG->admin . '/tool/policy/index.php';
    }
}
