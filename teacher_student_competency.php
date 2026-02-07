<?php
require_once(__DIR__.'/../../config.php');
require_once("$CFG->libdir/formslib.php");
require_login();

global $DB, $OUTPUT, $PAGE;

$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

$context = context_course::instance($courseid);
require_capability('mod/quiz:viewreports', $context);

$userid       = optional_param('userid', 0, PARAM_INT);
$competencyid = optional_param('competencyid', 0, PARAM_INT);

$PAGE->set_url('/local/yetkinlik/teacher_student_competency.php', ['courseid'=>$courseid]);
$PAGE->set_title(get_string('teacherstudentcompetency','local_yetkinlik'));
$PAGE->set_heading(get_string('teacherstudentcompetency','local_yetkinlik'));
$PAGE->set_pagelayout('course');
$PAGE->set_context($context);

echo $OUTPUT->header();

/* Öğrenciler */
$students = get_enrolled_users($context);
$studentoptions = [0 => get_string('selectstudent','local_yetkinlik')];
foreach ($students as $s) {
    $studentoptions[$s->id] = fullname($s);
}

/* Yetkinlikler */
$competencies = $DB->get_records_sql("
    SELECT DISTINCT c.id, c.shortname
    FROM {local_yetkinlik_qmap} m
    JOIN {competency} c ON c.id = m.competencyid
    ORDER BY c.shortname
");
$compoptions = [0 => get_string('selectcompetency','local_yetkinlik')];
foreach ($competencies as $c) {
    $compoptions[$c->id] = $c->shortname;
}

/* Form API */
class local_yetkinlik_teacher_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $studentoptions = $this->_customdata['studentoptions'];
        $compoptions    = $this->_customdata['compoptions'];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('autocomplete', 'userid', get_string('selectstudent','local_yetkinlik'), $studentoptions);
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', 0);

        $mform->addElement('autocomplete', 'competencyid', get_string('selectcompetency','local_yetkinlik'), $compoptions);
        $mform->setType('competencyid', PARAM_INT);
        $mform->setDefault('competencyid', 0);

        $this->add_action_buttons(false, get_string('show','local_yetkinlik'));
    }
}

$mform = new local_yetkinlik_teacher_form(null, [
    'courseid'      => $courseid,
    'studentoptions'=> $studentoptions,
    'compoptions'   => $compoptions
]);

if ($mform->is_submitted() && $mform->is_validated()) {
    $data        = $mform->get_data();
    $userid      = $data->userid;
    $competencyid= $data->competencyid;
}
$mform->display();

if ($userid && $competencyid) {
    $sql = "
      SELECT
      quiz.id AS quizid,
      quiz.name AS quizname,
      CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS questions,
      CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
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
    WHERE m.competencyid = ? AND u.id = ? AND quiz.course = ?
    GROUP BY quiz.id, quiz.name
    ORDER BY quiz.name
    ";

    $rows = $DB->get_records_sql($sql, [$competencyid, $userid, $courseid]);

    if ($rows) {
        echo '<table class="generaltable">';
        echo '<tr>
                <th>'.get_string('quiz','local_yetkinlik').'</th>
                <th>'.get_string('question','local_yetkinlik').'</th>
                <th>'.get_string('correct','local_yetkinlik').'</th>
                <th>'.get_string('success','local_yetkinlik').'</th>
              </tr>';

        $totalq = 0;
        $totalc = 0;

        foreach ($rows as $r) {
            $rate = $r->questions ? number_format(($r->correct / $r->questions) * 100,1) : 0;

            if ($rate >= 80) { $color = 'green'; }
            elseif ($rate >= 60) { $color = 'blue'; }
            elseif ($rate >= 40) { $color = 'orange'; }
            else { $color = 'red'; }

            $totalq += $r->questions;
            $totalc += $r->correct;

            // Son girişimi bul
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

            echo "<tr>
                <td>$link</td>
                <td>{$r->questions}</td>
                <td>{$r->correct}</td>
                <td style='color:$color;font-weight:bold'>%$rate</td>
            </tr>";
        }

        $totalrate = $totalq ? number_format(($totalc / $totalq) * 100,1) : 0;
        $tcolor = ($totalrate >= 80) ? 'green' : (($totalrate >= 60) ? 'blue' : (($totalrate >= 40) ? 'orange' : 'red'));

        echo "<tr style='font-weight:bold;background:#eee'>
            <td>".get_string('total','local_yetkinlik')."</td>
            <td>$totalq</td>
            <td>$totalc</td>
            <td style='color:$tcolor'>%$totalrate</td>
        </tr>";

        echo '</table>';
    } else {
        echo '<p>'.get_string('nodatastudentcompetency','local_yetkinlik').'</p>';
    }
}

echo $OUTPUT->footer();