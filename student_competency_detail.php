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
 * Detailed competency report for a specific student.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$userid   = optional_param('userid', $USER->id, PARAM_INT);

require_login($courseid);

global $DB, $USER, $OUTPUT, $PAGE;

$context = context_course::instance($courseid);

// Check if the user is allowed to see this report.
if ($userid != $USER->id) {
    require_capability('mod/quiz:viewreports', $context);
}

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$student = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$PAGE->set_url('/local/yetkinlik/student_competency_detail.php', [
    'courseid' => $courseid,
    'userid'   => $userid,
]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('studentreport', 'local_yetkinlik'));
$PAGE->set_heading(fullname($student) . ' - ' . $course->fullname);

echo $OUTPUT->header();

// Fetch competency data for the specific user.
$sql = "
    SELECT c.id,
           c.shortname,
           c.description,
           CAST(SUM(qa.maxfraction) AS DECIMAL(12, 1)) AS questions,
           CAST(SUM(qas.fraction) AS DECIMAL(12, 1)) AS correct
    FROM {quiz_attempts} quiza
    JOIN {user} u ON quiza.userid = u.id
    JOIN {question_usages} qu ON qu.id = quiza.uniqueid
    JOIN {question_attempts} qa ON qa.questionusageid = qu.id
    JOIN {quiz} quiz ON quiz.id = quiza.quiz
    JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
    JOIN {competency} c ON c.id = m.competencyid
    JOIN (
        SELECT MAX(fraction) AS fraction, questionattemptid
        FROM {question_attempt_steps}
        GROUP BY questionattemptid
    ) qas ON qas.questionattemptid = qa.id
    WHERE quiz.course = :courseid
      AND u.id = :userid
      AND quiza.state = 'finished'
    GROUP BY c.id, c.shortname, c.description
";

$rows = $DB->get_records_sql($sql, [
    'courseid' => $courseid,
    'userid'   => $userid,
]);

echo html_writer::start_tag('table', ['class' => 'generaltable']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('competencycode', 'local_yetkinlik'));
echo html_writer::tag('th', get_string('competency', 'local_yetkinlik'));
echo html_writer::tag('th', get_string('questioncount', 'local_yetkinlik'));
echo html_writer::tag('th', get_string('correctcount', 'local_yetkinlik'));
echo html_writer::tag('th', get_string('successrate', 'local_yetkinlik'));
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

$rates = [];

foreach ($rows as $r) {
    $rate = $r->questions ? number_format(($r->correct / $r->questions) * 100, 1) : 0;
    $rates[$r->shortname] = $rate;

    if ($rate >= 80) {
        $color = 'green';
    } else if ($rate >= 60) {
        $color = 'blue';
    } else if ($rate >= 40) {
        $color = 'orange';
    } else {
        $color = 'red';
    }

    // Clean description tags.
    $cleandesc = strip_tags(html_entity_decode($r->description, ENT_QUOTES, 'UTF-8'));

    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', s($r->shortname));
    echo html_writer::tag('td', $cleandesc);
    echo html_writer::tag('td', $r->questions);
    echo html_writer::tag('td', $r->correct);
    echo html_writer::tag('td', "%{$rate}", [
        'style' => "color: $color; font-weight: bold;",
    ]);
    echo html_writer::end_tag('tr');
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

// PDF Button Section with userid parameter.
$pdfurl = new moodle_url('/local/yetkinlik/parent_pdf.php', [
    'courseid' => $courseid,
    'userid'   => $userid, // Added userid here for correct reporting.
]);

echo html_writer::start_tag('div', ['class' => 'mt-4']);
echo html_writer::link($pdfurl, get_string('pdfmystudent', 'local_yetkinlik'), [
    'class' => 'btn btn-secondary',
    'target' => '_blank',
]);
echo html_writer::end_tag('div');

// AI Commentary Section.
require_once(__DIR__ . '/ai.php');
echo html_writer::tag('h3', get_string('generalcomment', 'local_yetkinlik'), ['class' => 'mt-4']);
echo html_writer::tag('div', local_yetkinlik_generate_comment($rates, 'student'), [
    'class' => 'alert alert-info',
]);

echo $OUTPUT->footer();
