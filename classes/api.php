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
     * @param int array|int|null Load only the given policies, defaults to all.
     * @return stdClass;
     */
    public static function list_policies($ids = null) {
        global $DB;

        $sql = "SELECT d.id AS policyid, d.name, d.description, d.currentversionid, d.sortorder,
                       v.id AS versionid, v.usermodified, v.timecreated, v.timemodified, v.revision
                  FROM {tool_policy} d
             LEFT JOIN {tool_policy_versions} v ON v.policyid = d.id ";

        $params = [];

        if ($ids) {
            list($idsql, $idparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $sql .= " WHERE d.id $idsql ";
            $params = array_merge($params, $idparams);
        }

        $sql .= " ORDER BY d.sortorder ASC, v.timecreated DESC";

        $policies = [];

        $rs = $DB->get_recordset_sql($sql, $params);

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
                $policies[$r->policyid]->versions[$r->versionid] = (object) [
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

        return $DB->get_record('tool_policy', ['id' => $policyid],
            'id AS policyid,name,description,currentversionid,sortorder', MUST_EXIST);
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

        $sql = "SELECT d.id AS policyid, d.name, d.description, d.currentversionid, d.sortorder,
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
     * @param string $template The name of the template to use.
     * @return stdClass
     */
    public static function form_policydoc_data($policyid = null, $versionid = null, $template = '') {

        if ($policyid) {
            if ($versionid) {
                // Editing an existing policy document version.
                $data = static::get_policy_version($policyid, $versionid);
                $contentfieldoptions = static::policy_content_field_options();
                $data = file_prepare_standard_editor($data, 'content', $contentfieldoptions, $contentfieldoptions['context'],
                    'tool_policy', 'policydocumentcontent', $versionid);

            } else {
                // Adding a new version of an existing policy document.
                $data = static::get_policy($policyid);
            }

        } else {
            if ($template) {
                // Adding a new policy document from a template.
                $data = static::policy_from_template($template);
                $contentfieldoptions = static::policy_content_field_options();
                $data = file_prepare_standard_editor($data, 'content', $contentfieldoptions, $contentfieldoptions['context']);

            } else {
                // Adding a new policy document without a template.
                $data = (object) [];
            }
        }

        if (!isset($data->revision)) {
            $data->revision = date('Y').'-'.date('m').'-'.date('d').'-'.date('Hi');
        }

        if (!isset($data->contentformat)) {
            $data->contentformat = editors_get_preferred_format();
        }

        return $data;
    }

    /**
     * Returns policy form data from a given template.
     *
     * @param string $template one of site|privacy|thirdparties
     * @return stdClass
     */
    protected static function policy_from_template($template) {

        $data = (object) [
            'name' => get_string('template_'.$template.'_name', 'tool_policy'),
            'content' => get_string('template_'.$template.'_content', 'tool_policy'),
            'contentformat' => FORMAT_HTML,
        ];

        return $data;
    }

    /**
     * Save the data from the policydoc form as a new policy document.
     *
     * @param stdClass $form data submitted from the {@link \tool_policy\form\policydoc} form.
     * @return stdClass policy version object as returned by {@link static::get_policy_version()}}
     */
    public static function form_policydoc_add(stdClass $form) {
        global $DB, $USER;

        $now = time();

        $contentfieldoptions = static::policy_content_field_options();
        $data = file_postupdate_standard_editor($form, 'content', $contentfieldoptions, $contentfieldoptions['context']);

        $policy = (object) [
            'name' => $form->name,
            'description' => empty($form->description) ? '' : $form->description,
            'sortorder' => 999,
        ];

        $policyid = $DB->insert_record('tool_policy', $policy);

        static::distribute_policy_document_sortorder();

        return static::form_policydoc_update_new($policyid, $form);
    }

    /**
     * Save the data from the policydoc form as a new policy document version.
     *
     * @param int $policyid
     * @param stdClass $form data submitted from the {@link \tool_policy\form\policydoc} form.
     * @return stdClass policy version object as returned by {@link static::get_policy_version()}}
     */
    public static function form_policydoc_update_new($policyid, stdClass $form) {
        global $DB, $USER;

        $now = time();

        // The policy name and description may be changed.
        $DB->update_record('tool_policy', (object) [
            'id' => $policyid,
            'name' => $form->name,
            'description' => empty($form->description) ? '' : $form->description,
        ]);

        $policy = static::get_policy($policyid);

        $contentfieldoptions = static::policy_content_field_options();
        $data = file_postupdate_standard_editor($form, 'content', $contentfieldoptions, $contentfieldoptions['context']);

        $version = (object) [
            'usermodified' => $USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
            'policyid' => $policy->policyid,
            'revision' => $form->revision,
            'content' => $form->content,
            'contentformat' => $form->contentformat,
        ];

        $versionid = $DB->insert_record('tool_policy_versions', $version);

        $data = file_postupdate_standard_editor($form, 'content', $contentfieldoptions, $contentfieldoptions['context'],
            'tool_policy', 'policydocumentcontent', $versionid);

        $DB->set_field('tool_policy_versions', 'content', $data->content, ['id' => $versionid]);

        return static::get_policy_version($policyid, $versionid);
    }


    /**
     * Save the data from the policydoc form, overwriting the existing policy document version.
     *
     * @param int $policyid
     * @param int $versionid
     * @param stdClass $form data submitted from the {@link \tool_policy\form\policydoc} form.
     * @return stdClass policy version object as returned by {@link static::get_policy_version()}}
     */
    public static function form_policydoc_update_overwrite($policyid, $versionid, stdClass $form) {
        global $DB, $USER;

        // Check the data consistency.
        static::get_policy_version($policyid, $versionid);

        // The policy name and description may be changed.
        $DB->update_record('tool_policy', (object) [
            'id' => $policyid,
            'name' => $form->name,
            'description' => $form->description,
        ]);

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

        return static::get_policy_version($policyid, $versionid);
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
     * Check if the user is a digital minor.
     *
     * @param int $dateofbirth
     * @param string $country
     * @return bool
     */
    public static function is_minor($dateofbirth, $country) {

        $age = static::return_age($dateofbirth);
        $agedigitalconsentmap = static::parse_age_digital_consent_map();

        return array_key_exists($country, $agedigitalconsentmap) ?
            $age < $agedigitalconsentmap[$country] : $age < $agedigitalconsentmap['*'];
    }

    /**
     * Editor field options for the policy content text.
     *
     * @return array
     */
    protected static function policy_content_field_options() {
        return ['trusttext' => true, 'subdirs' => false, 'context' => context_system::instance()];
    }

    /**
     * Re-sets the sortorder field of the policy documents to even values.
     */
    protected static function distribute_policy_document_sortorder() {
        global $DB;

        $sql = "SELECT p.id, p.sortorder, MAX(v.timecreated) AS timerecentcreated
                  FROM {tool_policy} p
             LEFT JOIN {tool_policy_versions} v ON v.policyid = p.id
              GROUP BY p.id, p.sortorder
              ORDER BY p.sortorder ASC, timerecentcreated ASC";

        $rs = $DB->get_recordset_sql($sql);
        $sortorder = 10;

        foreach ($rs as $record) {
            if ($record->sortorder != $sortorder) {
                $DB->set_field('tool_policy', 'sortorder', $sortorder, ['id' => $record->id]);
            }
            $sortorder = $sortorder + 2;
        }

        $rs->close();
    }

    /**
     * Change the policy document's sortorder.
     *
     * @param int $policyid
     * @param int $step
     */
    protected static function move_policy_document($policyid, $step) {
        global $DB;

        $policy = static::get_policy($policyid);
        $DB->set_field('tool_policy', 'sortorder', $policy->sortorder + $step, ['id' => $policyid]);
        static::distribute_policy_document_sortorder();
    }

    /**
     * Move the given policy document up in the list.
     *
     * @param id $policyid
     */
    public static function move_up($policyid) {
        static::move_policy_document($policyid, -3);
    }

    /**
     * Move the given policy document down in the list.
     *
     * @param id $policyid
     */
    public static function move_down($policyid) {
        static::move_policy_document($policyid, 3);
    }

    /**
     * Parse the agedigitalconsentmap setting into an array.
     *
     * @return array $ageconsentmapparsed
     */
    protected static function parse_age_digital_consent_map() {

        $ageconsentmapparsed = array();
        $ageconsentmap = get_config('tool_policy', 'agedigitalconsentmap');
        $lines = preg_split( '/\r\n|\r|\n/', $ageconsentmap);
        foreach ($lines as $line) {
            $arr = explode(" ", $line);
            $ageconsentmapparsed[$arr[0]] = $arr[1];
        }

        return $ageconsentmapparsed;
    }

    /**
     * Return age from a date.
     *
     * @param int $date
     * @return float
     */
    protected static function return_age($date) {

        $t = time();
        $age = $t - $date;

        return floor($age/31536000);
    }
}
