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
 * Öğrenci Sınav Yetkinlik Analiz Raporu.
 * * Bu dosya öğrencinin seçtiği sınava göre yetkinlik başarısını raporlar.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

// Gerekli parametrelerin alınması.
$courseid = required_param('courseid', PARAM_INT);
$quizid   = optional_param('quizid', 0, PARAM_INT);

// Kullanıcı oturum ve kurs erişim kontrolü.
require_login($courseid);

global $DB, $USER, $OUTPUT, $PAGE;

$context = context_course::instance($courseid);

// Sayfa URL ve Moodle yapılandırması.
$PAGE->set_url('/local/yetkinlik/student_exam.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('studentexam', 'local_yetkinlik'));
$PAGE->set_heading(get_string('studentexam', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

// --- 1. SINAV SEÇİM FORMU ---
// Öğrencinin bu kursta tamamladığı sınavları getir.
$quizzes = $DB->get_records_sql("
    SELECT DISTINCT q.id, q.name
      FROM {quiz} q
      JOIN {quiz_attempts} qa ON qa.quiz = q.id
     WHERE qa.userid = ? AND q.course = ? AND qa.state = 'finished'
  ORDER BY q.name
", [$USER->id, $courseid]);

echo html_writer::start_tag('div', ['class' => 'card mb-4 border-0 shadow-sm']);
echo html_writer::start_tag('div', ['class' => 'card-body']);

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);

$options = [0 => get_string('selectquiz', 'local_yetkinlik')];
foreach ($quizzes as $q) {
    $options[$q->id] = $q->name;
}

echo html_writer::label(get_string('selectquiz', 'local_yetkinlik'), 'quizid', false, ['class' => 'mr-2 font-weight-bold']);
echo html_writer::select($options, 'quizid', $quizid, false, ['class' => 'form-control mr-2', 'id' => 'quizid']);
echo html_writer::tag('button', get_string('show', 'local_yetkinlik'), ['type' => 'submit', 'class' => 'btn btn-primary']);

echo html_writer::end_tag('form');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// --- 2. VERİ HESAPLAMA VE GÖRÜNÜM ---
if ($quizid) {
    $sql = "
    SELECT
      c.shortname,
      c.description,
      SUM(qa.maxfraction) AS attempts,
      SUM(qas.fraction) AS correct
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
    WHERE quiz.id = ? AND u.id = ? AND quiza.state = 'finished'
    GROUP BY c.shortname, c.description
    ORDER BY c.shortname
    ";

    $rows = $DB->get_records_sql($sql, [$quizid, $USER->id]);

    if ($rows) {
        // Tablo hazırlığı.
        echo html_writer::start_tag('table', ['class' => 'generaltable table-hover mt-3 shadow-sm', 'style' => 'width:100%']);
        echo '<thead><tr>';
        echo html_writer::tag('th', get_string('competencycode', 'local_yetkinlik'));
        echo html_writer::tag('th', get_string('competency', 'local_yetkinlik'));
        echo html_writer::tag('th', get_string('success', 'local_yetkinlik'), ['class' => 'text-center']);
        echo '</tr></thead><tbody>';

        $labels = [];
        $chartdata = [];
        $bgcolors = [];

        foreach ($rows as $r) {
            $rate = $r->attempts ? round(($r->correct / $r->attempts) * 100, 1) : 0;
            
            // Başarı oranına göre renk belirleme (Bootstrap standartlarına uygun HEX kodları).
            if ($rate >= 80) {
                $color = '#28a745'; // Başarılı (Yeşil)
            } else if ($rate >= 60) {
                $color = '#007bff'; // İyi (Mavi)
            } else if ($rate >= 40) {
                $color = '#fd7e14'; // Orta (Turuncu)
            } else {
                $color = '#dc3545'; // Düşük (Kırmızı)
            }

            $labels[] = $r->shortname;
            $chartdata[] = $rate;
            $bgcolors[] = $color;

            echo '<tr>';
            echo html_writer::tag('td', html_writer::tag('strong', $r->shortname));
            echo html_writer::tag('td', $r->description);
            echo html_writer::tag('td', '%' . $rate, [
                'class' => 'text-center font-weight-bold',
                'style' => "color: $color; font-size: 1.1em;"
            ]);
            echo '</tr>';
        }
        echo '</tbody></table>';

        // --- 3. GRAFİK ALANI VE JS ÇAĞRISI ---
       echo html_writer::div(
            '<canvas id="studentexamchart"></canvas>', 
            'card mt-4 p-4 shadow-sm bg-light', 
            ['style' => 'height:400px; min-height:400px; width:100%;']
        );

        $chartparams = [
            'labels'     => $labels,
            'chartData'  => $chartdata,
            'bgColors'   => $bgcolors,
            'chartLabel' => get_string('successpercent', 'local_yetkinlik') . ' (%)'
        ];
         

        // AMD modülündeki ilgili fonksiyonu çağır.
        $PAGE->requires->data_for_js('examData', $chartparams);
        $PAGE->requires->js_call_amd('local_yetkinlik/visualizer', 'initStudentExam', [$chartparams]);

    } else {
        echo $OUTPUT->notification(get_string('noexamdata', 'local_yetkinlik'), 'info');
    }
}

echo $OUTPUT->footer();
