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

        // Pre-load the form for adding a new policy document.
        $formdata = api::form_policydoc_data();
        $this->assertNotNull($formdata->revision);

        // Pre-load the form for adding a new policy document based on a template.
        $formdata = api::form_policydoc_data(null, null, 'site');
        $this->assertNotNull($formdata->name);
        $this->assertNotNull($formdata->summary_editor['text']);
        $this->assertNotNull($formdata->summary_editor['format']);
        $this->assertSame(0, $formdata->summary_editor['itemid']);
        $this->assertNotNull($formdata->content_editor['text']);
        $this->assertNotNull($formdata->content_editor['format']);
        $this->assertSame(0, $formdata->content_editor['itemid']);

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
    }

    /**
     * Test changing the sort order of the policy documents.
     */
    public function test_policy_sortorder() {
        $this->resetAfterTest();

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
}
