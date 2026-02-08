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

$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

$context = context_course::instance($courseid);
$userid = $USER->id;

$course = $DB->get_record('course', ['id'=>$courseid], '*', MUST_EXIST);

$PAGE->set_url('/local/yetkinlik/student_report.php', ['courseid'=>$courseid]);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('studentreport','local_yetkinlik'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
global $DB;

/* Öğrenci verisi */
$sql = "
SELECT
  c.shortname,
  c.description,
  CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS questions,
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
WHERE quiz.course = :courseid AND u.id = :userid
GROUP BY c.shortname, c.description
";

$rows = $DB->get_records_sql($sql, ['courseid'=>$courseid,'userid'=>$userid]);

echo '<table class="generaltable">';
echo '<tr>
        <th>'.get_string('competencycode','local_yetkinlik').'</th>
        <th>'.get_string('competency','local_yetkinlik').'</th>
        <th>'.get_string('questioncount','local_yetkinlik').'</th>
        <th>'.get_string('correctcount','local_yetkinlik').'</th>
        <th>'.get_string('successrate','local_yetkinlik').'</th>
      </tr>';

$rates = [];

foreach ($rows as $r) {
    $rate = $r->questions ? number_format(($r->correct / $r->questions) * 100,1) : 0;
    $rates[$r->shortname] = $rate;

    if ($rate >= 80) { $color = 'green'; }
    elseif ($rate >= 60) { $color = 'blue'; }
    elseif ($rate >= 40) { $color = 'orange'; }
    else { $color = 'red'; }

    echo "<tr>
        <td>$r->shortname</td>
        <td>$r->description</td>
        <td>{$r->questions}</td>
        <td>{$r->correct}</td>
        <td style='color:$color;font-weight:bold'>%$rate</td>
    </tr>";
}
echo '</table>';

/* PDF raporu butonu */
echo '<div style="margin-top:20px;">
<a class="btn btn-secondary" target="_blank" 
href="'.$CFG->wwwroot.'/local/yetkinlik/parent_pdf.php?courseid='.$courseid.'">'
. get_string('pdfmystudent','local_yetkinlik') . '
</a>
</div>';

/* Yorum kısmı parent_pdf.php uyumlu */
require_once(__DIR__.'/ai.php');
echo "<h3>".get_string('generalcomment','local_yetkinlik')."</h3>";
echo local_yetkinlik_generate_comment($rates, 'student');

echo $OUTPUT->footer();
