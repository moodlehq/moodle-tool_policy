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
 * Page helper.
 *
 * @package    tool_policy
 * @copyright  2018 Sara Arjona (sara@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy;
defined('MOODLE_INTERNAL') || die();

use coding_exception;
use context;
use moodle_exception;
use moodle_url;
use core_user;
use context_user;
use context_course;
use stdClass;

/**
 * Page helper.
 *
 * @package    tool_policy
 * @copyright  2018 Sara Arjona (sara@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_helper {

    /**
     * Set-up a public page.
     *
     * Example:
     * list($title, $subtitle) = page_helper::setup_for_public_page($url, $pagetitle);
     * echo $OUTPUT->heading($title);
     * echo $OUTPUT->heading($subtitle, 3);
     *
     * @param  moodle_url $url The current page.
     * @param  string $subtitle The title of the subpage, if any.
     * @return array With the following:
     *               - Page title
     *               - Page sub title
     */
    public static function setup_for_public_page(moodle_url $url, $subtitle = '') {
        global $PAGE, $SITE;

        $context = \context_system::instance();
        $PAGE->set_context($context);

        if (!empty($subtitle)) {
            $title = $subtitle;
        } else {
            $title = get_string('policiesagreements', 'tool_policy');
        }

        $heading = $SITE->fullname;
        $PAGE->set_pagelayout('standard');
        $PAGE->set_url($url);
        $PAGE->set_title($title);
        $PAGE->set_heading($heading);

        return array($title, $subtitle);
    }

    /**
     * Set-up the policy acceptance page.
     *
     * Example:
     * list($title, $subtitle) = page_helper::setup_for_agreedocs_page($url, $pagetitle);
     * echo $OUTPUT->heading($title);
     * echo $OUTPUT->heading($subtitle, 3);
     *
     * @param  moodle_url $url The current page.
     * @param  string $subtitle The title of the subpage, if any.
     * @return array With the following:
     *               - Page title
     *               - Page sub title
     */
    public static function setup_for_agreedocs_page(moodle_url $url, $subtitle = '') {
        global $PAGE, $SITE;

        $PAGE->set_popup_notification_allowed(false);

        if (!isloggedin()) {
            require_login();
        }

        $context = \context_system::instance();
        $PAGE->set_context($context);


        if (!empty($subtitle)) {
            $title = $subtitle;
        } else {
            $title = get_string('policiesagreements', 'tool_policy');
        }

        $heading = $SITE->fullname;
        $PAGE->set_pagelayout('standard');
        $PAGE->set_url($url);
        $PAGE->set_title($title);
        $PAGE->set_heading($heading);

        return array($title, $subtitle);
    }

    /**
     * Before display the consent page, the user has to view all the still-non-accepted policy docs.
     * This function checks if the non-accepted policy docs have been shown and redirect to them.
     *
     * @param array $userid User identifier who wants to access to the consent page.
     * @param array $policies List of policies. If it's null, all the policies with a current version will be used.
     * @param url $returnurl URL to return after shown the policy docs.
     */
    public static function redirect_to_policies($userid, $policies = null, $returnurl = null) {
        global $SESSION;

        if (empty($policies)) {
            $policies = \tool_policy\api::list_policies(null, true);
        }
        $currentlanguage = current_language();
        $acceptances = \tool_policy\api::get_user_acceptances($userid);
        foreach($policies as $policy) {
            if (array_key_exists($policy->currentversionid, $acceptances)) {
                if (array_key_exists($currentlanguage, $acceptances[$policy->currentversionid])) {
                    // If version has been agreed in current language, remove from the pending policies list.
                    unset($policies[$policy->id]);
                }
            }
        }

        if (!empty($policies)) {
            $policies = array_keys($policies);
            if (!empty($SESSION->tool_policy->viewedpolicies)) {
                // Get the list of the policies docs which the user haven't viewed during this session.
                $pendingpolicies = array_diff($policies, $SESSION->tool_policy->viewedpolicies);
            } else {
                $pendingpolicies = $policies;
            }
            if (sizeof($pendingpolicies) > 0) {
                // Still is needed to show some policies docs. Save in the session and redirect.
                // TODO: Add language to SESSION to make sure users view the full policy doc if they change the current lang.
                $policyid = array_pop($pendingpolicies);
                $SESSION->tool_policy->viewedpolicies[] = $policyid;
                if (empty($returnurl)) {
                    $returnurl = new moodle_url('/admin/tool/policy/index.php');
                }
                $urlparams = ['policyid' => $policyid, 'returnurl' => $returnurl];
                redirect(new moodle_url('/admin/tool/policy/view.php', $urlparams));
            }
        }
    }
}
