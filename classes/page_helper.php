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
}
