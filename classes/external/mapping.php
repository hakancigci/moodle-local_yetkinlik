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

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use context_course;
use stdClass;

/**
 * mapping external API.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mapping extends external_api {

    /**
     * Describes the parameters for save_mapping.
     *
     * @return external_function_parameters
     */
    public static function save_mapping_parameters() {
        return new external_function_parameters([
            'courseid'     => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
            'questionid'   => new external_value(PARAM_INT, 'Question ID', VALUE_REQUIRED),
            'competencyid' => new external_value(PARAM_INT, 'Competency ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * Save or update the competency mapping for a specific question.
     *
     * @param int $courseid
     * @param int $questionid
     * @param int $competencyid
     * @return array
     */
    public static function save_mapping($courseid, $questionid, $competencyid) {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::save_mapping_parameters(), [
            'courseid'     => $courseid,
            'questionid'   => $questionid,
            'competencyid' => $competencyid,
        ]);

        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/question:editall', $context);

        $existing = $DB->get_record('local_yetkinlik_qmap', [
            'courseid'   => $params['courseid'],
            'questionid' => $params['questionid']
        ]);

        // If competencyid is 0, delete the record.
        if ((int) $params['competencyid'] === 0) {
            if ($existing) {
                $DB->delete_records('local_yetkinlik_qmap', ['id' => $existing->id]);
            }
            return ['status' => 'deleted'];
        }

        if ($existing) {
            $existing->competencyid = $params['competencyid'];
            $DB->update_record('local_yetkinlik_qmap', $existing);
        } else {
            $record = new stdClass();
            $record->courseid     = $params['courseid'];
            $record->questionid   = $params['questionid'];
            $record->competencyid = $params['competencyid'];
            $record->timecreated  = time();
            
            $DB->insert_record('local_yetkinlik_qmap', $record);
        }

        return ['status' => 'ok'];
    }

    /**
     * Describes the save_mapping return value.
     *
     * @return external_single_structure
     */
    public static function save_mapping_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Result status')
        ]);
    }
}
