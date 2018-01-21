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
 * Plugin strings are defined here.
 *
 * @package     tool_policy
 * @category    string
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addpolicydocument'] = 'Add new policy document';
$string['addpolicyversion'] = 'Add new version';
$string['ageofdigitalconsentmap'] = 'Age of digital consent';
$string['ageofdigitalconsentmap_desc'] = 'Allows to define the age of digital consent. Users below the age are not allowed to accept the policy documents on their own. In such a case, policies must accepted by the holder of parental responsibility over the user. The age may be different in different countries. If there is no explicit value provided for the user\'s country code, the wildcard character\'s value is used.';
$string['dataproc'] = 'Personal data processing';
$string['editingpolicydocument'] = 'Editing policy document';
$string['inactivate'] = 'Inactivate';
$string['inactivating'] = 'Inactivating a policy document';
$string['inactivatingconfirm'] = '<p>You are about to inactivate a policy document <em>\'{$a->name}\'</em> revision <em>\'{$a->revision}\'</em>.</p><p>The policy will not apply until some version is made the current one.</p>';
$string['inactive'] = 'Inactive';
$string['makeactive'] = 'Activate';
$string['makecurrent'] = 'Make current';
$string['makingcurrent'] = 'Activating a new policy version';
$string['makingcurrentconfirm'] = '<p>You are about to activate a policy document <em>\'{$a->name}\'</em> and make the revision <em>\'{$a->revision}\'</em> the current one.</p><p>All users will be required to accept this new policy version to be able to use the site.</p>';
$string['pluginname'] = 'Policies';
$string['policiesagreements'] = 'Policies and agreements';
$string['policy:accept'] = 'Accept policies';
$string['policy:acceptbehalf'] = 'Accept policies on someone else\'s behalf';
$string['policy:managedataproc'] = 'Manage data processing services';
$string['policy:managedocs'] = 'Manage policy documents';
$string['policy:manageprivacy'] = 'Manage privacy settings';
$string['policydoccontent'] = 'Content';
$string['policydocdesc'] = 'Description';
$string['policydocname'] = 'Policy name';
$string['policydocrevision'] = 'Revision';
$string['policydocs'] = 'Policy documents';
$string['privacysettings'] = 'Privacy settings';
$string['saveasnew'] = 'Save as new version';
$string['saveasnew0'] = '<strong>No</strong>. Made changes do not amend the meaning of the policy text, terms or conditions. Users do not need to reconfirm their consent.';
$string['saveasnew1'] = '<strong>Yes</strong>. This change amends the meaning of the policy. Users will be required to reconfirm their consent with this new version.';
$string['statusarchive'] = 'Archive';
$string['statuscurrent'] = 'Current';
$string['statusdraft'] = 'Draft';
$string['userpolicysettings'] = 'Policies';
$string['usersaccepted'] = 'Users accepted';
$string['newpolicy'] = 'New policy';
$string['newversion'] = 'New version';
$string['newpolicysite'] = 'Site policy';
$string['newpolicyprivacy'] = 'Privacy policy';
$string['newpolicythirdparties'] = 'Third parties policy';
$string['newpolicyother'] = 'Other policy';
