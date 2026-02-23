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
 * Student competency progress timeline report.
 * Tracks performance over time to visualize improvement or gaps.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$days     = optional_param('days', 90, PARAM_INT);

require_login($courseid);
$context = context_course::instance($courseid);

// Page Setup and Navigation items.
$PAGE->set_url('/local/yetkinlik/timeline.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('timelineheading', 'local_yetkinlik'));
$PAGE->set_heading(get_string('timelineheading', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

// 1. SQL Preparation.
$where = "quiz.course = :courseid AND u.id = :userid AND quiza.state = 'finished'";
$params = ['courseid' => $courseid, 'userid' => $USER->id];

// PostgreSQL uyumluluğu için tarih filtresini PHP üzerinden hesaplıyoruz.
if ($days > 0) {
    $cutoff = time() - ($days * 86400); 
    $where .= " AND qas2.timecreated > :cutoff";
    $params['cutoff'] = $cutoff;
}

// SQL Sorgusu: Veritabanı bağımsız (Cross-DB) hale getirildi.
// FROM_UNIXTIME yerine ham timecreated çekilip PHP'de formatlanacak.
$sql = "SELECT qas2.id AS stepid, 
               c.shortname, 
               qas2.timecreated,
               qa.maxfraction AS attempt_max, 
               qas.fraction AS step_fraction
        FROM {quiz_attempts} quiza
        JOIN {user} u ON quiza.userid = u.id
        JOIN {question_usages} qu ON qu.id = quiza.uniqueid
        JOIN {question_attempts} qa ON qa.questionusageid = qu.id
        JOIN {quiz} quiz ON quiz.id = quiza.quiz
        JOIN {qbank_yetkinlik_qmap} m ON m.questionid = qa.questionid
        JOIN {competency} c ON c.id = m.competencyid
        JOIN (
            SELECT questionattemptid, MAX(id) as id, MAX(timecreated) AS timecreated
            FROM {question_attempt_steps}
            GROUP BY questionattemptid
        ) qas2 ON qas2.questionattemptid = qa.id
        JOIN (
            SELECT MAX(fraction) AS fraction, questionattemptid
            FROM {question_attempt_steps}
            GROUP BY questionattemptid
        ) qas ON qas.questionattemptid = qa.id
        WHERE $where
        ORDER BY qas2.timecreated ASC";

$rows = $DB->get_records_sql($sql, $params);

// 2. Data Processing.
$compdata = [];
$periods = [];
$raw_totals = [];

foreach ($rows as $r) {
    // Tarihi PHP tarafında formatlayarak DB uyumsuzluğunu gideriyoruz (Yıl-Ay).
    $period = date('Y-m', $r->timecreated);
    $periods[$period] = true;
    
    if (!isset($raw_totals[$r->shortname][$period])) {
        $raw_totals[$r->shortname][$period] = ['attempts' => 0, 'correct' => 0];
    }
    
    $raw_totals[$r->shortname][$period]['attempts'] += $r->attempt_max;
    $raw_totals[$r->shortname][$period]['correct'] += $r->step_fraction;
}

// Yüzdelik hesaplama ve Chart.js formatına hazırlık.
$periods = array_keys($periods);
sort($periods);

$datasets = [];
$colors = ['#e53935', '#1e88e5', '#43a047', '#fb8c00', '#8e24aa', '#00897b'];
$i = 0;

foreach ($raw_totals as $comp => $monthly_vals) {
    $line = [];
    foreach ($periods as $p) {
        if (isset($monthly_vals[$p]) && $monthly_vals[$p]['attempts'] > 0) {
            $rate = round(($monthly_vals[$p]['correct'] / $monthly_vals[$p]['attempts']) * 100, 1);
            $line[] = (float)$rate;
        } else {
            $line[] = 0;
        }
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

// 3. Output Generation.
echo $OUTPUT->header();

$renderdata = new stdClass();
$renderdata->courseid = $courseid;
$renderdata->days = $days;
$renderdata->periods = $periods;
$renderdata->datasets = $datasets;

$page = new \local_yetkinlik\output\timeline_page($renderdata);
echo $OUTPUT->render($page);

echo $OUTPUT->footer();
