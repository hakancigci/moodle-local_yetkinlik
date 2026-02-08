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
 * Report for competency.
 *
 * @package   local_yetkinlik
 * @copyright 2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later*/

namespace local_yetkinlik\external;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

class mapping extends external_api {

    public static function save_mapping_parameters() {
        return new external_function_parameters([
            'courseid'     => new external_value(PARAM_INT, 'Course ID'),
            'questionid'   => new external_value(PARAM_INT, 'Question ID'),
            'competencyid' => new external_value(PARAM_INT, 'Competency ID'),
        ]);
    }

    public static function save_mapping($courseid, $questionid, $competencyid) {
        global $DB;

        $context = \context_course::instance($courseid);
        self::validate_context($context);
        require_capability('moodle/question:editall', $context);

        $existing = $DB->get_record('local_yetkinlik_qmap', [
            'courseid'   => $courseid,
            'questionid' => $questionid
        ]);

        if ((int)$competencyid === 0) {
            if ($existing) {
                $DB->delete_records('local_yetkinlik_qmap', ['id' => $existing->id]);
            }
            return ['status' => 'deleted'];
        }

        if ($existing) {
            $existing->competencyid = $competencyid;
            $DB->update_record('local_yetkinlik_qmap', $existing);
        } else {
            $DB->insert_record('local_yetkinlik_qmap', [
                'courseid'     => $courseid,
                'questionid'   => $questionid,
                'competencyid' => $competencyid,
                'timecreated'  => time()
            ]);
        }

        return ['status' => 'ok'];
    }

    public static function save_mapping_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Result')
        ]);
    }
}
