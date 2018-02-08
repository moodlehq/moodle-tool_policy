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
 * Provides {@link tool_policy\output\page_managedocs_list} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\output;

use tool_policy\api;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use renderable;
use renderer_base;
use single_button;
use templatable;

/**
 * Represents a management page with the list of policy documents.
 *
 * @copyright 2018 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_managedocs_list implements renderable, templatable {

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = (object) [];
        $data->pluginbaseurl = (new moodle_url('/admin/tool/policy'))->out(false);
        $data->haspolicies = true;
        $data->canmanage = has_capability('tool/policy:managedocs', \context_system::instance());
        $data->canviewacceptances = has_capability('tool/policy:viewacceptances', \context_system::instance());
        $data->policies = [];

        foreach (api::list_policies() as $policy) {
            $datapolicy = (object) [
                'id' => $policy->id,
                'manageurl' => (new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $policy->id]))->out(false),
                'moveupurl' => (new moodle_url('/admin/tool/policy/editpolicydoc.php', [
                    'moveup' => $policy->id,
                    'sesskey' => sesskey(),
                ]))->out(false),
                'movedownurl' => (new moodle_url('/admin/tool/policy/editpolicydoc.php', [
                    'movedown' => $policy->id,
                    'sesskey' => sesskey(),
                ]))->out(false),
                'name' => $policy->name,
                'description' => $policy->description,
                'usersaccepted' => '???',
            ];

            if ($policy->currentversionid) {
                $current = $policy->versions[$policy->currentversionid];
                $datapolicy->currentrevision = $current->revision;
                $datapolicy->viewcurrenturl = (new moodle_url('/admin/tool/policy/view.php', [
                    'policyid' => $policy->id,
                    'versionid' => $policy->currentversionid,
                    'manage' => 1,
                    'returnurl' => (new moodle_url('/admin/tool/policy/managedocs.php'))->out(false),
                ]))->out(false);
            }

            $data->policies[] = $datapolicy;
        }

        return $data;
    }
}
