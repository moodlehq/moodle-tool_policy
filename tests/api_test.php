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
 * Provides the {@link tool_policy_api_testcase} class.
 *
 * @package     tool_policy
 * @category    test
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_policy\api;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Unit tests for the {@link \tool_policy\api} class.
 *
 * @copyright 2018 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_policy_api_testcase extends advanced_testcase {

    /**
     * Test the common operations with a policy document and its versions.
     */
    public function test_policy_document_life_cycle() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Pre-load the form for adding a new policy document.
        $formdata = api::form_policydoc_data();

        // Pre-load the form for adding a new policy document based on a template.
        $formdata = api::form_policydoc_data(null, null, 'site');
        $this->assertNotNull($formdata->name);
        $this->assertNotNull($formdata->summary_editor['text']);
        $this->assertNotNull($formdata->summary_editor['format']);
        $this->assertNotNull($formdata->content_editor['text']);
        $this->assertNotNull($formdata->content_editor['format']);

        // Save the form.
        $policy = api::form_policydoc_add($formdata);
        $this->assertNotEmpty($policy->policyid);
        $this->assertNotEmpty($policy->versionid);
        $this->assertNull($policy->currentversionid);
        $this->assertNotNull($policy->summary);
        $this->assertNotNull($policy->summaryformat);
        $this->assertNotNull($policy->content);
        $this->assertNotNull($policy->contentformat);

        // Update the policy document version.
        $formdata = api::form_policydoc_data($policy->policyid, $policy->versionid);
        $formdata->revision = '*** Unit test ***';
        $formdata->summary_editor['text'] = '__Just a summary__';
        $formdata->summary_editor['format'] = FORMAT_MARKDOWN;
        $formdata->content_editor['text'] = '### Just a test ###';
        $formdata->content_editor['format'] = FORMAT_MARKDOWN;
        $updated = api::form_policydoc_update_overwrite($policy->policyid, $policy->versionid, $formdata);
        $this->assertEquals($policy->policyid, $updated->policyid);
        $this->assertEquals($policy->versionid, $updated->versionid);

        // Save form as a new version.
        $formdata = api::form_policydoc_data($policy->policyid, $policy->versionid);
        $formdata->revision = '*** Unit test 2 ***';
        $formdata->summary_editor['text'] = '<strong>Yet another summary</strong>';
        $formdata->summary_editor['format'] = FORMAT_MOODLE;
        $formdata->content_editor['text'] = '<h3>Yet another test</h3>';
        $formdata->content_editor['format'] = FORMAT_HTML;
        $new = api::form_policydoc_update_new($policy->policyid, $formdata);
        $this->assertEquals($policy->policyid, $new->policyid);
        $this->assertNotEquals($policy->versionid, $new->versionid);

        // Add yet another policy document.
        $formdata = api::form_policydoc_data(null, null, 'privacy');
        $another = api::form_policydoc_add($formdata);

        // Get the list of all policies and their versions.
        $docs = api::list_policies();
        $this->assertEquals(2, count($docs));
        $this->assertEquals(2, count($docs[$policy->policyid]->versions));
        $this->assertEquals('*** Unit test ***', $docs[$policy->policyid]->versions[$policy->versionid]->revision);
        $this->assertEquals('*** Unit test 2 ***', $docs[$policy->policyid]->versions[$new->versionid]->revision);
        $this->assertEquals($another->name, $docs[$another->policyid]->name);

        // Get just one policy and all its versions.
        $docs = api::list_policies($another->policyid);
        $this->assertEquals(1, count($docs));
        $this->assertEquals(1, count($docs[$another->policyid]->versions));

        // Activate a policy.
        $this->assertNull($policy->currentversionid);
        api::make_current($policy->policyid, $updated->versionid);
        $policy = api::get_policy($policy->policyid);
        $this->assertEquals($policy->currentversionid, $updated->versionid);

        // Get just the activated policies.
        $docs = api::list_policies(null, true);
        $this->assertEquals(1, count($docs));
        $this->assertEquals($docs[$policy->policyid]->currentversionid, $updated->versionid);

        // Activate another policy version.
        api::make_current($policy->policyid, $new->versionid);
        $policy = api::get_policy($policy->policyid);
        $this->assertEquals($policy->currentversionid, $new->versionid);

        // Inactivate the policy.
        api::inactivate($policy->policyid);
        $policy = api::get_policy($policy->policyid);
        $this->assertNull($policy->currentversionid);

        // Load the policy version using both policyid and versionid.
        $loaded = api::get_policy_version($another->policyid, $another->versionid);
        $this->assertEquals($loaded->policyid, $another->policyid);
        $this->assertEquals($loaded->versionid, $another->versionid);

        // Load the policy version using versionid only.
        $loaded = api::get_policy_version(null, $another->versionid);
        $this->assertEquals($loaded->policyid, $another->policyid);
        $this->assertEquals($loaded->versionid, $another->versionid);

        // Save form as a new version with an empty revision.
        $formdata = api::form_policydoc_data($policy->policyid, $new->versionid);
        $formdata->revision = '';
        $formdata->summary_editor['text'] = '<strong>And one more summary</strong>';
        $formdata->summary_editor['format'] = FORMAT_MOODLE;
        $formdata->content_editor['text'] = '<h3>And one more test</h3>';
        $formdata->content_editor['format'] = FORMAT_HTML;
        $defaultrevision = api::get_default_policy_revision_value($new->policyid);
        $new2 = api::form_policydoc_update_new($policy->policyid, $formdata);
        $this->assertEquals($policy->policyid, $new2->policyid);
        $this->assertNotEquals($new->versionid, $new2->versionid);
        $this->assertEquals($defaultrevision, $new2->revision);

        // Save form as a new version with an existing revision.
        $formdata = api::form_policydoc_data($policy->policyid, $new->versionid);
        $formdata->revision = $new->revision;
        $formdata->summary_editor['text'] = '<strong>And one more summary</strong>';
        $formdata->summary_editor['format'] = FORMAT_MOODLE;
        $formdata->content_editor['text'] = '<h3>And one more test</h3>';
        $formdata->content_editor['format'] = FORMAT_HTML;
        $defaultrevision = api::get_default_policy_revision_value($new->policyid);
        $new2 = api::form_policydoc_update_new($policy->policyid, $formdata);
        $this->assertEquals($policy->policyid, $new2->policyid);
        $this->assertNotEquals($new->versionid, $new2->versionid);
        $this->assertEquals($defaultrevision, $new2->revision);
    }

    /**
     * Test changing the sort order of the policy documents.
     */
    public function test_policy_sortorder() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $formdata = api::form_policydoc_data();
        $formdata->name = 'Policy1';
        $formdata->summary_editor = ['text' => 'P1 summary', 'format' => FORMAT_HTML, 'itemid' => 0];
        $formdata->content_editor = ['text' => 'P1 content', 'format' => FORMAT_HTML, 'itemid' => 0];
        $policy1 = api::form_policydoc_add($formdata);

        $formdata = api::form_policydoc_data();
        $formdata->name = 'Policy2';
        $formdata->summary_editor = ['text' => 'P2 summary', 'format' => FORMAT_HTML, 'itemid' => 0];
        $formdata->content_editor = ['text' => 'P2 content', 'format' => FORMAT_HTML, 'itemid' => 0];
        $policy2 = api::form_policydoc_add($formdata);

        $this->assertTrue($policy1->sortorder < $policy2->sortorder);

        $formdata = api::form_policydoc_data();
        $formdata->name = 'Policy3';
        $formdata->summary_editor = ['text' => 'P3 summary', 'format' => FORMAT_HTML, 'itemid' => 0];
        $formdata->content_editor = ['text' => 'P3 content', 'format' => FORMAT_HTML, 'itemid' => 0];
        $policy3 = api::form_policydoc_add($formdata);

        $this->assertTrue($policy1->sortorder < $policy2->sortorder);
        $this->assertTrue($policy2->sortorder < $policy3->sortorder);

        api::move_up($policy3->policyid);

        $policy1 = api::get_policy($policy1->policyid);
        $policy2 = api::get_policy($policy2->policyid);
        $policy3 = api::get_policy($policy3->policyid);

        $this->assertTrue($policy1->sortorder < $policy3->sortorder);
        $this->assertTrue($policy3->sortorder < $policy2->sortorder);

        api::move_down($policy1->policyid);

        $policy1 = api::get_policy($policy1->policyid);
        $policy2 = api::get_policy($policy2->policyid);
        $policy3 = api::get_policy($policy3->policyid);

        $this->assertTrue($policy3->sortorder < $policy1->sortorder);
        $this->assertTrue($policy1->sortorder < $policy2->sortorder);

        $orderedlist = api::list_policies();
        $this->assertEquals([$policy3->policyid, $policy1->policyid, $policy2->policyid], array_keys($orderedlist));
    }

    /**
     * Test that list of policies can be filtered by audience
     */
    public function test_list_policies_audience() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $policy1 = $this->add_policy(['audience' => api::AUDIENCE_LOGGEDIN]);
        $policy2 = $this->add_policy(['audience' => api::AUDIENCE_GUESTS]);
        $policy3 = $this->add_policy();

        $list = api::list_policies();
        $this->assertEquals([$policy1->policyid, $policy2->policyid, $policy3->policyid], array_keys($list));

        $list = api::list_policies(null, false, api::AUDIENCE_LOGGEDIN);
        $this->assertEquals([$policy1->policyid, $policy3->policyid], array_keys($list));

        $list = api::list_policies([$policy1->policyid, $policy2->policyid], false, api::AUDIENCE_LOGGEDIN);
        $this->assertEquals([$policy1->policyid], array_keys($list));

        $list = api::list_policies(null, false, api::AUDIENCE_GUESTS);
        $this->assertEquals([$policy2->policyid, $policy3->policyid], array_keys($list));
    }

    /**
     * Helper method that creates a new policy for testing
     *
     * @param array $params
     * @return stdClass
     */
    protected function add_policy($params = []) {
        static $counter = 0;
        $counter++;

        $defaults = [
            'name' => 'Policy '.$counter,
            'summary_editor' => ['text' => "P$counter summary", 'format' => FORMAT_HTML, 'itemid' => 0],
            'content_editor' => ['text' => "P$counter content", 'format' => FORMAT_HTML, 'itemid' => 0],
            'audience' => api::AUDIENCE_ALL,
        ];

        $params = (array)$params + $defaults;
        $formdata = api::form_policydoc_data();
        foreach ($params as $key => $value) {
            $formdata->$key = $value;
        }
        return api::form_policydoc_add($formdata);
    }

    /**
     * Helper method that prepare a policy document with some versions.
     *
     * @param int $numbersions The number of policy versions to create.
     * @return array Array with all the policy versions created.
     */
    protected function create_versions($numversions = 2) {
        $policyversions = [];
        // Prepare a policy document with some versions.
        $formdata = api::form_policydoc_data();
        $formdata->name = 'Test policy';
        $formdata->revision = 'v1';
        $formdata->summary_editor = ['text' => 'summary', 'format' => FORMAT_HTML, 'itemid' => 0];
        $formdata->content_editor = ['text' => 'content', 'format' => FORMAT_HTML, 'itemid' => 0];
        $policy1 = api::form_policydoc_add($formdata);
        $policyversions[] = $policy1;

        for ($i = 2; $i <= $numversions; $i++) {
            $formdata = api::form_policydoc_data($policy1->policyid, $policy1->versionid);
            $formdata->revision = 'v'.$i;
            $policyversions[] = api::form_policydoc_update_new($policy1->policyid, $formdata);
        }

        return $policyversions;
    }

    /**
     * Test check if a user is a digital minor.
     */
    public function test_is_minor() {
        $this->resetAfterTest();

        $country1 = 'AU';
        $country2 = 'AT';
        $age1 = 8;
        $age2 = 14;
        $age3 = 16;

        $isminor1 = api::is_minor($age1, $country1);
        $isminor2 = api::is_minor($age2, $country1);
        $isminor3 = api::is_minor($age3, $country1);
        $isminor4 = api::is_minor($age1, $country2);
        $isminor5 = api::is_minor($age2, $country2);
        $isminor6 = api::is_minor($age3, $country2);

        $this->assertTrue($isminor1);
        $this->assertTrue($isminor2);
        $this->assertFalse($isminor3);
        $this->assertTrue($isminor4);
        $this->assertFalse($isminor5);
        $this->assertFalse($isminor6);
    }

    /**
     * Test behaviour of the {@link api::is_public()} method.
     */
    public function test_is_public() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Prepare a policy document with some versions.
        list($policy1, $policy2, $policy3) = $this->create_versions(3);

        api::make_current($policy2->policyid, $policy2->versionid);

        $policy1 = api::get_policy_version($policy1->policyid, $policy1->versionid);
        $policy2 = api::get_policy_version($policy2->policyid, $policy2->versionid);
        $policy3 = api::get_policy_version($policy3->policyid, $policy3->versionid);

        $this->assertFalse(api::is_public($policy1));
        $this->assertTrue(api::is_public($policy2));
        $this->assertFalse(api::is_public($policy3));
    }

    /**
     * Test behaviour of the {@link api::can_user_view_policy_version()} method.
     */
    public function test_can_user_view_policy_version() {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();

        $child = $this->getDataGenerator()->create_user();
        $parent = $this->getDataGenerator()->create_user();
        $adult = $this->getDataGenerator()->create_user();
        $officer = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $syscontext = context_system::instance();
        $childcontext = context_user::instance($child->id);

        $roleminorid = create_role('Digital minor', 'digiminor', 'Not old enough to accept site policies themselves');
        $roleparentid = create_role('Parent', 'parent', 'Can accept policies on behalf of their child');
        $roleofficerid = create_role('Policy officer', 'policyofficer', 'Can see all acceptances but can\'t edit policy documents');
        $rolemanagerid = create_role('Policy manager', 'policymanager', 'Can manage policy documents');

        assign_capability('tool/policy:accept', CAP_PROHIBIT, $roleminorid, $syscontext->id);
        assign_capability('tool/policy:acceptbehalf', CAP_ALLOW, $roleparentid, $syscontext->id);
        assign_capability('tool/policy:viewacceptances', CAP_ALLOW, $roleofficerid, $syscontext->id);
        assign_capability('tool/policy:managedocs', CAP_ALLOW, $rolemanagerid, $syscontext->id);

        role_assign($roleminorid, $child->id, $syscontext->id);
        // Becoming a parent is easy. Being a good one is difficult.
        role_assign($roleparentid, $parent->id, $childcontext->id);
        role_assign($roleofficerid, $officer->id, $syscontext->id);
        role_assign($rolemanagerid, $manager->id, $syscontext->id);

        accesslib_clear_all_caches_for_unit_testing();

        // Prepare a policy document with some versions.
        list($policy1, $policy2, $policy3) = $this->create_versions(3);

        // Normally users do not have access to policy drafts.
        $this->assertFalse(api::can_user_view_policy_version($policy1, null, $child->id));
        $this->assertFalse(api::can_user_view_policy_version($policy2, null, $parent->id));
        $this->assertFalse(api::can_user_view_policy_version($policy3, null, $CFG->siteguest));

        // Officers and managers have access even to drafts.
        $this->assertTrue(api::can_user_view_policy_version($policy1, null, $officer->id));
        $this->assertTrue(api::can_user_view_policy_version($policy3, null, $manager->id));

        // Current versions are public so that users can decide whether to even register on such a site.
        api::make_current($policy2->policyid, $policy2->versionid);
        $policy1 = api::get_policy_version($policy1->policyid, $policy1->versionid);
        $policy2 = api::get_policy_version($policy2->policyid, $policy2->versionid);
        $policy3 = api::get_policy_version($policy3->policyid, $policy3->versionid);

        $this->assertFalse(api::can_user_view_policy_version($policy1, null, $child->id));
        $this->assertTrue(api::can_user_view_policy_version($policy2, null, $child->id));
        $this->assertTrue(api::can_user_view_policy_version($policy2, null, $CFG->siteguest));
        $this->assertFalse(api::can_user_view_policy_version($policy3, null, $child->id));

        // Let the parent accept the policy on behalf of her child.
        $this->setUser($parent);
        api::accept_policies($policy2->versionid, $child->id);

        // Release a new version of the policy.
        api::make_current($policy3->policyid, $policy3->versionid);
        $policy1 = api::get_policy_version($policy1->policyid, $policy1->versionid);
        $policy2 = api::get_policy_version($policy2->policyid, $policy2->versionid);
        $policy3 = api::get_policy_version($policy3->policyid, $policy3->versionid);

        api::get_user_minors($parent->id);
        // They should now have access to the archived version (because they agreed) and the current one.
        $this->assertFalse(api::can_user_view_policy_version($policy1, null, $child->id));
        $this->assertFalse(api::can_user_view_policy_version($policy1, null, $parent->id));
        $this->assertTrue(api::can_user_view_policy_version($policy2, null, $child->id));
        $this->assertTrue(api::can_user_view_policy_version($policy2, null, $parent->id));
        $this->assertTrue(api::can_user_view_policy_version($policy3, null, $child->id));
        $this->assertTrue(api::can_user_view_policy_version($policy3, null, $parent->id));
    }

    /**
     * Test that accepting policy updates 'policyagreed'
     */
    public function test_accept_policies() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $policy1 = $this->add_policy();
        api::make_current($policy1->policyid, $policy1->versionid);
        $policy2 = $this->add_policy();
        api::make_current($policy2->policyid, $policy2->versionid);

        // Accept policy on behalf of somebody else.
        $user1 = $this->getDataGenerator()->create_user();
        $this->assertEquals(0, $DB->get_field('user', 'policyagreed', ['id' => $user1->id]));

        api::accept_policies([$policy1->versionid, $policy2->versionid], $user1->id);
        $this->assertEquals(1, $DB->get_field('user', 'policyagreed', ['id' => $user1->id]));

        // Now revoke.
        api::revoke_acceptance($policy1->versionid, $user1->id);
        $this->assertEquals(0, $DB->get_field('user', 'policyagreed', ['id' => $user1->id]));

        // Accept policies for oneself.
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user2);

        $this->assertEquals(0, $DB->get_field('user', 'policyagreed', ['id' => $user2->id]));

        api::accept_policies([$policy1->versionid]);
        $this->assertEquals(0, $DB->get_field('user', 'policyagreed', ['id' => $user2->id]));

        api::accept_policies([$policy2->versionid]);
        $this->assertEquals(1, $DB->get_field('user', 'policyagreed', ['id' => $user2->id]));
    }

    /**
     * Test behaviour of the {@link api::get_user_minors()} method.
     */
    public function test_get_user_minors() {
        $this->resetAfterTest();

        // A mother having two children, each child having own father.
        $mother1 = $this->getDataGenerator()->create_user();
        $father1 = $this->getDataGenerator()->create_user();
        $father2 = $this->getDataGenerator()->create_user();
        $child1 = $this->getDataGenerator()->create_user();
        $child2 = $this->getDataGenerator()->create_user();

        $syscontext = context_system::instance();
        $child1context = context_user::instance($child1->id);
        $child2context = context_user::instance($child2->id);

        $roleparentid = create_role('Parent', 'parent', 'Can accept policies on behalf of their child');

        assign_capability('tool/policy:acceptbehalf', CAP_ALLOW, $roleparentid, $syscontext->id);

        role_assign($roleparentid, $mother1->id, $child1context->id);
        role_assign($roleparentid, $mother1->id, $child2context->id);
        role_assign($roleparentid, $father1->id, $child1context->id);
        role_assign($roleparentid, $father2->id, $child2context->id);

        accesslib_clear_all_caches_for_unit_testing();

        $mother1minors = api::get_user_minors($mother1->id);
        $this->assertEquals(2, count($mother1minors));

        $father1minors = api::get_user_minors($father1->id);
        $this->assertEquals(1, count($father1minors));
        $this->assertEquals($child1->id, $father1minors[$child1->id]->id);

        $father2minors = api::get_user_minors($father2->id);
        $this->assertEquals(1, count($father2minors));
        $this->assertEquals($child2->id, $father2minors[$child2->id]->id);

        $this->assertEmpty(api::get_user_minors($child1->id));
        $this->assertEmpty(api::get_user_minors($child2->id));

        $extradata = api::get_user_minors($mother1->id, ['policyagreed', 'deleted']);
        $this->assertTrue(property_exists($extradata[$child1->id], 'policyagreed'));
        $this->assertTrue(property_exists($extradata[$child1->id], 'deleted'));
        $this->assertTrue(property_exists($extradata[$child2->id], 'policyagreed'));
        $this->assertTrue(property_exists($extradata[$child2->id], 'deleted'));
    }

    /**
     * Test behaviour of the {@link api::policy_revision_exists()} method.
     */
    public function test_policy_revision_exists() {
        $this->resetAfterTest();
        $this->setAdminUser();

        list($policy1, $policy2) = $this->create_versions(2);

        $this->assertTrue(api::policy_revision_exists($policy1->revision, $policy1->policyid));
        $this->assertTrue(api::policy_revision_exists($policy2->revision, $policy2->policyid));

        // Check that the function excludes the specified version.
        $this->assertFalse(api::policy_revision_exists($policy1->revision, $policy1->policyid, $policy1->versionid));
        $this->assertTrue(api::policy_revision_exists($policy2->revision, $policy1->policyid, $policy1->versionid));
    }
}
