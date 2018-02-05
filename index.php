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

$urlparams = array();
$url = new moodle_url('/admin/tool/policy/index.php', $urlparams);
list($title, $subtitle) = \tool_policy\page_helper::setup_for_agreedocs_page($url);

if (!empty($SESSION->wantsurl)) {
    $return = $SESSION->wantsurl;
} else {
    $return = $CFG->wwwroot.'/';
}

if (isguestuser()) {
    redirect($return);
}

if (empty($userid)) {
    $userid = $USER->id;
}

$usercontext = \context_user::instance($userid);
if ($userid == $USER->id) {
    require_capability('tool/policy:accept', context_system::instance());
} else {
    require_capability('tool/policy:acceptbehalf', $usercontext);
}

if (!empty($agreedoc) && confirm_sesskey()) {
    // Accept / revoke policies.
    $acceptversionids = array();
    foreach($agreedoc as $policyid) {
        $policy = tool_policy\api::get_policy($policyid);
        $acceptversionids[] = $policy->currentversionid;
    }
    \tool_policy\api::accept_policies($acceptversionids, $userid, null, current_language());
    // TODO: Revoke acceptances.
    //\tool_policy\api::revoke_acceptance($acceptversionids, $userid, null, current_language());
}

// If the user hasn't any policy versions to agreed to, redirect to return page.
if (!is_siteadmin() && $USER->id == $userid && $USER->policyagreed) {
    redirect($return);
}

$output = $PAGE->get_renderer('tool_policy');

echo $output->header();
$page = new \tool_policy\output\page_agreedocs($userid);
echo $output->render($page);
echo $output->footer();
