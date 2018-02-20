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
 * External policy webservice API tests.
 *
 * @package tool_policy
 * @copyright 2018 Sara Arjona <sara@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

use tool_policy\api;
use tool_policy\external;

/**
 * External policy webservice API tests.
 *
 * @package tool_policy
 * @copyright 2018 Sara Arjona <sara@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_policy_external_testcase extends externallib_advanced_testcase {

    /**
     * Setup function- we will create some policy docs.
     */
    public function setUp() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Prepare a policy document with some versions.
        $formdata = api::form_policydoc_data();
        $formdata->name = 'Test policy';
        $formdata->revision = 'v1';
        $formdata->summary_editor = ['text' => 'summary', 'format' => FORMAT_HTML, 'itemid' => 0];
        $formdata->content_editor = ['text' => 'content', 'format' => FORMAT_HTML, 'itemid' => 0];
        $this->policy1 = api::form_policydoc_add($formdata);

        $formdata = api::form_policydoc_data($this->policy1->policyid, $this->policy1->versionid);
        $formdata->revision = 'v2';
        $this->policy2 = api::form_policydoc_update_new($this->policy1->policyid, $formdata);

        $formdata = api::form_policydoc_data($this->policy1->policyid, $this->policy1->versionid);
        $formdata->revision = 'v3';
        $this->policy3 = api::form_policydoc_update_new($this->policy1->policyid, $formdata);

        api::make_current($this->policy2->policyid, $this->policy2->versionid);

        // Create users.
        $this->child = $this->getDataGenerator()->create_user();
        $this->parent = $this->getDataGenerator()->create_user();
        $this->adult = $this->getDataGenerator()->create_user();

        $syscontext = context_system::instance();
        $childcontext = context_user::instance($this->child->id);

        $roleminorid = create_role('Digital minor', 'digiminor', 'Not old enough to accept site policies themselves');
        $roleparentid = create_role('Parent', 'parent', 'Can accept policies on behalf of their child');

        assign_capability('tool/policy:accept', CAP_PROHIBIT, $roleminorid, $syscontext->id);
        assign_capability('tool/policy:acceptbehalf', CAP_ALLOW, $roleparentid, $syscontext->id);

        role_assign($roleminorid, $this->child->id, $syscontext->id);
        role_assign($roleparentid, $this->parent->id, $childcontext->id);
    }

    /**
     * Test for the get_policy_version() function.
     */
    public function test_get_policy_version() {
        $this->setUser($this->adult);

        // View current policy version.
        $result = external::get_policy_version($this->policy2->versionid);
        $result = external_api::clean_returnvalue(external::get_policy_version_returns(), $result);
        $this->assertCount(1, $result['result']);
        $this->assertEquals($this->policy1->name, $result['result']['policy']['name']);
        $this->assertEquals($this->policy1->content, $result['result']['policy']['content']);

        // View draft policy version.
        $result = external::get_policy_version($this->policy3->versionid);
        $result = external_api::clean_returnvalue(external::get_policy_version_returns(), $result);
        $this->assertCount(0, $result['result']);
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals(array_pop($result['warnings'])['warningcode'], 'errorusercantviewpolicyversion');

        // Add test for non existing versionid.
        $result = external::get_policy_version(999);
        $result = external_api::clean_returnvalue(external::get_policy_version_returns(), $result);
        $this->assertCount(0, $result['result']);
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals(array_pop($result['warnings'])['warningcode'], 'errorpolicyversionnotfound');

        // View previous non-accepted version in behalf of a child.
        $this->setUser($this->parent);
        $result = external::get_policy_version($this->policy1->versionid, $this->child->id);
        $result = external_api::clean_returnvalue(external::get_policy_version_returns(), $result);
        $this->assertCount(0, $result['result']);
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals(array_pop($result['warnings'])['warningcode'], 'errorusercantviewpolicyversion');

        // Let the parent accept the policy on behalf of her child and view it again.
        api::accept_policies($this->policy1->versionid, $this->child->id);
        $result = external::get_policy_version($this->policy1->versionid, $this->child->id);
        $result = external_api::clean_returnvalue(external::get_policy_version_returns(), $result);
        $this->assertCount(1, $result['result']);
        $this->assertEquals($this->policy1->name, $result['result']['policy']['name']);
        $this->assertEquals($this->policy1->content, $result['result']['policy']['content']);

        // Only parent is able to view the child policy version accepted by her child.
        $this->setUser($this->adult);
        $result = external::get_policy_version($this->policy1->versionid, $this->child->id);
        $result = external_api::clean_returnvalue(external::get_policy_version_returns(), $result);
        $this->assertCount(0, $result['result']);
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals(array_pop($result['warnings'])['warningcode'], 'errorusercantviewpolicyversion');
    }
}
