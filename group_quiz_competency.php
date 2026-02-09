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
 * Group and Quiz based competency report.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$groupid  = optional_param('groupid', 0, PARAM_INT);
$quizid   = optional_param('quizid', 0, PARAM_INT);

require_login($courseid);

global $DB, $USER, $OUTPUT, $PAGE;

$context = context_course::instance($courseid);
require_capability('mod/quiz:viewreports', $context);

$PAGE->set_url('/local/yetkinlik/group_quiz_competency.php', [
    'courseid' => $courseid,
    'groupid'  => $groupid,
    'quizid'   => $quizid,
]);
$PAGE->set_title(get_string('groupquizcompetency', 'local_yetkinlik'));
$PAGE->set_heading(get_string('groupquizcompetency', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');
$PAGE->set_context($context);

echo $OUTPUT->header();

/* Kurs grupları */
$groups = groups_get_all_groups($courseid);

/* Kurs sınavları */
$quizzes = $DB->get_records('quiz', ['course' => $courseid], 'name ASC');

// Form başlangıcı
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline mb-4']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);

/* Grup seçimi */
$groupoptions = [0 => get_string('selectgroup', 'local_yetkinlik')];
foreach ($groups as $g) {
    $groupoptions[$g->id] = format_string($g->name);
}
echo html_writer::select($groupoptions, 'groupid', $groupid, false, ['class' => 'form-control mr-2']);

/* Sınav seçimi */
$quizoptions = [0 => get_string('selectquiz', 'local_yetkinlik')];
foreach ($quizzes as $q) {
    $quizoptions[$q->id] = format_string($q->name);
}
echo html_writer::select($quizoptions, 'quizid', $quizid, false, ['class' => 'form-control mr-2']);

echo html_writer::tag('button', get_string('show', 'local_yetkinlik'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');
echo html_writer::tag('hr', '');

if ($groupid && $quizid) {
    // SQL SORGULARI DEĞİŞTİRİLMEDİ
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
    ", ['groupid' => $groupid, 'courseid' => $courseid]);

    $competencies = $DB->get_records_sql("
        SELECT DISTINCT c.id, c.shortname, c.description
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
        WHERE quiz.id = :quizid
        ORDER BY c.shortname
    ", ['quizid' => $quizid]);

    if ($competencies) {
        echo html_writer::start_tag('table', ['class' => 'generaltable mt-3']);
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('student', 'local_yetkinlik'));
        foreach ($competencies as $c) {
            echo html_writer::tag('th', s($c->shortname));
        }
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');

        $groupTotals = [];
        foreach ($competencies as $c) {
            $groupTotals[$c->id] = ['attempts' => 0, 'correct' => 0];
        }

        foreach ($students as $s) {
            // Öğrenci detayı için link (userid ve courseid ile)
            $studenturl = new moodle_url('/local/yetkinlik/student_competency_detail.php', [
                'courseid' => $courseid,
                'userid'   => $s->id,
            ]);
            
            echo html_writer::start_tag('tr');
            echo html_writer::tag('td', html_writer::link($studenturl, fullname($s)));

            foreach ($competencies as $c) {
                // SQL SORGUSU DEĞİŞTİRİLMEDİ
                $sql = "
                    SELECT SUM(qa.maxfraction) AS attempts, SUM(qas.fraction) AS correct
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
                    WHERE quiz.id = :quizid AND u.id = :userid AND m.competencyid = :competencyid
                    GROUP BY c.shortname
                ";
                $data = $DB->get_record_sql($sql, [
                    'quizid' => $quizid,
                    'userid' => $s->id,
                    'competencyid' => $c->id,
                ]);

                if ($data && $data->attempts) {
                    $rate = number_format(($data->correct / $data->attempts) * 100, 1);

                    if ($rate >= 80) { $color = 'green'; }
                    elseif ($rate >= 60) { $color = 'blue'; }
                    elseif ($rate >= 40) { $color = 'orange'; }
                    else { $color = 'red'; }

                    echo html_writer::tag('td', "%$rate", [
                        'style' => "color: $color; font-weight: bold;",
                    ]);

                    $groupTotals[$c->id]['attempts'] += $data->attempts;
                    $groupTotals[$c->id]['correct']  += $data->correct;
                } else {
                    echo html_writer::tag('td', '-');
                }
            }
            echo html_writer::end_tag('tr');
        }

        // Grup toplam satırı
        echo html_writer::start_tag('tr', ['style' => 'font-weight: bold; background: #eee;']);
        echo html_writer::tag('td', get_string('total', 'local_yetkinlik'));
        foreach ($competencies as $c) {
            $attempts = $groupTotals[$c->id]['attempts'];
            $correct  = $groupTotals[$c->id]['correct'];
            $rate = ($attempts) ? number_format(($correct / $attempts) * 100, 1) : '';

            if ($rate !== '') {
                if ($rate >= 80) { $color = 'green'; }
                elseif ($rate >= 60) { $color = 'blue'; }
                elseif ($rate >= 40) { $color = 'orange'; }
                else { $color = 'red'; }
                echo html_writer::tag('td', "%$rate", ['style' => "color: $color;"]);
            } else {
                echo html_writer::tag('td', '-');
            }
        }
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
    } else {
        echo $OUTPUT->notification(get_string('noexamdata', 'local_yetkinlik'), 'info');
    }
}

echo $OUTPUT->footer();
