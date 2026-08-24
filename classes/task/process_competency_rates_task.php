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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Class Report for Competency Matching.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_yetkinlik\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Class process_competency_rates_task
 *
 * @package    local_yetkinlik
 */
class process_competency_rates_task extends \core\task\adhoc_task {

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        if (empty($data->courseid) || empty($data->adminid)) {
            mtrace("Eksik veri: courseid veya adminid bulunamadı.");
            return;
        }

        $courseid = (int)$data->courseid;
        $adminid = (int)$data->adminid;
        $context = \context_course::instance($courseid);
        $contextid = $context->id;

        $sql = "SELECT DISTINCT c.id, c.shortname, c.competencyframeworkid
                  FROM {qbank_yetkinlik_qmap} m
                  JOIN {competency} c ON c.id = m.competencyid
              ORDER BY c.shortname";
        $competencies = $DB->get_records_sql($sql);
        if (!$competencies) {
            mtrace("Eşleşen yetkinlik bulunamadı.");
            return;
        }

        $students = $DB->get_records('user', ['deleted' => 0, 'suspended' => 0]);
        if (!$students) {
            mtrace("Öğrenci bulunamadı.");
            return;
        }

        foreach ($students as $student) {
            $studentid = (int)$student->id;

            foreach ($competencies as $c) {
                $competencyid = (int)$c->id;
                $rate = $this->get_user_competency_rate($studentid, $competencyid, $courseid);

                if ($rate === null) {
                    continue;
                }

                $grade = 1;
                $isproficient = false;

                $framework = $DB->get_record('competency_framework', ['id' => (int)$c->competencyframeworkid], 'scaleid, scaleconfiguration');
                if ($framework && !empty($framework->scaleid)) {
                    $scaleid = (int)$framework->scaleid;
                    $sqlmap = "SELECT * FROM {local_yetkinlik_scale_map}
                               WHERE scaleid = :scaleid 
                                 AND :rate1 >= minpercent 
                                 AND :rate2 < maxpercent
                               LIMIT 1";
                    
                    $mapping = $DB->get_record_sql($sqlmap, [
                        'scaleid' => $scaleid,
                        'rate1'   => $rate,
                        'rate2'   => $rate
                    ]);

                    if (!$mapping && $rate >= 100) {
                        $sqlmap100 = "SELECT * FROM {local_yetkinlik_scale_map}
                                      WHERE scaleid = :scaleid 
                                    ORDER BY scaleitemindex DESC 
                                    LIMIT 1";
                        $mapping = $DB->get_record_sql($sqlmap100, ['scaleid' => $scaleid]);
                    }

                    if ($mapping) {
                        $grade = (int)$mapping->scaleitemindex;
                    }

                    // Ölçek yapılandırmasındaki JSON verisini çözüp doğru proficient değerini okuma
                    if (!empty($framework->scaleconfiguration)) {
                        $configarr = json_decode($framework->scaleconfiguration, true);
                        if (is_array($configarr)) {
                            foreach ($configarr as $conf) {
                                if (isset($conf['id']) && (int)$conf['id'] === $grade) {
                                    $isproficient = !empty($conf['proficient']);
                                    break;
                                }
                            }
                        }
                    }
                }

                $proficiencyval = $isproficient ? 1 : 0;

                // Kurs Bazlı Yetkinlik Tablosu Güncellemesi ({competency_usercompcourse})
                $usercompcourse = $DB->get_record('competency_usercompcourse', ['courseid' => $courseid, 'userid' => $studentid, 'competencyid' => $competencyid]);
                if (!$usercompcourse) {
                    $usercompcourse = new \stdClass();
                    $usercompcourse->courseid = $courseid;
                    $usercompcourse->userid = $studentid;
                    $usercompcourse->competencyid = $competencyid;
                    $usercompcourse->grade = $grade;
                    $usercompcourse->proficiency = $proficiencyval;
                    $usercompcourse->timecreated = time();
                    $usercompcourse->timemodified = time();
                    $usercompcourse->usermodified = $adminid;
                    $DB->insert_record('competency_usercompcourse', $usercompcourse);
                } else {
                    $usercompcourse->grade = $grade;
                    $usercompcourse->proficiency = $proficiencyval;
                    $usercompcourse->timemodified = time();
                    $usercompcourse->usermodified = $adminid;
                    $DB->update_record('competency_usercompcourse', $usercompcourse);
                }

                // Genel Yetkinlik Tablosu Güncellemesi ({competency_usercomp})
                $uc = $DB->get_record('competency_usercomp', ['userid' => $studentid, 'competencyid' => $competencyid]);
                if (!$uc) {
                    $uc = new \stdClass();
                    $uc->userid = $studentid;
                    $uc->competencyid = $competencyid;
                    $uc->grade = $grade;
                    $uc->proficiency = $proficiencyval;
                    $uc->timecreated = time();
                    $uc->timemodified = time();
                    $uc->usermodified = $adminid;
                    $uc->id = $DB->insert_record('competency_usercomp', $uc);
                } else {
                    $uc->grade = $grade;
                    $uc->proficiency = $proficiencyval;
                    $uc->timemodified = time();
                    $uc->usermodified = $adminid;
                    $DB->update_record('competency_usercomp', $uc);
                }

                // Kanıt ve Detayların Kaydedilmesi
                $ratestr = number_format($rate, 1);
                $a = new \stdClass();
                $a->competency = $c->shortname;
                $a->rate = $ratestr;

                $evidence = new \stdClass();
                $evidence->userid = $studentid;
                $evidence->name = get_string('process_success_title', 'local_yetkinlik') . " (" . date('d.m.Y') . ")";
                $evidence->description = get_string('evidence_description', 'local_yetkinlik', $a);
                $evidence->descriptionformat = FORMAT_HTML;
                $evidence->url = '';
                $evidence->timecreated = time();
                $evidence->timemodified = time();
                $evidence->usermodified = $adminid;
                $evidenceid = $DB->insert_record('competency_userevidence', $evidence);

                $link = new \stdClass();
                $link->userevidenceid = (int)$evidenceid;
                $link->competencyid = $competencyid;
                $link->timecreated = time();
                $link->timemodified = time();
                $link->usermodified = $adminid;
                $DB->insert_record('competency_userevidencecomp', $link);

                $cevidence = new \stdClass();
                $cevidence->usercompetencyid = (int)$uc->id;
                $cevidence->contextid = $contextid;
                $cevidence->action = 1;
                $cevidence->actionuserid = $adminid;
                $cevidence->descidentifier = 'evidence';
                $cevidence->desccomponent = 'local_yetkinlik';
                $cevidence->desca = null;
                $cevidence->url = '';
                $cevidence->grade = (int)$rate;
                $cevidence->note = get_string('evidence_note', 'local_yetkinlik', $a);
                $cevidence->timecreated = time();
                $cevidence->timemodified = time();
                $cevidence->usermodified = $adminid;
                $DB->insert_record('competency_evidence', $cevidence);
            }
        }
    }

    /**
     * Calculate user competency rate based on quiz attempts.
     */
    private function get_user_competency_rate($userid, $competencyid, $courseid) {
        global $DB;
        $sql = "SELECT CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS questions,
                       CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
                  FROM {quiz_attempts} quiza
                  JOIN {question_usages} qu ON qu.id = quiza.uniqueid
                  JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                  JOIN {quiz} quiz ON quiz.id = quiza.quiz
                  JOIN {qbank_yetkinlik_qmap} m ON m.questionid = qa.questionid
                  JOIN (
                       SELECT MAX(fraction) AS fraction, questionattemptid
                         FROM {question_attempt_steps}
                     GROUP BY questionattemptid
                  ) qas ON qas.questionattemptid = qa.id
             WHERE quiz.course = :courseid
               AND quiza.userid = :userid
               AND m.competencyid = :competencyid";

        $row = $DB->get_record_sql($sql, ['courseid' => $courseid, 'userid' => $userid, 'competencyid' => $competencyid]);
        if ($row && $row->questions > 0) {
            return ($row->correct / $row->questions) * 100;
        }
        return null;
    }
}
