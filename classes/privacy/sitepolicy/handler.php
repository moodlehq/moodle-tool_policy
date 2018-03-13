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
 * Site policy handler class.
 *
 * @package    tool_policy
 * @copyright  2018 Sara Arjona <sara@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use tool_policy\api;

/**
 * Class implementation for a site policy handler.
 *
 * @package    tool_policy
 * @copyright  2018 Sara Arjona <sara@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_policy_privacy_sitepolicy_handler extends \core_privacy\sitepolicy\handler {

    /**
     * Returns URL to redirect user to when user needs to agree to site policy
     *
     * This is a regular interactive page for web users. It should have normal Moodle header/footers, it should
     * allow user to view policies and accept them.
     *
     * @param bool $forguests
     * @return moodle_url|null (returns null if site policy is not defined)
     */
    public function get_redirect_url($forguests = false) {
        if (!$forguests) {
            return (new \moodle_url('/admin/tool/policy/index.php'))->out();
        }
        return null;
    }

    /**
     * Returns URL of the site policy that needs to be displayed to the user (inside iframe or to use in WS such as mobile app)
     *
     * This page should not have any header/footer, it does not also have any buttons/checkboxes. The caller needs to implement
     * the "Accept" button and call {@link self::accept()} on completion.
     *
     * @param bool $forguests
     * @return moodle_url|null
     */
    public function get_embed_url($forguests = false) {
        if (!$forguests) {
            return (new \moodle_url('/admin/tool/policy/viewall.php'))->out();
        }
        return null;
    }

    /**
     * Accept site policy for the current user
     *
     * @return bool - false if sitepolicy not defined, user is not logged in or user has already agreed to site policy;
     *     true - if we have successfully marked the user as agreed to the site policy
     */
    public function accept() {
       if (!isguestuser()) {
            // TODO: Review + false if user has already agreed to site policy.
            // Accepts all policies with a current version for logged users on behalf of the current user.
            $policies = api::list_current_versions(policy_version::AUDIENCE_LOGGEDIN);
            $policyversionid = array();
            foreach ($policies as $policy) {
                $policyversionid[] = $policy->id;
            }
            api::accept_policies($policyversionid);
            return true;
        }
        return false;
    }
}