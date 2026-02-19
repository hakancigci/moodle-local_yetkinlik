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
 * Teacher view for specific student analysis based on a selected quiz.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$userid   = optional_param('userid', 0, PARAM_INT);
$quizid   = optional_param('quizid', 0, PARAM_INT);

require_login($courseid);
$context = context_course::instance($courseid);

// Ensure the user has teacher-level report viewing capabilities.
require_capability('mod/quiz:viewreports', $context);

// Page configuration and navigation setup.
$PAGE->set_url('/local/yetkinlik/teacher_student_exam.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('studentanalysis', 'local_yetkinlik'));
$PAGE->set_heading(get_string('studentanalysis', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

// 1. Fetch Student List (to be used in selection filters).
// Only users who can attempt quizzes are considered students in this context.
$students = get_enrolled_users($context, 'mod/quiz:attempt');

// 2. Fetch Quiz List within the current course.
$quizzes = $DB->get_records('quiz', ['course' => $courseid], 'name ASC');

$rows = [];
if ($userid && $quizid) {
    // 3. Execution of normalized and secure SQL query for competency calculation.
    // This query aggregates the student's performance across competencies for a single quiz.
    $sql = "SELECT c.id, c.shortname,
                   SUM(qa.maxfraction) AS attempts,
                   SUM(qas.fraction) AS correct
            FROM {quiz_attempts} quiza
            JOIN {question_usages} qu ON qu.id = quiza.uniqueid
            JOIN {question_attempts} qa ON qa.questionusageid = qu.id
            JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
            JOIN {competency} c ON c.id = m.competencyid
            JOIN (
                SELECT MAX(fraction) AS fraction, questionattemptid
                FROM {question_attempt_steps}
                GROUP BY questionattemptid
            ) qas ON qas.questionattemptid = qa.id
            WHERE quiza.quiz = :quizid
              AND quiza.userid = :userid
              AND quiza.state = 'finished'
            GROUP BY c.id, c.shortname
            ORDER BY c.shortname ASC";

    $rows = $DB->get_records_sql($sql, ['quizid' => $quizid, 'userid' => $userid]);
}

// 4. Page Output Generation.
echo $OUTPUT->header();

$renderdata = new stdClass();
$renderdata->courseid = $courseid;
$renderdata->userid   = $userid;
$renderdata->quizid   = $quizid;
$renderdata->students = $students;
$renderdata->quizzes  = $quizzes;
$renderdata->rows     = $rows;

// Pass data to the renderable output class.
$page = new \local_yetkinlik\output\teacher_student_exam_page($renderdata);
echo $OUTPUT->render($page);

echo $OUTPUT->footer();
