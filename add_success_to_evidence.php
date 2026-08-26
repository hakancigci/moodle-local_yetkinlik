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
 * AI and Rule-based commentary logic for competencies.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

// Parameters.
$courseid = required_param('courseid', PARAM_INT);
$run = optional_param('run', 0, PARAM_BOOL);
$evaluationmode = optional_param('evaluationmode', 'score_by_question', PARAM_ALPHANUMEXT);

// Security and Context.
$context = context_course::instance($courseid);
require_login($courseid);
require_capability('moodle/site:config', context_system::instance());

// Page Settings.
$PAGE->set_url('/local/yetkinlik/add_success_to_evidence.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('process_success_title', 'local_yetkinlik'));
$PAGE->set_heading(get_string('process_success_heading', 'local_yetkinlik'));

echo $OUTPUT->header();

if ($run) {
    // Create an adhoc task.
    $task = new \local_yetkinlik\task\process_competency_rates_task();
    $task->set_custom_data([
        'courseid' => $courseid,
        'adminid' => $USER->id,
        'evaluationmode' => $evaluationmode,
    ]);

    \core\task\manager::queue_adhoc_task($task);

    echo $OUTPUT->notification(get_string('process_queued', 'local_yetkinlik'), 'success');
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', ['id' => $courseid]));
} else {
    // Form and selection UI.
    echo $OUTPUT->box(get_string('process_success_desc', 'local_yetkinlik'), 'generalbox boxaligncenter');

    $formhtml = '<form action="' . $PAGE->url . '" method="GET">';
    $formhtml .= '<input type="hidden" name="courseid" value="' . $courseid . '">';
    $formhtml .= '<input type="hidden" name="run" value="1">';
    
    $formhtml .= '<div class="form-group mb-3" style="max-width: 500px; margin: 20px auto;">';
    $formhtml .= '<label for="evaluationmode"><strong>' . get_string('label_evaluation_mode', 'local_yetkinlik') . '</strong></label>';
    $formhtml .= '<select name="evaluationmode" id="evaluationmode" class="custom-select form-control">';
    $formhtml .= '<option value="only_evidence">' . get_string('mode_only_evidence', 'local_yetkinlik') . '</option>';
    $formhtml .= '<option value="score_by_question" selected>' . get_string('mode_score_by_question', 'local_yetkinlik') . '</option>';
    $formhtml .= '<option value="score_by_average">' . get_string('mode_score_by_average', 'local_yetkinlik') . '</option>';
    $formhtml .= '</select>';
    $formhtml .= '</div>';

    $formhtml .= '<div class="text-center">';
    $formhtml .= '<input type="submit" class="btn btn-primary" value="' . get_string('btn_process_now', 'local_yetkinlik') . '">';
    $formhtml .= '</div>';
    $formhtml .= '</form>';

    echo $formhtml;
}

echo $OUTPUT->footer();
