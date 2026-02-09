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
 * Detailed student competency report.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

global $DB, $USER, $OUTPUT, $PAGE;

$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

$context = context_course::instance($courseid);
require_capability('mod/quiz:viewreports', $context);

$PAGE->set_url('/local/yetkinlik/student_competency_detail.php', ['courseid' => $courseid, 'userid' => $userid]);
$PAGE->set_title(get_string('studentcompetencydetail', 'local_yetkinlik'));
$PAGE->set_heading(get_string('studentcompetencydetail', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

// Öğrenci bilgisi.
$student = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
echo html_writer::tag('h3', fullname($student));

// Kurs yetkinlikleri.
$competencies = $DB->get_records_sql("
    SELECT DISTINCT c.id, c.shortname, c.description
    FROM {local_yetkinlik_qmap} m
    JOIN {competency} c ON c.id = m.competencyid
    ORDER BY c.shortname
");

echo html_writer::start_tag('table', ['class' => 'generaltable']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', get_string('competencycode', 'local_yetkinlik'));
echo html_writer::tag('th', get_string('competency', 'local_yetkinlik'));
echo html_writer::tag('th', get_string('success', 'local_yetkinlik'));
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

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
        WHERE quiza.userid = :userid
          AND quiza.state = 'finished'
          AND m.competencyid = :competencyid
    ";
    $data = $DB->get_record_sql($sql, ['userid' => $userid, 'competencyid' => $c->id]);

    $ratecell = '';
    if ($data && $data->attempts) {
        $rate = number_format(($data->correct / $data->attempts) * 100, 1);

        if ($rate >= 80) {
            $color = 'green';
        } else if ($rate >= 60) {
            $color = 'blue';
        } else if ($rate >= 40) {
            $color = 'orange';
        } else {
            $color = 'red';
        }
        $ratecell = html_writer::tag('span', '%' . $rate, [
            'style' => "color: $color; font-weight: bold;"
        ]);
    }

    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', s($c->shortname));
    echo html_writer::tag('td', s($c->description));
    echo html_writer::tag('td', $ratecell);
    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();
