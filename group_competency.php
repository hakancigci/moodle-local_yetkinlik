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
require_login($courseid);

$context = context_course::instance($courseid);

$groupid = optional_param('groupid', 0, PARAM_INT);

$PAGE->set_url('/local/yetkinlik/group_competency.php', ['courseid'=>$courseid]);
$PAGE->set_title(get_string('groupcompetency','local_yetkinlik'));
$PAGE->set_heading(get_string('groupcompetency','local_yetkinlik'));
$PAGE->set_pagelayout('course');
$PAGE->set_context($context);

echo $OUTPUT->header();

/* Kurs grupları */
$groups = groups_get_all_groups($courseid);
echo '<form method="get">';
echo '<input type="hidden" name="courseid" value="'.$courseid.'">';
echo '<select name="groupid">';
echo '<option value="0">'.get_string('selectgroup','local_yetkinlik').'</option>';
foreach ($groups as $g) {
    $sel = ($groupid == $g->id) ? 'selected' : '';
    echo "<option value='{$g->id}' $sel>{$g->name}</option>";
}
echo '</select> ';
echo '<button>'.get_string('show','local_yetkinlik').'</button>';
echo '</form><hr>';

if ($groupid) {
    // Grup öğrencilerini idnumber’a göre sırala (sadece student rolü olanlar)
    $students = $DB->get_records_sql("
        SELECT u.id, u.idnumber, u.firstname, u.lastname
        FROM {groups_members} gm
        JOIN {user} u ON u.id = gm.userid
        JOIN {role_assignments} ra ON ra.userid = u.id
        JOIN {context} ctx ON ctx.id = ra.contextid
        JOIN {course} c ON c.id = ctx.instanceid
        WHERE gm.groupid = :groupid
          AND c.id = :courseid
          AND ra.roleid = (SELECT id FROM {role} WHERE shortname = 'student')
        ORDER BY u.idnumber ASC
    ", ['groupid'=>$groupid, 'courseid'=>$courseid]);

    // Kurs yetkinliklerini çek
    $competencies = $DB->get_records_sql("
        SELECT DISTINCT c.id, c.shortname
        FROM {local_yetkinlik_qmap} m
        JOIN {competency} c ON c.id = m.competencyid
        ORDER BY c.shortname
    ");

    // Tablo başlıkları
    echo '<table class="generaltable">';
    echo '<tr><th>'.get_string('student','local_yetkinlik').'</th>';
    foreach ($competencies as $c) {
        echo "<th>{$c->shortname}</th>";
    }
    echo '</tr>';

    // Grup toplamları için hazırlık
    $groupTotals = [];
    foreach ($competencies as $c) {
        $groupTotals[$c->id] = ['attempts'=>0,'correct'=>0];
    }

    // Her öğrenci için yetkinlik başarıları
    foreach ($students as $s) {
        // Öğrenci adı link olacak
        $url = new moodle_url('/local/yetkinlik/student_competency_detail.php', [
            'courseid' => $courseid,
            'userid'   => $s->id
        ]);
        $studentlink = html_writer::link($url, fullname($s), ['target' => '_blank']);

        echo "<tr><td>$studentlink</td>";
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
            $data = $DB->get_record_sql($sql, [
                'userid' => $s->id,
                'competencyid' => $c->id
            ]);

            if ($data && $data->attempts) {
                $rate = number_format(($data->correct / $data->attempts) * 100,1);

                if ($rate >= 80) { $color = 'green'; }
                elseif ($rate >= 60) { $color = 'blue'; }
                elseif ($rate >= 40) { $color = 'orange'; }
                else { $color = 'red'; }

                echo "<td style='color:$color;font-weight:bold'>%$rate</td>";

                $groupTotals[$c->id]['attempts'] += $data->attempts;
                $groupTotals[$c->id]['correct']  += $data->correct;
            } else {
                echo "<td></td>"; // girişim yoksa boş hücre
            }
        }
        echo '</tr>';
    }

    // Grup ortalama satırı
    echo "<tr style='font-weight:bold;background:#eee'><td>".get_string('total','local_yetkinlik')."</td>";
    foreach ($competencies as $c) {
        $attempts = $groupTotals[$c->id]['attempts'];
        $correct  = $groupTotals[$c->id]['correct'];
        $rate = ($attempts) ? number_format(($correct / $attempts) * 100,1) : '';

        if ($rate !== '') {
            if ($rate >= 80) { $color = 'green'; }
            elseif ($rate >= 60) { $color = 'blue'; }
            elseif ($rate >= 40) { $color = 'orange'; }
            else { $color = 'red'; }

            echo "<td style='color:$color'>%$rate</td>";
        } else {
            echo "<td></td>";
        }
    }
    echo '</tr>';

    echo '</table>';
}

echo $OUTPUT->footer();
