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
 * Student competency report.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

$context = context_course::instance($courseid);
$userid = $USER->id;

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

$PAGE->set_url('/local/yetkinlik/student_report.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('studentreport', 'local_yetkinlik'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

global $DB;

// Student data SQL query.
$sql = "
    SELECT c.id,
           c.shortname,
           c.description,
           c.descriptionformat,
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
    WHERE quiz.course = :courseid AND u.id = :userid
    GROUP BY c.id, c.shortname, c.description, c.descriptionformat
";

$rows = $DB->get_records_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);

echo html_writer::start_tag('table', ['class' => 'generaltable mt-3 w-100']);
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
        $color = '#28a745'; // Green.
    } else if ($rate >= 60) {
        $color = '#007bff'; // Blue.
    } else if ($rate >= 40) {
        $color = '#fd7e14'; // Orange.
    } else {
        $color = '#dc3545'; // Red.
    }

    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', s($r->shortname));

    // Düzenlenen Kısım: HTML taglarını render eder.
    $description = format_text($r->description, $r->descriptionformat, ['context' => $context]);
    echo html_writer::tag('td', $description);

    echo html_writer::tag('td', $r->questions);
    echo html_writer::tag('td', $r->correct);
    echo html_writer::tag('td', '%' . $rate, [
        'style' => "color: $color; font-weight: bold;",
    ]);
    echo html_writer::end_tag('tr');
}

if (empty($rows)) {
    $nodata = get_string('nodatafound', 'local_yetkinlik');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', $nodata, ['colspan' => 5, 'class' => 'text-center']);
    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

// PDF report button.
$pdfUrl = new moodle_url('/local/yetkinlik/parent_pdf.php', ['courseid' => $courseid]);
echo html_writer::start_tag('div', ['class' => 'mt-4']);
echo html_writer::link($pdfUrl, get_string('pdfmystudent', 'local_yetkinlik'), [
    'class' => 'btn btn-secondary',
    'target' => '_blank',
]);
echo html_writer::end_tag('div');

// AI Comment section.
require_once(__DIR__ . '/ai.php');
if (!empty($rates)) {
    echo html_writer::tag('h3', get_string('generalcomment', 'local_yetkinlik'), ['class' => 'mt-4']);
    echo html_writer::tag('div', local_yetkinlik_generate_comment($rates, 'student'), [
        'class' => 'alert alert-info',
    ]);
}

echo $OUTPUT->footer();
