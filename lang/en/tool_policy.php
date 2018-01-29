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
$string['guestconsent:continue'] = 'Continue';
$string['guestconsentmessage'] = 'If you continue browsing this website, we\'ll assume that you agree to the following policy documents:';
$string['inactivate'] = 'Inactivate';
$string['inactivating'] = 'Inactivating a policy document';
$string['inactivatingconfirm'] = '<p>You are about to inactivate a policy document <em>\'{$a->name}\'</em> revision <em>\'{$a->revision}\'</em>.</p><p>The policy will not apply until some version is made the current one.</p>';
$string['inactive'] = 'Inactive';
$string['makeactive'] = 'Activate';
$string['makecurrent'] = 'Make current';
$string['makingcurrent'] = 'Activating a new policy version';
$string['makingcurrentconfirm'] = '<p>You are about to activate a policy document <em>\'{$a->name}\'</em> and make the revision <em>\'{$a->revision}\'</em> the current one.</p><p>All users will be required to accept this new policy version to be able to use the site.</p>';
$string['newpolicy'] = 'New policy';
$string['newpolicyother'] = 'Other policy';
$string['newpolicyprivacy'] = 'Privacy policy';
$string['newpolicysite'] = 'Site policy';
$string['newpolicythirdparties'] = 'Third parties policy';
$string['newversion'] = 'New version';
$string['pluginname'] = 'Policies';
$string['policiesagreements'] = 'Policies and agreements';
$string['policy:accept'] = 'Accept policies';
$string['policy:acceptbehalf'] = 'Accept policies on someone else\'s behalf';
$string['policy:managedataproc'] = 'Manage data processing services';
$string['policy:managedocs'] = 'Manage policy documents';
$string['policy:manageprivacy'] = 'Manage privacy settings';
$string['policy:viewacceptances'] = 'View user acceptances reports';
$string['policydoccontent'] = 'Content';
$string['policydocdesc'] = 'Description';
$string['policydocname'] = 'Policy name';
$string['policydocrevision'] = 'Revision';
$string['policydocrevisioncurrent'] = 'Current revision';
$string['policydocs'] = 'Policy documents';
$string['privacysettings'] = 'Privacy settings';
$string['saveasnew'] = 'Save as new version';
$string['saveasnew_help'] = 'Should the text be saved as a new version of the policy text?

* Select __No__ if the made changes do not amend the meaning of the policy text, terms or conditions. Users do not need to reconfirm their consent.
* Select __Yes__ if this change amends the meaning of the policy. Users will be required to reconfirm their consent with this new version.';
$string['statusarchive'] = 'Archive';
$string['statuscurrent'] = 'Current';
$string['statusdraft'] = 'Draft';
$string['template_privacy_content'] = '<h2>Privacy policy example</h2><h3>What personal data are collected?</h3><ul><li>We need to know your full name and email to identify you as a person.</li><li>As you use the site, your study progress, grades and overall activity on the site is tracked and logged.</li><li>All the content you submit — such as assignments, quiz attempts or forum posts — is stored in the database.</li></ul><h3>How is this information used?</h3><p>We use the information to evaluate your progress and performance and to improve the content. We do it to help you learn more effectively.</p><h3>Who can I contact regarding privacy?</h3><p>The data protection officer at our school is Mrs. Tabby. She may be contacted via email privacy@example.com</p><h3>How long is my data stored?</h3><p>Schools in our country are obliged to archive all students data for 10 years after the end of study period.</p>';
$string['template_privacy_name'] = 'Privacy policy';
$string['template_site_content'] = '<h2>Site policy example</h2><p>By agreeing to this site policy, you acknowledge that you understand and agree with the following points.</p><h3>Changes to the terms</h3><p>These terms may change. When the changes are important, you will be asked to agree with the new version of the policy.</p><h3>Your content</h3><ul><li>We do not take any ownership of your content when you post it on our site.</li><li>If you post content you own, you agree it can be used under the terms of CC BY 4.0 or any future version of that license.</li><li>If you do not own the content, then you should not post it unless it is in the public domain or licensed CC BY 4.0, except that you may also post pictures and videos if you are authorized to use them under law (e.g., fair use) or if they are available under any CC license.</li><li>You must note that information on the file when you upload it.</li><li>You are responsible for any content you upload to our sites.</li></ul>';
$string['template_site_name'] = 'Site policy';
$string['template_thirdparties_content'] = '<h2>Sharing data with third parties</h2><p>This is the list of other institutions with whom personal data may be shared.</p><table class="generaltable"><thead><tr><th scope="col">Name</th><th scope="col">Purpose of data sharing</th><th scope="col">Data we share with them</th></tr></thead><tbody><tr><td><a href="https://example.com">Pandora\'s Box Ltd.</a></td><td>Regular database backup are stored in their data center and can be used to restore our school systems in case of damage.</td><td>All data we have in our database. Data are encrypted before being sent to them.</td></tr></tbody></table>';
$string['template_thirdparties_name'] = 'Third parties policy example';
$string['useracceptances'] = 'User acceptances';
$string['userpolicysettings'] = 'Policies';
$string['usersaccepted'] = 'Users accepted';
