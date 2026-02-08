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
 * Report for competency.
 *
 * @package   local_yetkinlik
 * @copyright 2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later*/

require_once(__DIR__.'/../../config.php');
require_login();

global $DB, $USER, $OUTPUT, $PAGE;

$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

$context = context_course::instance($courseid);

$quizid = optional_param('quizid', 0, PARAM_INT);

$PAGE->set_url('/local/yetkinlik/student_exam.php', ['courseid'=>$courseid]);
$PAGE->set_title(get_string('studentexam','local_yetkinlik'));
$PAGE->set_heading(get_string('studentexam','local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

/* Öğrencinin girdiği sınavları çek */
$quizzes = $DB->get_records_sql("
SELECT DISTINCT q.id, q.name
FROM {quiz} q
JOIN {quiz_attempts} qa ON qa.quiz = q.id
WHERE qa.userid = ? AND q.course = ?
ORDER BY q.name
", [$USER->id, $courseid]);

echo '<form method="get">';
echo '<input type="hidden" name="courseid" value="'.$courseid.'">';
echo '<select name="quizid">';
echo '<option value="0">'.get_string('selectquiz','local_yetkinlik').'</option>';

foreach ($quizzes as $q) {
    $sel = ($quizid == $q->id) ? 'selected' : '';
    echo "<option value='{$q->id}' $sel>{$q->name}</option>";
}

echo '</select> ';
echo '<button>'.get_string('show','local_yetkinlik').'</button>';
echo '</form><hr>';

if ($quizid) {

    $sql = "
    SELECT
      c.shortname,
      c.description,
      SUM(qa.maxfraction) attempts,
      SUM(qas.fraction) correct
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
    GROUP BY c.shortname
    ORDER BY c.shortname
    ";

    $rows = $DB->get_records_sql($sql, [$quizid, $USER->id]);

    if ($rows) {
        echo '<table class="generaltable">';
        echo '<tr>
                <th>'.get_string('competencycode','local_yetkinlik').'</th>
                <th>'.get_string('competency','local_yetkinlik').'</th>
                <th>'.get_string('success','local_yetkinlik').'</th>
              </tr>';

        $labels = [];
        $data = [];

        foreach ($rows as $r) {
            $rate = $r->attempts ? number_format(($r->correct / $r->attempts) * 100,1) : 0;
            $labels[] = $r->shortname;
            $data[] = $rate;

            if ($rate >= 80) { $color = 'green'; }
            elseif ($rate >= 60) { $color = 'blue'; }
            elseif ($rate >= 40) { $color = 'orange'; }
            else { $color = 'red'; }

            echo "<tr>
                <td>{$r->shortname}</td>
                <td>{$r->description}</td>
                <td style='color:$color;font-weight:bold'>%$rate</td>
            </tr>";
        }
        echo '</table>';

        $labelsjs = json_encode($labels);
        $datajs = json_encode($data);
        ?>

        <canvas id="studentexamchart"></canvas>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        new Chart(document.getElementById('studentexamchart'), {
            type: 'bar',
            data: {
                labels: <?php echo $labelsjs; ?>,
                datasets: [{
                    label: '<?php echo get_string('successpercent','local_yetkinlik'); ?>',
                    data: <?php echo $datajs; ?>,
                    backgroundColor: <?php echo json_encode(array_map(function($rate) {
                        if ($rate >= 80) return 'green';
                        elseif ($rate >= 60) return 'blue';
                        elseif ($rate >= 40) return 'orange';
                        else return 'red';
                    }, $data)); ?>
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true, max: 100 }
                }
            }
        });
        </script>

        <?php
    } else {
        echo '<p>'.get_string('noexamdata','local_yetkinlik').'</p>';
    }
}

echo $OUTPUT->footer();
