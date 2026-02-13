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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/forms/selector_form.php');

$courseid = required_param('courseid', PARAM_INT);
$context  = context_course::instance($courseid);

require_login();
require_capability('moodle/course:view', $context);

global $DB, $OUTPUT, $PAGE, $USER;

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

$PAGE->set_url('/local/yetkinlik/class_report.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('report_title', 'local_yetkinlik'));
$PAGE->set_heading($course->fullname . " - " . get_string('report_heading', 'local_yetkinlik'));

echo $OUTPUT->header();

// 1. Parameter Management and Form.
$userid     = optional_param('userid', 0, PARAM_INT);
$competency = optional_param('competencyid', 0, PARAM_INT);

$mform = new local_yetkinlik_selector_form(null, ['courseid' => $courseid]);
if ($data = $mform->get_data()) {
    $userid     = $data->userid;
    $competency = $data->competencyid;
}
$mform->set_data(['userid' => $userid, 'competencyid' => $competency]);

// Upper Buttons Group.
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');
$pdfparams = ['courseid' => $courseid];
$pdfurl = new moodle_url('/local/yetkinlik/pdf_report.php', $pdfparams);
echo html_writer::link($pdfurl, get_string('pdfreport', 'local_yetkinlik'), ['class' => 'btn btn-secondary', 'target' => '_blank']);
echo html_writer::end_div();

$mform->display();

// 2. Data Queries.
$coursesql = "SELECT c.id, c.shortname,
                     CAST(SUM(qa.maxfraction) AS DECIMAL(12, 1)) AS attempts,
                     CAST(SUM(qas.fraction) AS DECIMAL(12, 1)) AS correct
              FROM {quiz_attempts} quiza
              JOIN {question_usages} qu ON qu.id = quiza.uniqueid
              JOIN {question_attempts} qa ON qa.questionusageid = qu.id
              JOIN {quiz} quiz ON quiz.id = quiza.quiz
              JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
              JOIN {competency} c ON c.id = m.competencyid
              JOIN (SELECT MAX(fraction) AS fraction, questionattemptid
                      FROM {question_attempt_steps}
                  GROUP BY questionattemptid) qas ON qas.questionattemptid = qa.id
              WHERE quiz.course = :courseid AND quiza.state = 'finished' ";

if ($competency) {
    $coursesql .= " AND c.id = :competencyid ";
}
$coursesql .= " GROUP BY c.id, c.shortname";

$coursedata = $DB->get_records_sql($coursesql, ['courseid' => $courseid, 'competencyid' => $competency]);

if (empty($coursedata)) {
    echo $OUTPUT->notification(get_string('nodatafound', 'local_yetkinlik'), 'info');
} else {
    $classdata = [];
    $studentdata = [];

    if ($userid) {
        $userdept = $DB->get_field('user', 'department', ['id' => $userid]);
        if (!empty($userdept)) {
            $classsql = str_replace(
                "FROM {quiz_attempts} quiza",
                "FROM {quiz_attempts} quiza JOIN {user} u ON quiza.userid = u.id",
                $coursesql
            );
            $classsql = str_replace("WHERE quiz.course", "WHERE u.department = :dept AND quiz.course", $classsql);
            $classdata = $DB->get_records_sql($classsql, [
                'courseid' => $courseid,
                'dept' => $userdept,
                'competencyid' => $competency
            ]);
        }

        $studentsql = str_replace("WHERE quiz.course", "WHERE quiza.userid = :userid AND quiz.course", $coursesql);
        $studentdata = $DB->get_records_sql($studentsql, [
            'courseid' => $courseid,
            'userid' => $userid,
            'competencyid' => $competency
        ]);
    }

    // 3. Table Output.
    echo html_writer::start_tag('table', ['class' => 'generaltable mt-4 shadow-sm', 'style' => 'width:100%']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('competencyname', 'local_yetkinlik'));
    echo html_writer::tag('th', get_string('courseavg', 'local_yetkinlik'), ['class' => 'text-center']);
    echo html_writer::tag('th', get_string('classavg', 'local_yetkinlik'), ['class' => 'text-center']);
    echo html_writer::tag('th', get_string('studentavg', 'local_yetkinlik'), ['class' => 'text-center']);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    $labels = [];
    $courserates = [];
    $classrates = [];
    $studentrates = [];

    foreach ($coursedata as $cid => $c) {
        $courserate = $c->attempts ? round(($c->correct / $c->attempts) * 100, 1) : 0;
        $classrate  = (isset($classdata[$cid]) && $classdata[$cid]->attempts) ?
            round(($classdata[$cid]->correct / $classdata[$cid]->attempts) * 100, 1) : 0;
        $studrate   = (isset($studentdata[$cid]) && $studentdata[$cid]->attempts) ?
            round(($studentdata[$cid]->correct / $studentdata[$cid]->attempts) * 100, 1) : 0;

        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', html_writer::tag('strong', $c->shortname));
        echo html_writer::tag('td', '%' . $courserate, ['class' => 'text-center font-weight-bold']);
        echo html_writer::tag('td', '%' . $classrate, ['class' => 'text-center text-muted']);
        echo html_writer::tag('td', '%' . $studrate, ['class' => 'text-center text-primary font-weight-bold']);
        echo html_writer::end_tag('tr');

        $labels[] = $c->shortname;
        $courserates[] = $courserate;
        $classrates[] = $classrate;
        $studentrates[] = $studrate;
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    // 4. Chart Area (Visualizer.js compatible).
    echo html_writer::div(
        '<canvas id="studentClassChart"></canvas>',
        'card mt-4 p-4 shadow-sm bg-light',
        ['style' => 'height:400px; width:100%;']
    );

    // 5. Data Preparation and AMD Module Call.
    $chartparams = [
        'labels'     => $labels,
        'courseData' => $courserates,
        'classData'  => $classrates,
        'myData'     => $studentrates,
        'labelNames' => [
            'course' => get_string('courseavg', 'local_yetkinlik'),
            'class'  => get_string('classavg', 'local_yetkinlik'),
            'my'     => get_string('studentavg', 'local_yetkinlik'),
        ],
    ];

    $PAGE->requires->js_call_amd('local_yetkinlik/visualizer', 'initStudentClass', [$chartparams]);
}

echo $OUTPUT->footer();
