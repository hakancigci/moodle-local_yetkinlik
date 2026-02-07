<?php
namespace local_yetkinlik\external;

defined('MOODLE_INTERNAL') || die();

// Moodle 5.0 ve modern sürümler için doğru namespace kullanımları
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;

/**
 * local_yetkinlik mapping save external API.
 */
class save_mapping extends external_api {

    /**
     * Parametre tanımlamaları.
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid'     => new external_value(PARAM_INT, 'Course ID', VALUE_REQUIRED),
            'questionid'   => new external_value(PARAM_INT, 'Question ID', VALUE_REQUIRED),
            'competencyid' => new external_value(PARAM_INT, 'Competency ID', VALUE_REQUIRED),
        ]);
    }

    /**
     * Mapping işlemini gerçekleştirir.
     *
     * @param int $courseid
     * @param int $questionid
     * @param int $competencyid
     * @return array
     */
    public static function execute($courseid, $questionid, $competencyid) {
        global $DB;

        // Parametre validasyonu
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid'     => $courseid,
            'questionid'   => $questionid,
            'competencyid' => $competencyid,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        
        // Yetki kontrolü (Örn: Soruları düzenleme yetkisi)
        require_capability('moodle/question:editall', $context);

        $existing = $DB->get_record('local_yetkinlik_qmap', [
            'courseid'   => $params['courseid'],
            'questionid' => $params['questionid']
        ]);

        if ((int)$params['competencyid'] === 0) {
            if ($existing) {
                $DB->delete_records('local_yetkinlik_qmap', ['id' => $existing->id]);
            }
            return ['status' => 'deleted'];
        }

        if ($existing) {
            $existing->competencyid = $params['competencyid'];
            $existing->timemodified = time(); // Varsa timemodified alanı iyi olur
            $DB->update_record('local_yetkinlik_qmap', $existing);
        } else {
            $newrecord = new \stdClass();
            $newrecord->courseid     = $params['courseid'];
            $newrecord->questionid   = $params['questionid'];
            $newrecord->competencyid = $params['competencyid'];
            $newrecord->timecreated  = time();
            
            $DB->insert_record('local_yetkinlik_qmap', $newrecord);
        }

        return ['status' => 'ok'];
    }

    /**
     * Geri dönüş yapısı tanımlaması.
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Result status')
        ]);
    }
}