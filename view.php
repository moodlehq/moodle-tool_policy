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
 * View current document policy version.
 *
 * @package     tool_policy
 * @copyright   2018 Sara Arjona (sara@moodle.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');

$policyid = required_param('policyid', PARAM_INT);
$versionid = optional_param('versionid', 0, PARAM_INT);
$returnurl  = optional_param('returnurl', null, PARAM_LOCALURL);
$userid = optional_param('userid', 0, PARAM_INT);

$urlparams = array('policyid' => $policyid, 'versionid' => $versionid);
$url = new moodle_url('/admin/tool/policy/view.php', $urlparams);
list($title, $subtitle) = \tool_policy\page_helper::setup_for_page($url);

$output = $PAGE->get_renderer('tool_policy');
$page = new \tool_policy\output\page_viewdoc($policyid, $versionid, $returnurl, $userid);

echo $output->header();
echo $output->render($page);
echo $output->footer();
