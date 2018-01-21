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
 * Manage policy documents used on the site.
 *
 * @package     tool_policy
 * @category    admin
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('tool_policy_managedocs', '', ['id' => $id]);

$output = $PAGE->get_renderer('tool_policy');

if (empty($id)) {
    $manpage = new \tool_policy\output\page_managedocs_list();

} else {
    $manpage = new \tool_policy\output\page_managedocs_document($id);
}

echo $output->header();
echo $output->render($manpage);
echo $output->footer();
