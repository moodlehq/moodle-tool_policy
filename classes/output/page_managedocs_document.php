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

    /** @var array */
    protected $versions = [];

    /**
     * Constructor.
     */
    public function __construct() {
    }

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = (object) [];
        $data->pluginbaseurl = (new moodle_url('/admin/tool/policy'))->out(true);
        $data->canmanage = has_capability('tool/policy:managedocs', \context_system::instance());
        $data->canviewacceptances = has_capability('tool/policy:viewacceptances', \context_system::instance());

        // TODO Replace the following mockup data with actual API calls.
        $data->name = 'Example policy document';

        $data->versions = [
            (object) [
                'id' => 15,
                'revision' => '2.1',
                'status' => get_string('statusdraft', 'tool_policy'),
                'timemodified' => time(),
                'usersaccepted' => '0 %',
            ],
            (object) [
                'id' => 15,
                'revision' => '2.0',
                'status' => get_string('statuscurrent', 'tool_policy'),
                'timemodified' => time(),
                'usersaccepted' => '99%',
            ],
            (object) [
                'id' => 15,
                'revision' => '1.0',
                'status' => get_string('statusarchive', 'tool_policy'),
                'timemodified' => time() - 180 * DAYSECS,
                'usersaccepted' => '99%',
            ],

        ];

        return $data;
    }
}
