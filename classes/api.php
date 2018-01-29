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
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy;

use context_system;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides the API of the policies plugin.
 *
 * @copyright 2018 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    /**
     * Returns a list of all policy documents and their versions (but with no actual content).
     *
     * @return stdClass;
     */
    public static function list_policies() {
        global $DB;

        $sql = "SELECT d.id AS policyid, d.name, d.description, d.currentversionid, d.sortorder,
                       v.id AS versionid, v.usermodified, v.timecreated, v.timemodified, v.revision
                  FROM {tool_policy} d
             LEFT JOIN {tool_policy_versions} v ON v.policyid = d.id
              ORDER BY d.sortorder ASC, v.timecreated DESC";

        $policies = [];

        $rs = $DB->get_recordset_sql($sql);

        foreach ($rs as $r) {
            if (!isset($policies[$r->policyid])) {
                $policies[$r->policyid] = (object) [
                    'id' => $r->policyid,
                    'name' => $r->name,
                    'description' => $r->description,
                    'currentversionid' => $r->currentversionid,
                    'sortorder' => $r->sortorder,
                    'versions' => [],
                ];
            }

            if (!empty($r->versionid)) {
                $policies[$r->policyid]->versions[] = (object) [
                    'id' => $r->versionid,
                    'timecreated' => $r->timecreated,
                    'timemodified' => $r->timemodified,
                    'revision' => $r->revision,
                ];
            };
        }

        $rs->close();

        return $policies;
    }

    /**
     * Load a particular policy document - without a particular version.
     *
     * @param int $policyid ID of the policy document.
     * @return stdClass
     */
    public static function get_policy($policyid) {
        global $DB;

        return $DB->get_record('tool_policy', ['id' => $policyid], 'id,name,currentversionid,sortorder', MUST_EXIST);
    }


    /**
     * Load a particular policy document version.
     *
     * @param int $policyid ID of the policy document.
     * @param int $versionid ID of the policy document revision.
     * @return stdClass
     */
    public static function get_policy_version($policyid, $versionid) {
        global $DB;

        $sql = "SELECT d.id AS policyid, d.name, d.currentversionid, d.sortorder,
                       v.id AS versionid, v.usermodified, v.timecreated, v.timemodified, v.revision, v.content, v.contentformat
                  FROM {tool_policy} d
                  JOIN {tool_policy_versions} v ON v.policyid = d.id
                 WHERE d.id = :policyid AND v.id = :versionid";

        $params = [
            'policyid' => $policyid,
            'versionid' => $versionid,
        ];

        return $DB->get_record_sql($sql, $params, MUST_EXIST);
    }

    /**
     * Load the policy version to be used in the {@link \tool_policy\form\policydoc} form.
     *
     * @param int $policyid ID of the policy document.
     * @param int $versionid ID of the policy document revision.
     * @return stdClass
     */
    public static function form_policydoc_data($policyid = null, $versionid = null) {

        $data = (object) [];

        if ($policyid) {
            if ($versionid) {
                $data = self::get_policy_version($policyid, $versionid);
                $contentfieldoptions = self::policy_content_field_options();
                $data = file_prepare_standard_editor($data, 'content', $contentfieldoptions, $contentfieldoptions['context'],
                    'tool_policy', 'policydocumentcontent', $versionid);

            } else {
                $data = self::get_policy($policyid);
            }
        }

        if (empty($versionid)) {
            $data->revision = date('Y').'-'.date('m').'-'.date('d').'-'.date('Hi');
            $data->contentformat = editors_get_preferred_format();
        }

        return $data;
    }

    /**
     * Save the data from the policydoc form as a new policy document.
     *
     * @param stdClass $form data submitted from the {@link \tool_policy\form\policydoc} form.
     * @return array [object,object] the new policy document and the new version record objects.
     */
    public static function form_policydoc_add(stdClass $form) {
        global $DB, $USER;

        $now = time();

        $contentfieldoptions = static::policy_content_field_options();
        $data = file_postupdate_standard_editor($form, 'content', $contentfieldoptions, $contentfieldoptions['context']);

        $policy = (object) [
            'name' => $form->name,
            'description' => $form->description,
            'sortorder' => 999,
        ];

        $policy->id = $DB->insert_record('tool_policy', $policy);

        $version = (object) [
            'usermodified' => $USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
            'policyid' => $policy->id,
            'revision' => $form->revision,
            'content' => $form->content,
            'contentformat' => $form->contentformat,
        ];

        $version->id = $DB->insert_record('tool_policy_versions', $version);

        $data = file_postupdate_standard_editor($form, 'content', $contentfieldoptions, $contentfieldoptions['context'],
            'tool_policy', 'policydocumentcontent', $version->id);

        $DB->set_field('tool_policy_versions', 'content', $data->content);

        return [$policy, $version];
    }

    /**
     * Save the data from the policydoc form as a new policy document version.
     *
     * @param int $policyid
     * @param stdClass $form data submitted from the {@link \tool_policy\form\policydoc} form.
     * @return array [object,object] the new policy document and the new version record objects.
     */
    public static function form_policydoc_update_new($policyid, stdClass $form) {
        global $DB, $USER;

        $now = time();

        // The policy name may be changed.
        $DB->set_field('tool_policy', 'name', $form->name, ['id' => $policyid]);

        $policy = static::get_policy($policyid);

        $contentfieldoptions = static::policy_content_field_options();
        $data = file_postupdate_standard_editor($form, 'content', $contentfieldoptions, $contentfieldoptions['context']);

        $version = (object) [
            'usermodified' => $USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
            'policyid' => $policy->id,
            'revision' => $form->revision,
            'content' => $form->content,
            'contentformat' => $form->contentformat,
        ];

        $version->id = $DB->insert_record('tool_policy_versions', $version);

        $data = file_postupdate_standard_editor($form, 'content', $contentfieldoptions, $contentfieldoptions['context'],
            'tool_policy', 'policydocumentcontent', $version->id);

        $DB->set_field('tool_policy_versions', 'content', $data->content);

        return [$policy, $version];
    }


    /**
     * Save the data from the policydoc form, overwriting the existing policy document version.
     *
     * @param int $policyid
     * @param int $versionid
     * @param stdClass $form data submitted from the {@link \tool_policy\form\policydoc} form.
     */
    public static function form_policydoc_update_overwrite($policyid, $versionid, stdClass $form) {
        global $DB, $USER;

        // Check the data consistency.
        static::get_policy_version($policyid, $versionid);

        // The policy name may be changed.
        $DB->set_field('tool_policy', 'name', $form->name, ['id' => $policyid]);

        $contentfieldoptions = static::policy_content_field_options();
        $data = file_postupdate_standard_editor($form, 'content', $contentfieldoptions, $contentfieldoptions['context'],
            'tool_policy', 'policydocumentcontent', $versionid);

        $version = (object) [
            'id' => $versionid,
            'usermodified' => $USER->id,
            'timemodified' => time(),
            'revision' => $form->revision,
            'content' => $form->content,
            'contentformat' => $form->contentformat,
        ];

        $DB->update_record('tool_policy_versions', $version);
    }

    /**
     * Make the given version the current one that everybody must accept.
     *
     * @param int $policyid
     * @param int $versionid
     */
    public static function make_current($policyid, $versionid) {
        global $DB;

        $policyversion = static::get_policy_version($policyid, $versionid);
        $DB->set_field('tool_policy', 'currentversionid', $versionid, ['id' => $policyid]);
    }

    /**
     * Inactivate the policy document - no version marked as current and the document does not apply.
     *
     * @param int $policyid
     */
    public static function inactivate($policyid) {
        global $DB;

        $policy = static::get_policy($policyid);
        $DB->set_field('tool_policy', 'currentversionid', null, ['id' => $policyid]);
    }

    /**
     * Editor field options for the policy content text.
     *
     * @return array
     */
    protected static function policy_content_field_options() {
        return ['trusttext' => true, 'subdirs' => false, 'context' => context_system::instance()];
    }
}
