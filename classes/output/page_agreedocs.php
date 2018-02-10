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
 * Provides {@link tool_policy\output\renderer} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 Sara Arjona <sara@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\output;

defined('MOODLE_INTERNAL') || die();

use context_system;
use core_user;
use html_writer;
use moodle_url;
use renderable;
use renderer_base;
use single_button;
use templatable;
use tool_policy\api;
use tool_policy\page_helper;

/**
 * Represents a page for showing all the policy documents which an user has to agree to.
 *
 * @copyright 2018 Sara Arjona <sara@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_agreedocs implements renderable, templatable {

    /** @var array $policies List of public policies objects with information about the user acceptance. */
    protected $policies = null;

    /** @var array $agreedocs List of policy identifiers which the user has agreed using the form. */
    protected $agreedocs = null;

    /** @var int User id who wants to accept this page. */
    protected $behalfid = null;

    /**
     * Prepare the page for rendering.
     *
     * @param array $agreedocs Array with the policy identifiers which the user has agreed using the form.
     * @param int $behalfid The userid to accept the policy versions as (such as child's id).
     */
    public function __construct($agreedocs = null, $behalfid = 0) {
        global $USER;

        $this->agreedocs = $agreedocs;
        if (empty($this->agreedocs)) {
            $this->agreedocs = [];
        }

        $this->behalfid = $behalfid;
        if (empty($this->behalfid)) {
            $this->behalfid = $USER->id;
        }

        // TODO: Get the policies for this audience, which are all except the specifics for guests.
        $this->policies = api::list_policies(null, true);

        $this->accept_and_revoke_policies();
        $this->prepare_global_page_access();
        $this->prepare_user_acceptances();
    }

    /**
     * Accept and revoke the policy versions.
     *
     */
    protected function accept_and_revoke_policies() {
        global $USER, $SESSION;

        // TODO: Make sure the user has the right capabilities for accepting/revoking these policies.

        // TODO: Decide what to do if there are no policies to agree but the user has policyagreed = 0.
        if (!empty($this->agreedocs) && confirm_sesskey()) {
            if (!empty($USER->id)) {
                // Existing user.
                $lang = current_language();
                // Accept / revoke policies.
                $acceptversionids = array();
                foreach ($this->policies as $policy) {
                    if (in_array($policy->id, $this->agreedocs)) {
                        // Save policy version doc to accept it.
                        $acceptversionids[] = $policy->currentversionid;
                    } else {
                        // TODO: Revoke policy doc.
                        //api::revoke_acceptance($policy->currentversionid, $userid);
                    }
                }
                // Accept all policy docs saved in $acceptversionids.
                api::accept_policies($acceptversionids, $this->behalfid, null, $lang);
            } else {
                // New user.
                // If the user has accepted all the policies, add this to the SESSION to let continue with the signup process.
                $SESSION->userpolicyagreed = empty(array_diff(array_keys($this->policies), $this->agreedocs));

                // TODO: Show a message to let know the user he/she must agree all the policies if he/she wants to create an user.
            }
        }
    }

    /**
     * Redirect to $SESSION->wantsurl if defined or to $CFG->wwwroot if not.
     */
    protected function redirect_to_previous_url() {
        global $SESSION;

        if (!empty($SESSION->wantsurl)) {
            $returnurl = $SESSION->wantsurl;
            unset($SESSION->wantsurl);
        } else {
            $returnurl = $CFG->wwwroot.'/';
        }

        redirect($returnurl);
    }

    /**
     * Sets up the global $PAGE and performs the access checks.
     */
    protected function prepare_global_page_access() {
        global $CFG, $PAGE, $SESSION, $SITE, $USER;

        // Guest users or not logged users (but the users during the signup process) are not allowed to access to this page.
        $newsignupuser = !empty($SESSION->wantsurl) && $SESSION->wantsurl->compare(new moodle_url('/login/signup.php'), URL_MATCH_BASE);
        if (isguestuser() || (empty($USER->id) && !$newsignupuser)) {
            $this->redirect_to_previous_url();
        }

        // Check for correct user capabilities.
        if (!empty($USER->id)) {
            // For existing users, it's needed to check if they have the capability for accepting policies.
            if (empty($this->behalfid) || $this->behalfid == $USER->id) {
                require_capability('tool/policy:accept', context_system::instance());
            } else {
                $usercontext = \context_user::instance($this->behalfid);
                require_capability('tool/policy:acceptbehalf', $usercontext);
            }
        } else {
            // For new users, the behalfid parameter is ignored.
            if ($this->behalfid != $USER->id) {
                redirect(new moodle_url('/admin/tool/policy/index.php'));
            }
        }

        // If the current user has the $USER->policyagreed = 1 or $SESSION->userpolicyagreed = 1, redirect to the return page.
        $hasagreedsignupuser = empty($USER->id) && !empty($SESSION->userpolicyagreed);
        $hasagreedloggeduser = $USER->id == $this->behalfid && !empty($USER->policyagreed);
        // TODO: Redirect only if $SESSION->wantsurl is set (to let users to access to this page after from his/her profile) ?
        if (!is_siteadmin() && ($hasagreedsignupuser || $hasagreedloggeduser)) {
            $this->redirect_to_previous_url();
        }

        $myparams = [];
        if (!empty($USER->id) && !empty($this->behalfid) && $this->behalfid != $USER->id) {
            $myparams['userid'] = $this->behalfid;
        }
        $myurl = new moodle_url('/admin/tool/policy/index.php', $myparams);

        // Redirect to policy docs before the consent page.
        page_helper::redirect_to_policies($this->behalfid, $this->policies, $myurl);

        // Page setup.
        $PAGE->set_context(context_system::instance());
        $PAGE->set_pagelayout('standard');
        $PAGE->set_url($myurl);
        $PAGE->set_heading($SITE->fullname);
        $PAGE->set_title(get_string('policiesagreements', 'tool_policy'));
        $PAGE->navbar->add(get_string('policiesagreements', 'tool_policy'), new moodle_url('/admin/tool/policy/index.php'));
    }

    /**
     * Prepare user acceptances.
     */
    protected function prepare_user_acceptances() {
        // TODO: Use $policy->currentversionid instead of policy->id in the form.

        // Get all the policy version acceptances for this user.
        $acceptances = api::get_user_acceptances($this->behalfid);
        $lang = current_language();
        foreach ($this->policies as $policy) {
            // Get only current version object. Remove the other versions from the object because at this point they aren't needed.
            $this->policies[$policy->id]->currentversion = api::get_policy_version($policy->id, $policy->currentversionid);
            unset($this->policies[$policy->id]->versions);

            // Get a link to display the full policy document.
            // TODO: Review this part for adding the policy link name to the template (both, the link and the template).
            //$policy->url = new moodle_url('/admin/tool/policy/view.php', array('policyid' => $policy->id, 'returnurl' => qualified_me()));
            //$policylinkname = html_writer::link($policy->url, $policy->name);
            $policy->url = '#';
            $policyattributes = array('data-action' => 'view',
                                      'data-versionid' => $policy->currentversionid,
                                      'data-behalfid' => $this->behalfid);
            $policylinkname = html_writer::link($policy->url, $policy->name, $policyattributes);

            // Check if this policy version has been agreed or not.
            if (!empty($this->behalfid)) {
                // Existing user.
                $versionagreed = false;
                $this->policies[$policy->id]->versionacceptance = api::get_user_version_acceptance($this->behalfid, $policy->currentversionid, $acceptances);
                if (!empty($this->policies[$policy->id]->versionacceptance)) {
                    // The policy version has ever been agreed. Check if status = 1 to know if still is accepted.
                    $versionagreed = $this->policies[$policy->id]->versionacceptance->status;
                    if ($this->policies[$policy->id]->versionacceptance->lang != $lang) {
                        // Add a message because this version has been accepted in a different language than the current one.
                        $this->policies[$policy->id]->versionlangsagreed = get_string('policyversionacceptedinotherlang', 'tool_policy');
                    }
                    if ($this->policies[$policy->id]->versionacceptance->usermodified != $this->behalfid) {
                        // Add a message because this version has been accepted in behalf of current user.
                        $this->policies[$policy->id]->versionbehalfsagreed = get_string('policyversionacceptedinbehalf', 'tool_policy');
                    }
                }
            } else {
                // New user.
                $versionagreed = in_array($policy->id, $this->agreedocs);
            }
            // TODO: Mark as mandatory this checkbox (style).
            $this->policies[$policy->id]->refertofulltext = get_string('refertofullpolicytext', 'tool_policy', $policylinkname);
            $this->policies[$policy->id]->agreecheckbox = html_writer::checkbox('agreedoc[]', $policy->id, $versionagreed, get_string('iagree', 'tool_policy', $policylinkname));
        }
    }

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $USER;

        // TODO: Refactor this code for moving some to the template, like the form or the buttons.
        $data = (object) [
            'pluginbaseurl' => (new moodle_url('/admin/tool/policy'))->out(false),
        ];

        $myparams = [];
        if (!empty($USER->id) && !empty($this->behalfid) && $this->behalfid != $USER->id) {
            $myparams['userid'] = $this->behalfid;
        }
        $myurl = new moodle_url('/admin/tool/policy/index.php', $myparams);

        $attributes = array('method' => 'post',
                            'action' => $myurl,
                            'id'     => 'agreedocs');

        $data->policies = array_values($this->policies);

        // Get privacy officer information.
        if (!empty($CFG->privacyofficer)) {
            $data->privacyofficer = $CFG->privacyofficer;
        }

        if ($USER->id != $this->behalfid) {
            // If viewing docs in behalf of other user, get his/her full name and profile link.
            $behalfuser = core_user::get_user($this->behalfid, '*', MUST_EXIST);
            $userfullname = fullname($behalfuser, has_capability('moodle/site:viewfullnames', \context_system::instance()) ||
                        has_capability('moodle/site:viewfullnames', \context_user::instance($this->behalfid)));
            $data->user = html_writer::link(\context_user::instance($this->behalfid)->get_url(), $userfullname);
        }

        // TODO: Check if there is a better way to create this form with the buttons. This is a quite dirty hack :-(
        $data->startform = html_writer::start_tag('form', $attributes);
        $data->endform = html_writer::end_tag('form');
        $formcontinue = new single_button($myurl, get_string('continue'));
        $formcontinue->formid = 'agreedocs';
        $formcancel = new single_button($myurl, get_string('cancel'));
        $data->navigation = array();
        $data->navigation[] = $output->render($formcontinue);
        $data->navigation[] = $output->render($formcancel);

        return $data;
    }

}
