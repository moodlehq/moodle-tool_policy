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
$string['actions'] = 'Actions';
$string['activate'] = 'Set status to "Active"';
$string['activating'] = 'Activating a policy document';
$string['activateconfirm'] = '<p>You are about to activate a policy document <em>\'{$a->name}\'</em> and make the revision <em>\'{$a->revision}\'</em> the current one.</p><p>All users will be required to accept this new policy version to be able to use the site.</p>';
$string['addpolicydocument'] = 'Add new policy document';
$string['addpolicyversion'] = 'Add new version';
$string['agreed'] = 'Agreed';
$string['agreedby'] = 'Agreed by';
$string['agreedon'] = 'Agreed on';
$string['agreepolicies'] = 'Please agree to the following policies';
$string['backtotop'] = 'Back to top';
$string['consentdetails'] = 'Consent details';
$string['consentpagetitle'] = 'Policy summary and agreements';
$string['dataproc'] = 'Personal data processing';
$string['deleting'] = 'Deleting a version';
$string['deleteconfirm'] = '<p>Are you sure you want to delete a policy document <em>\'{$a->name}\'</em>?</p><p>This operation can not be undone.</p>';
$string['editingpolicydocument'] = 'Editing policy document';
$string['errorpolicyversionnotfound'] = 'There isn\'t any policy version with this identifier.';
$string['errorsaveasdraft'] = 'Minor change can not be saved as draft';
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
$string['iagree'] = 'I agree to the {$a}';
$string['iagreetothepolicy'] = 'I agree to the policy';
$string['inactivate'] = 'Set status to "Inactive"';
$string['inactivating'] = 'Inactivating a policy document';
$string['inactivatingconfirm'] = '<p>You are about to inactivate a policy document <em>\'{$a->name}\'</em> revision <em>\'{$a->revision}\'</em>.</p><p>The policy will not apply until some version is made the current one.</p>';
$string['invalidversionid'] = 'There isn\'t any policy document with this identifier.';
$string['movedown'] = 'Move down';
$string['moveup'] = 'Move up';
$string['mustagreetocontinue'] = 'Before continuing you must agree to all these policies.';
$string['newpolicy'] = 'New policy';
$string['newversion'] = 'New version';
$string['nofiltersapplied'] = 'No filters applied';
$string['nopermissiontoagreedocs'] = 'No permission to accept policies.';
$string['nopermissiontoagreedocs_desc'] = 'Sorry, you do not have the required permissions to accept policies.<br />You will not be able to use the site until the policies are agreed.';
$string['nopermissiontoagreedocsbehalf'] = 'No permission to accept policies on behalf of another user.';
$string['nopermissiontoagreedocsbehalf_desc'] = 'Sorry, you do not have the required permission to accept policies on behalf of {$a}.';
$string['nopermissiontoagreedocscontact'] = 'For further assistance, please contact the following person:';
$string['nopermissiontoviewpolicyversion'] = 'You do not have the required permissions to view this policy document version.';
$string['steppolicies'] = 'Policy document {$a->numpolicy} of {$a->totalpolicies}';
$string['pluginname'] = 'Policies';
$string['policiesagreements'] = 'Policies and agreements';
$string['policy:accept'] = 'Accept policies';
$string['policy:acceptbehalf'] = 'Accept policies on someone else\'s behalf';
$string['policy:managedocs'] = 'Manage policy documents';
$string['policy:manageprivacy'] = 'Manage privacy settings';
$string['policy:viewacceptances'] = 'View user acceptances reports';
$string['policydocaudience'] = 'User consent';
$string['policydocaudience0'] = 'All users';
$string['policydocaudience1'] = 'Registered users';
$string['policydocaudience2'] = 'Guests';
$string['policydoccontent'] = 'Full policy';
$string['policydochdrpolicy'] = 'Policy';
$string['policydochdrversion'] = 'Document version';
$string['policydocname'] = 'Name';
$string['policydocrevision'] = 'Version';
$string['policydocsummary'] = 'Summary';
$string['policydocsummary_help'] = 'This text should provide a summary of the policy, potentially in a simplified and easily accessible form, using clear and plain language.';
$string['policydocs'] = 'Policy documents';
$string['policydoctype'] = 'Type';
$string['policydoctype0'] = 'Site policy';
$string['policydoctype1'] = 'Privacy policy';
$string['policydoctype2'] = 'Third parties policy';
$string['policydoctype99'] = 'Other policy';
$string['policyversionacceptedinbehalf'] = 'This policy version has been accepted by another user on behalf of you.';
$string['policyversionacceptedinotherlang'] = 'This policy version has been accepted in a different language.';
$string['previousversions'] = '{$a} previous versions';
$string['privacyofficer'] = 'Privacy officer';
// TODO: Review the list of places where the privacyofficer will be shown.
$string['privacyofficer_desc'] = 'Information and contact details of the privacy officer, such as the address, email. phone... This information will be shown to the users in the consent page.';
$string['privacysettings'] = 'Privacy settings';
$string['readpolicy'] = 'Please read our {$a}';
$string['refertofullpolicytext'] = 'Please refer to the full {$a} text if you would like to review.';
$string['save'] = 'Save';
$string['saveasdraft'] = 'Save as draft';
$string['status'] = 'Policy status';
$string['statusinfo'] = 'An active policy will require consent from new users. Registered users will need to consent to this policy on their next logged in session.';
$string['status'] = 'Status';
$string['status0'] = 'Draft';
$string['status1'] = 'Active';
$string['status2'] = 'Inactive';
$string['useracceptancecount'] = '{$a->agreedcount} of {$a->userscount} ({$a->percent}%)';
$string['useracceptancecountna'] = 'N/A';
$string['useracceptances'] = 'User acceptances';
$string['userpolicysettings'] = 'Policies';
$string['usersaccepted'] = 'Acceptance';
$string['viewarchived'] = 'View previous versions';
$string['viewconsentpageforuser'] = 'Viewing this page in behalf of {$a}';
$string['agreedno'] = 'Not agreed';
$string['agreedyes'] = 'Agreed';
$string['agreedyesonbehalf'] = 'Agreed on behalf of';
