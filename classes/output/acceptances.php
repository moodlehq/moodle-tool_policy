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
 * Provides {@link tool_policy\output\acceptances} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 Marina Glancy
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
 * List of users and their acceptances
 *
 * @copyright 2018 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class acceptances implements renderable, templatable {

    protected $policyid;
    protected $versionid;

    public function __construct($policyid = null, $verionid = null) {
        $this->policyid = $policyid;
        $this->versionid = $verionid;
    }

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE;

        $data = (object) ['versions' => [], 'users' => []];
        $data->pluginbaseurl = (new moodle_url('/admin/tool/policy'))->out(false);
        $policies = api::list_policies();
        $versionids = [];
        foreach ($policies as $policy) {
            if ($this->policyid && $policy->id != $this->policyid) {
                continue;
            }
            foreach ($policy->versions as $version) {
                if ($this->versionid && $version->id != $this->versionid) {
                    continue;
                }
                if (!$this->policyid && !$this->versionid && $this->versionid != $policy->currentversionid) {
                    continue;
                }
                $versionids[] = $version->id;
                $data->versions[] = [
                    'name' => format_string($policy->name).'<br>'.format_string($version->revision) // TODO
                ];
            }
        }
        $acceptances = api::get_acceptances($versionids);
        $versionsindex = array_flip($versionids);


        $useracceptances = [];
        $canviewfullnames = has_capability('moodle/site:viewfullnames', \context_system::instance());
        $canacceptany = has_capability('tool/policy:acceptbehalf', \context_system::instance());
        foreach ($acceptances as $row) {
            $row->userid = $row->mainuserid;
            if (!array_key_exists($row->userid, $useracceptances)) {
                $user = (object)['id' => $row->userid];
                username_load_fields_from_object($user, $row, 'user');
                $useracceptances[$row->userid] = [
                    'id' => $row->userid,
                    'name' => fullname($user, $canviewfullnames ||
                        has_capability('moodle/site:viewfullnames', \context_user::instance($row->userid))),
                    'policyagreed' => $row->policyagreed,
                    'acceptances' => [],
                ];
                foreach ($versionids as $versionid) {
                    $canaccept = $canacceptany || has_capability('tool/policy:acceptbehalf', \context_user::instance($row->userid));
                    $useracceptances[$row->userid]['acceptances'][] = [
                        'status' => 0,
                        'canaccept' => $canaccept,
                        'acceptlink' => new moodle_url('/admin/tool/policy/acceptances.php',
                            ['versionid' => $versionid, 'acceptfor' => $row->userid,
                            'returnurl' => $PAGE->url->out_as_local_url(false)])
                    ];
                }
            }
            if ($row->policyversionid) {
                $accept = &$useracceptances[$row->userid]['acceptances'][$versionsindex[$row->policyversionid]];
                $accept['status'] = $row->status;
                $accept['note'] = $row->note;
                $accept['timemodified'] = $row->timemodified ? userdate($row->timemodified) : '';
                if ($row->usermodified && $row->usermodified != $row->userid) {
                    $usermodified = (object)['id' => $row->usermodified];
                    username_load_fields_from_object($usermodified, $row, 'mod');
                    $accept['modifiedby'] = fullname($usermodified, $canviewfullnames ||
                        has_capability('moodle/site:viewfullnames', \context_user::instance($row->usermodified)));
                }
                if ($row->status) {
                    unset($accept['acceptlink']);
                    $accept['canaccept'] = 0;
                }
            }
        }

        $data->users = array_values($useracceptances);

        //print_object($data->users);
/*

        $data->haspolicies = true;
        $data->canmanage = has_capability('tool/policy:managedocs', \context_system::instance());
        $data->canviewacceptances = has_capability('tool/policy:viewacceptances', \context_system::instance());
        $data->policies = [];

        foreach (api::list_policies() as $policy) {
            $datapolicy = (object) [
                'id' => $policy->id,
                'manageurl' => (new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $policy->id]))->out(false),
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
                ]))->out(false);
            }

            $data->policies[] = $datapolicy;
        }*/

        return $data;
    }
}
