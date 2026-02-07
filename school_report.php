<?php
require_once(__DIR__.'/../../config.php');
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url('/local/yetkinlik/school_report.php');
$PAGE->set_title('Okul Genel Kazanım Raporu');
$PAGE->set_heading('Okul Genel Kazanım Raporu');

echo $OUTPUT->header();
global $DB;

/* Tüm okul yetkinlik başarısı */
$sql = "
SELECT c.id, c.shortname,c.description,
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
WHERE quiza.state = 'finished'
GROUP BY c.id, c.shortname
";

$rows = $DB->get_records_sql($sql);

echo '<table class="generaltable">';
echo '<tr><th>Kazanım Kodu</th><th>Kazanım</th><th>Çözülen</th><th>Doğru</th><th>Başarı</th></tr>';

$labels=[]; $data=[];
foreach($rows as $r){
 $rate = $r->attempts?number_format(($r->correct/$r->attempts)*100,1):0;
 $labels[]=$r->shortname;
 $data[]=$rate;
 $color=$rate>=70?'green':($rate>=50?'orange':'red');
 echo "<tr><td>$r->shortname</td><td>$r->description</td><td>$r->attempts</td><td>$r->correct</td><td style='color:$color'>%$rate</td></tr>";
}
echo '</table>';

$labelsjs=json_encode($labels);
$datajs=json_encode($data);
?>

<canvas id="schoolchart"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('schoolchart'),{
 type:'bar',
 data:{labels:<?php echo $labelsjs;?>,datasets:[{label:'Okul Başarı %',data:<?php echo $datajs;?>,backgroundColor:'#673ab7'}]},
 options:{scales:{y:{beginAtZero:true,max:100}}}
});
</script>

<?php
echo $OUTPUT->footer();
