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

// Set up the page as an admin page 'tool_policy_managedocs'.
$urlparams = ($policyid ? ['policyid' => $policyid] : []) + ($versionid ? ['versionid' => $versionid] : []);
admin_externalpage_setup('tool_policy_managedocs', '', $urlparams,
    new moodle_url('/admin/tool/policy/acceptances.php'));

// Find all policies that need to be displayed. Unless versionid is specified we only display current versions.
$policies = \tool_policy\api::list_policies($policyid ? [$policyid] : null, !$versionid, \tool_policy\api::AUDIENCE_LOGGEDIN);
if ($versionid) {
    // If versionid is specified leave only the policy where this version is present and remove all other versions.
    $policies = array_filter($policies, function($policy) use ($versionid) {
        $policy->versions = array_intersect_key($policy->versions, [$versionid => true]);
        return !empty($policy->versions);
    });
}
if (!$policies) {
    throw new \moodle_exception('No policies found'); // TODO string
}

if ($policyid || $versionid) {
    $singlepolicy = reset($policies);
    $PAGE->navbar->add(format_string($singlepolicy->name),
        new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $singlepolicy->id]));
    if ($versionid && $versionid <> $singlepolicy->currentversionid) {
        $PAGE->navbar->add(format_string($singlepolicy->versions[$versionid]->revision));
    }
    // TODO add them to the heading?
}
$PAGE->navbar->add(get_string('useracceptances', 'tool_policy'));

$acceptances = new \tool_policy\acceptances_table('tool_policy_user_acceptances', $PAGE->url, $policies);
if ($acceptances->is_downloading()) {
    $acceptances->download();
}

$output = $PAGE->get_renderer('tool_policy');
echo $output->header();
echo $output->heading(get_string('useracceptances', 'tool_policy'));
$acceptances->display();
echo $output->footer();
