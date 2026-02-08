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
require_once($CFG->libdir.'/tcpdf/tcpdf.php');
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

global $DB;

/* Okul geneli */
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
WHERE quiza.state = 'finished'
GROUP BY c.id, c.shortname, c.description
";

$rows = $DB->get_records_sql($sql);

/* Yüzdeler */
$rates = [];
foreach ($rows as $r) {
    $rates[] = [
        'shortname'   => $r->shortname,
        'description' => strip_tags($r->description),
        'rate'        => $r->attempts ? number_format(($r->correct / $r->attempts) * 100, 1) : 0
    ];
}

/* AI yorum fonksiyonuna uygun format */
$stats = [];
foreach ($rates as $r) {
    $stats[$r['shortname']] = $r['rate'];
}

/* Yorum */
require_once(__DIR__.'/ai.php');
$comment = local_yetkinlik_generate_comment($stats, 'school');


/* PDF */
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('freeserif','',12);

$pdf->Cell(0,10,get_string('schoolpdfreport','local_yetkinlik'),0,1,'C');
$pdf->Ln(5);

/* Tablo başlıkları */
$pdf->SetFillColor(224,224,224);
$pdf->SetDrawColor(0,0,0);
$pdf->SetLineWidth(0.3);

$pdf->Cell(40,10,get_string('competencycode','local_yetkinlik'),1,0,'C',true);
$pdf->Cell(100,10,get_string('competency','local_yetkinlik'),1,0,'C',true);
$pdf->Cell(40,10,get_string('success','local_yetkinlik'),1,1,'C',true);

/* Tablo satırları */
foreach ($rates as $row) {
    if ($row['rate'] >= 70) {
        $pdf->SetFillColor(204,255,204); // yeşil
    } elseif ($row['rate'] >= 50) {
        $pdf->SetFillColor(255,243,205); // sarı
    } else {
        $pdf->SetFillColor(248,215,218); // kırmızı
    }

    $desc = $row['description'];
    $descHeight = $pdf->getStringHeight(100, $desc);
    $lineHeight = max(10, $descHeight);

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    $pdf->MultiCell(40, $lineHeight, $row['shortname'], 1, 'C', true, 0, $x, $y, true);
    $pdf->MultiCell(100, $lineHeight, $desc, 1, 'L', true, 0, $x+40, $y, true);
    $pdf->MultiCell(40, $lineHeight, '%'.$row['rate'], 1, 'C', true, 1, $x+140, $y, true);
}

$pdf->Ln(10);
$pdf->writeHTML("<b>".get_string('generalcomment','local_yetkinlik')."</b><br>$comment");

$pdf->Output("school_achievement_report.pdf","I");
exit;
