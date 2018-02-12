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
 * Show an user the policy documents to be agreed to.
 *
 * @package     tool_policy
 * @copyright   2018 Sara Arjona (sara@moodle.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');

$agreedoc =optional_param_array('agreedoc', null, PARAM_INT);
$userid = optional_param('userid', null, PARAM_INT);

if (!empty($SESSION->wantsurl)) {
    $return = $SESSION->wantsurl;
} else {
    $return = $CFG->wwwroot.'/';
}

// Guest users are not allowed to access to this page.
if (isguestuser()) {
    unset($SESSION->wantsurl);
    redirect($return);
}

$urlparams = array();
if (!empty($USER->id) && !empty($userid) && $userid != $USER->id) {
    $urlparams['userid'] = $userid;
}
$url = new moodle_url('/admin/tool/policy/index.php', $urlparams);
list($title, $subtitle) = \tool_policy\page_helper::setup_for_page($url);

if (empty($userid)) {
    $userid = $USER->id;
}

if (!empty($USER->id)) {
    // For existing users, it's needed to check they have the capability for accepting policies.
    if ($userid == $USER->id) {
        require_capability('tool/policy:accept', context_system::instance());
    } else {
        $usercontext = \context_user::instance($userid);
        require_capability('tool/policy:acceptbehalf', $usercontext);
    }
} else {
    // For new users, the userid parameter is ignored.
    if ($userid != $USER->id) {
        redirect($url);
    }
}

$policies = \tool_policy\api::list_policies(null, true, \tool_policy\api::AUDIENCE_LOGGEDIN);
// TODO: Decide what to do if there are no policies to agree but the user has policyagreed = 0.
if (!empty($agreedoc) && confirm_sesskey()) {
    if (!empty($USER->id)) {
        // Existing user.
        $lang = current_language();
        // Accept / revoke policies.
        $acceptversionids = array();
        foreach ($policies as $policy) {
            if (in_array($policy->id, $agreedoc)) {
                // Save policy version doc to accept it.
                $acceptversionids[] = $policy->currentversionid;
            } else {
                // TODO: Revoke policy doc.
                //\tool_policy\api::revoke_acceptance($policy->currentversionid, $userid);
            }
        }
        // Accept all policy docs saved in $acceptversionids.
        \tool_policy\api::accept_policies($acceptversionids, $userid, null, $lang);
    } else {
        // New user.
        // If the user has accepted all the policies, add this to the SESSION to let continue with the signup process.
        $SESSION->userpolicyagreed = empty(array_diff(array_keys($policies), $agreedoc));

        // TODO: Show a message to let know the user he/she must agree all the policies if he/she wants to create an user.
    }
}

$hasagreedsignupuser = empty($USER->id) && !empty($SESSION->userpolicyagreed);
$hasagreedloggeduser = $USER->id == $userid && !empty($USER->policyagreed);
// If the current user has the $USER->policyagreed = 1 or $SESSION->userpolicyagreed = 1, redirect to the return page.
if (!is_siteadmin() && ($hasagreedsignupuser || $hasagreedloggeduser)) {
    unset($SESSION->wantsurl);
    redirect($return);
}

// Redirect to policy docs before the consent page.
\tool_policy\page_helper::redirect_to_policies($userid, $policies, $url);

// Show the consent page.
$output = $PAGE->get_renderer('tool_policy');

echo $output->header();
$page = new \tool_policy\output\page_agreedocs($userid, $policies, $agreedoc, $url);
echo $output->render($page);
echo $output->footer();
