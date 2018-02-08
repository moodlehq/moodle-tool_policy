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
 * Provides {@link tool_policy\output\page_managedocs_document} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\output;

use tool_policy\api;

defined('MOODLE_INTERNAL') || die();

use context_system;
use moodle_url;
use renderable;
use renderer_base;
use single_button;
use templatable;

/**
 * Represents a management page with the list of versions of the given policy document.
 *
 * @copyright 2018 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_managedocs_document implements renderable, templatable {

    /** @var int ID of the policy document to display */
    protected $policyid;

    /** @var bool Can the user manage the policy documents. */
    protected $canmanage;

    /** @var bool Can the user view the acceptances. */
    protected $canviewacceptances;

    /**
     * Constructor.
     *
     * @param int $policyid ID of the policy document to display
     */
    public function __construct($policyid) {

        $this->policyid = $policyid;
        $this->canmanage = has_capability('tool/policy:managedocs', context_system::instance());
        $this->canviewacceptances = has_capability('tool/policy:viewacceptances', context_system::instance());
    }

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = (object) [];
        $data->pluginbaseurl = (new moodle_url('/admin/tool/policy'))->out(false);
        $data->canmanage = $this->canmanage;
        $data->canviewacceptances = $this->canviewacceptances;

        $policy = api::list_policies($this->policyid)[$this->policyid];

        $data->policyid = $policy->id;
        $data->name = $policy->name;

        $data->versions = [];
        $statushelper = 'draft';

        foreach ($policy->versions as $version) {
            $dataversion = (object) [
                'id' => $version->id,
                'viewurl' => (new moodle_url('/admin/tool/policy/view.php', [
                    'policyid' => $policy->id,
                    'versionid' => $version->id,
                    'manage' => 1,
                    'returnurl' => (new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $this->policyid]))->out(false),
                ]))->out(false),
                'timemodified' => $version->timemodified,
                'revision' => $version->revision,
                'usersaccepted' => '???',
                'actions' => [],
            ];

            $editbaseurl = new moodle_url('/admin/tool/policy/editpolicydoc.php', [
                'policyid' => $policy->id,
            ]);

            if ($this->canmanage) {
                $dataversion->actions[] = (object) [
                    'url' => (new moodle_url($editbaseurl, [
                        'versionid' => $version->id,
                    ]))->out(false),
                    'text' => get_string('edit', 'core'),
                    'icon' => $output->pix_icon('t/edit', ''),
                ];
            }

            if ($version->id === $policy->currentversionid) {
                $dataversion->iscurrent = true;
                $dataversion->status = get_string('statuscurrent', 'tool_policy');
                $statushelper = 'archive';
                if ($this->canmanage) {
                    $dataversion->actions[] = (object) [
                        'url' => (new moodle_url($editbaseurl, ['inactivate' => $version->id]))->out(false),
                        'text' => get_string('inactivate', 'tool_policy'),
                        'icon' => $output->pix_icon('t/show', ''),
                    ];
                }

            } else if ($statushelper === 'draft') {
                $dataversion->iscurrent = false;
                $dataversion->status = get_string('statusdraft', 'tool_policy');

                if ($this->canmanage) {
                    if ($policy->currentversionid) {
                        $makecurrenttext = get_string('makecurrent', 'tool_policy');
                    } else {
                        $makecurrenttext = get_string('makeactive', 'tool_policy');
                    }

                    $dataversion->actions[] = (object) [
                        'url' => (new moodle_url($editbaseurl, ['makecurrent' => $version->id]))->out(false),
                        'text' => $makecurrenttext,
                        'icon' => $output->pix_icon('t/hide', ''),
                    ];
                }

            } else {
                $dataversion->iscurrent = false;
                $dataversion->status = get_string('statusarchive', 'tool_policy');
            }

            $data->versions[] = $dataversion;
        }

        return $data;
    }
}
