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
 * Display administrator's contact details to a digital minor.
 *
 * @package     tool_policy
 * @category    admin
 * @copyright   2018 Mihail Geshoski <mihail@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/authlib.php');

if (!$authplugin = signup_is_enabled()) {
    print_error('notlocalisederrormessage', 'error', '', 'Sorry, you may not use this page.');
}

//HTTPS is required in this page when $CFG->loginhttps enabled
$PAGE->https_required();

$PAGE->set_context(context_system::instance());
$PAGE->set_url($CFG->wwwroot.'/admin/tool/policy/contactadmin.php');

if (isloggedin() and !isguestuser()) {
    // Prevent signing up when already logged in.
    echo $OUTPUT->header();
    echo $OUTPUT->box_start();
    $logout = new single_button(new moodle_url($CFG->httpswwwroot . '/login/logout.php',
        array('sesskey' => sesskey(), 'loginpage' => 1)), get_string('logout'), 'post');
    $continue = new single_button(new moodle_url('/'), get_string('cancel'), 'get');
    echo $OUTPUT->confirm(get_string('cannotsignup', 'error', fullname($USER)), $logout, $continue);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('contactadmin', 'tool_policy'));
$PAGE->set_heading($SITE->fullname);

// Handle if minor session does not exist.
if (!\tool_policy\validateminor_helper::minor_session_exists()) {
    redirect(new moodle_url('/login/index.php'));
}

// The minor session exist.
$is_minor = \tool_policy\validateminor_helper::get_minor_session_status();

if (!\tool_policy\validateminor_helper::is_valid_minor_session()) { // Minor session is no longer valid.
    \tool_policy\validateminor_helper::destroy_minor_session();
    redirect(new moodle_url('/login/index.php'));
}

if (!$is_minor) { // If not a minor.
    redirect(new moodle_url('/login/index.php'));
}

$output = $PAGE->get_renderer('tool_policy');
$page = new \tool_policy\output\page_contactadmin();

echo $output->header();
echo $output->render($page);
echo $output->footer();

