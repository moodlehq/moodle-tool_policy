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

    /** @var array */
    protected $policies = [];

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
        $data->haspolicies = true;
        $data->canmanage = has_capability('tool/policy:managedocs', \context_system::instance());
        $data->canviewacceptances = has_capability('tool/policy:viewacceptances', \context_system::instance());

        // TODO Replace the following mockup data with actual API calls.
        $data->policies = [
            (object) [
                'id' => 7,
                'name' => 'Terms and conditions',
                'description' => 'Basic rules, terms and guidelines that users need to follow in order to use and access the site.',
                'usersaccepted' => 'Inactive',
            ],
            (object) [
                'id' => 9,
                'name' => 'Privacy policy',
                'description' => 'Describes the type of personal data we collect, and how we collect, store and delete theme.',
                'usersaccepted' => '99%',
            ],
            (object) [
                'id' => 10,
                'name' => 'Sharing personal data with third parties',
                'description' => 'List of third parties we share data with, together with the purpose of that sharing.',
                'usersaccepted' => '99%',
            ],

        ];

        return $data;
    }
}
