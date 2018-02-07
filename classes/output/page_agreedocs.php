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

use core_user;
use html_writer;
use moodle_url;
use renderable;
use renderer_base;
use single_button;
use templatable;
use tool_policy\api;

/**
 * Represents a page for showing all the policy documents which an user has to agree to.
 *
 * @copyright 2018 Sara Arjona <sara@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_agreedocs implements renderable, templatable {

    /** @var array $policies List of the policies to be agree to the user. */
    protected $policies = null;

    /** @var int $userid The id of the user to show all the policy version consents page. */
    protected $userid = null;

    /** @var object $user User object. */
    protected $user = null;

    /**
     * Construct this renderable.
     *
     * @param int $userid The userid which wants to view this policy version.
     * @param array $policies Array with all the policies which the user has to agree to. Each policy object must have currentversionid field.
     */
    public function __construct($userid = 0, $policies = null) {
        global $USER;

        $this->userid = $userid;
        if (empty($this->userid)) {
            $this->userid = $USER->id;
        }
        if ($USER->id != $this->userid){
            $this->user = core_user::get_user($userid, '*', MUST_EXIST);
        }

        $this->policies = $policies;
        if (!isset($this->policies)) {
            $this->policies = \tool_policy\api::list_policies(null, true);
        }
    }

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;

        $data = (object) [];
        $data->pluginbaseurl = (new moodle_url('/admin/tool/policy'))->out(true);

        $url = new moodle_url('/admin/tool/policy/index.php', array('userid' => $this->userid));
        $attributes = array('method' => 'post',
                            'action' => $url,
                            'id'     => 'agreedocs');

        // Get all the policy version acceptances for this user.
        $lang = current_language();
        $acceptances = \tool_policy\api::get_user_acceptances($this->userid);
        foreach ($this->policies as $policy) {
            // Get only current version object. Remove the other versions from the object because at this point they aren't needed.
            $this->policies[$policy->id]->currentversion = $policy->versions[$policy->currentversionid];
            unset($this->policies[$policy->id]->versions);

            // Get the link to display the full policy document.
            $policy->url = new moodle_url('/admin/tool/policy/view.php', array('policyid' => $policy->id, 'returnurl' => qualified_me()));
            // TODO: Replace current link to policy document to open it in a modal window.
            $policylinkname = html_writer::link($policy->url, $policy->name);

            // Check if this policy version has been agreed or not.
            $versionagreed = false;
            $this->policies[$policy->id]->versionacceptance = \tool_policy\api::get_user_version_acceptance($this->userid, $policy->currentversionid, $acceptances);
            if (!empty($this->policies[$policy->id]->versionacceptance)) {
                // The policy version has ever been agreed. Check if status = 1 to know if still is accepted.
                $versionagreed = $this->policies[$policy->id]->versionacceptance->status;
                if ($this->policies[$policy->id]->versionacceptance->lang != $lang) {
                    // Add a message because this version has been accepted in a different language than the current one.
                    $this->policies[$policy->id]->versionlangsagreed = get_string('policyversionacceptedinotherlang', 'tool_policy');
                }
                if ($this->policies[$policy->id]->versionacceptance->usermodified != $this->userid) {
                    // Add a message because this version has been accepted in behalf of current user.
                    $this->policies[$policy->id]->versionbehalfsagreed = get_string('policyversionacceptedinbehalf', 'tool_policy');
                }
            }
            // TODO: Mark as mandatory this checkbox (style).
            $this->policies[$policy->id]->refertofulltext = get_string('refertofullpolicytext', 'tool_policy', $policylinkname);
            $this->policies[$policy->id]->agreecheckbox = html_writer::checkbox('agreedoc[]', $policy->id, $versionagreed, get_string('iagree', 'tool_policy', $policylinkname));
        }
        $data->policies = array_values($this->policies);


        // Get privacy officer information.
        if (!empty($CFG->privacyofficer)) {
            $data->privacyofficer = $CFG->privacyofficer;
        }

        if (!empty($this->user)) {
            // If viewing docs in behalf of other user, get his/her full name and profile link.
            $userfullname = fullname($this->user, has_capability('moodle/site:viewfullnames', \context_system::instance()) ||
                        has_capability('moodle/site:viewfullnames', \context_user::instance($this->userid)));
            $user = html_writer::link(\context_user::instance($this->userid)->get_url(), $userfullname);
            $data->user = $user;
        }

        // TODO: Check if there is a better way to create this form with the buttons. This is a quite dirty hack :-(
        $data->startform = html_writer::start_tag('form', $attributes);
        $data->endform = html_writer::end_tag('form');
        $formcontinue = new single_button($url, get_string('continue'));
        $formcontinue->formid = 'agreedocs';
        $formcancel = new single_button($url, get_string('cancel'));
        $data->navigation = array();
        $data->navigation[] = $output->render($formcontinue);
        $data->navigation[] = $output->render($formcancel);

        return $data;
    }

}
