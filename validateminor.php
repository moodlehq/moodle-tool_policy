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
 * Validate a digital minor.
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
$PAGE->set_url($CFG->wwwroot.'/admin/tool/policy/validateminor.php');

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
$PAGE->set_title(get_string('agelocationverification', 'tool_policy'));
$PAGE->set_heading($SITE->fullname);

// Handle if minor check has already been done.
if (isset($SESSION->minor)) {
    if ($SESSION->minor == true) { // The user is a minor.
        // Redirect to "Contact administrator" page.
        die("You are considered to be a digital minor. Please contact admin.");
    } else { // The user is not a minor.
        // Redirect to "Policy" pages.
        die("Policy page");
    }
}

$output = $PAGE->get_renderer('tool_policy');
$mform = new \tool_policy\output\page_validateminor();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/login/index.php'));
} else if ($data = $mform->get_data()) {
    if (\tool_policy\api::is_minor($data->dateofbirth, $data->country)) {
        $SESSION->minor = true;
        // Redirect to "Contact administrator" page.
        die("You are considered to be a digital minor. Please contact admin.");
    } else {
        $SESSION->minor = false;
        // Redirect to "Policy" pages.
        die("Policy page");
    }
} else {
    echo $output->header();
    echo $output->render($mform);
    echo $output->footer();
}
