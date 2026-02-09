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
 * Student Comparison Report (Fully Internationalized).
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course   = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context  = context_course::instance($courseid);

// Güvenlik ve Kurs Kaydı Kontrolü.
require_login($course);

global $DB, $USER, $PAGE, $OUTPUT;

// Sayfa Yapılandırması.
$PAGE->set_url('/local/yetkinlik/student_class.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('studentanalysis', 'local_yetkinlik'));
$PAGE->set_heading(get_string('analysisfor', 'local_yetkinlik', $course->fullname));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

// 1. VERİ SORGULARI.
$course_sql = "SELECT c.id, c.shortname,
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

$course_data = $DB->get_records_sql($course_sql, ['courseid' => $courseid]);

$class_data = [];
$student_data = [];

if (!empty($course_data)) {
    // Sınıf Ortalaması (Departman bazlı).
    if (!empty($USER->department)) {
        $class_sql = "SELECT c.id,
                             CAST(SUM(qa.maxfraction) AS DECIMAL(12, 1)) AS attempts,
                             CAST(SUM(qas.fraction) AS DECIMAL(12, 1)) AS correct
                      FROM {quiz_attempts} quiza
                      JOIN {user} u ON quiza.userid = u.id
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
                      WHERE quiz.course = :courseid AND u.department = :dept AND quiza.state = 'finished'
                      GROUP BY c.id";
        $class_data = $DB->get_records_sql($class_sql, ['courseid' => $courseid, 'dept' => $USER->department]);
    }

    // Öğrencinin Kendi Verisi.
    $student_sql = "SELECT c.id,
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
                    WHERE quiz.course = :courseid AND quiza.userid = :userid AND quiza.state = 'finished'
                    GROUP BY c.id";
    $student_data = $DB->get_records_sql($student_sql, ['courseid' => $courseid, 'userid' => $USER->id]);
}

// 2. EKRAN ÇIKTISI.
if (empty($course_data)) {
    echo $OUTPUT->notification(get_string('nodatafound', 'local_yetkinlik'), 'info');
} else {
    // Bilgi Kutusu.
    $infotext = get_string('compareinfo', 'local_yetkinlik');
    if (!empty($USER->department)) {
        $infotext .= ' ' . get_string('classinfo', 'local_yetkinlik', $USER->department);
    }
    echo html_writer::div($infotext, 'alert alert-info border-0 shadow-sm mb-4');

    // Tablo.
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

    foreach ($course_data as $cid => $c) {
        $courserate = $c->attempts ? round(($c->correct / $c->attempts) * 100, 1) : 0;
        $classrate  = (isset($class_data[$cid]) && $class_data[$cid]->attempts)
            ? round(($class_data[$cid]->correct / $class_data[$cid]->attempts) * 100, 1) : 0;
        $myrate     = (isset($student_data[$cid]) && $student_data[$cid]->attempts)
            ? round(($student_data[$cid]->correct / $student_data[$cid]->attempts) * 100, 1) : 0;

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

    // Grafik.
    echo html_writer::div('<canvas id="compareChart" height="120"></canvas>', 'card mt-4 p-4 shadow-sm border-0 bg-light');
    ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('compareChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: '<?php echo get_string('courseavg', 'local_yetkinlik'); ?>',
                    data: <?php echo json_encode($courserates); ?>,
                    backgroundColor: 'rgba(156, 39, 176, 0.4)',
                    borderRadius: 5
                },
                {
                    label: '<?php echo get_string('classavg', 'local_yetkinlik'); ?>',
                    data: <?php echo json_encode($classrates); ?>,
                    backgroundColor: 'rgba(76, 175, 80, 0.4)',
                    borderRadius: 5
                },
                {
                    label: '<?php echo get_string('myavg', 'local_yetkinlik'); ?>',
                    data: <?php echo json_encode($myrates); ?>,
                    backgroundColor: 'rgba(33, 150, 243, 0.8)',
                    borderColor: '#1976d2',
                    borderWidth: 1,
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return '%' + value;
                        }
                    }
                }
            }
        }
    });
});
</script>

    <?php
}
echo $OUTPUT->footer();
