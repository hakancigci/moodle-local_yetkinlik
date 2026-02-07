<?php
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