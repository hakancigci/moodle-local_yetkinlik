<?php
require_once(__DIR__.'/../../config.php');
require_login();

global $DB, $USER, $OUTPUT, $PAGE;

$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

$context = context_course::instance($courseid);

$competencyid = optional_param('competencyid', 0, PARAM_INT);

$PAGE->set_url('/local/yetkinlik/student_competency_exams.php', ['courseid'=>$courseid]);
$PAGE->set_title(get_string('studentcompetencyexams','local_yetkinlik'));
$PAGE->set_heading(get_string('studentcompetencyexams','local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

/* Öğrencinin bu derste sahip olduğu yetkinlikler */
$competencies = $DB->get_records_sql("
SELECT DISTINCT c.id, c.shortname,c.description
FROM {local_yetkinlik_qmap} m
JOIN {competency} c ON c.id = m.competencyid
ORDER BY c.shortname
");

echo '<form method="get">';
echo '<input type="hidden" name="courseid" value="'.$courseid.'">';
echo '<select name="competencyid">';
echo '<option value="0">'.get_string('selectcompetency','local_yetkinlik').'</option>';
foreach ($competencies as $c) {
    $sel = ($competencyid == $c->id) ? 'selected' : '';
    echo "<option value='{$c->id}' $sel>{$c->shortname}</option>";
}
echo '</select> ';
echo '<button>'.get_string('show','local_yetkinlik').'</button>';
echo '</form><hr>';

$yetkinliksor=$DB->get_records_sql("
SELECT c.description
FROM {competency} c
WHERE c.id=$competencyid
");
foreach ($yetkinliksor as $yetkinlikadi) {
    echo $yetkinlikadi->description;
}

if ($competencyid) {
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
    
    WHERE quiz.course = $courseid
      AND m.competencyid = $competencyid
      AND u.id = $USER->id
    
    GROUP BY quiza.userid, quiz.id
    ORDER BY quiz.id
    ";
   
    $rows = $DB->get_records_sql($sql, [$competencyid, $USER->id, $courseid]);

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
            $rate = $r->questions ? number_format(($r->correct / $r->questions)*100, 1)  : 0;

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
            ", ['quizid' => $r->quizid, 'userid' => $USER->id]);

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

        if ($totalrate >= 80) { $tcolor = 'green'; }
        elseif ($totalrate >= 60) { $tcolor = 'blue'; }
        elseif ($totalrate >= 40) { $tcolor = 'orange'; }
        else { $tcolor = 'red'; }

        echo "<tr style='font-weight:bold;background:#eee'>
            <td>".get_string('total','local_yetkinlik')."</td>
            <td>$totalq</td>
            <td>$totalc</td>
            <td style='color:$tcolor'>%$totalrate</td>
        </tr>";

        echo '</table>';

    } else {
        echo '<p>'.get_string('nocompetencyexamdata','local_yetkinlik').'</p>';
    }
}

echo $OUTPUT->footer();