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

global $DB, $USER, $OUTPUT, $PAGE;

$courseid = required_param('courseid', PARAM_INT);
$userid   = required_param('userid', PARAM_INT);

$context = context_course::instance($courseid);
require_capability('mod/quiz:viewreports', $context);

$PAGE->set_url('/local/yetkinlik/student_competency_detail.php', ['courseid'=>$courseid,'userid'=>$userid]);
$PAGE->set_title(get_string('studentcompetencydetail','local_yetkinlik'));
$PAGE->set_heading(get_string('studentcompetencydetail','local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

// Öğrenci bilgisi
$student = $DB->get_record('user', ['id'=>$userid], '*', MUST_EXIST);
echo "<h3>".fullname($student)."</h3>";

// Kurs yetkinlikleri
$competencies = $DB->get_records_sql("
    SELECT DISTINCT c.id, c.shortname, c.description
    FROM {local_yetkinlik_qmap} m
    JOIN {competency} c ON c.id = m.competencyid
    ORDER BY c.shortname
");

echo '<table class="generaltable">';
echo '<tr><th>'.get_string('competencycode','local_yetkinlik').'</th>
          <th>'.get_string('competency','local_yetkinlik').'</th>
          <th>'.get_string('success','local_yetkinlik').'</th></tr>';

foreach ($competencies as $c) {
    $sql = "
        SELECT SUM(qa.maxfraction) AS attempts, SUM(qas.fraction) AS correct
        FROM {quiz_attempts} quiza
        JOIN {question_usages} qu ON qu.id = quiza.uniqueid
        JOIN {question_attempts} qa ON qa.questionusageid = qu.id
        JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
        JOIN (
            SELECT MAX(fraction) AS fraction, questionattemptid
            FROM {question_attempt_steps}
            GROUP BY questionattemptid
        ) qas ON qas.questionattemptid = qa.id
        WHERE quiza.userid = :userid AND quiza.state = 'finished'
          AND m.competencyid = :competencyid
    ";
    $data = $DB->get_record_sql($sql, ['userid'=>$userid,'competencyid'=>$c->id]);

    if ($data && $data->attempts) {
        $rate = number_format(($data->correct / $data->attempts) * 100,1);

        if ($rate >= 80) { $color = 'green'; }
        elseif ($rate >= 60) { $color = 'blue'; }
        elseif ($rate >= 40) { $color = 'orange'; }
        else { $color = 'red'; }

        echo "<tr>
                <td>{$c->shortname}</td>
                <td>{$c->description}</td>
                <td style='color:$color;font-weight:bold'>%$rate</td>
              </tr>";
    } else {
        echo "<tr>
                <td>{$c->shortname}</td>
                <td>{$c->description}</td>
                <td></td> <!-- girişim yoksa boş hücre -->
              </tr>";
    }
}
echo '</table>';

echo $OUTPUT->footer();
