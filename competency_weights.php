<?php
// This file is part of Moodle - http://moodle.org/

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

// Yetkinlik listesi
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

// Form Tanımı
class local_yetkinlik_weights_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'competencyid');
        $mform->setType('competencyid', PARAM_INT);

        $items = $this->_customdata['items'] ?? [];

        $mform->addElement('html', '<div class="alert alert-warning">Ağırlıkların toplamı tam olarak <strong>100</strong> olmalıdır. Hariç tutmak istediğiniz öğeler için ağırlığı 0 yapabilir veya hariç tut kutucuğunu işaretleyebilirsiniz.</div>');

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

// Seçilen yetkinliğe ait ögeleri toplama
$items = [];
if ($competencyid) {
    // 1. Soru Başarı Grubu
    $w_quiz = $DB->get_record('local_yetkinlik_weights', ['courseid' => $courseid, 'competencyid' => $competencyid, 'itemtype' => 'quiz_questions']);
    $items['quiz_questions'] = [
        'name' => get_string('summaryreport', 'local_yetkinlik') . ' (Soru Başarıları)',
        'weight' => $w_quiz ? $w_quiz->weight : 0,
        'excluded' => $w_quiz ? $w_quiz->excluded : 0,
    ];

    // 2. Modül Etkinlikleri
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
            'name' => 'Etkinlik: ' . $modulename,
            'weight' => $w_mod ? $w_mod->weight : 0,
            'excluded' => $w_mod ? $w_mod->excluded : 0,
            'modname' => $ar->modname,
            'instance' => $ar->instance
        ];
    }
}

$mform = new local_yetkinlik_weights_form(null, ['items' => $items]);

// Form verilerini alırken competencyid'yi güvenli şekilde yakalayalım
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
        \core\notification::error("Ağırlıklar toplamı 100 olmalıdır! Mevcut toplam: " . $totalweight);
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
        \core\notification::success("Ağırlıklar başarıyla kaydedildi.");
        redirect(new moodle_url('/local/yetkinlik/competency_weights.php', ['courseid' => $courseid, 'competencyid' => $competencyid]));
    }
}

// Form nesnesine başlangıç verilerini set ediyoruz (hidden alanın formda dolması için kritik)
$mform->set_data(['courseid' => $courseid, 'competencyid' => $competencyid]);

echo $OUTPUT->header();

// Yetkinlik seçme dropdown arayüzü
echo '<div class="card mb-4 p-3 bg-light"><form method="GET" action="">';
echo '<input type="hidden" name="courseid" value="'.$courseid.'">';
echo '<label><b>Yetkinlik Seçin:</b></label> ';
echo html_writer::select($compoptions, 'competencyid', $competencyid, false, ['class' => 'custom-select d-inline-block w-auto ml-2 mr-2', 'onchange' => 'this.form.submit()']);
echo '</form></div>';

if ($competencyid) {
    $mform->display();
}
echo $OUTPUT->footer();