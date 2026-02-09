<?php
namespace local_yetkinlik\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use context_course;

class save_mapping extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'questionid' => new external_value(PARAM_INT, 'Question ID'),
            'competencyid' => new external_value(PARAM_INT, 'Competency ID')
        ]);
    }

    public static function execute($courseid, $questionid, $competencyid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'questionid' => $questionid,
            'competencyid' => $competencyid
        ]);

        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('moodle/question:editall', $context);

        $existing = $DB->get_record('local_yetkinlik_qmap', [
            'courseid' => $params['courseid'],
            'questionid' => $params['questionid']
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
                'competencyid' => $params['competencyid']
            ]);
        }

        return ['status' => 'ok'];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Operation status')
        ]);
    }
}