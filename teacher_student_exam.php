<?php
require_once(__DIR__.'/../../config.php');
require_login();

$quizid = required_param('quizid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$quiz = $DB->get_record('quiz',['id'=>$quizid],'*',MUST_EXIST);
$courseid = $quiz->course;

$context = context_course::instance($courseid);
require_capability('mod/quiz:viewreports', $context);

$student = $DB->get_record('user',['id'=>$userid],'*',MUST_EXIST);

$PAGE->set_url('/local/yetkinlik/teacher_student_exam.php',['quizid'=>$quizid,'userid'=>$userid]);
$PAGE->set_title('Öğrenci Sınav Analizi');
$PAGE->set_heading($student->firstname.' '.$student->lastname.' - '.$quiz->name);

echo $OUTPUT->header();
global $DB;

$sql="
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

$rows=$DB->get_records_sql($sql,['quizid'=>$quizid,'userid'=>$userid]);

echo '<table class="generaltable">';
echo '<tr><th>Kazanım</th><th>Başarı</th></tr>';

$labels=[]; $data=[];
foreach($rows as $r){
 $rate=$r->attempts?round($r->correct/$r->attempts*100):0;
 $labels[]=$r->shortname;
 $data[]=$rate;
 $color=$rate>=70?'green':($rate>=50?'orange':'red');
 echo "<tr><td>$r->shortname</td><td style='color:$color'>%$rate</td></tr>";
}
echo '</table>';

$labelsjs=json_encode($labels);
$datajs=json_encode($data);
?>

<canvas id="teacherchart"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('teacherchart'),{
 type:'bar',
 data:{labels:<?php echo $labelsjs;?>,datasets:[{label:'Başarı %',data:<?php echo $datajs;?>,backgroundColor:'#ff9800'}]},
 options:{scales:{y:{beginAtZero:true,max:100}}}
});
</script>

<?php
echo $OUTPUT->footer();
