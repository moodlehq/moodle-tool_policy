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
 * View user acceptances to the policies
 *
 * @package     tool_policy
 * @copyright   2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/lib/tablelib.php');

/**
 * Class acceptances_table
 *
 * @package     tool_policy
 * @copyright   2018 Marina Glancy
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class acceptances_table extends \table_sql {

    protected $versionids;
    protected $policies;

    public function __construct($uniqueid, $baseurl, $policies) {
        global $CFG;
        parent::__construct($uniqueid);
        $this->is_downloading(optional_param('download', 0, PARAM_ALPHA), 'user_acceptances'); // TODO add policy name and/or timestamp to the filename?
        $this->baseurl = $baseurl;

        $this->versionids = [];
        if (count($policies) > 1) {
            foreach ($policies as $policy) {
                $this->versionids[$policy->currentversionid] = format_string($policy->name);
            }
        } else {
            $policy = reset($policies);
            $version = reset($policy->versions);
            $this->versionids[$version->id] = format_string($policy->name);
            if ($version->id != $policy->currentversionid) {
                $this->versionids[$version->id] .= '<br>' . format_string($version->revision); // TODO think about it
            }
        }

        $extrafields = get_extra_user_fields(\context_system::instance());
        $userfields = \user_picture::fields('u', $extrafields);

        $this->set_sql("$userfields",
            "{user} u",
            'u.id <> :siteguestid',
            ['siteguestid' => $CFG->siteguest]);

        if (count($this->versionids) == 1) {
            $this->configure_for_single_version();
        } else {
            $this->configure_for_multiple_versions();
        }

        $this->sortable(true);
    }

    protected function configure_for_single_version() {
        $userfieldsmod = get_all_user_name_fields(true, 'm', null, 'mod');
        $v = key($this->versionids);
        $this->sql->fields .= ", $userfieldsmod, a{$v}.status AS status{$v}, a{$v}.note, a{$v}.timemodified, a{$v}.usermodified";
        $this->sql->from .= " LEFT JOIN {tool_policy_acceptances} a{$v} ON a{$v}.userid = u.id AND a{$v}.policyversionid=:versionid{$v}
                  LEFT JOIN {user} m ON m.id = a{$v}.usermodified AND m.id <> u.id AND a{$v}.status = 1";
        $this->sql->params['versionid' . $v] = $v;

        $headers = [ // TODO strings
            'userpic' => '',
            'fullname' => '',
            'status' . $v => 'Agreed',
            'timemodified' => 'Agreed on',
            'usermodified' => 'Agreed by',
            'note' => 'Remarks'
        ];
        if ($this->is_downloading()) {
            unset($headers['userpic']);
        }
        $this->define_columns(array_keys($headers));
        $this->define_headers(array_values($headers));
        $this->no_sorting('note');
    }

    protected function configure_for_multiple_versions() {
        $headers = [ // TODO strings
            'userpic' => '',
            'fullname' => '',
            'statusall' => 'Overall',
        ];
        foreach ($this->versionids as $v => $versionname) {
            $this->sql->fields .= ", a{$v}.status AS status{$v}, a{$v}.usermodified AS usermodified{$v}"; // TODO only modified self vs somebodyelse
            $this->sql->from .= " LEFT JOIN {tool_policy_acceptances} a{$v} ON a{$v}.userid = u.id AND a{$v}.policyversionid=:versionid{$v}";
            $this->sql->params['versionid' . $v] = $v;
            $headers['status' . $v] = $versionname;
        }

        if ($this->is_downloading()) {
            unset($headers['userpic']);
        }
        $this->define_columns(array_keys($headers));
        $this->define_headers(array_values($headers));
    }

    /**
     * Download the data.
     */
    public function download() {
        \core\session\manager::write_close();
        $this->out(0, false);
        exit;
    }

    /**
     * @return string sql to add to where statement.
     */
    function get_sql_where() {
        list($where, $params) = parent::get_sql_where();
        $where = preg_replace('/firstname/', 'u.firstname', $where);
        $where = preg_replace('/lastname/', 'u.lastname', $where);
        return [$where, $params];
    }

    public function display() {
        global $OUTPUT;
        $this->out(100, true);
    }

    /**
     * Prepares column userpic for display
     * @param stdClass $row
     * @return string
     */
    public function col_userpic($row) {
        global $OUTPUT;
        $user = \user_picture::unalias($row, [], $this->useridfield);
        return $OUTPUT->user_picture($user);
    }

    public function col_usermodified($row) {
        if ($row->usermodified && $row->usermodified != $row->id) {
            return $this->username($row, 'mod', 'usermodified');
        }
        return null;
    }

    public function col_fullname($row) {
        return $this->username($row);
    }

    protected function username($row, $fieldsprefix = '', $useridfield = 'id') {
        if (!empty($row->$useridfield)) {
            $user = (object)['id' => $row->$useridfield];
            username_load_fields_from_object($user, $row, $fieldsprefix);
            $name = fullname($user);
            if ($this->is_downloading()) {
                return $name;
            }
            $profileurl = new \moodle_url('/user/profile.php', array('id' => $user->id));
            return \html_writer::link($profileurl, $name); // TODO cap view full names, cap to see profile
        }
        return null;
    }

    protected function status($versionid, $row) {
        $status = $row->{'status' . $versionid};
        $statusstr = empty($status) ? get_string('no') : get_string('yes');
        if ($this->is_downloading()) {
            return $statusstr;
        }
        if (empty($status)) {
            $pageurl = $this->baseurl;
            if ($this->currpage) {
                $pageurl = new \moodle_url($pageurl, [$this->request[TABLE_VAR_PAGE] => $this->currpage]);
            }
            $link = new \moodle_url('/admin/tool/policy/user.php',
                ['acceptforversion' => $versionid, 'userid' => $row->id,
                    'returnurl' => $pageurl->out_as_local_url(false)]);
            return \html_writer::link($link, $statusstr);
        } else {
            return $statusstr;
        }
    }

    public function col_timemodified($row) {
        if ($row->timemodified) {
            return userdate($row->timemodified); // TODO format, different for download and display
        } else {
            return null;
        }
    }

    public function col_note($row) {
        if ($this->is_downloading()) {
            return $row->note;
        } else {
            return format_text($row->note, FORMAT_MOODLE); // TODO shorten?
        }
    }

    public function col_statusall($row) {
        $totalcnt = count($this->versionids);
        $cnt = 0;
        $onbehalf = false;
        foreach ($this->versionids as $v => $unused) {
            if (!empty($row->{'status' . $v})) {
                $cnt++;
                $agreedby = $row->{'usermodified' . $v};
                if ($agreedby && $agreedby != $row->id) {
                    $onbehalf = true;
                }
            }
        }
        if ($this->is_downloading()) {
            return $cnt . " of " . $totalcnt; // TODO string
        } else {
            // TODO icon that takes into account onbehalf
            $s = ($cnt < $totalcnt) ? get_string('no') : get_string('yes');
            return $s . "<br>" . $cnt . " of " . $totalcnt; // TODO string
        }
    }

    /**
     * You can override this method in a child class. See the description of
     * build_table which calls this method.
     */
    function other_cols($column, $row) {
        if (preg_match('/^status([\d]+)$/', $column, $matches)) {
            $versionid = $matches[1];
            return $this->status($versionid, $row);
        }
        return null;
    }
}