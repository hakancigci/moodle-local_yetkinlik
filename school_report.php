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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Competency success report page.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/ai.php');

require_login();

$courseid = optional_param('courseid', 0, PARAM_INT);
global $DB, $OUTPUT, $PAGE;

if ($courseid) {
    $context = context_course::instance($courseid);
    require_capability('moodle/course:view', $context);
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $reporttitle = get_string('report_title', 'local_yetkinlik', $course->fullname);
    $wheresql = "WHERE quiz.course = :courseid AND quiza.state = 'finished'";
    $params = ['courseid' => $courseid];
    $url = new moodle_url('/local/yetkinlik/school_report.php', ['courseid' => $courseid]);
} else {
    $context = context_system::instance();
    require_capability('moodle/site:config', $context);
    $reporttitle = get_string('report_title', 'local_yetkinlik');
    $wheresql = "WHERE quiza.state = 'finished'";
    $params = [];
    $url = new moodle_url('/local/yetkinlik/school_report.php');
}

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title($reporttitle);
$PAGE->set_heading($reporttitle);

echo $OUTPUT->header();
echo $OUTPUT->heading($reporttitle);

// SQL Sorgusu.
$sql = "SELECT c.id, c.shortname, c.description,
               CAST(SUM(qa.maxfraction) AS DECIMAL(12, 1)) AS attempts,
               CAST(SUM(qas.fraction) AS DECIMAL(12, 1)) AS correct
        FROM {quiz_attempts} quiza
        JOIN {quiz} quiz ON quiz.id = quiza.quiz
        JOIN {question_usages} qu ON qu.id = quiza.uniqueid
        JOIN {question_attempts} qa ON qa.questionusageid = qu.id
        JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
        JOIN {competency} c ON c.id = m.competencyid
        JOIN (
            SELECT MAX(fraction) AS fraction, questionattemptid
            FROM {question_attempt_steps}
            GROUP BY questionattemptid
        ) qas ON qas.questionattemptid = qa.id
        $wheresql
        GROUP BY c.id, c.shortname, c.description
        ORDER BY c.shortname ASC";

$rows = $DB->get_records_sql($sql, $params);
$rates = [];

if ($rows) {
    $table = new html_table();
    $table->head = [
        get_string('competencycode', 'local_yetkinlik'),
        get_string('competencyname', 'local_yetkinlik'),
        get_string('questioncount', 'local_yetkinlik'),
        get_string('correctcount', 'local_yetkinlik'),
        get_string('successrate', 'local_yetkinlik'),
    ];

    foreach ($rows as $r) {
        $rate = $r->attempts ? number_format(($r->correct / $r->attempts) * 100, 1) : 0;
        $rates[$r->shortname] = $rate;
        $rowclass = ($rate >= 70) ? 'table-success' : (($rate >= 50) ? 'table-warning' : 'table-danger');

        $row = new html_table_row([
            '<strong>' . $r->shortname . '</strong>',
            format_text($r->description, FORMAT_HTML),
            $r->attempts,
            $r->correct,
            '<strong>%' . $rate . '</strong>',
        ]);
        $row->attributes['class'] = $rowclass;
        $table->data[] = $row;
    }

    $comment = local_yetkinlik_generate_comment($rates);
    if (!empty($comment)) {
        echo $OUTPUT->box_start('generalbox mb-4');
        $aicaption = '<h4 class="text-info"><i class="fa fa-magic"></i> ' .
            get_string('generalcomment', 'local_yetkinlik') . '</h4>';
        echo $aicaption;
        echo format_text($comment, FORMAT_HTML);
        echo $OUTPUT->box_end();
    }

    echo html_writer::table($table);

    $pdfurl = new moodle_url('/local/yetkinlik/school_pdf.php', ['courseid' => $courseid]);
    $pdflink = html_writer::link(
        $pdfurl,
        '<i class="fa fa-file-pdf-o"></i> ' . get_string('schoolpdf', 'local_yetkinlik'),
        ['class' => 'btn btn-secondary']
    );
    echo html_writer::div($pdflink, 'text-right');
} else {
    echo $OUTPUT->notification(get_string('no_data_found', 'local_yetkinlik'), 'info');
}

echo $OUTPUT->footer();
