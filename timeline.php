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

/**
 * Student Competency Performance Timeline Report.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

// 1. Parametreler ve güvenlik kontrolleri.
$courseid = required_param('courseid', PARAM_INT);
$days     = optional_param('days', 90, PARAM_INT);

require_login($courseid);

global $USER, $DB, $PAGE, $OUTPUT;

$userid = $USER->id;
$context = context_course::instance($courseid);

// 2. Sayfa yapılandırması.
$PAGE->set_url('/local/yetkinlik/timeline.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('timelineheading', 'local_yetkinlik'));
$PAGE->set_heading(get_string('timelineheading', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

// 3. SQL Sorgusu.
$where = "quiz.course = :courseid AND u.id = :userid";
$params = ['courseid' => $courseid, 'userid' => $userid];

if ($days > 0) {
    $where .= " AND qas2.timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL :days DAY))";
    $params['days'] = $days;
}

$sql = "
SELECT
  c.shortname,
  FROM_UNIXTIME(qas2.timecreated, '%Y-%m') AS period,
  CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS attempts,
  CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
FROM {quiz_attempts} quiza
JOIN {user} u ON quiza.userid = u.id
JOIN {question_usages} qu ON qu.id = quiza.uniqueid
JOIN {question_attempts} qa ON qa.questionusageid = qu.id
JOIN {quiz} quiz ON quiz.id = quiza.quiz
JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
JOIN {competency} c ON c.id = m.competencyid
JOIN (
    SELECT questionattemptid, MAX(timecreated) AS timecreated
    FROM {question_attempt_steps}
    GROUP BY questionattemptid
) qas2 ON qas2.questionattemptid = qa.id
JOIN (
    SELECT MAX(fraction) AS fraction, questionattemptid
    FROM {question_attempt_steps}
    GROUP BY questionattemptid
) qas ON qas.questionattemptid = qa.id
WHERE $where AND quiza.state = 'finished'
GROUP BY c.shortname, period
ORDER BY period ASC
";

$rows = $DB->get_records_sql($sql, $params);

// 4. Veri işleme.
$data = [];
$periods = [];

foreach ($rows as $r) {
    $rate = $r->attempts ? round(($r->correct / $r->attempts) * 100, 1) : 0;
    $data[$r->shortname][$r->period] = $rate;
    $periods[$r->period] = true;
}

$periods = array_keys($periods);
sort($periods);

// 5. Grafik için Dataset hazırlığı.
$datasets = [];
$colors = ['#e53935', '#1e88e5', '#43a047', '#fb8c00', '#8e24aa', '#00897b'];
$i = 0;

foreach ($data as $comp => $vals) {
    $line = [];
    foreach ($periods as $p) {
        $line[] = isset($vals[$p]) ? (float)$vals[$p] : 0;
    }
    $datasets[] = [
        'label' => $comp,
        'data' => $line,
        'borderColor' => $colors[$i % count($colors)],
        'backgroundColor' => $colors[$i % count($colors)],
        'fill' => false,
        'tension' => 0.3,
    ];
    $i++;
}

// 6. Filtreleme Formu (UI).
echo html_writer::start_tag('div', ['class' => 'card mb-4 shadow-sm']);
echo html_writer::start_tag('div', ['class' => 'card-body']);

$formurl = new moodle_url('/local/yetkinlik/timeline.php');
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $formurl, 'class' => 'form-inline']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);

echo html_writer::label(get_string('filterlabel', 'local_yetkinlik'), 'days', false, ['class' => 'mr-2']);

$options = [
    '30' => get_string('last30days', 'local_yetkinlik'),
    '90' => get_string('last90days', 'local_yetkinlik'),
    '0'  => get_string('alltime', 'local_yetkinlik'),
];
echo html_writer::select($options, 'days', $days, false, ['class' => 'form-control mr-2', 'id' => 'days']);

echo html_writer::tag('button', get_string('show', 'local_yetkinlik'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);

echo html_writer::end_tag('form');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// 7. Grafik Alanı.
echo html_writer::div(
    '<canvas id="timeline"></canvas>',
    'card p-4 shadow-sm bg-light',
    ['style' => 'height:450px; width:100%;']
);

// 8. AMD Modülünü Çağırma.
$chartparams = [
    'labels'       => $periods,
    'datasets'     => $datasets,
    'successLabel' => get_string('successrate', 'local_yetkinlik')
];

$PAGE->requires->js_call_amd('local_yetkinlik/visualizer', 'initTimeline', [$chartparams]);

echo $OUTPUT->footer();
