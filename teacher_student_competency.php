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
 * Teacher student competency report.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

require_login();

global $DB, $OUTPUT, $PAGE;

$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

$context = context_course::instance($courseid);
require_capability('mod/quiz:viewreports', $context);

$userid = optional_param('userid', 0, PARAM_INT);
$competencyid = optional_param('competencyid', 0, PARAM_INT);

$PAGE->set_url('/local/yetkinlik/teacher_student_competency.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('teacherstudentcompetency', 'local_yetkinlik'));
$PAGE->set_heading(get_string('teacherstudentcompetency', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');
$PAGE->set_context($context);

echo $OUTPUT->header();

/* Öğrenciler listesi. */
$students = get_enrolled_users($context);
$studentoptions = [0 => get_string('selectstudent', 'local_yetkinlik')];
foreach ($students as $s) {
    $studentoptions[$s->id] = fullname($s);
}

/* Yetkinlikler listesi. */
$competencies = $DB->get_records_sql("
    SELECT DISTINCT c.id, c.shortname
    FROM {local_yetkinlik_qmap} m
    JOIN {competency} c ON c.id = m.competencyid
    ORDER BY c.shortname
");

$compoptions = [0 => get_string('selectcompetency', 'local_yetkinlik')];
foreach ($competencies as $c) {
    $compoptions[$c->id] = $c->shortname;
}

/**
 * Form class for teacher report filters.
 */
class local_yetkinlik_teacher_form extends moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $studentoptions = $this->_customdata['studentoptions'];
        $compoptions = $this->_customdata['compoptions'];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('autocomplete', 'userid', get_string('selectstudent', 'local_yetkinlik'), $studentoptions);
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', 0);

        $mform->addElement('autocomplete', 'competencyid', get_string('selectcompetency', 'local_yetkinlik'), $compoptions);
        $mform->setType('competencyid', PARAM_INT);
        $mform->setDefault('competencyid', 0);

        $this->add_action_buttons(false, get_string('show', 'local_yetkinlik'));
    }
}

$mform = new local_yetkinlik_teacher_form(null, [
    'courseid'       => $courseid,
    'studentoptions' => $studentoptions,
    'compoptions'    => $compoptions,
]);

if ($data = $mform->get_data()) {
    $userid = $data->userid;
    $competencyid = $data->competencyid;
}

$mform->display();

if ($userid && $competencyid) {
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
        WHERE m.competencyid = :competencyid
          AND u.id = :userid
          AND quiz.course = :courseid
        GROUP BY quiz.id, quiz.name
        ORDER BY quiz.name
    ";

    $params = [
        'competencyid' => $competencyid,
        'userid'       => $userid,
        'courseid'     => $courseid,
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
            $color = ($rate >= 80) ? 'green' : (($rate >= 60) ? 'blue' : (($rate >= 40) ? 'orange' : 'red'));

            $totalq += $r->questions;
            $totalc += $r->correct;

            // Son bitmiş girişimi bulma.
            $lastattempt = $DB->get_record_sql("
                SELECT id
                FROM {quiz_attempts}
                WHERE quiz = :quizid AND userid = :userid AND state = 'finished'
                ORDER BY attempt DESC
                LIMIT 1
            ", ['quizid' => $r->quizid, 'userid' => $userid]);

            $link = $r->quizname;
            if ($lastattempt) {
                $url = new moodle_url('/mod/quiz/review.php', ['attempt' => $lastattempt->id]);
                $link = html_writer::link($url, $r->quizname, ['target' => '_blank']);
            }

            echo html_writer::start_tag('tr');
            echo html_writer::tag('td', $link);
            echo html_writer::tag('td', $r->questions);
            echo html_writer::tag('td', $r->correct);
            echo html_writer::tag('td', "%{$rate}", [
                'style' => "color: $color; font-weight: bold;",
            ]);
            echo html_writer::end_tag('tr');
        }

        $totalrate = $totalq ? number_format(($totalc / $totalq) * 100, 1) : 0;
        $tcolor = ($totalrate >= 80) ? 'green' : (($totalrate >= 60) ? 'blue' : (($totalrate >= 40) ? 'orange' : 'red'));

        echo html_writer::start_tag('tr', ['style' => 'font-weight: bold; background: #eee;']);
        echo html_writer::tag('td', get_string('total', 'local_yetkinlik'));
        echo html_writer::tag('td', $totalq);
        echo html_writer::tag('td', $totalc);
        echo html_writer::tag('td', "%{$totalrate}", ['style' => "color: $tcolor;"]);
        echo html_writer::end_tag('tr');

        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
    } else {
        echo $OUTPUT->notification(get_string('nodatastudentcompetency', 'local_yetkinlik'), 'info');
    }
}

echo $OUTPUT->footer();
