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

$string['acceptanceacknowledgement'] = 'I acknowledge that consents to these policies have been acquired';
$string['acceptancecount'] = '{$a->agreedcount} of {$a->policiescount}';
$string['acceptancenote'] = 'Remarks';
$string['acceptancepolicies'] = 'Policies';
$string['acceptancessavedsucessfully'] = 'The agreements has been saved successfully.';
$string['acceptancestatusoverall'] = 'Overall';
$string['acceptanceusers'] = 'Users';
$string['addpolicydocument'] = 'Add new policy document';
$string['addpolicyversion'] = 'Add new version';
$string['agelocationverification'] = 'Age and location verification';
$string['ageofdigitalconsentmap'] = 'Age of digital consent';
$string['ageofdigitalconsentmap_desc'] = 'Allows to define the age of digital consent. Users below the age are not allowed to accept the policy documents on their own. In such a case, policies must accepted by the holder of parental responsibility over the user. The age may be different in different countries. If there is no explicit value provided for the user\'s country code, the wildcard character\'s value is used.';
$string['agreed'] = 'Agreed';
$string['agreedby'] = 'Agreed by';
$string['agreedon'] = 'Agreed on';
$string['agreepolicies'] = 'Please agree to the following policies';
$string['backtohome'] = 'Back to home';
$string['backtotop'] = 'Back to top';
$string['consentdetails'] = 'Consent details';
$string['consentpagetitle'] = 'Policy summary and agreements';
$string['contactadmin'] = 'Contact administrator';
$string['contactadmin_desc'] = 'To create an account on this site please have your parent / guardian contact the following person.';
$string['dataproc'] = 'Personal data processing';
$string['editingpolicydocument'] = 'Editing policy document';
$string['errorpolicyversionnotfound'] = 'There isn\'t any policy version with this identifier.';
$string['errorusercantviewpolicyversion'] = 'The user hasn\'t access to this policy version.';
$string['event_acceptance_created'] = 'User policy acceptance created';
$string['event_acceptance_updated'] = 'User policy acceptance updated';
$string['filtercapabilityno'] = 'Permission: Can not agree';
$string['filtercapabilityyes'] = 'Permission: Can agree';
$string['filterrevision'] = 'Version: {$a}';
$string['filterrevisionstatus'] = 'Version: {$a->name} ({$a->status})';
$string['filterrole'] = 'Role: {$a}';
$string['filters'] = 'Filters';
$string['filterstatusno'] = 'Status: Not agreed';
$string['filterstatusyes'] = 'Status: Agreed';
$string['filterplaceholder'] = 'Search keyword or select filter';
$string['filterpolicy'] = 'Policy: {$a}';
$string['guestconsent:continue'] = 'Continue';
$string['guestconsentmessage'] = 'If you continue browsing this website, we\'ll assume that you agree to the following policy documents:';
$string['howoldareyou'] = 'How old are you?';
$string['iagree'] = 'I agree to the {$a}';
$string['iagreetothepolicy'] = 'I agree to the policy';
$string['inactivate'] = 'Inactivate';
$string['inactivating'] = 'Inactivating a policy document';
$string['inactivatingconfirm'] = '<p>You are about to inactivate a policy document <em>\'{$a->name}\'</em> revision <em>\'{$a->revision}\'</em>.</p><p>The policy will not apply until some version is made the current one.</p>';
$string['inactive'] = 'Inactive';
$string['invalidage'] = 'Invalid age.';
$string['invalidversionid'] = 'There isn\'t any policy document with this identifier.';
$string['makeactive'] = 'Activate';
$string['makecurrent'] = 'Make current';
$string['makingcurrent'] = 'Activating a new policy version';
$string['makingcurrentconfirm'] = '<p>You are about to activate a policy document <em>\'{$a->name}\'</em> and make the revision <em>\'{$a->revision}\'</em> the current one.</p><p>All users will be required to accept this new policy version to be able to use the site.</p>';
$string['mustagreetocontinue'] = 'Before continuing you must agree to all these policies.';
$string['newpolicy'] = 'New policy';
$string['newpolicyother'] = 'Other policy';
$string['newpolicyprivacy'] = 'Privacy policy';
$string['newpolicysite'] = 'Site policy';
$string['newpolicythirdparties'] = 'Third parties policy';
$string['newversion'] = 'New version';
$string['nofiltersapplied'] = 'No filters applied';
$string['nopermissiontoviewpolicyversion'] = 'You do not have the required permissions to view this policy document version.';
// TODO: Change dummy with real text in explanationdigitalminor.
$string['explanationdigitalminor'] = 'Some explanation about digital minors and ensuring personal information is not stored / processed / etc.';
$string['steppolicies'] = 'Policy document {$a->numpolicy} of {$a->totalpolicies}';
$string['pluginname'] = 'Policies';
$string['policiesagreements'] = 'Policies and agreements';
$string['policy:accept'] = 'Accept policies';
$string['policy:acceptbehalf'] = 'Accept policies on someone else\'s behalf';
$string['policy:managedocs'] = 'Manage policy documents';
$string['policy:manageprivacy'] = 'Manage privacy settings';
$string['policy:viewacceptances'] = 'View user acceptances reports';
$string['policydocaudience'] = 'Targeted users';
$string['policydocaudience_help'] = 'Allows to control what users this policy applies to.';
$string['policydocaudience_all'] = 'All users';
$string['policydocaudience_loggedin'] = 'Logged in users only';
$string['policydocaudience_guests'] = 'Guest users only';
$string['policydoccontent'] = 'Content';
$string['policydoccontent_help'] = 'The full text content of the policy document.';
$string['policydocdesc'] = 'Description';
$string['policydocdesc_help'] = 'Short description of the policy document as will be seen at places with list of policy documents.';
$string['policydochdrpolicy'] = 'Policy';
$string['policydochdrversion'] = 'Document version';
$string['policydocname'] = 'Policy name';
$string['policydocname_help'] = 'The name of the policy document as will be seen at places with list of policy documents.';
$string['policydocrevision'] = 'Revision';
$string['policydocrevision_help'] = 'This identifies a particular version of the policy document for users. The actual format is arbitrary. Commonly this is date-based or follows some version numbering scheme.';
$string['policydocrevisioncurrent'] = 'Current revision';
$string['policydocsummary'] = 'Summary';
$string['policydocsummary_help'] = 'This text should provide a summary of the policy, potentially in a simplified and easily accessible form, using clear and plain language.';
$string['policydocs'] = 'Policy documents';
$string['policyversionacceptedinbehalf'] = 'This policy version has been accepted by another user on behalf of you.';
$string['policyversionacceptedinotherlang'] = 'This policy version has been accepted in a different language.';
$string['privacyofficer'] = 'Privacy officer';
// TODO: Review the list of places where the privacyofficer will be shown.
$string['privacyofficer_desc'] = 'Information and contact details of the privacy officer, such as the address, email. phone... This information will be shown to the users in the consent page.';
$string['privacysettings'] = 'Privacy settings';
$string['proceed'] = 'Proceed';
$string['readpolicy'] = 'Please read our {$a}';
$string['refertofullpolicytext'] = 'Please refer to the full {$a} text if you would like to review.';
$string['revisionunique'] = 'Revision must be unique for each policy document.';
$string['saveasnew'] = 'Save as new version';
$string['saveasnew_help'] = 'Should the text be saved as a new version of the policy text?

* Select __No__ if the made changes do not amend the meaning of the policy text, terms or conditions. Users do not need to reconfirm their consent.
* Select __Yes__ if this change amends the meaning of the policy. Users will be required to reconfirm their consent with this new version.';
$string['statusarchive'] = 'Archive';
$string['statuscurrent'] = 'Current';
$string['statusdraft'] = 'Draft';
$string['template_privacy_content'] = '<h2>Privacy policy example</h2><h3>What personal data are collected?</h3><ul><li>We need to know your full name and email to identify you as a person.</li><li>As you use the site, your study progress, grades and overall activity on the site is tracked and logged.</li><li>All the content you submit — such as assignments, quiz attempts or forum posts — is stored in the database.</li></ul><h3>How is this information used?</h3><p>We use the information to evaluate your progress and performance and to improve the content. We do it to help you learn more effectively.</p><h3>Who can I contact regarding privacy?</h3><p>The data protection officer at our school is Mrs. Tabby. She may be contacted via email privacy@example.com</p><h3>How long is my data stored?</h3><p>Schools in our country are obliged to archive all students data for 10 years after the end of study period.</p>';
$string['template_privacy_description'] = 'How we store and process users personal data';
$string['template_privacy_name'] = 'Privacy policy';
$string['template_privacy_summary'] = '<p><strong>We store various personal data in our database on our own server - for example your full name, email, grades and activity on the site. All your content is stored in the database, too. We evaluate your progress and performance to improve the content and to help you learn more effectively. We have data protection officer at our school to be contacted in case of any troubles or questions. We have to archive your personal data for 10 years.</p>';
$string['template_site_content'] = '<h2>Site policy full text example</h2><p>By agreeing to this site policy, you acknowledge that you understand and agree with the following points.</p><h3>Changes to the terms</h3><p>These terms may change. When the changes are important, you will be asked to agree with the new version of the policy.</p><h3>Your content</h3><ul><li>We do not take any ownership of your content when you post it on our site.</li><li>If you post content you own, you agree it can be used under the terms of <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a> or any future version of that license.</li><li>If you do not own the content, then you should not post it unless it is in the public domain or licensed CC BY 4.0, except that you may also post pictures and videos if you are authorized to use them under law (e.g., fair use) or if they are available under any CC license.</li><li>You must note that information on the file when you upload it.</li><li>You are responsible for any content you upload to our sites.</li></ul>';
$string['template_site_description'] = 'Rules, terms and conditions at this site';
$string['template_site_name'] = 'Site policy';
$string['template_site_summary'] = '<p><strong>These policies may change. All contents must be under <a href="https://creativecommons.org/licenses/by/4.0/">Creative Commons</a> license. You are responsible for what you submit and share here.</strong></p>';
$string['template_thirdparties_content'] = '<h2>Sharing data with third parties</h2><p>This is the list of other institutions with whom personal data may be shared.</p><table class="generaltable"><thead><tr><th scope="col">Name</th><th scope="col">Purpose of data sharing</th><th scope="col">Data we share with them</th></tr></thead><tbody><tr><td><a href="https://example.com">Pandora\'s Box Ltd.</a></td><td>Regular database backup are stored in their data center and can be used to restore our school systems in case of damage.</td><td>All data we have in our database. Data are encrypted before being sent to them.</td></tr></tbody></table>';
$string['template_thirdparties_description'] = 'Sharing personal data with other institutions';
$string['template_thirdparties_name'] = 'Third parties policy example';
$string['template_thirdparties_summary'] = '<p><strong>Some of your personal data are shared with other institutions. The list of these institutions, description of shared data and the purpose of that sharing is available in the full text of the policy.</strong></p>';
$string['useracceptancecount'] = '{$a->agreedcount} of {$a->userscount} ({$a->percent}%)';
$string['useracceptances'] = 'User acceptances';
$string['userpolicysettings'] = 'Policies';
$string['usersaccepted'] = 'Users accepted';
$string['viewconsentpageforuser'] = 'Viewing this page in behalf of {$a}';
$string['wheredoyoulive'] = 'Where do you live?';
$string['whyisthisrequired'] = 'Why is this required?';
$string['agreedno'] = 'Not agreed';
$string['agreedyes'] = 'Agreed';
$string['agreedyesonbehalf'] = 'Agreed on behalf of';
