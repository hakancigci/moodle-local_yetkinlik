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
 * Competency Weights.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

$courseid = required_param('courseid', PARAM_INT);
$competencyid = optional_param('competencyid', 0, PARAM_INT);

require_login($courseid);
$context = context_course::instance($courseid);
require_capability('mod/quiz:viewreports', $context);

$PAGE->set_url('/local/yetkinlik/competency_weights.php', ['courseid' => $courseid, 
    //'competencyid' => $competencyid
]);
$PAGE->set_title(get_string('configureweights', 'local_yetkinlik'));
$PAGE->set_heading(get_string('configureweights', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

// Fetch competency list.
$competencies = $DB->get_records_sql("
    SELECT DISTINCT c.id, c.shortname
    FROM {qbank_yetkinlik_qmap} m
    JOIN {competency} c ON c.id = m.competencyid
    JOIN {competency_coursecomp} cc ON cc.competencyid = c.id
    WHERE cc.courseid = :courseid
    ORDER BY c.shortname", ['courseid' => $courseid]);

$compoptions = [0 => get_string('selectcompetency', 'local_yetkinlik')];
foreach ($competencies as $c) {
    $compoptions[$c->id] = $c->shortname;
}

// Form Definition.
class local_yetkinlik_weights_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'competencyid');
        $mform->setType('competencyid', PARAM_INT);

        $items = $this->_customdata['items'] ?? [];

        $mform->addElement('html', '<div class="alert alert-warning">' . get_string('weighttotalwarning', 'local_yetkinlik') . '</div>');

        foreach ($items as $key => $item) {
            $mform->addElement('header', 'hdr_' . $key, $item['name']);
            
            $mform->addElement('text', 'weight_' . $key, get_string('weight_percentage', 'local_yetkinlik'));
            $mform->setType('weight_' . $key, PARAM_FLOAT);
            $mform->setDefault('weight_' . $key, $item['weight']);

            $mform->addElement('checkbox', 'excluded_' . $key, get_string('exclude_item', 'local_yetkinlik'));
            $mform->setDefault('excluded_' . $key, $item['excluded']);
        }

        if (!empty($items)) {
            $this->add_action_buttons(true, get_string('savechanges', 'admin'));
        }
    }
}

// Collect items belonging to the selected competency.
$items = [];
if ($competencyid) {
    // 1. Quiz Performance Group.
    $w_quiz = $DB->get_record('local_yetkinlik_weights', ['courseid' => $courseid, 'competencyid' => $competencyid, 'itemtype' => 'quiz_questions']);
    $items['quiz_questions'] = [
        'name' => get_string('summaryreport', 'local_yetkinlik') . ' (' . get_string('quizperformances', 'local_yetkinlik') . ')',
        'weight' => $w_quiz ? $w_quiz->weight : 0,
        'excluded' => $w_quiz ? $w_quiz->excluded : 0,
    ];

    // 2. Module Activities.
    $sqlactivities = "SELECT cm.id AS cmid, m.name AS modname, cm.instance
                      FROM {competency_modulecomp} mc
                      JOIN {course_modules} cm ON cm.id = mc.cmid
                      JOIN {modules} m ON m.id = cm.module
                      WHERE mc.competencyid = :competencyid AND cm.course = :courseid";
    $rawactivities = $DB->get_records_sql($sqlactivities, ['competencyid' => $competencyid, 'courseid' => $courseid]);

    foreach ($rawactivities as $ar) {
        $modulename = ucfirst($ar->modname);
        if ($DB->get_manager()->table_exists($ar->modname) && $modrec = $DB->get_record($ar->modname, ['id' => $ar->instance])) {
            $modulename = $modrec->name ?? $modrec->title ?? $modulename;
        }
        
        $itemkey = 'mod_' . $ar->cmid;
        $w_mod = $DB->get_record('local_yetkinlik_weights', ['courseid' => $courseid, 'competencyid' => $competencyid, 'itemtype' => $ar->modname, 'itemid' => $ar->instance]);
        
        $items[$itemkey] = [
            'name' => get_string('activityprefix', 'local_yetkinlik') . ': ' . $modulename,
            'weight' => $w_mod ? $w_mod->weight : 0,
            'excluded' => $w_mod ? $w_mod->excluded : 0,
            'modname' => $ar->modname,
            'instance' => $ar->instance
        ];
    }
}

$mform = new local_yetkinlik_weights_form(null, ['items' => $items]);

// Capture competencyid safely while getting form data.
if ($frmdata = $mform->get_data()) {
    $competencyid = $frmdata->competencyid;
    $totalweight = 0;
    foreach ($items as $key => $item) {
        $w = floatval($frmdata->{'weight_' . $key});
        $ex = isset($frmdata->{'excluded_' . $key}) ? 1 : 0;
        if (!$ex) {
            $totalweight += $w;
        }
    }

    if (round($totalweight, 2) != 100.00) {
        \core\notification::error(get_string('weighttotalerror', 'local_yetkinlik', $totalweight));
    } else {
        foreach ($items as $key => $item) {
            $w = floatval($frmdata->{'weight_' . $key});
            $ex = isset($frmdata->{'excluded_' . $key}) ? 1 : 0;
            $itemtype = ($key === 'quiz_questions') ? 'quiz_questions' : $item['modname'];
            $itemid = ($key === 'quiz_questions') ? 0 : $item['instance'];

            $existing = $DB->get_record('local_yetkinlik_weights', [
                'courseid' => $courseid, 'competencyid' => $competencyid, 'itemtype' => $itemtype, 'itemid' => $itemid
            ]);

            $record = new stdClass();
            $record->courseid = $courseid;
            $record->competencyid = $competencyid;
            $record->itemtype = $itemtype;
            $record->itemid = $itemid;
            $record->weight = $w;
            $record->excluded = $ex;

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('local_yetkinlik_weights', $record);
            } else {
                $DB->insert_record('local_yetkinlik_weights', $record);
            }
        }
        \core\notification::success(get_string('weightsavedsuccess', 'local_yetkinlik'));
        redirect(new moodle_url('/local/yetkinlik/competency_weights.php', ['courseid' => $courseid, 'competencyid' => $competencyid]));
    }
}

// Set initial data to form object (critical for hidden field population).
$mform->set_data(['courseid' => $courseid, 'competencyid' => $competencyid]);

echo $OUTPUT->header();

// Competency selector dropdown layout.
echo '<div class="card mb-4 p-3 bg-light"><form method="GET" action="">';
echo '<input type="hidden" name="courseid" value="'.$courseid.'">';
echo '<label><b>' . get_string('selectcompetencylabel', 'local_yetkinlik') . ':</b></label> ';
echo html_writer::select($compoptions, 'competencyid', $competencyid, false, ['class' => 'custom-select d-inline-block w-auto ml-2 mr-2', 'onchange' => 'this.form.submit()']);
echo '</form></div>';

if ($competencyid) {
    $mform->display();
}
echo $OUTPUT->footer();
