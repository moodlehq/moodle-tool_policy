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
use tool_policy\policy_version;

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

    foreach (api::list_current_versions() as $policyversion) {
        $userpolicysettings->add($policyversion->name, new moodle_url('/admin/tool/policy/view.php', [
            'policyid' => $policyversion->policyid,
            'versionid' => $policyversion->id,
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
 * Hooks redirection to policy acceptance pages before sign up.
 */
function tool_policy_pre_signup_requests() {
    global $CFG, $SESSION;

    // Do nothing if we are not set as the site policies handler.
    if (empty($CFG->sitepolicyhandler) || $CFG->sitepolicyhandler !== 'tool_policy') {
        return;
    }

    $userpolicyagreed = cache::make('core', 'presignup')->get('tool_policy_userpolicyagreed');
    if (!$userpolicyagreed) {
        // Redirect to "Policy" pages for consenting before creating the user.
        $SESSION->wantsurl = (new \moodle_url('/login/signup.php'))->out();
        redirect(new \moodle_url('/admin/tool/policy/index.php'));
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

    $policy = api::get_policy_version($itemid);

    if ($policy->status != policy_version::STATUS_ACTIVE) {
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
 * Site policy handler callback implemented by tool_policy as an alternative mechanisms
 * for site policies managements and agreements.
 *
 * The return value will depend on $action:
 * [redirect] =  Policy acceptance page.
 * [viewall] = URL to a page (with 'popup' layout, without any headers) that lists all policies on the same page, one under another.
 * [acceptall] = Accept all policies for the current user.
 * [checkcanaccept] = Return whether current user is allowed to accept policies for themselves.
 *
 * @param string $action []
 * @return moodle_url|string The URL to be redirected depending on the $action or the
 * result of the callback (if no URL has to be returned).
 */
function tool_policy_site_policy_handler($action = 'redirect') {
    global $CFG, $USER, $DB;

    if (!isguestuser()) {
        if ($action === 'redirect') {
            // Policy acceptance page.
            return (new \moodle_url('/admin/tool/policy/index.php'))->out();
        } else if ($action === 'viewall') {
            // Page with all the public policies of this site, one under another.
            return (new \moodle_url('/admin/tool/policy/viewall.php'))->out();
        } else if ($action === 'acceptall') {
            // Accepts all policies with a current version for logged users on behalf of the current user.
            $policies = api::list_current_versions(policy_version::AUDIENCE_LOGGEDIN);
            $policyversionid = array();
            foreach ($policies as $policy) {
                $policyversionid[] = $policy->currentversionid;
            }
            api::accept_policies($policyversionid);
        } else if ($action === 'checkcanaccept') {
            return has_capability('tool/policy:accept', context_system::instance());
        } else {
            throw new coding_exception('Unrecognised action');
        }
    }
}
