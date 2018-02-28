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
 * Plugin administration pages are defined here.
 *
 * @package     tool_policy
 * @category    admin
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Do nothing if we are not set as the site policies handler.
if (empty($CFG->sitepolicyhandler) || $CFG->sitepolicyhandler !== 'tool_policy') {
    return;
}

$managecaps = [
    'tool/policy:managedocs',
    'tool/policy:manageprivacy',
    'tool/policy:viewacceptances',
];

if ($hassiteconfig || has_any_capability($managecaps, context_system::instance())) {

    $ADMIN->add('users', new admin_category(
        'tool_policy_admin_category',
        new lang_string('policiesagreements', 'tool_policy')
    ));

    $ADMIN->add('tool_policy_admin_category', new admin_externalpage(
        'tool_policy_managedocs',
        new lang_string('policydocs', 'tool_policy'),
        new moodle_url('/admin/tool/policy/managedocs.php'),
        ['tool/policy:managedocs', 'tool/policy:viewacceptances']
    ));

    $privacysettings = new admin_settingpage(
        'tool_policy_privacy',
        new lang_string('privacysettings', 'tool_policy'),
        ['tool/policy:manageprivacy']
    );

    $ADMIN->add('tool_policy_admin_category', $privacysettings);

    if ($ADMIN->fulltree) {
        // TODO: Decide whether to maintain or not this field for displaying information about the officer in the consent page.
        $privacysettings->add(new admin_setting_configtextarea(
            'tool_policy/privacyofficer',
            new lang_string('privacyofficer', 'tool_policy'),
            new lang_string('privacyofficer_desc', 'tool_policy'),
            '',
            PARAM_RAW
        ));
    }
}
