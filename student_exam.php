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
 * Student exam competency analysis report.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$quizid   = optional_param('quizid', 0, PARAM_INT);

require_login($courseid);

global $DB, $USER, $OUTPUT, $PAGE;

$context = context_course::instance($courseid);

$PAGE->set_url('/local/yetkinlik/student_exam.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('studentexam', 'local_yetkinlik'));
$PAGE->set_heading(get_string('studentexam', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

// Fetch quizzes completed by the student.
$quizzes = $DB->get_records_sql("
    SELECT DISTINCT q.id, q.name
      FROM {quiz} q
      JOIN {quiz_attempts} qa ON qa.quiz = q.id
     WHERE qa.userid = ? AND q.course = ?
  ORDER BY q.name
", [$USER->id, $courseid]);

// Start Form.
echo html_writer::start_tag('div', ['class' => 'card mb-4']);
echo html_writer::start_tag('div', ['class' => 'card-body']);

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);

$options = [0 => get_string('selectquiz', 'local_yetkinlik')];
foreach ($quizzes as $q) {
    $options[$q->id] = $q->name;
}

echo html_writer::label(get_string('selectquiz', 'local_yetkinlik'), 'quizid', false, ['class' => 'mr-2']);
echo html_writer::select($options, 'quizid', $quizid, false, ['class' => 'form-control mr-2', 'id' => 'quizid']);
echo html_writer::tag('button', get_string('show', 'local_yetkinlik'), ['type' => 'submit', 'class' => 'btn btn-primary']);

echo html_writer::end_tag('form');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

if ($quizid) {
    $sql = "
    SELECT
      c.shortname,
      c.description,
      SUM(qa.maxfraction) AS attempts,
      SUM(qas.fraction) AS correct
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
    WHERE quiz.id = ? AND u.id = ?
    GROUP BY c.shortname, c.description
    ORDER BY c.shortname
    ";

    $rows = $DB->get_records_sql($sql, [$quizid, $USER->id]);

    if ($rows) {
        $table = new html_table();
        $table->head = [
            get_string('competencycode', 'local_yetkinlik'),
            get_string('competency', 'local_yetkinlik'),
            get_string('success', 'local_yetkinlik'),
        ];
        $table->attributes['class'] = 'generaltable mt-3';

        $labels = [];
        $data = [];
        $bgcolors = [];

        foreach ($rows as $r) {
            $rate = $r->attempts ? number_format(($r->correct / $r->attempts) * 100, 1) : 0;
            $labels[] = $r->shortname;
            $data[] = $rate;

            if ($rate >= 80) {
                $color = 'green';
            } else if ($rate >= 60) {
                $color = 'blue';
            } else if ($rate >= 40) {
                $color = 'orange';
            } else {
                $color = 'red';
            }

            $bgcolors[] = $color;

            $formattedrate = html_writer::tag('span', "%{$rate}", [
                'style' => "color: $color; font-weight: bold;",
            ]);

            $table->data[] = [$r->shortname, $r->description, $formattedrate];
        }

        echo html_writer::table($table);

        $labelsjs = json_encode($labels);
        $datajs = json_encode($data);
        $colorsjs = json_encode($bgcolors);

        // Student exam success chart display.
        // Chart output section.
        ?>

        <div class="chart-container mt-4" style="position: relative; height:40vh; width:100%">
            <canvas id="studentexamchart"></canvas>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('studentexamchart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo $labelsjs; ?>,
                    datasets: [{
                        label: '<?php echo get_string('successpercent', 'local_yetkinlik'); ?> (%)',
                        data: <?php echo $datajs; ?>,
                        backgroundColor: <?php echo $colorsjs; ?>
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });
        });
        </script>

        <?php
    } else {
        echo $OUTPUT->notification(get_string('noexamdata', 'local_yetkinlik'), 'info');
    }
}

// Footer section.
/**
 * Footer section.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
echo $OUTPUT->footer();
