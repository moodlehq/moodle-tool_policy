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

use action_menu;
use action_menu_link;
use moodle_url;
use pix_icon;
use renderable;
use renderer_base;
use single_button;
use templatable;

/**
 * Represents a management page with the list of policy documents.
 *
 * The page displays all policy documents in their sort order, together with draft future versions.
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
        $data->canmanage = has_capability('tool/policy:managedocs', \context_system::instance());
        $data->canviewacceptances = has_capability('tool/policy:viewacceptances', \context_system::instance());
        $data->policies = [];

        foreach (api::list_policies() as $policy) {
            $editbaseurl = new moodle_url('/admin/tool/policy/editpolicydoc.php', [
                'sesskey' => sesskey(),
                'policyid' => $policy->id,
            ]);

            $viewbaseurl = new moodle_url('/admin/tool/policy/view.php', [
                'policyid' => $policy->id,
                'manage' => 1,
                'returnurl' => (new moodle_url('/admin/tool/policy/managedocs.php'))->out(false),
            ]);

            if (empty($policy->currentversion) && empty($policy->draftversions)) {
                // A policy with only archived versions - what TODO?
                continue;

            } else if (empty($policy->currentversion) && !empty($policy->draftversions)) {
                // Use the first draft version as if it was the current one.
                $policy->currentversion = array_shift($policy->draftversions);
                $policy->currentversion->statustext = get_string('status0', 'tool_policy');

            } else {
                $policy->currentversion->statustext = get_string('status1', 'tool_policy');
            }

            $actionmenu = new action_menu();
            $actionmenu->set_menu_trigger(get_string('actions', 'tool_policy'));
            $actionmenu->set_alignment(action_menu::TL, action_menu::BL);
            $actionmenu->add(new action_menu_link(
                new moodle_url($editbaseurl, ['moveup' => $policy->id]),
                new pix_icon('t/up', get_string('moveup', 'tool_policy')),
                get_string('moveup', 'tool_policy'),
                true
            ));
            $actionmenu->add(new action_menu_link(
                new moodle_url($editbaseurl, ['movedown' => $policy->id]),
                new pix_icon('t/down', get_string('movedown', 'tool_policy')),
                get_string('movedown', 'tool_policy'),
                true
            ));
            $actionmenu->add(new action_menu_link(
                new moodle_url($viewbaseurl, ['versionid' => $policy->currentversion->id]),
                null,
                get_string('view'),
                false
            ));

            $policy->currentversion->actionmenu = $actionmenu->export_for_template($output);

            foreach ($policy->draftversions as $draft) {
                $draft->statustext = get_string('status0', 'tool_policy');
                $draft->actions = [
                    (object) [
                        'name' => get_string('view'),
                        'url' => (new moodle_url($viewbaseurl, ['versionid' => $draft->id]))->out(false),
                    ],
                ];
            }

            if ($data->canviewacceptances) {
                $policy->acceptancescount = null;
                $policy->acceptancescounttext = null;
            }

            $data->policies[] = $policy;
        }

        return $data;
    }
}
