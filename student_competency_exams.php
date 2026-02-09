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
 * Report for student competency exams.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

global $DB, $USER, $OUTPUT, $PAGE;

$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

$context = context_course::instance($courseid);

$competencyid = optional_param('competencyid', 0, PARAM_INT);

$PAGE->set_url('/local/yetkinlik/student_competency_exams.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('studentcompetencyexams', 'local_yetkinlik'));
$PAGE->set_heading(get_string('studentcompetencyexams', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

// Get competencies associated with this course.
$competencies = $DB->get_records_sql("
    SELECT DISTINCT c.id, c.shortname, c.description
    FROM {local_yetkinlik_qmap} m
    JOIN {competency} c ON c.id = m.competencyid
    ORDER BY c.shortname
");

echo html_writer::start_tag('form', ['method' => 'get']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
echo html_writer::start_tag('select', ['name' => 'competencyid']);
echo html_writer::tag('option', get_string('selectcompetency', 'local_yetkinlik'), ['value' => '0']);
foreach ($competencies as $c) {
    $attributes = ['value' => $c->id];
    if ($competencyid == $c->id) {
        $attributes['selected'] = 'selected';
    }
    echo html_writer::tag('option', s($c->shortname), $attributes);
}
echo html_writer::end_tag('select');
echo ' ' . html_writer::tag('button', get_string('show', 'local_yetkinlik'), ['class' => 'btn btn-primary']);
echo html_writer::end_tag('form');
echo html_writer::tag('hr');

if ($competencyid) {
    // Safely fetch competency details.
    if ($competency = $DB->get_record('competency', ['id' => $competencyid])) {
        echo html_writer::tag('div', $competency->description, ['class' => 'competency-description mb-3 alert alert-info']);
    }

    $sql = "
        SELECT
            quiz.id AS quizid,
            quiz.name AS quizname,
            CAST(SUM(qa.maxfraction) AS DECIMAL(12, 1)) AS questions,
            CAST(SUM(qas.fraction) AS DECIMAL(12, 1)) AS correct
        FROM {quiz_attempts} quiza
        JOIN {user} u ON quiza.userid = u.id
        JOIN {question_usages} qu ON qu.id = quiza.uniqueid
        JOIN {question_attempts} qa ON qa.questionusageid = qu.id
        JOIN {quiz} quiz ON quiz.id = quiza.quiz
        JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
        JOIN (
            SELECT MAX(fraction) AS fraction, questionattemptid
            FROM {question_attempt_steps}
            GROUP BY questionattemptid
        ) qas ON qas.questionattemptid = qa.id
        WHERE quiz.course = :courseid
          AND m.competencyid = :competencyid
          AND u.id = :userid
        GROUP BY quiz.id, quiz.name
        ORDER BY quiz.id
    ";

    $params = [
        'courseid'     => $courseid,
        'competencyid' => $competencyid,
        'userid'       => $USER->id,
    ];

    $rows = $DB->get_records_sql($sql, $params);

    if ($rows) {
        echo html_writer::start_tag('table', ['class' => 'generaltable']);
        echo html_writer::start_tag('thead');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('th', get_string('quiz', 'local_yetkinlik'));
        echo html_writer::tag('th', get_string('question', 'local_yetkinlik'));
        echo html_writer::tag('th', get_string('correct', 'local_yetkinlik'));
        echo html_writer::tag('th', get_string('success', 'local_yetkinlik'));
        echo html_writer::end_tag('tr');
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');

        $totalq = 0;
        $totalc = 0;

        foreach ($rows as $r) {
            $rate = $r->questions ? number_format(($r->correct / $r->questions) * 100, 1) : 0;

            if ($rate >= 80) {
                $color = 'green';
            } else if ($rate >= 60) {
                $color = 'blue';
            } else if ($rate >= 40) {
                $color = 'orange';
            } else {
                $color = 'red';
            }

            $totalq += $r->questions;
            $totalc += $r->correct;

            // Find last finished attempt.
            $sqlattempt = "
                SELECT id
                FROM {quiz_attempts}
                WHERE quiz = :quizid AND userid = :userid AND state = 'finished'
                ORDER BY attempt DESC
                LIMIT 1
            ";
            $lastattempt = $DB->get_record_sql($sqlattempt, [
                'quizid' => $r->quizid,
                'userid' => $USER->id,
            ]);

            $link = s($r->quizname);
            if ($lastattempt) {
                $url = new moodle_url('/mod/quiz/review.php', ['attempt' => $lastattempt->id]);
                $link = html_writer::link($url, $r->quizname, ['target' => '_blank']);
            }

            echo html_writer::start_tag('tr');
            echo html_writer::tag('td', $link);
            echo html_writer::tag('td', $r->questions);
            echo html_writer::tag('td', $r->correct);
            echo html_writer::tag('td', '%' . $rate, [
                'style' => "color: $color; font-weight: bold;",
            ]);
            echo html_writer::end_tag('tr');
        }

        $totalrate = $totalq ? number_format(($totalc / $totalq) * 100, 1) : 0;

        if ($totalrate >= 80) {
            $tcolor = 'green';
        } else if ($totalrate >= 60) {
            $tcolor = 'blue';
        } else if ($totalrate >= 40) {
            $tcolor = 'orange';
        } else {
            $tcolor = 'red';
        }

        echo html_writer::start_tag('tr', ['style' => 'font-weight: bold; background: #eee;']);
        echo html_writer::tag('td', get_string('total', 'local_yetkinlik'));
        echo html_writer::tag('td', $totalq);
        echo html_writer::tag('td', $totalc);
        echo html_writer::tag('td', '%' . $totalrate, [
            'style' => "color: $tcolor;",
        ]);
        echo html_writer::end_tag('tr');

        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
    } else {
        echo html_writer::tag('p', get_string('nocompetencyexamdata', 'local_yetkinlik'), [
            'class' => 'alert alert-warning',
        ]);
    }
}

echo $OUTPUT->footer();
