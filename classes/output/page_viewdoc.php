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

    /** @var bool Can the user view draft versions. */
    protected $canviewdraftrevision;

    /**
     * Construct this renderable.
     * @param int $policyid The policy id for this page.
     * @param int $versionid The version id to show.
     * @param string $returnurl Return URL.
     */
    public function __construct($policyid, $versionid = 0, $returnurl = null) {
        $this->policyid = $policyid;
        $this->versionid = $versionid;
        $this->returnurl = $returnurl;
        $this->canviewdraftrevision = has_capability('tool/policy:managedocs', context_system::instance());
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

        $policy = api::list_policies($this->policyid)[$this->policyid];
        if (empty($this->versionid) && !empty($policy->currentversionid)) {
            // If versionid is not defined, get the one defined as current.
            $this->versionid = $policy->currentversionid;
        }

        if (!empty($this->versionid)) {
            $version = \tool_policy\api::get_policy_version($this->policyid, $this->versionid);
            $version->status = \tool_policy\api::get_policy_version_status($this->policyid, $this->versionid);
            if (isset($version->status) && ($version->status != \tool_policy\api::VERSION_STATUS_DRAFT || $this->canviewdraftrevision)) {
                $data->version = $version;
            } else {
                $data->error[] = get_string('usercantviewdraftversion', 'tool_policy');
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

        return $data;
    }
}
