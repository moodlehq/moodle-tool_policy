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
 * Edit/create a policy document version.
 *
 * @package     tool_policy
 * @category    admin
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$policyid = optional_param('policyid', null, PARAM_INT);
$versionid = optional_param('versionid', null, PARAM_INT);
$makecurrent = optional_param('makecurrent', null, PARAM_INT);
$inactivate = optional_param('inactivate', null, PARAM_INT);
$confirm = optional_param('confirm', false, PARAM_BOOL);
$template = optional_param('template', '', PARAM_ALPHA);

admin_externalpage_setup('tool_policy_managedocs', '', ['policyid' => $policyid, 'versionid' => $versionid],
    new moodle_url('/admin/tool/policy/editpolicydoc.php'));
require_capability('tool/policy:managedocs', context_system::instance());

$output = $PAGE->get_renderer('tool_policy');
$PAGE->navbar->add(get_string('editingpolicydocument', 'tool_policy'));

if ($makecurrent) {
    $policy = \tool_policy\api::get_policy_version($policyid, $makecurrent);

    if ($confirm) {
        require_sesskey();
        \tool_policy\api::make_current($policyid, $makecurrent);
        redirect(new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $policyid]));
    }

    echo $output->header();
    echo $output->heading(get_string('makingcurrent', 'tool_policy'));
    echo $output->confirm(
        get_string('makingcurrentconfirm', 'tool_policy', [
            'name' => format_string($policy->name),
            'revision' => format_string($policy->revision),
        ]),
        new moodle_url($PAGE->url, ['makecurrent' => $makecurrent, 'confirm' => 1]),
        new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $policyid])
    );
    echo $output->footer();
    die();
}

if ($inactivate) {
    $policy = \tool_policy\api::get_policy_version($policyid, $inactivate);

    if ($policy->currentversionid != $policy->versionid) {
        redirect(new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $policyid]));
    }

    if ($confirm) {
        require_sesskey();
        \tool_policy\api::inactivate($policyid);
        redirect(new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $policyid]));
    }

    echo $output->header();
    echo $output->heading(get_string('inactivating', 'tool_policy'));
    echo $output->confirm(
        get_string('inactivatingconfirm', 'tool_policy', [
            'name' => format_string($policy->name),
            'revision' => format_string($policy->revision),
        ]),
        new moodle_url($PAGE->url, ['inactivate' => $inactivate, 'confirm' => 1]),
        new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $policyid])
    );
    echo $output->footer();
    die();
}

$formdata = \tool_policy\api::form_policydoc_data($policyid, $versionid, $template);
$form = new \tool_policy\form\policydoc($PAGE->url, ['formdata' => $formdata]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $policyid]));

} else if ($data = $form->get_data()) {
    if (empty($policyid)) {
        \tool_policy\api::form_policydoc_add($data);

    } else if (empty($versionid) || !empty($data->saveasnew)) {
        \tool_policy\api::form_policydoc_update_new($policyid, $data);

    } else {
        \tool_policy\api::form_policydoc_update_overwrite($policyid, $versionid, $data);
    }

    redirect(new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $policyid]));

} else {
    $form->set_data($formdata);

    echo $output->header();
    echo $output->heading(get_string('editingpolicydocument', 'tool_policy'));
    echo $form->render();
    echo $output->footer();
}
