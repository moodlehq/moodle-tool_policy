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
 * Provides {@link tool_policy\output\user_agreement} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\output;

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
class user_agreement implements \templatable, \renderable {

    /** @var int */
    protected $userid;

    protected $onbehalf;

    /** @var moodle_url */
    protected $pageurl;

    /** @var array */
    protected $versions;

    protected $accepted;

    protected $canaccept;

    /**
     * user_agreement constructor
     *
     * @param int $userid
     * @param array $accepted list of ids of accepted versions
     * @param moodle_url $pageurl
     * @param array $versions list of versions (id=>name)
     * @param bool $onbehalf whether at least one version was accepted by somebody else on behalf of the user
     */
    public function __construct($userid, $accepted, moodle_url $pageurl, $versions, $onbehalf = false, $canaccept = null) {
        $this->userid = $userid;
        $this->onbehalf = $onbehalf;
        $this->pageurl = $pageurl;
        $this->versions = $versions;
        $this->accepted = $accepted;
        $this->canaccept = $canaccept;
        if (count($this->accepted) < count($this->versions) && $canaccept === null) {
            $this->canaccept = (has_capability('tool/policy:acceptbehalf', \context_system::instance()) ||
                has_capability('tool/policy:acceptbehalf', \context_user::instance($this->userid)));
        }
    }

    /**
     * Export data to be rendered.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(\renderer_base $output) {
        $data = [
            'status' => count($this->accepted) == count($this->versions),
            'onbehalf' => $this->onbehalf,
            'canaccept' => $this->canaccept,
        ];
        if (!$data['status'] && $this->canaccept) {
            $acceptforversions = array_diff(array_keys($this->versions), $this->accepted);
            $link = new \moodle_url('/admin/tool/policy/user.php',
                ['acceptforversions' => join(',', $acceptforversions), 'userid' => $this->userid,
                    'returnurl' => $this->pageurl->out_as_local_url(false)]);
            $data['acceptlink'] = $link->out(false);
        }
        $data['singleversion'] = count($this->versions) == 1;
        if ($data['singleversion']) {
            $firstversion = reset($this->versions);
            $data['versionname'] = $firstversion;
        }
        return $data;
    }
}