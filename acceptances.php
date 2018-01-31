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
 * View user acceptances to the policies
 *
 * @package     tool_policy
 * @copyright   2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$policyid = optional_param('policyid', null, PARAM_INT);
$versionid = optional_param('versionid', null, PARAM_INT);
$userid = optional_param('userid', null, PARAM_INT);
$acceptfor = optional_param('acceptfor', null, PARAM_INT);

if ($acceptfor) {
    $versionid = required_param('versionid', PARAM_INT);
    $usercontext = context_user::instance($acceptfor);
    require_capability('tool/policy:acceptbehalf', $usercontext);
    $userid = $acceptfor;
}

// Set up the page either in an individual user context or as an admin page 'tool_policy_managedocs'.
if ($userid || !has_capability('tool/policy:viewacceptances', context_system::instance())) {
    // Viewing report for the individual user - either for oneself or for mentee or this is a privacy officer.
    require_login();
    if (isguestuser()) {
        print_error('noguest');
    }
    $context = context_user::instance($userid ?: $USER->id);
    if ($userid && $USER->id != $userid) {
        if (!has_any_capability(['tool/policy:acceptbehalf', 'tool/policy:viewacceptances'], $context)) {
            require_capability('tool/policy:viewacceptances', $context);
        }
    } else {
        $userid = $USER->id;
    }
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/admin/tool/policy/acceptances.php', ['userid' => $userid]));
} else {
    // Viewing report for multiple users.
    admin_externalpage_setup('tool_policy_managedocs', '', ['policyid' => $policyid, 'versionid' => $versionid],
        new moodle_url('/admin/tool/policy/acceptances.php', ['policyid' => $policyid, 'versionid' => $versionid]));
}

$policy = null;
if ($versionid) {
    $policy = tool_policy\api::get_policy_version($policyid, $versionid);
} else if ($policyid) {
    $policy = tool_policy\api::get_policy($policyid);
}

if ($policy && !$userid) {
    $PAGE->navbar->add(format_string($policy->name),
        new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $policy->policyid]));
}
$PAGE->navbar->add(get_string('useracceptances', 'tool_policy'));

if ($acceptfor) {
    $user = $DB->get_record('user', ['id' => $acceptfor], 'id,'.get_all_user_name_fields(true), MUST_EXIST);
    $returnurl = optional_param('returnurl', null, PARAM_LOCALURL);
    $form = new \tool_policy\form\accept_policy(null, ['policy' => $policy, 'user' => $user]);
    $form->set_data('returnurl', $returnurl);

    if ($form->is_cancelled()) {
        redirect($returnurl ?: $PAGE->url);
    } else if ($data = $form->get_data()) {
        \tool_policy\api::accept_policies([$policy->versionid], $user->id, $data->note);
        redirect($returnurl ?: $PAGE->url);
    }
}

$output = $PAGE->get_renderer('tool_policy');
echo $output->header();
echo $output->heading(get_string('useracceptances', 'tool_policy'));
if ($acceptfor) {
    $form->display();
} else {
    $acceptances = new \tool_policy\output\acceptances();
    echo $output->render($acceptances);
}
echo $output->footer();
