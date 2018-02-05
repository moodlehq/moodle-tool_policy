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
 * Provides {@link tool_policy\output\renderer} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 Sara Arjona <sara@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\output;


defined('MOODLE_INTERNAL') || die();

use context_system;
use moodle_url;
use renderable;
use renderer_base;
use single_button;
use templatable;
use tool_policy\api;

/**
 * Represents a page for showing the given policy document version.
 *
 * @copyright 2018 Sara Arjona <sara@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_viewdoc implements renderable, templatable {

    /** @var int $versionid Policy id for this page. */
    protected $policyid = null;

    /** @var int $versionid Version policy id for this page. */
    protected $versionid = null;

    /** @var string $url Return URL. */
    protected $returnurl = null;

    /** @var bool Can the user view all the versions or only their own or the current ones? */
    protected $canviewallversions;

    /** @var int $url User id who wants to view this page. */
    protected $userid = null;

    /**
     * Construct this renderable.
     * @param int $policyid The policy id for this page.
     * @param int $versionid The version id to show. If none is specified is used the current version id.
     * @param string $returnurl Return URL.
     * @param int $userid The userid which wants to view this policy version.
     */
    public function __construct($policyid, $versionid = 0, $returnurl = null, $userid = 0) {
        $this->policyid = $policyid;
        $this->versionid = $versionid;
        $this->returnurl = $returnurl;
        $this->canviewallversions = has_capability('tool/policy:managedocs', context_system::instance()) || has_capability('tool/policy:viewacceptances', context_system::instance());
        $this->userid = $userid;
    }

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = (object) [];
        $data->error = [];
        $data->pluginbaseurl = (new moodle_url('/admin/tool/policy'))->out(true);

        if (empty($this->policyid)) {
            $data->error[] = get_string('invalidversionid', 'tool_policy');
        } else {
            $policy = api::list_policies($this->policyid)[$this->policyid];
            if (empty($this->versionid) && !empty($policy->currentversionid)) {
                // If versionid is not defined, get the one defined as current.
                $this->versionid = $policy->currentversionid;
            } else {
                // Show error if the policy hasn't the specified versionid.
                if (!array_key_exists($this->versionid, $policy->versions)) {
                    $data->error[] = get_string('invalidversionid', 'tool_policy');
                    $this->versionid = null;
                }
            }

            if (!empty($this->versionid)) {
                $version = \tool_policy\api::get_policy_version($this->policyid, $this->versionid);
                // TODO: Check if current user has agreed to the version.
                // TODO: Display if the policy is shown in behalf of other user.
                $acceptances = \tool_policy\api::get_user_acceptances($this->userid, $this->versionid);
                if ($this->canviewallversions || $this->versionid == $policy->currentversionid || (!empty($acceptances) && $acceptances->policyagreed)){
                    $data->version = $version;
                } else {
                    $data->error[] = get_string('nopermissiontoviewpolicyversion', 'tool_policy');
                }
            }

            $data->navigation = array();
            if (!empty($this->returnurl)) {
                $backbutton = new single_button(
                   new moodle_url($this->returnurl),
                   get_string('back'), 'get'
                );
                $data->navigation[] = $output->render($backbutton);
            }
        }
        return $data;
    }
}
