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
 * PDF report for competency analysis.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tcpdf/tcpdf.php');
require_once(__DIR__ . '/ai.php');

// Page parameters.
$courseid = required_param('courseid', PARAM_INT);

// Security and capability checks.
require_login();
$context = context_course::instance($courseid);
require_capability('moodle/course:view', $context);

global $DB, $CFG;

// Fetch course information.
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

// SQL query to fetch competency achievement data.
$sql = "
    SELECT c.id, c.shortname, c.description,
           CAST(SUM(qa.maxfraction) AS DECIMAL(12, 1)) AS attempts,
           CAST(SUM(qas.fraction) AS DECIMAL(12, 1)) AS correct
    FROM {quiz_attempts} quiza
    JOIN {quiz} quiz ON quiz.id = quiza.quiz
    JOIN {question_usages} qu ON qu.id = quiza.uniqueid
    JOIN {question_attempts} qa ON qa.questionusageid = qu.id
    JOIN {qbank_yetkinlik_qmap} m ON m.questionid = qa.questionid
    JOIN {competency} c ON c.id = m.competencyid
    JOIN (
        SELECT MAX(fraction) AS fraction, questionattemptid
        FROM {question_attempt_steps}
        GROUP BY questionattemptid
    ) qas ON qas.questionattemptid = qa.id
    WHERE quiz.course = :courseid AND quiza.state = 'finished'
    GROUP BY c.id, c.shortname, c.description
    ORDER BY c.shortname ASC
";

$rows = $DB->get_records_sql($sql, ['courseid' => $courseid]);
$rates = [];

// Calculate success rates for AI processing.
foreach ($rows as $r) {
    $rate = $r->attempts ? ($r->correct / $r->attempts) * 100 : 0;
    $rates[$r->shortname] = number_format($rate, 1);
}

// Generate AI comment using the local function.
$comment = local_yetkinlik_generate_comment($rates);

/* --- PDF Generation --- */

// Initialize TCPDF.
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information.
$pdf->SetCreator('Moodle - local_yetkinlik');
$pdf->SetAuthor('Hakan Çiğci');
$pdf->SetTitle(get_string('report_title', 'local_yetkinlik'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);

// Set margins and auto page break.
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

// Add first page.
$pdf->AddPage();

// Set font for Unicode support (Turkish etc.).
$pdf->SetFont('freeserif', '', 12);

// Report Header.
$reporttitle = get_string('report_title_course', 'local_yetkinlik', $course->fullname);
$pdf->SetFont('freeserif', 'B', 16);
$pdf->Cell(0, 10, $reporttitle, 0, 1, 'C');

$pdf->SetFont('freeserif', '', 9);
$dateinfo = get_string('creation_date', 'local_yetkinlik') . ": " . userdate(time(), get_string('strftimedatetimeshort', 'langconfig'));
$pdf->Cell(0, 5, $dateinfo, 0, 1, 'R');
$pdf->Ln(5);

// Build HTML table.
$html = '
<table border="0.5" cellpadding="6" style="width: 100%;">
    <thead>
        <tr style="background-color: #eeeeee; font-weight: bold; text-align: center;">
            <th width="20%">' . get_string('competency', 'local_yetkinlik') . '</th>
            <th width="40%">' . get_string('description', 'local_yetkinlik') . '</th>
            <th width="12%">' . get_string('attempts', 'local_yetkinlik') . '</th>
            <th width="12%">' . get_string('correct', 'local_yetkinlik') . '</th>
            <th width="16%">' . get_string('success_rate', 'local_yetkinlik') . '</th>
        </tr>
    </thead>
    <tbody>';

if (!empty($rows)) {
    foreach ($rows as $r) {
        $rate = $r->attempts ? ($r->correct / $r->attempts) * 100 : 0;
        
        // Background color logic based on performance.
        $bgcolor = $rate >= 70 ? '#e6ffec' : ($rate >= 50 ? '#fff9e6' : '#ffe6e6');
        
        // Clean description for PDF output.
        $cleandesc = html_entity_decode(strip_tags($r->description), ENT_QUOTES, 'UTF-8');

        $html .= '
        <tr bgcolor="' . $bgcolor . '">
            <td width="20%">' . s($r->shortname) . '</td>
            <td width="40%">' . $cleandesc . '</td>
            <td width="12%" align="center">' . $r->attempts . '</td>
            <td width="12%" align="center">' . $r->correct . '</td>
            <td width="16%" align="center"><b>%' . number_format($rate, 1) . '</b></td>
        </tr>';
    }
} else {
    $html .= '<tr><td colspan="5" align="center">' . get_string('no_data_found', 'local_yetkinlik') . '</td></tr>';
}

$html .= '</tbody></table>';

// Write the table.
$pdf->writeHTML($html, true, false, true, false, '');

// AI Feedback Section.
if (!empty($comment)) {
    $pdf->Ln(10);
    $pdf->SetFont('freeserif', 'B', 12);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(0, 10, " " . get_string('ai_analysis', 'local_yetkinlik'), 0, 1, 'L', true);
    
    $pdf->Ln(2);
    $pdf->SetFont('freeserif', '', 11);
    
    // Clean and decode comment.
    $cleancomment = html_entity_decode(strip_tags($comment), ENT_QUOTES, 'UTF-8');
    $pdf->MultiCell(0, 7, $cleancomment, 0, 'L', false, 1);
}

// Close and output PDF.
$filename = "competency_report_" . $courseid . ".pdf";
$pdf->Output($filename, "I");
exit;
