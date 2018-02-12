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

use moodle_exception;

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

    /** @var int Policy version to display on this page. */
    protected $policy;

    /** @var string Return URL. */
    protected $returnurl = null;

    /** @var int User id who wants to view this page. */
    protected $behalfid = null;

    /**
     * Prepare the page for rendering.
     *
     * @param int $policyid The policy id for this page.
     * @param int $versionid The version id to show. Empty tries to load the current one.
     * @param string $returnurl URL of a page to continue after reading the policy text.
     * @param int $behalfid The userid to view this policy version as (such as child's id).
     * @param bool $manage View the policy as a part of the management UI.
     * @param int $numpolicy Position of the current policy with respect to the total of policy docs to display.
     * @param int $totalpolicies Total number of policy documents which the user has to agree to.
     */
    public function __construct($policyid, $versionid, $returnurl, $behalfid, $manage, $numpolicy = 0, $totalpolicies = 0) {

        $this->returnurl = $returnurl;
        $this->behalfid = $behalfid;
        $this->manage = $manage;
        $this->numpolicy = $numpolicy;
        $this->totalpolicies = $totalpolicies;

        $this->prepare_policy($policyid, $versionid);
        $this->prepare_global_page_access();
    }

    /**
     * Loads the policy version to display on the page.
     *
     * @param int $policyid The policy id for this page.
     * @param int $versionid The version id to show. Empty tries to load the current one.
     */
    protected function prepare_policy($policyid, $versionid) {

        if ($versionid) {
            $this->policy = api::get_policy_version($policyid, $versionid);

        } else {
            $document = api::get_policy($policyid);
            $this->policy = api::get_policy_version($document->policyid, $document->currentversionid);
        }
    }

    /**
     * Sets up the global $PAGE and performs the access checks.
     */
    protected function prepare_global_page_access() {
        global $CFG, $PAGE, $SITE;

        $myurl = new moodle_url('/admin/tool/policy/view.php', [
            'policyid' => $this->policy->policyid,
            'versionid' => $this->policy->versionid,
            'returnurl' => $this->returnurl,
            'behalfid' => $this->behalfid,
            'manage' => $this->manage,
            'numpolicy' => $this->numpolicy,
            'totalpolicies' => $this->totalpolicies,
        ]);

        if ($this->manage) {
            require_once($CFG->libdir.'/adminlib.php');
            admin_externalpage_setup('tool_policy_managedocs', '', null, $myurl);
            require_capability('tool/policy:managedocs', context_system::instance());
            $PAGE->navbar->add(format_string($this->policy->name),
                new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $this->policy->policyid]));

        } else {
            if (!api::is_public($this->policy)) {
                require_login();
            }
            $PAGE->set_context(context_system::instance());
            $PAGE->set_pagelayout('standard');
            $PAGE->set_url($myurl);
            $PAGE->set_heading($SITE->fullname);
            $PAGE->set_title(get_string('policiesagreements', 'tool_policy'));
            $PAGE->navbar->add(get_string('policiesagreements', 'tool_policy'), new moodle_url('/admin/tool/policy/index.php'));
            $PAGE->navbar->add(format_string($this->policy->name));
        }

        if (!api::can_user_view_policy_version($this->policy, $this->behalfid)) {
            throw new moodle_exception('accessdenied', 'tool_policy');
        }
    }

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = (object) [
            'pluginbaseurl' => (new moodle_url('/admin/tool/policy'))->out(false),
            'returnurl' => $this->returnurl ? (new moodle_url($this->returnurl))->out(false) : null,
            'editurl' => $this->manage ? (new moodle_url('/admin/tool/policy/editpolicydoc.php',
                ['policyid' => $this->policy->policyid, 'versionid' => $this->policy->versionid]))->out(false) : null,
            'numpolicy' => $this->numpolicy ? : null,
            'totalpolicies' => $this->totalpolicies ? : null,
        ];

        $data->policy = clone($this->policy);

        $data->policy->summary = file_rewrite_pluginfile_urls($data->policy->summary, 'pluginfile.php', SYSCONTEXTID,
            'tool_policy', 'policydocumentsummary', $data->policy->versionid);

        $data->policy->content = file_rewrite_pluginfile_urls($data->policy->content, 'pluginfile.php', SYSCONTEXTID,
            'tool_policy', 'policydocumentcontent', $data->policy->versionid);

        return $data;
    }
}
