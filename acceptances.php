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

admin_externalpage_setup('tool_policy_managedocs', '', ['policyid' => $policyid, 'versionid' => $versionid],
    new moodle_url('/admin/tool/policy/acceptances.php',
        ['policyid' => $policyid, 'versionid' => $versionid, 'userid' => $userid]));
require_capability('tool/policy:viewacceptances', \context_system::instance());

$output = $PAGE->get_renderer('tool_policy');
$PAGE->navbar->add(get_string('useracceptances', 'tool_policy'));

echo $output->header();
echo $output->heading(get_string('useracceptances', 'tool_policy'));
echo $output->footer();
