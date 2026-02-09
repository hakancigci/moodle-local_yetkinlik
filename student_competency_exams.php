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
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$competencyid = optional_param('competencyid', 0, PARAM_INT);

require_login($courseid);

global $DB, $USER, $OUTPUT, $PAGE;

$context = context_course::instance($courseid);

$PAGE->set_url('/local/yetkinlik/student_competency_exams.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('studentcompetencyexams', 'local_yetkinlik'));
$PAGE->set_heading(get_string('studentcompetencyexams', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

/* Öğrencinin bu derste sahip olduğu yetkinlikler */
$competencies = $DB->get_records_sql("
    SELECT DISTINCT c.id, c.shortname, c.description
    FROM {local_yetkinlik_qmap} m
    JOIN {competency} c ON c.id = m.competencyid
    ORDER BY c.shortname
");

// Formu başlat
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline mb-3', 'id' => 'competency_filter_form']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);

// Select menüsü için seçenekleri hazırla
$options = [0 => get_string('selectcompetency', 'local_yetkinlik')];
foreach ($competencies as $c) {
    $options[$c->id] = format_string($c->shortname);
}

// Select menüsünü oluştur (ID ekledik ki JavaScript ile yakalayalım)
echo html_writer::select($options, 'competencyid', $competencyid, false, [
    'id' => 'id_competency_select',
    'class' => 'form-control mr-2'
]);

echo html_writer::tag('button', get_string('show', 'local_yetkinlik'), [
    'type' => 'submit',
    'class' => 'btn btn-primary'
]);
echo html_writer::end_tag('form');

// Autocomplete özelliğini aktifleştiren JavaScript (AMD)
$PAGE->requires->js_call_amd('core/form-autocomplete', 'enhance', [
    '#id_competency_select', // Seçici
    false,                   // Çoklu seçim kapalı
    false,                   // Yeni giriş ekleme kapalı
    get_string('selectcompetency', 'local_yetkinlik'), // Placeholder
]);

echo html_writer::empty_tag('hr');

if ($competencyid) {
    // Yetkinlik açıklamasını güvenli bir şekilde çekelim.
    if ($competency = $DB->get_record('competency', ['id' => $competencyid])) {
        // HTML etiketlerini temizleyip gösterelim
        $cleandesc = strip_tags(html_entity_decode($competency->description, ENT_QUOTES, 'UTF-8'));
        echo html_writer::tag('div', $cleandesc, [
            'class' => 'alert alert-info competency-description mb-3'
        ]);
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
          AND quiza.state = 'finished'
        GROUP BY quiz.id, quiz.name
        ORDER BY quiz.id
    ";

    $params = [
        'courseid'     => $courseid,
        'competencyid' => $competencyid,
        'userid'       => $USER->id
    ];

    $rows = $DB->get_records_sql($sql, $params);

    if ($rows) {
        $table = new html_table();
        $table->head = [
            get_string('quiz', 'local_yetkinlik'),
            get_string('question', 'local_yetkinlik'),
            get_string('correct', 'local_yetkinlik'),
            get_string('success', 'local_yetkinlik'),
        ];
        $table->attributes['class'] = 'generaltable mt-3';

        $totalq = 0;
        $totalc = 0;

        foreach ($rows as $r) {
            $rate = $r->questions ? number_format(($r->correct / $r->questions) * 100, 1) : 0;

            if ($rate >= 80) { $color = 'text-success'; }
            elseif ($rate >= 60) { $color = 'text-primary'; }
            elseif ($rate >= 40) { $color = 'text-warning'; }
            else { $color = 'text-danger'; }

            $totalq += $r->questions;
            $totalc += $r->correct;

            // Son girişimi bulma mantığı korundu
            $lastattempt = $DB->get_record_sql("
                SELECT id
                FROM {quiz_attempts}
                WHERE quiz = :quizid AND userid = :userid AND state = 'finished'
                ORDER BY attempt DESC
                LIMIT 1
            ", ['quizid' => $r->quizid, 'userid' => $USER->id]);

            $link = s($r->quizname);
            if ($lastattempt) {
                $url = new moodle_url('/mod/quiz/review.php', ['attempt' => $lastattempt->id]);
                $link = html_writer::link($url, $r->quizname, ['target' => '_blank']);
            }

            $table->data[] = [
                $link,
                $r->questions,
                $r->correct,
                html_writer::tag('span', "%$rate", ['class' => "$color font-weight-bold"])
            ];
        }

        // Toplam satırı
        $totalrate = $totalq ? number_format(($totalc / $totalq) * 100, 1) : 0;
        $tcolor = ($totalrate >= 80) ? 'text-success' : (($totalrate >= 40) ? 'text-warning' : 'text-danger');

        $table->data[] = new html_table_row([
            html_writer::tag('strong', get_string('total', 'local_yetkinlik')),
            html_writer::tag('strong', $totalq),
            html_writer::tag('strong', $totalc),
            html_writer::tag('strong', "%$totalrate", ['class' => $tcolor])
        ]);

        echo html_writer::table($table);
    } else {
        echo $OUTPUT->notification(get_string('nocompetencyexamdata', 'local_yetkinlik'), 'info');
    }
}

echo $OUTPUT->footer();
