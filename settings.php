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

$managecaps = [
    'tool/policy:managedocs',
    'tool/policy:manageprivacy',
    'tool/policy:managedataproc',
    'tool/policy:viewacceptances',
];

if ($hassiteconfig || has_any_capability($managecaps, context_system::instance())) {

    $ADMIN->add('users', new admin_category(
        'tool_policy_admin_category',
        new lang_string('policiesagreements', 'tool_policy')
    ));

    $privacysettings = new admin_settingpage(
        'tool_policy_privacy',
        new lang_string('privacysettings', 'tool_policy'),
        ['tool/policy:manageprivacy']
    );

    $ADMIN->add('tool_policy_admin_category', $privacysettings);

    if ($ADMIN->fulltree) {
        $privacysettings->add(new admin_setting_configtextarea(
            'tool_policy/agedigitalconsentmap',
            new lang_string('ageofdigitalconsentmap', 'tool_policy'),
            new lang_string('ageofdigitalconsentmap_desc', 'tool_policy'),
            // See {@link https://gdpr-info.eu/art-8-gdpr/}.
            // See {@link https://www.betterinternetforkids.eu/web/portal/practice/awareness/detail?articleId=2019355}.
            implode(PHP_EOL, [
                '* 16',
                'AT 14',
                'CZ 13',
                'DE 14',
                'DK 13',
                'ES 13',
                'FI 15',
                'GB 13',
                'HU 14',
                'IE 13',
                'LT 16',
                'LU 16',
                'NL 16',
                'PL 13',
                'SE 13',
            ]),
            PARAM_RAW
        ));
        $privacysettings->add(new admin_setting_configtextarea(
            'tool_policy/privacyofficer',
            new lang_string('privacyofficer', 'tool_policy'),
            new lang_string('privacyofficer_desc', 'tool_policy'),
            new lang_string('privacyofficer_default', 'tool_policy'),
            PARAM_RAW
        ));
    }

    $ADMIN->add('tool_policy_admin_category', new admin_externalpage(
        'tool_policy_managedocs',
        new lang_string('policydocs', 'tool_policy'),
        new moodle_url('/admin/tool/policy/managedocs.php'),
        ['tool/policy:managedocs', 'tool/policy:viewacceptances']
    ));

    $ADMIN->add('tool_policy_admin_category', new admin_externalpage(
        'tool_policy_managedataproc',
        new lang_string('dataproc', 'tool_policy'),
        new moodle_url('/admin/tool/policy/managedataproc.php'),
        ['tool/policy:managedataproc']
    ));
}
