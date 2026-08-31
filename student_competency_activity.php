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
 * Student view: Self-specific competency performance for quizzes and related activities.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

$courseid = required_param('courseid', PARAM_INT);
$competencyid = optional_param('competencyid', 0, PARAM_INT);

require_login($courseid);
$context = context_course::instance($courseid);

// Students don't have teacher capabilities, they view their own data.
$userid = $USER->id;

$PAGE->set_url('/local/yetkinlik/student_competency_activity.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('studentcompetencyactivity', 'local_yetkinlik'));
$PAGE->set_heading(get_string('studentcompetencyactivity', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

// 1. Data Preparation: Fetch competencies mapped to this course and questions/activities.
$competencies = $DB->get_records_sql("
    SELECT DISTINCT c.id, c.shortname
    FROM {qbank_yetkinlik_qmap} m
    JOIN {competency} c ON c.id = m.competencyid
    JOIN {competency_coursecomp} cc ON cc.competencyid = c.id
    WHERE cc.courseid = :courseid
    ORDER BY c.shortname", [
        'courseid' => $courseid
    ]);

$compoptions = [0 => get_string('selectcompetency', 'local_yetkinlik')];
foreach ($competencies as $c) {
    $compoptions[$c->id] = $c->shortname;
}

/**
 * Filter form for student competency selection.
 */
class local_yetkinlik_student_activity_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement(
            'autocomplete',
            'competencyid',
            get_string('selectcompetency', 'local_yetkinlik'),
            $this->_customdata['compoptions']
        );

        $this->add_action_buttons(false, get_string('show', 'local_yetkinlik'));
    }
}

$mform = new local_yetkinlik_student_activity_form(null, ['compoptions' => $compoptions]);
$mform->set_data(['courseid' => $courseid, 'competencyid' => $competencyid]);

if ($frmdata = $mform->get_data()) {
    $competencyid = $frmdata->competencyid;
}

// 3. Report Data Fetching for the logged-in student.
$renderdata = new stdClass();
$renderdata->userid = $userid;
$renderdata->competencyid = $competencyid;
$renderdata->competencies = $competencies;
$renderdata->rows = [];
$renderdata->total = null;
$renderdata->activityrows = [];
$renderdata->activitytotal = null;
$renderdata->overallsummary = null;
$renderdata->has_activityrows = false;

if ($competencyid) {
    if ($comp = $DB->get_record('competency', ['id' => $competencyid])) {
        $renderdata->description = format_text($comp->description, $comp->descriptionformat);
    }

    $quiztrate = null;

    // 3a. Quiz-based Question Success Analysis for the student
    $sqlsummary = "SELECT quiz.id AS quizid, quiz.name AS quizname, MAX(quiza.id) as lastattemptid,
                        SUM(qa.maxfraction) AS questions, SUM(qas.fraction) AS correct
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
                     AND quiza.state = 'finished'
                   GROUP BY quiz.id, quiz.name";

    $summaryrows = $DB->get_records_sql(
        $sqlsummary,
        ['competencyid' => $competencyid, 'userid' => $userid, 'courseid' => $courseid]
    );

    $tq = 0;
    $tc = 0;
    foreach ($summaryrows as $r) {
        $rate = $r->questions ? number_format(($r->correct / $r->questions) * 100, 1) : 0;
        $renderdata->rows[] = [
            'quizname'  => $r->quizname,
            'quizurl'   => (new moodle_url('/mod/quiz/review.php', ['attempt' => $r->lastattemptid]))->out(false),
            'questions' => (float) $r->questions,
            'correct'   => number_format($r->correct, 1),
            'rate'      => $rate,
            'color'     => ($rate >= 80) ? 'green' : (($rate >= 40) ? 'orange' : 'red'),
        ];
        $tq += $r->questions;
        $tc += $r->correct;
    }

    if ($tq > 0) {
        $quiztrate = ($tc / $tq) * 100;
        $renderdata->total = [
            'questions' => $tq,
            'correct'   => number_format($tc, 1),
            'rate'      => number_format($quiztrate, 1),
            'color'     => ($quiztrate >= 80) ? 'green' : 'red',
        ];
    }

    // 3b. Fetching competency-mapped module activities and grades
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
    $weightedsum = 0;
    $totalweightsused = 0;

    // Sınav grubu ağırlığını veritabanından çekelim
    $quizweightrec = $DB->get_record('local_yetkinlik_weights', [
        'courseid' => $courseid, 'competencyid' => $competencyid, 'itemtype' => 'quiz_questions'
    ]);

    if (!is_null($quiztrate) && $quizweightrec && !$quizweightrec->excluded && $quizweightrec->weight > 0) {
        $weightedsum += ($quiztrate * ($quizweightrec->weight / 100));
        $totalweightsused += $quizweightrec->weight;
    }

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

        // Fetching grades from grade_items and grade_grades tables
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

        $actrate = '-';
        $actnumrate = null;
        $color = 'black';

        if ($gradegrd && !is_null($gradegrd->finalgrade) && $gradegrd->grademax > 0) {
            $actnumrate = ($gradegrd->finalgrade / $gradegrd->grademax) * 100;
            $actrate = number_format($actnumrate, 1);
            $color = ($actnumrate >= 80) ? 'green' : (($actnumrate >= 40) ? 'orange' : 'red');
            $activityrates[] = $actnumrate;

            // Etkinlik ağırlığını kontrol et
            $modweightrec = $DB->get_record('local_yetkinlik_weights', [
                'courseid' => $courseid, 'competencyid' => $competencyid, 'itemtype' => $ar->modname, 'itemid' => $ar->instance
            ]);

            if ($modweightrec && !$modweightrec->excluded && $modweightrec->weight > 0) {
                $weightedsum += ($actnumrate * ($modweightrec->weight / 100));
                $totalweightsused += $modweightrec->weight;
            }
        }

        $renderdata->activityrows[] = [
            'activityname' => $modulename,
            'rate'         => $actrate,
            'color'        => $color,
            'url'          => (new moodle_url('/mod/' . $ar->modname . '/view.php', ['id' => $ar->cmid]))->out(false),
        ];
    }

    if (!empty($renderdata->activityrows)) {
        $renderdata->has_activityrows = true;
    }

    // Activities average calculation (bilgi amaçlı)
    $activitytrate = null;
    if (!empty($activityrates)) {
        $activitytrate = array_sum($activityrates) / count($activityrates);
        $renderdata->activitytotal = [
            'rate'  => number_format($activitytrate, 1),
            'color' => ($activitytrate >= 80) ? 'green' : (($activitytrate >= 40) ? 'orange' : 'red'),
        ];
    }

    // 3c. Ağırlıklı Genel Özet Hesabı (Öğrenci Paneli)
    if ($totalweightsused > 0) {
        $overallrate = $weightedsum;
        $renderdata->overallsummary = [
            'rate'  => number_format($overallrate, 1),
            'color' => ($overallrate >= 80) ? 'green' : (($overallrate >= 40) ? 'orange' : 'red'),
        ];
    }
}

echo $OUTPUT->header();
$page = new \local_yetkinlik\output\student_competency_activity_page($renderdata, $mform);
echo $OUTPUT->render($page);
echo $OUTPUT->footer();
