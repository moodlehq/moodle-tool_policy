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
 * Validate minor helper.
 *
 * @package    tool_policy
 * @copyright  2018 Mihail Geshoski (mihail@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy;
defined('MOODLE_INTERNAL') || die();


/**
 * Validate minor helper.
 *
 * @package    tool_policy
 * @copyright  2018 Mihail Geshoski (mihail@moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validateminor_helper {

    /**
     * Maximum time the session can be active.
     */
    const MAX_SESSION_TIME = 1800;

    /**
     * Create a digital minor session.
     *
     * @param bool $is_minor
     */
    public static function create_minor_session($is_minor) {
        global $SESSION;

        $SESSION->tool_policy->minor["status"] = $is_minor;
        $SESSION->tool_policy->minor["timestamp"] = time();
    }

    /**
     * Destroy a digital minor session.
     */
    public static function destroy_minor_session() {
        global $SESSION;

        unset($SESSION->tool_policy->minor);
    }

    /**
     * Get the minor status from the digital minor session.
     *
     * @return bool
     */
    public static function get_minor_session_status() {
        global $SESSION;

        return $SESSION->tool_policy->minor["status"];
    }

    /**
     * Get the creation timestamp of the digital minor session.
     *
     * @return int
     */
    public static function get_minor_session_timestamp() {
        global $SESSION;

        return $SESSION->tool_policy->minor["timestamp"];
    }

    /**
     * Check if a digital minor session exists.
     *
     * @return bool
     */
    public static function minor_session_exists() {
        global $SESSION;

        return isset($SESSION->tool_policy->minor);
    }

    /**
     * Redirect user to the proper page, depending on his digital minor status.
     *
     * @param bool $is_minor
     */
    public static function redirect($is_minor) {
        global $SESSION;

        if ($is_minor) {
            redirect(new \moodle_url('/admin/tool/policy/contactadmin.php'));
        } else if (empty($SESSION->userpolicyagreed)) {
            // Redirect to "Policy" pages for consenting before creating the user.
            $SESSION->wantsurl = new \moodle_url('/login/signup.php');
            redirect(new \moodle_url('/admin/tool/policy/index.php'));
        }
    }

    /**
     * Check if a digital minor session is valid.
     *
     * @return bool
     */
    public static function is_valid_minor_session() {

        return time() - self::get_minor_session_timestamp() < self::MAX_SESSION_TIME;
    }
}
