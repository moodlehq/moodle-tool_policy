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

use context_helper;
use context_system;
use context_user;
use stdClass;
use tool_policy\event\acceptance_created;
use tool_policy\event\acceptance_updated;
use user_picture;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides the API of the policies plugin.
 *
 * @copyright 2018 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {

    const VERSION_STATUS_DRAFT = 'draft';
    const VERSION_STATUS_CURRENT = 'current';
    const VERSION_STATUS_ARCHIVE = 'archive';

    const AUDIENCE_ALL = 0;
    const AUDIENCE_LOGGEDIN = 1;
    const AUDIENCE_GUESTS = 2;

    /**
     * Returns a list of all policy documents and their versions (but with no actual content).
     *
     * @param array|int|null $ids Load only the given policies, defaults to all.
     * @param bool $onlycurrent If true, return only policies with a current version defined.
     * @param int $audience Only those that match specified audience (null means any). Policies with audience AUDIENCE_ALL are always returned.
     * @param int $countacceptances return number of user acceptances for each version
     * @return stdClass;
     */
    public static function list_policies($ids = null, $onlycurrent = false, $audience = null, $countacceptances = false) {
        global $DB;

        $fields = "SELECT d.id AS policyid, d.name, d.description, d.audience, d.currentversionid, d.sortorder,
                       v.id AS versionid, v.usermodified, v.timecreated, v.timemodified, v.revision";
        $sql = " FROM {tool_policy} d";
        if ($onlycurrent) {
            $sql .= " INNER JOIN {tool_policy_versions} v ON v.policyid = d.id AND v.id = d.currentversionid ";
        } else {
            $sql .= " LEFT JOIN {tool_policy_versions} v ON v.policyid = d.id ";
        }

        $params = [];
        $where = [];

        if ($ids) {
            list($idsql, $idparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $where[]  = "d.id $idsql";
            $params = array_merge($params, $idparams);
        }

        if ($audience) {
            $where[] = "(d.audience = :audience OR d.audience = :audienceall)";
            $params['audience'] = $audience;
            $params['audienceall'] = self::AUDIENCE_ALL;
        }

        if ($countacceptances) {
            $sql .= " LEFT JOIN (
                            SELECT policyversionid, count(*) AS acceptancescount
                            FROM {tool_policy_acceptances}
                            GROUP BY policyversionid
                        ) ua ON ua.policyversionid = v.id";
            $fields .= ", COALESCE(ua.acceptancescount,0) AS acceptancescount";
        }

        if ($where) {
            $sql .= " WHERE " . join(" AND ", $where) . " ";
        }

        $sql .= " ORDER BY d.sortorder ASC, v.timecreated DESC";

        $policies = [];

        $rs = $DB->get_recordset_sql($fields . $sql, $params);

        foreach ($rs as $r) {
            if (!isset($policies[$r->policyid])) {
                $policies[$r->policyid] = (object) [
                    'id' => $r->policyid,
                    'name' => $r->name,
                    'description' => $r->description,
                    'audience' => $r->audience,
                    'currentversionid' => $r->currentversionid,
                    'sortorder' => $r->sortorder,
                    'versions' => [],
                ];
                if ($countacceptances) {
                    $policies[$r->policyid]->acceptancescount = null;
                }
            }

            if (!empty($r->versionid)) {
                $policies[$r->policyid]->versions[$r->versionid] = (object) [
                    'id' => $r->versionid,
                    'timecreated' => $r->timecreated,
                    'timemodified' => $r->timemodified,
                    'revision' => $r->revision,
                ];
            }

            if ($countacceptances && $r->versionid) {
                $acceptancescount = ($r->audience == self::AUDIENCE_GUESTS) ? null : $r->acceptancescount;
                $policies[$r->policyid]->versions[$r->versionid]->acceptancescount = $acceptancescount;
                if ($r->versionid == $r->currentversionid) {
                    $policies[$r->policyid]->acceptancescount = $acceptancescount;
                }
            }
        }

        $rs->close();

        return $policies;
    }

    /**
     * Returns total number of users who are expected to accept site policy
     *
     * @return int|null
     */
    public static function count_total_users() {
        global $DB, $CFG;
        static $cached = null;
        if ($cached === null) {
            $cached = $DB->count_records_select('user', 'deleted = 0 AND id <> ?', [$CFG->siteguest]);
        }
        return $cached;
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
            'id AS policyid,name,description,audience,currentversionid,sortorder', MUST_EXIST);
    }


    /**
     * Load a particular policy document version.
     *
     * @param int $policyid ID of the policy document (can be null)
     * @param int $versionid ID of the policy document revision.
     * @return stdClass
     */
    public static function get_policy_version($policyid, $versionid) {
        global $DB;

        $sql = "SELECT d.id AS policyid, d.name, d.description, d.audience, d.currentversionid, d.sortorder,
                       v.id AS versionid, v.usermodified, v.timecreated, v.timemodified, v.revision,
                       v.summary, v.summaryformat, v.content, v.contentformat
                  FROM {tool_policy} d
                  JOIN {tool_policy_versions} v ON v.policyid = d.id
                 WHERE v.id = :versionid";

        $params = [
            'versionid' => $versionid,
        ];

        if ($policyid) {
            $sql .= " AND d.id = :policyid";
            $params['policyid'] = $policyid;
        }

        return $DB->get_record_sql($sql, $params, MUST_EXIST);
    }

    /**
     * Check if there is another version with the same revision name for this policy.
     * If a version ID is specified, it will be excluded from the search.
     *
     * @param string $revision Revision name to check if exists.
     * @param int $policyid ID of the policy document.
     * @param int $excludedversionid ID of the policy version to exclude from the search.
     * @return bool
     */
    public static function policy_revision_exists($revision, $policyid, $excludedversionid = null) {
        global $DB;

        $sql = "SELECT v.id AS versionid
                  FROM {tool_policy_versions} v
                 WHERE v.policyid = :policyid AND v.revision = :revision";

        $params = [
            'policyid' => $policyid,
            'revision' => $revision,
        ];

        if ($excludedversionid) {
            $sql .= " AND v.id != :versionid";
            $params['versionid'] = $excludedversionid;
        }

        return $DB->record_exists_sql($sql, $params);
    }

    /**
     * Calculate the default revision value for a policy version.
     *
     * @param int $policyid ID of the policy document.
     * @param int $versionid ID of the policy version to get default revision.
     * @return string
     */
    public static function get_default_policy_revision_value($policyid, $versionid = null) {
        $revision = userdate(time(), get_string('strftimedate', 'core_langconfig'));

        // Make sure the revision is unique for this policy.
        $defaultrevision = $revision;
        $i = 1;
        while (static::policy_revision_exists($defaultrevision, $policyid, $versionid)) {
            $defaultrevision = "$revision - v$i";
            $i++;
        }

        return $defaultrevision;
    }

    /**
     * Is the given policy version available even to anybody?
     *
     * @param stdClass $policy Object with currentversionid and versionid properties
     * @return bool
     */
    public static function is_public($policy) {

        if ($policy->currentversionid == $policy->versionid) {
            return true;
        }

        return false;
    }

    /**
     * Can the user view the given policy version document?
     *
     * @param stdClass $policy Object with currentversionid and versionid properties
     * @param int $behalfid The id of user on whose behalf the user is viewing the policy
     * @param int $userid The user whom access is evaluated, defaults to the current one
     * @return bool
     */
    public static function can_user_view_policy_version($policy, $behalfid = null, $userid = null) {
        global $USER;

        if (static::is_public($policy)) {
            return true;
        }

        if (empty($userid)) {
            $userid = $USER->id;
        }

        // Check if the user is viewing the policy on someone else's behalf.
        // Typical scenario is a parent viewing the policy on behalf of her child.
        if ($behalfid > 0) {
            $behalfcontext = context_user::instance($behalfid);

            if ($behalfid != $userid && !has_capability('tool/policy:acceptbehalf', $behalfcontext, $userid)) {
                return false;
            }

            // Check that the other user (e.g. the child) has access to the policy.
            // Pass a negative third parameter to avoid eventual endless loop.
            // We do not support grand-parent relations.
            return static::can_user_view_policy_version($policy, -1, $behalfid);
        }

        // Users who can manage policies, can see all versions.
        if (has_capability('tool/policy:managedocs', context_system::instance(), $userid)) {
            return true;
        }

        // User who can see all acceptances, must be also allowed to see what was accepted.
        if (has_capability('tool/policy:viewacceptances', context_system::instance(), $userid)) {
            return true;
        }

        // Users have access to all the policies they have ever accepted.
        if (static::is_user_version_accepted($userid, $policy->versionid)) {
            return true;
        }

        // Check if the user could get access through some of her minors.
        if ($behalfid === null) {
            foreach (static::get_user_minors($userid) as $minor) {
                if (static::can_user_view_policy_version($policy, $minor->id, $userid)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return the user's minors - other users on which behalf we can accept policies.
     *
     * Returned objects contain all the standard user name and picture fields as well as the context instanceid.
     *
     * @param int $userid The id if the user with parental responsibility
     * @param array $extrafields Extra fields to be included in result
     * @return array of objects
     */
    public static function get_user_minors($userid, array $extrafields = null) {
        global $DB;

        $ctxfields = context_helper::get_preload_record_columns_sql('c');
        $namefields = get_all_user_name_fields(true, 'u');
        $pixfields = user_picture::fields('u', $extrafields);

        $sql = "SELECT $ctxfields, $namefields, $pixfields
                  FROM {role_assignments} ra
                  JOIN {context} c ON c.contextlevel = ".CONTEXT_USER." AND ra.contextid = c.id
                  JOIN {user} u ON c.instanceid = u.id
                 WHERE ra.userid = ?
              ORDER BY u.lastname ASC, u.firstname ASC";

        $rs = $DB->get_recordset_sql($sql, [$userid]);

        $minors = [];

        foreach ($rs as $record) {
            context_helper::preload_from_record($record);
            $childcontext = context_user::instance($record->id);
            if (has_capability('tool/policy:acceptbehalf', $childcontext, $userid)) {
                $minors[$record->id] = $record;
            }
        }

        $rs->close();

        return $minors;
    }

    /**
     * Calculate the status of a particular policy document version.
     *
     * @param stdClass $policy Policy document with all the versions.
     * @param int $versionid ID of the policy document revision.
     * @return string Status of the policy document version.
     */
    public static function get_policy_version_status($policy, $versionid) {
        $status = static::VERSION_STATUS_DRAFT;
        if (!empty($policy->currentversionid) && array_key_exists($versionid, $policy->versions)) {
            if ($policy->currentversionid == $versionid) {
                $status = static::VERSION_STATUS_CURRENT;
            } else if ($policy->versions[$versionid]->timecreated < $policy->versions[$policy->currentversionid]->timecreated) {
                $status = static::VERSION_STATUS_ARCHIVE;
            }
        }

        return $status;
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
                $summaryfieldoptions = static::policy_summary_field_options();
                $data = file_prepare_standard_editor($data, 'summary', $summaryfieldoptions, $summaryfieldoptions['context'],
                    'tool_policy', 'policydocumentsummary', $versionid);
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
                $summaryfieldoptions = static::policy_summary_field_options();
                $data = file_prepare_standard_editor($data, 'summary', $summaryfieldoptions, $summaryfieldoptions['context']);
                $contentfieldoptions = static::policy_content_field_options();
                $data = file_prepare_standard_editor($data, 'content', $contentfieldoptions, $contentfieldoptions['context']);

            } else {
                // Adding a new policy document without a template.
                $data = (object) [];
            }
        }

        if (!isset($data->summaryformat)) {
            $data->summaryformat = editors_get_preferred_format();
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
            'description' => get_string('template_'.$template.'_description', 'tool_policy'),
            'summary' => get_string('template_'.$template.'_summary', 'tool_policy'),
            'summaryformat' => FORMAT_HTML,
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

        $summaryfieldoptions = static::policy_summary_field_options();
        $form = file_postupdate_standard_editor($form, 'summary', $summaryfieldoptions, $summaryfieldoptions['context']);

        $contentfieldoptions = static::policy_content_field_options();
        $form = file_postupdate_standard_editor($form, 'content', $contentfieldoptions, $contentfieldoptions['context']);

        $policy = (object) [
            'name' => $form->name,
            'description' => empty($form->description) ? '' : $form->description,
            'audience' => empty($form->audience) ? 0 : $form->audience,
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

        // The policy properties may have changed too.
        $DB->update_record('tool_policy', (object) [
            'id' => $policyid,
            'name' => $form->name,
            'description' => empty($form->description) ? '' : $form->description,
            'audience' => empty($form->audience) ? 0 : $form->audience,
        ]);

        $policy = static::get_policy($policyid);

        $summaryfieldoptions = static::policy_summary_field_options();
        $form = file_postupdate_standard_editor($form, 'summary', $summaryfieldoptions, $summaryfieldoptions['context']);

        $contentfieldoptions = static::policy_content_field_options();
        $form = file_postupdate_standard_editor($form, 'content', $contentfieldoptions, $contentfieldoptions['context']);

        if (empty($form->revision) || static::policy_revision_exists($form->revision, $policyid)) {
            // Revision must be unique for each policy.
            $form->revision = static::get_default_policy_revision_value($policyid);
        }

        $version = (object) [
            'usermodified' => $USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
            'policyid' => $policy->policyid,
            'revision' => $form->revision,
            'summary' => $form->summary,
            'summaryformat' => $form->summaryformat,
            'content' => $form->content,
            'contentformat' => $form->contentformat,
        ];

        $versionid = $DB->insert_record('tool_policy_versions', $version);

        $form = file_postupdate_standard_editor($form, 'summary', $summaryfieldoptions, $summaryfieldoptions['context'],
            'tool_policy', 'policydocumentsummary', $versionid);

        $form = file_postupdate_standard_editor($form, 'content', $contentfieldoptions, $contentfieldoptions['context'],
            'tool_policy', 'policydocumentcontent', $versionid);

        $DB->update_record('tool_policy_versions', (object) [
            'id' => $versionid,
            'summary' => $form->summary,
            'content' => $form->content,
        ]);

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

        // The policy properties may have changed too.
        $DB->update_record('tool_policy', (object) [
            'id' => $policyid,
            'name' => $form->name,
            'description' => $form->description,
            'audience' => empty($form->audience) ? 0 : $form->audience,
        ]);

        $summaryfieldoptions = static::policy_summary_field_options();
        $form = file_postupdate_standard_editor($form, 'summary', $summaryfieldoptions, $summaryfieldoptions['context'],
            'tool_policy', 'policydocumentsummary', $versionid);

        $contentfieldoptions = static::policy_content_field_options();
        $form = file_postupdate_standard_editor($form, 'content', $contentfieldoptions, $contentfieldoptions['context'],
            'tool_policy', 'policydocumentcontent', $versionid);

        if (empty($form->revision) || static::policy_revision_exists($form->revision, $policyid, $versionid)) {
            // Revision must be unique for each policy.
            $form->revision = static::get_default_policy_revision_value($policyid, $versionid);
        }

        $version = (object) [
            'id' => $versionid,
            'usermodified' => $USER->id,
            'timemodified' => time(),
            'revision' => $form->revision,
            'summary' => $form->summary,
            'summaryformat' => $form->summaryformat,
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
     * Editor field options for the policy summary text.
     *
     * @return array
     */
    public static function policy_summary_field_options() {
        global $CFG;
        require_once($CFG->libdir.'/formslib.php');

        return [
            'trusttext' => true,
            'subdirs' => false,
            'maxfiles' => -1,
            'context' => context_system::instance(),
        ];
    }

    /**
     * Editor field options for the policy content text.
     *
     * @return array
     */
    public static function policy_content_field_options() {
        global $CFG;
        require_once($CFG->libdir.'/formslib.php');

        return [
            'trusttext' => true,
            'subdirs' => false,
            'maxfiles' => -1,
            'context' => context_system::instance(),
        ];
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
     * Returns list of acceptances for this user.
     *
     * @param int $userid id of a user.
     * @param int|array $versions list of policy versions.
     * @return array list of acceptances indexed by versionid.
     */
    public static function get_user_acceptances($userid, $versions = null) {
        global $DB;

        list($vsql, $vparams) = ['', []];
        if (!empty($versions)) {
            list($vsql, $vparams) = $DB->get_in_or_equal($versions, SQL_PARAMS_NAMED, 'ver');
            $vsql = ' AND a.policyversionid ' . $vsql;
        }

        $userfieldsmod = get_all_user_name_fields(true, 'm', null, 'mod');
        $sql = "SELECT u.id AS mainuserid, a.policyversionid, a.status, a.lang, a.timemodified, a.usermodified, a.note, 
                  u.policyagreed, $userfieldsmod
                  FROM {user} u
                  INNER JOIN {tool_policy_acceptances} a ON a.userid = u.id AND a.userid = :userid $vsql
                  LEFT JOIN {user} m ON m.id = a.usermodified";
        $params = ['userid' => $userid];
        $result = $DB->get_recordset_sql($sql, $params + $vparams);

        $acceptances = [];
        foreach ($result as $row) {
            if (!empty($row->policyversionid)) {
                $acceptances[$row->policyversionid] = $row;
            }
        }
        $result->close();

        return $acceptances;
    }

    /**
     * Returns version acceptance for this user.
     *
     * @param int $userid User identifier.
     * @param int $versionid Policy version identifier.
     * @param array|null $acceptances List of policy version acceptances indexed by versionid.
     * @return stdClass|null Acceptance object if the user has ever accepted this version or null if not.
     */
    public static function get_user_version_acceptance($userid, $versionid, $acceptances = null) {
        if (empty($acceptances)) {
            $acceptances = static::get_user_acceptances($userid, $versionid);
        }
        if (array_key_exists($versionid, $acceptances)) {
            // The policy version has ever been accepted.
            return $acceptances[$versionid];
        }

        return null;
    }

    /**
     * Returns version acceptance for this user.
     *
     * @param int $userid User identifier.
     * @param int $versionid Policy version identifier.
     * @param array|null $acceptances Iist of policy version acceptances indexed by versionid.
     * @return bool True if this user has accepted this policy version; false otherwise.
     */
    public static function is_user_version_accepted($userid, $versionid, $acceptances = null) {
        $acceptance = static::get_user_version_acceptance($userid, $versionid, $acceptances);
        if (!empty($acceptance)) {
            return $acceptance->status;
        }

        return false;
    }

    /**
     * Get the list of policies and versions that current user is able to see and the respective acceptance records for the selected user.
     *
     * @param int $userid
     * @return array array with the same structure that list_policies() returns with additional attribute acceptance for versions
     */
    public static function get_policies_with_acceptances($userid) {
        // Get the list of policies and versions that current user is able to see
        // and the respective acceptance records for the selected user.
        $policies = api::list_policies(null, false, api::AUDIENCE_LOGGEDIN);
        $acceptances = api::get_user_acceptances($userid);
        foreach ($policies as $policyid => $policy) {
            foreach ($policy->versions as $versionid => $version) {
                $policytocheck = fullclone($policy);
                $policytocheck->versionid = $versionid;
                if (!self::can_user_view_policy_version($policytocheck, $userid)) {
                    unset($policy->versions[$versionid]);
                } else if (!empty($acceptances[$versionid]->status)) {
                    $policy->versions[$versionid]->acceptance = $acceptances[$versionid];
                }
            }
            if (empty($policy->versions)) {
                // User can not view any version for this policy.
                unset($policies[$policyid]);
            }
        }

        return $policies;
    }

    /**
     * Accepts the current revisions of all policies that the user has not yet accepted
     *
     * @param array|int $policyversionid
     * @param int|null $userid
     * @param string|null $note
     * @param string|null $lang
     */
    public static function accept_policies($policyversionid, $userid = null, $note = null, $lang = null) {
        global $DB, $USER;
        if (!isloggedin() || isguestuser()) {
            throw new \moodle_exception('noguest');
        }
        if (!$userid) {
            $userid = $USER->id;
        }
        $usercontext = \context_user::instance($userid);
        if ($userid == $USER->id) {
            require_capability('tool/policy:accept', context_system::instance());
        } else {
            require_capability('tool/policy:acceptbehalf', $usercontext);
        }

        if (empty($policyversionid)) {
            return;
        } else if (!is_array($policyversionid)) {
            $policyversionid = [$policyversionid];
        }
        list($sql, $params) = $DB->get_in_or_equal($policyversionid, SQL_PARAMS_NAMED);
        $sql = "SELECT v.id AS versionid, a.*
                  FROM {tool_policy_versions} v
                  LEFT JOIN {tool_policy_acceptances} a ON a.userid = :userid AND a.policyversionid = v.id
                  WHERE (a.id IS NULL or a.status <> 1) AND v.id " . $sql;
        $needacceptance = $DB->get_records_sql($sql, ['userid' => $userid] + $params);

        $updatedata = ['status' => 1, 'lang' => $lang ?: current_language(),
            'timemodified' => time(), 'usermodified' => $USER->id, 'note' => $note];
        foreach ($needacceptance as $versionid => $currentacceptance) {
            unset($currentacceptance->versionid);
            if ($currentacceptance->id) {
                $updatedata['id'] = $currentacceptance->id;
                $DB->update_record('tool_policy_acceptances', $updatedata);
                acceptance_updated::create_from_record((object)($updatedata + (array)$currentacceptance))->trigger();
            } else {
                $updatedata['timecreated'] = $updatedata['timemodified'];
                $updatedata['policyversionid'] = $versionid;
                $updatedata['userid'] = $userid;
                $updatedata['id'] = $DB->insert_record('tool_policy_acceptances', $updatedata);
                acceptance_created::create_from_record((object)($updatedata + (array)$currentacceptance))->trigger();
            }
        }

        self::update_policyagreed($userid);
    }

    /**
     * Make sure that $user->policyagreed matches the agreement to the policies
     *
     * @param int|stdClass|null $user user to check (null for current user)
     */
    public static function update_policyagreed($user = null) {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot.'/user/lib.php');

        if (!$user || (is_numeric($user) && $user == $USER->id)) {
            $user = $USER;
        } else if (!is_object($user)) {
            $user = $DB->get_record('user', ['id' => $user], 'id, policyagreed');
        }

        $sql = "SELECT d.id, a.status
                  FROM {tool_policy} d
                  INNER JOIN {tool_policy_versions} v ON v.policyid = d.id AND v.id = d.currentversionid
                  LEFT JOIN {tool_policy_acceptances} a ON a.userid = :userid AND a.policyversionid = v.id
                  WHERE (d.audience = :audience OR d.audience = :audienceall)";
        $params = ['audience' => self::AUDIENCE_LOGGEDIN, 'audienceall' => self::AUDIENCE_ALL, 'userid' => $user->id];
        $policies = $DB->get_records_sql_menu($sql, $params);
        $acceptedpolicies = array_filter($policies);
        $policyagreed = (count($policies) == count($acceptedpolicies)) ? 1 : 0;

        if ($user->policyagreed != $policyagreed) {
            $user->policyagreed = $policyagreed;
            $DB->set_field('user', 'policyagreed', $policyagreed, ['id' => $user->id]);
        }
    }

    /**
     * May be used to revert accidentally granted acceptance for another user
     *
     * @param int $policyversionid
     * @param int $userid
     * @param null $note
     */
    public static function revoke_acceptance($policyversionid, $userid, $note = null) {
        global $DB, $USER;
        if (!isloggedin() || isguestuser()) {
            throw new \moodle_exception('noguest');
        }
        if (!$userid) {
            $userid = $USER->id;
        }
        $usercontext = \context_user::instance($userid);
        if ($userid == $USER->id) {
            require_capability('tool/policy:accept', context_system::instance());
        } else {
            require_capability('tool/policy:acceptbehalf', $usercontext);
        }

        if ($currentacceptance = $DB->get_record('tool_policy_acceptances',
                ['policyversionid' => $policyversionid, 'userid' => $userid])) {
            $updatedata = ['id' => $currentacceptance->id, 'status' => 0, 'timemodified' => time(),
                'usermodified' => $USER->id, 'note' => $note];
            $DB->update_record('tool_policy_acceptances', $updatedata);
            acceptance_updated::create_from_record((object)($updatedata + (array)$currentacceptance))->trigger();
        }

        self::update_policyagreed($userid);
    }
}
