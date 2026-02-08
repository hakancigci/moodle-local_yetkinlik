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
