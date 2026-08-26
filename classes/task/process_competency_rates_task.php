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
            mtrace("Missing data: courseid or adminid could not be found.");
            return;
        }

        $courseid = (int)$data->courseid;
        $adminid = (int)$data->adminid;
        $evaluationmode = $data->evaluationmode ?? 'score_by_question';
        $context = \context_course::instance($courseid);
        $contextid = $context->id;

        $sql = "SELECT DISTINCT c.id, c.shortname, c.competencyframeworkid
                  FROM {qbank_yetkinlik_qmap} m
                  JOIN {competency} c ON c.id = m.competencyid
              ORDER BY c.shortname";
        $competencies = $DB->get_records_sql($sql);
        if (!$competencies) {
            mtrace("No matching competencies found.");
            return;
        }

        $students = $DB->get_records('user', ['deleted' => 0, 'suspended' => 0]);
        if (!$students) {
            mtrace("No students found.");
            return;
        }

        foreach ($students as $student) {
            $studentid = (int)$student->id;

            foreach ($competencies as $c) {
                $competencyid = (int)$c->id;
                
                // Fetch user competency rate and detailed metrics
                $result = $this->get_user_competency_rate_and_details($studentid, $competencyid, $courseid, $evaluationmode);

                if ($result === null || $result['rate'] === null) {
                    continue;
                }

                $rate = $result['rate'];
                $gradedetails = $result['details'];

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

                if ($evaluationmode !== 'only_evidence') {
                    // Update course-based competency table ({competency_usercompcourse})
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

                    // Update general competency table ({competency_usercomp})
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
                } else {
                    $uc = $DB->get_record('competency_usercomp', ['userid' => $studentid, 'competencyid' => $competencyid]);
                }

                // Prepare evidence description variables
                $ratestr = number_format($rate, 1);
                
                $quizratestr = $gradedetails['quizrate'] !== null ? '%' . number_format($gradedetails['quizrate'], 1) : '-';
                
                $activityparts = [];
                if (!empty($gradedetails['activitylist'])) {
                    foreach ($gradedetails['activitylist'] as $actname => $actval) {
                        $activityparts[] = "{$actname} %" . number_format($actval, 1);
                    }
                }
                $activitydetailsstr = !empty($activityparts) ? implode(', ', $activityparts) : '-';

                // Fetch strings from language pack
                $lblcompetency = get_string('evidence_lbl_competency', 'local_yetkinlik');
                $lblquizrate   = get_string('evidence_lbl_quizrate', 'local_yetkinlik');
                $lblactivities = get_string('evidence_lbl_activities', 'local_yetkinlik');
                $lblaverage    = get_string('evidence_lbl_average', 'local_yetkinlik');

                // Constructing the detailed HTML output using localized strings
                $descriptionhtml = "{$lblcompetency}: {$c->shortname}<br>" .
                                   "{$lblquizrate}: {$quizratestr}";
                
                if (!empty($activityparts)) {
                    $descriptionhtml .= ", {$lblactivities}: {$activitydetailsstr}";
                }

                $descriptionhtml .= "<br><strong>{$lblaverage}: %{$ratestr}</strong>";

                // Create Evidence Record ({competency_userevidence})
                $evidence = new \stdClass();
                $evidence->userid = $studentid;
                $evidence->name = get_string('process_success_title', 'local_yetkinlik') . " (" . date('d.m.Y') . ")";
                $evidence->description = $descriptionhtml;
                $evidence->descriptionformat = FORMAT_HTML;
                $evidence->url = '';
                $evidence->timecreated = time();
                $evidence->timemodified = time();
                $evidence->usermodified = $adminid;
                $evidenceid = $DB->insert_record('competency_userevidence', $evidence);

                // Link evidence to competency ({competency_userevidencecomp})
                $link = new \stdClass();
                $link->userevidenceid = (int)$evidenceid;
                $link->competencyid = $competencyid;
                $link->timecreated = time();
                $link->timemodified = time();
                $link->usermodified = $adminid;
                $DB->insert_record('competency_userevidencecomp', $link);

                if ($uc) {
                    $cevidence = new \stdClass();
                    $cevidence->usercompetencyid = (int)$uc->id;
                    $cevidence->contextid = $contextid;
                    $cevidence->action = 1;
                    $cevidence->actionuserid = $adminid;
                    $cevidence->descidentifier = 'custom';
                    $cevidence->desccomponent = 'local_yetkinlik';
                    $cevidence->desca = '';
                    $cevidence->url = '';
                    $cevidence->grade = (int)$rate;
                    
                    // Writing stripped HTML into the longtext note column
                    $cevidence->note = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $descriptionhtml));
                    
                    $cevidence->timecreated = time();
                    $cevidence->timemodified = time();
                    $cevidence->usermodified = $adminid;
                    $DB->insert_record('competency_evidence', $cevidence);
                }
            }
        }
    }

    /**
     * Calculate user competency rate and collect individual activity/quiz details.
     *
     * @param int $userid
     * @param int $competencyid
     * @param int $courseid
     * @param string $evaluationmode
     * @return array|null
     */
    private function get_user_competency_rate_and_details($userid, $competencyid, $courseid, $evaluationmode) {
        global $DB;

        // 1. Calculate Quiz Question Success Rate
        $sqlsummary = "SELECT SUM(qa.maxfraction) AS questions, SUM(qas.fraction) AS correct
                         FROM {quiz_attempts} quiza
                         JOIN {quiz} quiz ON quiz.id = quiza.quiz
                         JOIN {question_usages} qu ON qu.id = quiza.uniqueid
                         JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                         JOIN {qbank_yetkinlik_qmap} map ON map.questionid = qa.questionid
                         JOIN (
                              SELECT MAX(fraction) AS fraction, questionattemptid
                                FROM {question_attempt_steps}
                            GROUP BY questionattemptid
                         ) qas ON qas.questionattemptid = qa.id
                        WHERE map.competencyid = :competencyid
                          AND quiza.userid = :userid
                          AND quiz.course = :courseid
                          AND quiza.state = 'finished'";

        $row = $DB->get_record_sql($sqlsummary, ['competencyid' => $competencyid, 'userid' => $userid, 'courseid' => $courseid]);
        
        $quiztrate = null;
        if ($row && $row->questions > 0) {
            $quiztrate = ($row->correct / $row->questions) * 100;
        }

        if ($evaluationmode === 'score_by_question') {
            return [
                'rate' => $quiztrate,
                'details' => [
                    'quizrate' => $quiztrate,
                    'activityrate' => null,
                    'activitylist' => []
                ]
            ];
        }

        // 2. Fetch other activities matched via competency_modulecomp
        $sqlactivities = "SELECT cm.id AS cmid, m.name AS modname, cm.instance
                            FROM {competency_modulecomp} mc
                            JOIN {course_modules} cm ON cm.id = mc.cmid
                            JOIN {modules} m ON m.id = cm.module
                           WHERE mc.competencyid = :competencyid
                             AND cm.course = :courseid";

        $rawactivities = $DB->get_records_sql($sqlactivities, [
            'competencyid' => $competencyid,
            'courseid'     => $courseid
        ]);

        $activityrates = [];
        $activitylist = [];
        foreach ($rawactivities as $ar) {
            $modulename = ucfirst($ar->modname);
            if ($DB->get_manager()->table_exists($ar->modname)) {
                if ($modrec = $DB->get_record($ar->modname, ['id' => $ar->instance])) {
                    if (isset($modrec->name)) {
                        $modulename = $modrec->name;
                    } else if (isset($modrec->title)) {
                        $modulename = $modrec->title;
                    }
                }
            }

            $gradegrd = $DB->get_record_sql("
                SELECT gg.finalgrade, gi.grademax
                  FROM {grade_items} gi
                  JOIN {grade_grades} gg ON gg.itemid = gi.id
                 WHERE gi.courseid = :courseid
                   AND gi.itemtype = 'mod'
                   AND gi.itemmodule = :modname
                   AND gi.iteminstance = :instance
                   AND gg.userid = :userid
            ", [
                'courseid' => $courseid,
                'modname'  => $ar->modname,
                'instance' => $ar->instance,
                'userid'   => $userid
            ]);

            if ($gradegrd && !is_null($gradegrd->finalgrade) && $gradegrd->grademax > 0) {
                $actnumrate = ($gradegrd->finalgrade / $gradegrd->grademax) * 100;
                $activityrates[] = $actnumrate;
                $activitylist[$modulename] = $actnumrate;
            }
        }

        $activitytrate = null;
        if (!empty($activityrates)) {
            $activitytrate = array_sum($activityrates) / count($activityrates);
        }

        if ($evaluationmode === 'only_evidence') {
            if ($quiztrate !== null || $activitytrate !== null) {
                $fallbackrate = $quiztrate !== null ? $quiztrate : $activitytrate;
                return [
                    'rate' => $fallbackrate,
                    'details' => [
                        'quizrate' => $quiztrate,
                        'activityrate' => $activitytrate,
                        'activitylist' => $activitylist
                    ]
                ];
            }
            return null;
        }

        // 3. Calculation for 'score_by_average' Mode
        $allrates = [];
        if (!is_null($quiztrate)) {
            $allrates[] = $quiztrate;
        }
        if (!is_null($activitytrate)) {
            $allrates[] = $activitytrate;
        }

        if (!empty($allrates)) {
            $overallrate = array_sum($allrates) / count($allrates);
            return [
                'rate' => $overallrate,
                'details' => [
                    'quizrate' => $quiztrate,
                    'activityrate' => $activitytrate,
                    'activitylist' => $activitylist
                ]
            ];
        }

        return null;
    }
}
