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

    protected $userid;

    public function __construct($userid) {
        $this->userid = $userid;
    }

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        $data = (object)[];
        $data->pluginbaseurl = (new moodle_url('/admin/tool/policy'))->out(false);

        // Get the list of policies and versions that current user is able to see
        // and the respective acceptance records for the selected user.
        $policies = api::get_policies_with_acceptances($this->userid);

        $canviewfullnames = has_capability('moodle/site:viewfullnames', \context_system::instance());
        foreach ($policies as $policy) {
            $policy->name = format_string($policy->name);
            unset($policy->description); // If description is needed later don't forget to apply format_text().

            foreach ($policy->versions as $version) {
                $version->iscurrent = ($version->id == $policy->currentversionid);
                $version->revision = format_string($version->revision);
                $returnurl = new moodle_url('/admin/tool/policy/user.php', ['userid' => $this->userid]);
                $version->viewurl = (new moodle_url('/admin/tool/policy/view.php', [
                    'policyid' => $policy->id,
                    'versionid' => $version->id,
                    'returnurl' => $returnurl->out(false),
                ]))->out(false);

                if (!empty($version->acceptance->status)) {
                    $acceptance = $version->acceptance;
                    $version->timeaccepted = userdate($acceptance->timemodified, get_string('strftimedatetime'));
                    $onbehalf = $acceptance->usermodified && $acceptance->usermodified != $this->userid;
                    $version->agreement = new user_agreement($this->userid, 1, $returnurl, $version->id, $onbehalf);
                    if ($onbehalf) {
                        $usermodified = (object)['id' => $acceptance->usermodified];
                        username_load_fields_from_object($usermodified, $acceptance, 'mod');
                        $version->acceptedby = fullname($usermodified, $canviewfullnames ||
                            has_capability('moodle/site:viewfullnames', \context_user::instance($acceptance->usermodified)));
                        // TODO link to profile.
                    }
                    $version->note = format_text($acceptance->note);
                } else if ($version->iscurrent) {
                    $version->agreement = new user_agreement($this->userid, 0, $returnurl, $version->id);
                }
                if (isset($version->agreement)) {
                    $version->agreement = $version->agreement->export_for_template($output);
                }
            }

            if (empty($policy->versions[$policy->currentversionid])) {
                // Add an empty "currentversion" on top.
                $policy->versions = [0 => (object)[]] + $policy->versions;
            } else if (array_search($policy->currentversionid, array_keys($policy->versions)) > 0) {
                // Move current version to the top.
                $currentversion = $policy->versions[$policy->currentversionid];
                unset($policy->versions[$policy->currentversionid]);
                $policy->versions = [$currentversion->id => $currentversion] + $policy->versions;
            }

            $policy->versioncount = count($policy->versions);
            $policy->versions = array_values($policy->versions);
            $policy->versions[0]->isfirst = 1;
        }

        $data->policies = array_values($policies);
        // TODO remove fields we don't need!
        return $data;
    }
}
