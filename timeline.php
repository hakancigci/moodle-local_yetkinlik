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
// Fetch student's achievement data grouped by competency and month (period).
$where = "quiz.course = :courseid AND u.id = :userid AND quiza.state = 'finished'";
$params = ['courseid' => $courseid, 'userid' => $USER->id];

// Apply date filter if specific timeframe is requested.
if ($days > 0) {
    $where .= " AND qas2.timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL :days DAY))";
    $params['days'] = $days;
}

$sql = "SELECT c.shortname, FROM_UNIXTIME(qas2.timecreated, '%Y-%m') AS period,
               SUM(qa.maxfraction) AS attempts, SUM(qas.fraction) AS correct
        FROM {quiz_attempts} quiza
        JOIN {user} u ON quiza.userid = u.id
        JOIN {question_usages} qu ON qu.id = quiza.uniqueid
        JOIN {question_attempts} qa ON qa.questionusageid = qu.id
        JOIN {quiz} quiz ON quiz.id = quiza.quiz
        JOIN {qbank_yetkinlik_qmap} m ON m.questionid = qa.questionid
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
        WHERE $where
        GROUP BY c.shortname, period
        ORDER BY period ASC";

$rows = $DB->get_records_sql($sql, $params);

// 2. Data Processing for Chart.js.
// Map SQL results into a structured array for time-series visualization.
$compdata = [];
$periods = [];
foreach ($rows as $r) {
    $rate = $r->attempts ? round(($r->correct / $r->attempts) * 100, 1) : 0;
    $compdata[$r->shortname][$r->period] = $rate;
    $periods[$r->period] = true;
}

// Generate a sorted unique list of time periods (Months).
$periods = array_keys($periods);
sort($periods);

$datasets = [];
$colors = ['#e53935', '#1e88e5', '#43a047', '#fb8c00', '#8e24aa', '#00897b'];
$i = 0;

// Transform processed data into Chart.js dataset format.
foreach ($compdata as $comp => $vals) {
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
        'tension' => 0.3, // Curve tension for smoother lines.
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

// Render using the timeline output class.
$page = new \local_yetkinlik\output\timeline_page($renderdata);
echo $OUTPUT->render($page);

echo $OUTPUT->footer();
