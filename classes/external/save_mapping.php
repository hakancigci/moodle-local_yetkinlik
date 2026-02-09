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
 * External API class for competency mapping operations.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_yetkinlik\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use context_course;

/**
 * External API class for save_mapping.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_mapping extends external_api {

    /**
     * Describes the parameters for execute.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'questionid' => new external_value(PARAM_INT, 'Question ID'),
            'competencyid' => new external_value(PARAM_INT, 'Competency ID'),
        ]);
    }

    /**
     * Save competency mapping.
     *
     * @param int $courseid
     * @param int $questionid
     * @param int $competencyid
     * @return array
     */
    public static function execute($courseid, $questionid, $competencyid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'questionid' => $questionid,
            'competencyid' => $competencyid,
        ]);

        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/question:editall', $context);

        $existing = $DB->get_record('local_yetkinlik_qmap', [
            'courseid' => $params['courseid'],
            'questionid' => $params['questionid'],
        ]);

        if ($params['competencyid'] == 0) {
            if ($existing) {
                $DB->delete_records('local_yetkinlik_qmap', ['id' => $existing->id]);
            }
            return ['status' => 'deleted'];
        }

        if ($existing) {
            $existing->competencyid = $params['competencyid'];
            $DB->update_record('local_yetkinlik_qmap', $existing);
        } else {
            $DB->insert_record('local_yetkinlik_qmap', [
                'courseid' => $params['courseid'],
                'questionid' => $params['questionid'],
                'competencyid' => $params['competencyid'],
            ]);
        }

        return ['status' => 'ok'];
    }

    /**
     * Describes the execute return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Operation status'),
        ]);
    }
}
