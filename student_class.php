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
 * Student Comparative Competency Analysis Report.
 *
 * This page provides a dynamic table and a Chart.js graph comparing
 * student exam performance with course and class averages.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

global $DB, $USER, $PAGE, $OUTPUT;

// 1. Get parameters and validate course context.
$courseid = required_param('courseid', PARAM_INT);
$course   = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context  = context_course::instance($courseid);

// Security check: Ensure the user is logged into the course.
require_login($course);

// 2. Page Configuration (Moodle Standards).
$PAGE->set_url('/local/yetkinlik/student_class.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('studentanalysis', 'local_yetkinlik'));
$PAGE->set_heading(get_string('analysisfor', 'local_yetkinlik', $course->fullname));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

// 3. Data Queries (SQL).
// General Course Average.
$coursesql = "SELECT c.id, c.shortname,
                      CAST(SUM(qa.maxfraction) AS DECIMAL(12, 1)) AS attempts,
                      CAST(SUM(qas.fraction) AS DECIMAL(12, 1)) AS correct
               FROM {quiz_attempts} quiza
               JOIN {quiz} quiz ON quiz.id = quiza.quiz
               JOIN {question_usages} qu ON qu.id = quiza.uniqueid
               JOIN {question_attempts} qa ON qa.questionusageid = qu.id
               JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
               JOIN {competency} c ON c.id = m.competencyid
               JOIN (
                   SELECT MAX(fraction) AS fraction, questionattemptid
                   FROM {question_attempt_steps}
                   GROUP BY questionattemptid
               ) qas ON qas.questionattemptid = qa.id
               WHERE quiz.course = :courseid AND quiza.state = 'finished'
               GROUP BY c.id, c.shortname";

$coursedata = $DB->get_records_sql($coursesql, ['courseid' => $courseid]);

if (empty($coursedata)) {
    echo $OUTPUT->notification(get_string('nodatafound', 'local_yetkinlik'), 'info');
} else {
    // Class (Department) Average.
    $classdata = [];
    if (!empty($USER->department)) {
        $classsql = str_replace("GROUP BY c.id, c.shortname", "AND u.department = :dept GROUP BY c.id", $coursesql);
        $classsql = str_replace(
            "FROM {quiz_attempts} quiza",
            "FROM {quiz_attempts} quiza JOIN {user} u ON quiza.userid = u.id",
            $classsql
        );
        $classdata = $DB->get_records_sql($classsql, ['courseid' => $courseid, 'dept' => $USER->department]);
    }

    // Student's Own Data.
    $studentsql = str_replace("GROUP BY c.id, c.shortname", "AND quiza.userid = :userid GROUP BY c.id", $coursesql);
    $studentdata = $DB->get_records_sql($studentsql, ['courseid' => $courseid, 'userid' => $USER->id]);

    // 4. Table Output.
    echo html_writer::start_tag('table', ['class' => 'generaltable table-hover mt-3 shadow-sm', 'style' => 'width:100%']);
    echo '<thead><tr>';
    echo html_writer::tag('th', get_string('competencyname', 'local_yetkinlik'));
    echo html_writer::tag('th', get_string('courseavg', 'local_yetkinlik'), ['class' => 'text-center']);
    echo html_writer::tag('th', get_string('classavg', 'local_yetkinlik'), ['class' => 'text-center']);
    echo html_writer::tag('th', get_string('myavg', 'local_yetkinlik'), ['class' => 'text-center']);
    echo '</tr></thead><tbody>';

    $labels = [];
    $courserates = [];
    $classrates = [];
    $myrates = [];

    foreach ($coursedata as $cid => $c) {
        $courserate = $c->attempts ? round(($c->correct / $c->attempts) * 100, 1) : 0;
        $classrate  = (isset($classdata[$cid]) && $classdata[$cid]->attempts)
            ? round(($classdata[$cid]->correct / $classdata[$cid]->attempts) * 100, 1) : 0;
        $myrate     = (isset($studentdata[$cid]) && $studentdata[$cid]->attempts)
            ? round(($studentdata[$cid]->correct / $studentdata[$cid]->attempts) * 100, 1) : 0;

        // Comparative coloring based on course average.
        $colorclass = ($myrate >= $courserate) ? 'text-success' : 'text-danger';

        echo '<tr>';
        echo html_writer::tag('td', html_writer::tag('strong', $c->shortname));
        echo html_writer::tag('td', '%' . $courserate, ['class' => 'text-center text-muted']);
        echo html_writer::tag('td', '%' . $classrate, ['class' => 'text-center text-muted']);
        echo html_writer::tag('td', '%' . $myrate, [
            'class' => 'text-center font-weight-bold ' . $colorclass,
            'style' => 'font-size:1.1em',
        ]);
        echo '</tr>';

        $labels[] = $c->shortname;
        $courserates[] = $courserate;
        $classrates[] = $classrate;
        $myrates[] = $myrate;
    }
    echo '</tbody></table>';

    // 5. Chart Area (Canvas).
    // IMPORTANT: The element must be ready in the DOM before the chart is triggered.
    echo html_writer::div(
        '<canvas id="studentClassChart"></canvas>',
        'card mt-4 p-4 shadow-sm bg-light',
        ['style' => 'height:400px; min-height:400px; width:100%;']
    );

    // 6. JavaScript Preparation and AMD Call.
    $chartparams = [
        'labels'     => $labels,
        'courseData' => $courserates,
        'classData'  => $classrates,
        'myData'     => $myrates,
        'labelNames' => [
            'course' => get_string('courseavg', 'local_yetkinlik'),
            'class'  => get_string('classavg', 'local_yetkinlik'),
            'my'     => get_string('myavg', 'local_yetkinlik'),
        ],
    ];

    // Transfer data to global configuration safely using data_for_js.
    $PAGE->requires->data_for_js('chartData', $chartparams);

    // Trigger the AMD module and pass the data as a parameter.
    $PAGE->requires->js_call_amd('local_yetkinlik/visualizer', 'initStudentClass', [$chartparams]);
}

echo $OUTPUT->footer();
