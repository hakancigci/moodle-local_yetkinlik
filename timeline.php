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
 * Timeline report for competency development.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$days     = optional_param('days', 90, PARAM_INT); // 30 / 90 / 0 (all).

require_login($courseid);

global $USER, $DB, $PAGE, $OUTPUT;

$userid = $USER->id;
$context = context_course::instance($courseid);

$PAGE->set_url('/local/yetkinlik/timeline.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('timelineheading', 'local_yetkinlik'));
$PAGE->set_heading(get_string('timelineheading', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

/* Filtreler ve SQL hazırlığı */
$where = "quiz.course = :courseid AND u.id = :userid";
$params = ['courseid' => $courseid, 'userid' => $userid];

if ($days > 0) {
    $where .= " AND qas2.timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL :days DAY))";
    $params['days'] = $days;
}

$sql = "
SELECT
  c.shortname,
  FROM_UNIXTIME(qas2.timecreated, '%Y-%m') AS period,
  CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS attempts,
  CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
FROM {quiz_attempts} quiza
JOIN {user} u ON quiza.userid = u.id
JOIN {question_usages} qu ON qu.id = quiza.uniqueid
JOIN {question_attempts} qa ON qa.questionusageid = qu.id
JOIN {quiz} quiz ON quiz.id = quiza.quiz
JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
JOIN {competency} c ON c.id = m.competencyid
JOIN (
    SELECT questionattemptid, MAX(timecreated) AS timecreated
    FROM {question_attempt_steps}
    GROUP BY questionattemptid
) qas2 ON qas2.questionattemptid = qa.id
JOIN (
    SELECT MAX(fraction) AS fraction, questionattemptid
    FROM {question_attempt_steps}
    GROUP BY questionattemptid
) qas ON qas.questionattemptid = qa.id
WHERE $where AND quiza.state = 'finished'
GROUP BY c.shortname, period
ORDER BY period ASC
";

$rows = $DB->get_records_sql($sql, $params);

/* Verileri işleme */
$data = [];
$periods = [];

foreach ($rows as $r) {
    // number_format ile tek ondalık hane.
    $rate = $r->attempts ? number_format(($r->correct / $r->attempts) * 100, 1) : 0;
    $data[$r->shortname][$r->period] = $rate;
    $periods[$r->period] = true;
}

$periods = array_keys($periods);
sort($periods);

$datasets = [];
$colors = ['#e53935', '#1e88e5', '#43a047', '#fb8c00', '#8e24aa', '#00897b'];
$i = 0;

foreach ($data as $comp => $vals) {
    $line = [];
    foreach ($periods as $p) {
        $line[] = isset($vals[$p]) ? $vals[$p] : 0;
    }
    $datasets[] = [
        'label' => $comp,
        'data' => $line,
        'borderColor' => $colors[$i % count($colors)],
        'backgroundColor' => $colors[$i % count($colors)],
        'fill' => false,
        'tension' => 0.1
    ];
    $i++;
}

$labelsjs = json_encode($periods);
$datasetsjs = json_encode($datasets);
?>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="form-inline">
            <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
            <label class="mr-2" for="days"><?php echo get_string('filterlabel', 'local_yetkinlik'); ?></label>
            <select name="days" id="days" class="form-control mr-2">
                <option value="30" <?php echo ($days == 30) ? 'selected' : ''; ?>>
                    <?php echo get_string('last30days', 'local_yetkinlik'); ?>
                </option>
                <option value="90" <?php echo ($days == 90) ? 'selected' : ''; ?>>
                    <?php echo get_string('last90days', 'local_yetkinlik'); ?>
                </option>
                <option value="0" <?php echo ($days == 0) ? 'selected' : ''; ?>>
                    <?php echo get_string('alltime', 'local_yetkinlik'); ?>
                </option>
            </select>
            <button type="submit" class="btn btn-primary"><?php echo get_string('show', 'local_yetkinlik'); ?></button>
        </form>
    </div>
</div>

<div class="chart-container" style="position: relative; height:400px; width:100%">
    <canvas id="timeline"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('timeline').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo $labelsjs; ?>,
                datasets: <?php echo $datasetsjs; ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: '<?php echo get_string('successrate', 'local_yetkinlik'); ?> (%)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>

<?php
echo $OUTPUT->footer();
