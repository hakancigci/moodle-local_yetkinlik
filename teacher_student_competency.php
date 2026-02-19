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
 * Teacher view: Student-specific competency performance report.
 * Provides filters for selecting students and competencies to view detailed quiz results.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

$courseid = required_param('courseid', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$competencyid = optional_param('competencyid', 0, PARAM_INT);

require_login($courseid);
$context = context_course::instance($courseid);

// Check for quiz report viewing capability (Teacher or Manager roles usually).
require_capability('mod/quiz:viewreports', $context);

// Page definitions and navigation setup.
$PAGE->set_url('/local/yetkinlik/teacher_student_competency.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('teacherstudentcompetency', 'local_yetkinlik'));
$PAGE->set_heading(get_string('teacherstudentcompetency', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');
$PAGE->set_context($context);

// 1. Data Preparation (Dropdown / Form Options).
// Get all enrolled users to populate the student selection filter.
$students = get_enrolled_users($context);
$studentoptions = [0 => get_string('selectstudent', 'local_yetkinlik')];
foreach ($students as $s) {
    $studentoptions[$s->id] = fullname($s);
}

// Get competencies that have been mapped to questions.
$competencies = $DB->get_records_sql("
    SELECT DISTINCT c.id, c.shortname
    FROM {qbank_yetkinlik_qmap} m
    JOIN {competency} c ON c.id = m.competencyid
    ORDER BY c.shortname");

$compoptions = [0 => get_string('selectcompetency', 'local_yetkinlik')];
foreach ($competencies as $c) {
    $compoptions[$c->id] = $c->shortname;
}

/**
 * Filter form for student and competency selection.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_yetkinlik_teacher_form extends moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        // Using autocomplete for better UX with large student/competency lists.
        $mform->addElement('autocomplete', 'userid',
            get_string('selectstudent', 'local_yetkinlik'),
            $this->_customdata['studentoptions']);

        $mform->addElement('autocomplete', 'competencyid',
            get_string('selectcompetency', 'local_yetkinlik'),
            $this->_customdata['compoptions']);

        $this->add_action_buttons(false, get_string('show', 'local_yetkinlik'));
    }
}

$mform = new local_yetkinlik_teacher_form(null, [
    'studentoptions' => $studentoptions,
    'compoptions'    => $compoptions,
]);
$mform->set_data(['courseid' => $courseid, 'userid' => $userid, 'competencyid' => $competencyid]);

// Handle form submission.
if ($frmdata = $mform->get_data()) {
    $userid = $frmdata->userid;
    $competencyid = $frmdata->competencyid;
}

// 3. Report Data Fetching (SQL).
$renderdata = new stdClass();
$renderdata->userid = $userid;
$renderdata->competencyid = $competencyid;
$renderdata->rows = [];

if ($userid && $competencyid) {
    // Fetch quiz-based performance data for the selected student and competency.
    $sql = "SELECT quiz.id AS quizid, quiz.name AS quizname,
                   SUM(qa.maxfraction) AS questions, SUM(qas.fraction) AS correct
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
            WHERE m.competencyid = :competencyid
              AND quiza.userid = :userid
              AND quiz.course = :courseid
              AND quiza.state = 'finished'
            GROUP BY quiz.id, quiz.name
            ORDER BY quiz.name";

    $rows = $DB->get_records_sql($sql, [
        'competencyid' => $competencyid,
        'userid' => $userid,
        'courseid' => $courseid,
    ]);

    $tq = 0;
    $tc = 0;
    foreach ($rows as $r) {
        $rate = $r->questions ? number_format(($r->correct / $r->questions) * 100, 1) : 0;

        // Find the last finished attempt to generate a review link.
        $lastattempt = $DB->get_record_sql("
            SELECT id FROM {quiz_attempts}
            WHERE quiz = :quizid AND userid = :userid AND state = 'finished'
            ORDER BY attempt DESC",
            ['quizid' => $r->quizid, 'userid' => $userid], IGNORE_MULTIPLE);

        $renderdata->rows[] = [
            'quizname'   => $r->quizname,
            'questions'  => (float)$r->questions,
            'correct'    => (float)$r->correct,
            'rate'       => $rate,
            'color'      => ($rate >= 80) ? 'green' : (($rate >= 60) ? 'blue' : (($rate >= 40) ? 'orange' : 'red')),
            'review_url' => $lastattempt ?
                (new moodle_url('/mod/quiz/review.php', ['attempt' => $lastattempt->id]))->out(false) : null,
        ];
        $tq += $r->questions;
        $tc += $r->correct;
    }

    // Calculate aggregated totals for the selected student/competency.
    if ($tq > 0) {
        $trate = number_format(($tc / $tq) * 100, 1);
        $renderdata->total = [
            'questions' => $tq,
            'correct' => $tc,
            'rate' => $trate,
            'color' => ($trate >= 80) ? 'green' : (($trate >= 60) ? 'blue' : (($trate >= 40) ? 'orange' : 'red')),
        ];
    }
}

// 4. Output Generation.
echo $OUTPUT->header();

// Render the page using the corresponding output class.
$page = new \local_yetkinlik\output\teacher_student_competency_page($renderdata, $mform);
echo $OUTPUT->render($page);

echo $OUTPUT->footer();
