<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tcpdf/tcpdf.php');

require_login();
$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
require_capability('moodle/course:view', $context);

global $DB;

// Aynı başarı SQL
$sql = "
SELECT c.id, c.shortname, c.description,
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
WHERE quiz.course = :courseid
GROUP BY c.shortname
ORDER BY c.shortname
";

$rows = $DB->get_records_sql($sql, ['courseid' => $courseid]);
$rates = [];   // ← bu mutlaka burada olmalı

foreach ($rows as $r) {
    $rate = $r->attempts ? number_format(($r->correct / $r->attempts) * 100,1) : 0;
    $rates[$r->shortname] = $rate;
}

require_once(__DIR__.'/ai.php');
$comment = local_yetkinlik_generate_comment($rates);
$course = $DB->get_record('course', ['id' => $courseid]);

$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('freeserif','',12);

$pdf->Cell(0,10,"$course->fullname - Kazanım Raporu",0,1);
$pdf->Ln(5);

$html = "<table border='1' cellpadding='6'>
<tr><th>Kazanım</th><th>Çözülen</th><th>Doğru</th><th>Başarı</th></tr>";

foreach ($rows as $r) {
    $rate = $r->attempts ? number_format(($r->correct / $r->attempts) * 100,1) : 0;
    $color = $rate >= 70 ? '#ccffcc' : ($rate >= 50 ? '#fff3cd' : '#f8d7da');

    $html .= "<tr style='background-color:$color'>
        <td>$r->shortname</td>
        <td>$r->attempts</td>
        <td>$r->correct</td>
        <td>%$rate</td>
    </tr>";
}

$html .= "</table>";
$pdf->Ln(10);


$pdf->writeHTML($html);
$pdf->writeHTML("<b>Yorum:</b><br>".$comment);


$pdf->Output("kazanım_raporu.pdf", "I");
exit;
