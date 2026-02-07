<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$userid   = $USER->id;   // giriş yapan öğrenci
require_login($courseid);
$context = context_course::instance($courseid);

$PAGE->set_url('/local/yetkinlik/student_class.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('studentclass','local_yetkinlik'));
$PAGE->set_heading(get_string('studentclass','local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();
global $DB;

/* Kurs ortalaması */
$courseSql = "
SELECT c.id, c.shortname,
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
    SELECT MAX(fraction) AS fraction, questionattemptid
    FROM {question_attempt_steps}
    GROUP BY questionattemptid
) qas ON qas.questionattemptid = qa.id
WHERE quiz.course = :courseid AND quiza.state = 'finished'
GROUP BY c.id, c.shortname
";
$courseData = $DB->get_records_sql($courseSql, ['courseid' => $courseid]);

/* Sınıf ortalaması */
$classSql = "
SELECT c.id, c.shortname,
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
    SELECT MAX(fraction) AS fraction, questionattemptid
    FROM {question_attempt_steps}
    GROUP BY questionattemptid
) qas ON qas.questionattemptid = qa.id
JOIN {user} u2 ON u.department = u2.department
WHERE quiz.course = :courseid
  AND u2.id = :userid
  AND quiza.state = 'finished'
GROUP BY c.id, c.shortname
";
$classData = $DB->get_records_sql($classSql, ['courseid' => $courseid,'userid' => $userid]);

/* Öğrenci verisi */
$studentSql = "
SELECT c.id, c.shortname,
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
    SELECT MAX(fraction) AS fraction, questionattemptid
    FROM {question_attempt_steps}
    GROUP BY questionattemptid
) qas ON qas.questionattemptid = qa.id
WHERE quiz.course = :courseid AND u.id = :userid AND quiza.state = 'finished'
GROUP BY c.id, c.shortname
";
$studentData = $DB->get_records_sql($studentSql, [
    'courseid' => $courseid,
    'userid'   => $userid
]);

/* Tablo */
echo '<table class="generaltable">';
echo '<tr>
        <th>'.get_string('competency','local_yetkinlik').'</th>
        <th>'.get_string('courseavg','local_yetkinlik').'</th>
        <th>'.get_string('classavg','local_yetkinlik').'</th>
        <th>'.get_string('studentavg','local_yetkinlik').'</th>
      </tr>';

$labels = [];
$courseRates = [];
$classRates = [];
$studentRates = [];

foreach ($courseData as $cid => $c) {
    $courseRate = $c->attempts ? number_format(($c->correct / $c->attempts) * 100,1) : 0;
    $class      = $classData[$cid] ?? null;
    $classRate  = $class && $class->attempts ? number_format(($class->correct / $class->attempts) * 100,1) : 0;
    $stud       = $studentData[$cid] ?? null;
    $studRate   = $stud && $stud->attempts ? number_format(($stud->correct / $stud->attempts) * 100,1) : 0;

    $colorCourse = ($courseRate >= 80) ? 'green' : (($courseRate >= 60) ? 'blue' : (($courseRate >= 40) ? 'orange' : 'red'));
    $colorClass  = ($classRate  >= 80) ? 'green' : (($classRate  >= 60) ? 'blue' : (($classRate  >= 40) ? 'orange' : 'red'));
    $colorStud   = ($studRate   >= 80) ? 'green' : (($studRate   >= 60) ? 'blue' : (($studRate   >= 40) ? 'orange' : 'red'));

    echo "<tr>
        <td>{$c->shortname}</td>
        <td style='color:$colorCourse;font-weight:bold'>%$courseRate</td>
        <td style='color:$colorClass;font-weight:bold'>%$classRate</td>
        <td style='color:$colorStud;font-weight:bold'>%$studRate</td>
    </tr>";

    $labels[]       = $c->shortname;
    $courseRates[]  = $courseRate;
    $classRates[]   = $classRate;
    $studentRates[] = $studRate;
}
echo '</table>';

$labelsjs   = json_encode($labels);
$coursejs   = json_encode($courseRates);
$classjs    = json_encode($classRates);
$studentjs  = json_encode($studentRates);
?>

<canvas id="chart" height="120"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('chart'), {
    type: 'bar',
    data: {
        labels: <?php echo $labelsjs; ?>,
        datasets: [
            { label: '<?php echo get_string('courseavg','local_yetkinlik'); ?>',
              data: <?php echo $coursejs; ?>,
              backgroundColor: '#9c27b0' },
            { label: '<?php echo get_string('classavg','local_yetkinlik'); ?>',
              data: <?php echo $classjs; ?>,
              backgroundColor: '#4caf50' },
            { label: '<?php echo get_string('studentavg','local_yetkinlik'); ?>',
              data: <?php echo $studentjs; ?>,
              backgroundColor: '#2196f3' }
        ]
    },
    options: { scales: { y: { beginAtZero: true, max: 100 } } }
});
</script>

<?php
echo $OUTPUT->footer();