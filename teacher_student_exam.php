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
 * Teacher student exam analysis report.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

global $DB, $OUTPUT, $PAGE;

$quizid = required_param('quizid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$quiz = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
$courseid = $quiz->course;

$context = context_course::instance($courseid);
require_capability('mod/quiz:viewreports', $context);

$student = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$PAGE->set_url('/local/yetkinlik/teacher_student_exam.php', ['quizid' => $quizid, 'userid' => $userid]);
$PAGE->set_title('Öğrenci Sınav Analizi');
$PAGE->set_heading(fullname($student) . ' - ' . $quiz->name);

echo $OUTPUT->header();

$sql = "
SELECT
 c.shortname,
 COUNT(qa.id) attempts,
 SUM(CASE WHEN qas.fraction > 0 THEN 1 ELSE 0 END) correct
FROM {local_yetkinlik_qmap} m
JOIN {competency} c ON c.id = m.competencyid
JOIN {question_attempts} qa ON qa.questionid = m.questionid
JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
JOIN {question_usages} qu ON qu.id = qa.questionusageid
JOIN {quiz_attempts} qa2 ON qa2.uniqueid = qu.id
WHERE qa2.quiz = :quizid AND qa2.userid = :userid
GROUP BY c.shortname
";

$rows = $DB->get_records_sql($sql, ['quizid' => $quizid, 'userid' => $userid]);

echo html_writer::start_tag('table', ['class' => 'generaltable']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', 'Kazanım');
echo html_writer::tag('th', 'Başarı');
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

$labels = [];
$data = [];

foreach ($rows as $r) {
    // Attempts varsa, sonucu 100 ile çarpıp tek ondalık hane olacak şekilde formatlıyoruz.
    $rate = $r->attempts ? number_format(($r->correct / $r->attempts) * 100, 1) : 0;

    $labels[] = $r->shortname;
    $data[] = $rate;

    $color = $rate >= 70 ? 'green' : ($rate >= 50 ? 'orange' : 'red');

    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', s($r->shortname));
    echo html_writer::tag('td', "%{$rate}", ['style' => "color: $color; font-weight: bold;"]);
    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

$labelsjs = json_encode($labels);
$datajs = json_encode($data);

// Chart display section for student competency performance.
/**
 * Chart display template.
 */
?>

<div class="chart-container mt-4" style="position: relative; height:40vh; width:100%">
    <canvas id="teacherchart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    /**
     * Initialize the bar chart using Chart.js.
     */
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('teacherchart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo $labelsjs; ?>,
                datasets: [{
                    label: 'Başarı %',
                    data: <?php echo $datajs; ?>,
                    backgroundColor: '#ff9800',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    });
</script>

<?php
/**
 * Footer of the page.
 */
echo $OUTPUT->footer();
