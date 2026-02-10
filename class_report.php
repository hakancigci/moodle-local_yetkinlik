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

require_login();

$courseid = required_param('courseid', PARAM_INT);
$context  = context_course::instance($courseid);
require_capability('moodle/course:view', $context);

global $DB, $CFG, $OUTPUT, $PAGE, $COURSE;

$COURSE = get_course($courseid);

$PAGE->set_url('/local/yetkinlik/class_report.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($COURSE);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('report_title', 'local_yetkinlik'));
$PAGE->set_heading($COURSE->fullname . " - " . get_string('report_heading', 'local_yetkinlik'));

echo $OUTPUT->header();

// 1. Parametre Yönetimi.
$userid     = optional_param('userid', 0, PARAM_INT);
$competency = optional_param('competencyid', 0, PARAM_INT);

$mform = new local_yetkinlik_selector_form(null, ['courseid' => $courseid]);

// Form gönderildiyse değerleri formdan güncelle.
if ($data = $mform->get_data()) {
    $userid     = $data->userid;
    $competency = $data->competencyid;
}

// Formun seçili kalmasını sağla.
$mform->set_data(['userid' => $userid, 'competencyid' => $competency]);

// PDF Butonu.
$pdfurl = new moodle_url('/local/yetkinlik/pdf_report.php', ['courseid' => $courseid]);
echo html_writer::start_tag('div', ['class' => 'mb-3']);
echo html_writer::link($pdfurl, 'PDF Rapor Al', ['class' => 'btn btn-secondary', 'target' => '_blank']);
echo html_writer::end_tag('div');

$mform->display();

// 2. Veri Hesaplama.

// Kurs Ortalaması SQL.
$coursesql = "SELECT c.id, c.shortname,
                      CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS attempts,
                      CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
               FROM {quiz_attempts} quiza
               JOIN {question_usages} qu ON qu.id = quiza.uniqueid
               JOIN {question_attempts} qa ON qa.questionusageid = qu.id
               JOIN {quiz} quiz ON quiz.id = quiza.quiz
               JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
               JOIN {competency} c ON c.id = m.competencyid
               JOIN (SELECT MAX(fraction) AS fraction, questionattemptid
                       FROM {question_attempt_steps}
                   GROUP BY questionattemptid) qas ON qas.questionattemptid = qa.id
               WHERE quiz.course = :courseid AND quiza.state = 'finished' "
               . ($competency ? " AND c.id = :competencyid " : "") .
               " GROUP BY c.id, c.shortname";

$coursedata = $DB->get_records_sql($coursesql, ['courseid' => $courseid, 'competencyid' => $competency]);

$classdata = [];
$studentdata = [];

if ($userid) {
    // Sınıf (Bölüm) Ortalaması.
    $userdept = $DB->get_field('user', 'department', ['id' => $userid]);

    if (!empty($userdept)) {
        $classsql = "SELECT c.id, c.shortname, CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS attempts,
                              CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
                      FROM {quiz_attempts} quiza
                      JOIN {user} u ON quiza.userid = u.id
                      JOIN {question_usages} qu ON qu.id = quiza.uniqueid
                      JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                      JOIN {quiz} quiz ON quiz.id = quiza.quiz
                      JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
                      JOIN {competency} c ON c.id = m.competencyid
                      JOIN (SELECT MAX(fraction) AS fraction, questionattemptid
                              FROM {question_attempt_steps}
                          GROUP BY questionattemptid) qas ON qas.questionattemptid = qa.id
                      WHERE quiz.course = :courseid AND u.department = :dept AND quiza.state = 'finished' "
                      . ($competency ? " AND c.id = :competencyid " : "") .
                      " GROUP BY c.id, c.shortname";
        $classdata = $DB->get_records_sql($classsql, [
            'courseid' => $courseid,
            'dept' => $userdept,
            'competencyid' => $competency,
        ]);
    }

    // Bireysel Öğrenci Ortalaması.
    $studentsql = "SELECT c.id, c.shortname, CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS attempts,
                            CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
                    FROM {quiz_attempts} quiza
                    JOIN {user} u ON quiza.userid = u.id
                    JOIN {question_usages} qu ON qu.id = quiza.uniqueid
                    JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                    JOIN {quiz} quiz ON quiz.id = quiza.quiz
                    JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
                    JOIN {competency} c ON c.id = m.competencyid
                    JOIN (SELECT MAX(fraction) AS fraction, questionattemptid
                            FROM {question_attempt_steps}
                        GROUP BY questionattemptid) qas ON qas.questionattemptid = qa.id
                    WHERE quiz.course = :courseid AND u.id = :userid AND quiza.state = 'finished' "
                    . ($competency ? " AND c.id = :competencyid " : "") .
                    " GROUP BY c.id, c.shortname";
    $studentdata = $DB->get_records_sql($studentsql, [
        'courseid' => $courseid,
        'userid' => $userid,
        'competencyid' => $competency,
    ]);
}

// 3. Tablo ve Grafik Çıktısı.
echo html_writer::start_tag('table', ['class' => 'generaltable mt-4', 'style' => 'width:100%']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', 'Yetkinlik Adı');
echo html_writer::tag('th', 'Kurs Ort.');
echo html_writer::tag('th', 'Sınıf Ort.');
echo html_writer::tag('th', 'Öğrenci Ort.');
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

$labels = [];
$course_rates = [];
$class_rates = [];
$student_rates = [];

foreach ($coursedata as $cid => $c) {
    $course_rate = $c->attempts ? round(($c->correct / $c->attempts) * 100, 1) : 0;
    $class_rate  = (isset($classdata[$cid]) && $classdata[$cid]->attempts)
        ? round(($classdata[$cid]->correct / $classdata[$cid]->attempts) * 100, 1) : 0;
    $stud_rate   = (isset($studentdata[$cid]) && $studentdata[$cid]->attempts)
        ? round(($studentdata[$cid]->correct / $studentdata[$cid]->attempts) * 100, 1) : 0;

    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', $c->shortname);
    echo html_writer::tag('td', '%' . $course_rate, ['class' => 'font-weight-bold']);
    echo html_writer::tag('td', '%' . $class_rate, ['class' => 'text-muted']);
    echo html_writer::tag('td', '%' . $stud_rate, ['class' => 'text-primary font-weight-bold']);
    echo html_writer::end_tag('tr');

    $labels[] = $c->shortname;
    $course_rates[] = $course_rate;
    $class_rates[] = $class_rate;
    $student_rates[] = $stud_rate;
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

// Grafik Alanı.
echo html_writer::start_tag('div', ['class' => 'mt-5']);
echo html_writer::tag('canvas', '', ['id' => 'competencyChart', 'height' => '100']);
echo html_writer::end_tag('div');

// ChartJS Script.
$chart_js_url = 'https://cdn.jsdelivr.net/npm/chart.js';
echo html_writer::script('', $chart_js_url);

$js_labels = json_encode($labels);
$js_course = json_encode($course_rates);
$js_class = json_encode($class_rates);
$js_student = json_encode($student_rates);

$script = "
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('competencyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: $js_labels,
            datasets: [
                { label: 'Kurs Ort.', data: $js_course, backgroundColor: 'rgba(156, 39, 176, 0.6)' },
                { label: 'Sınıf Ort.', data: $js_class, backgroundColor: 'rgba(76, 175, 80, 0.6)' },
                { label: 'Öğrenci Ort.', data: $js_student, backgroundColor: 'rgba(33, 150, 243, 0.6)' }
            ]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
});";
echo html_writer::script($script);

echo $OUTPUT->footer();
