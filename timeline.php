<?php
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$days     = optional_param('days', 90, PARAM_INT); // 30 / 90 / 0 (all)

require_login($courseid);

global $USER, $DB;
$userid = $USER->id;

$context = context_course::instance($courseid);

$PAGE->set_url('/local/yetkinlik/timeline.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('timelineheading','local_yetkinlik'));
$PAGE->set_heading(get_string('timelineheading','local_yetkinlik'));

echo $OUTPUT->header();

/* Filtreler */
$where = "quiz.course = :courseid AND u.id = :userid";
$params = ['courseid' => $courseid, 'userid' => $userid];

if ($days > 0) {
    $where .= " AND qas2.timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL :days DAY))";
    $params['days'] = $days;
}

/* SQL */
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
ORDER BY period
";

$rows = $DB->get_records_sql($sql, $params);

/* Verileri hazırlama */
$data = [];
$periods = [];

foreach ($rows as $r) {
    $rate = $r->attempts ? number_format(($r->correct / $r->attempts) * 100,1) : 0;
    $data[$r->shortname][$r->period] = $rate;
    $periods[$r->period] = true;
}

$periods = array_keys($periods);
sort($periods);

$datasets = [];
$colors = ['#e53935','#1e88e5','#43a047','#fb8c00','#8e24aa','#00897b'];
$i = 0;

foreach ($data as $comp => $vals) {
    $line = [];
    foreach ($periods as $p) {
        $line[] = $vals[$p] ?? 0;
    }
    $datasets[] = [
        'label' => $comp,
        'data' => $line,
        'borderColor' => $colors[$i % count($colors)],
        'fill' => false
    ];
    $i++;
}

$labelsjs = json_encode($periods);
$datasetsjs = json_encode($datasets);
?>

<form method="get">
    <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
    <label for="days"><?php echo get_string('filterlabel','local_yetkinlik'); ?></label>
    <select name="days" id="days">
        <option value="30" <?php if($days==30) echo 'selected'; ?>>
            <?php echo get_string('last30days','local_yetkinlik'); ?>
        </option>
        <option value="90" <?php if($days==90) echo 'selected'; ?>>
            <?php echo get_string('last90days','local_yetkinlik'); ?>
        </option>
        <option value="0" <?php if($days==0) echo 'selected'; ?>>
            <?php echo get_string('alltime','local_yetkinlik'); ?>
        </option>
    </select>
    <button class="btn btn-primary"><?php echo get_string('show','local_yetkinlik'); ?></button>
</form>

<canvas id="timeline" height="120"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('timeline'), {
    type: 'line',
    data: {
        labels: <?php echo $labelsjs; ?>,
        datasets: <?php echo $datasetsjs; ?>
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: '<?php echo get_string('successrate','local_yetkinlik'); ?>'
                }
            }
        }
    }
});
</script>

<?php
echo $OUTPUT->footer();