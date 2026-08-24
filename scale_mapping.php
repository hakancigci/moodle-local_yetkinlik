<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Scale mapping management page for local_yetkinlik.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$scaleid = optional_param('scaleid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

// Yetkilendirme kontrolü (Site yöneticisi veya ders yönetim yetkisi)
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url('/local/yetkinlik/scale_mapping.php', ['scaleid' => $scaleid, 'courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('scalemapping', 'local_yetkinlik'));
$PAGE->set_heading(get_string('scalemapping', 'local_yetkinlik'));
$PAGE->set_pagelayout('admin');

// Form gönderildiyse verileri kaydet
if (optional_param('save', 0, PARAM_INT) && confirm_sesskey() && $scaleid) {
    $minpercents = optional_param_array('minpercent', [], PARAM_FLOAT);
    $maxpercents = optional_param_array('maxpercent', [], PARAM_FLOAT);

    // Önce bu ölçeğe ait eski eşlemeleri temizle
    $DB->delete_records('local_yetkinlik_scale_map', ['scaleid' => $scaleid]);

    // Yeni aralıkları kaydet
    foreach ($minpercents as $index => $minval) {
        $maxval = isset($maxpercents[$index]) ? $maxpercents[$index] : 100.00;
        
        $record = new stdClass();
        $record->scaleid = $scaleid;
        $record->scaleitemindex = $index + 1; // 1 tabanlı indeks
        $record->minpercent = $minval;
        $record->maxpercent = $maxval;

        $DB->insert_record('local_yetkinlik_scale_map', $record);
    }

    redirect(new moodle_url('/local/yetkinlik/scale_mapping.php', ['scaleid' => $scaleid]), 
        get_string('changessaved', 'core'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Sistemdeki tüm ölçekleri çek
$scales = $DB->get_records_sql("SELECT id, name FROM {scale} ORDER BY name ASC");
$scalemenu = [0 => get_string('choosedots', 'moodle')];
foreach ($scales as $s) {
    $scalemenu[$s->id] = format_string($s->name);
}

// Seçilen ölçeğin maddelerini ve mevcut eşlemelerini hazırla
$scaleitems = [];
$currentmapping = [];
if ($scaleid && isset($scales[$scaleid])) {
    $scaledata = $scales[$scaleid];
    $rawscale = $DB->get_field('scale', 'scale', ['id' => $scaleid]);
    if ($rawscale) {
        $scaleitems = explode(',', $rawscale);
    }

    // Mevcut kayıtlı aralıkları çek
    $existing = $DB->get_records('local_yetkinlik_scale_map', ['scaleid' => $scaleid]);
    foreach ($existing as $ex) {
        $currentmapping[$ex->scaleitemindex] = [
            'min' => $ex->minpercent,
            'max' => $ex->maxpercent
        ];
    }
}

// Çıktı Üretimi
echo $OUTPUT->header();

echo '<div class="local_yetkinlik_scale_mapping">';

// 1. Ölçek Seçim Formu
echo '<form method="get" action="scale_mapping.php" class="mb-4 form-inline">';
echo '<label class="mr-2 font-weight-bold" for="scaleid">' . get_string('selectscale', 'local_yetkinlik') . '</label>';
echo html_writer::select($scalemenu, 'scaleid', $scaleid, false, ['class' => 'form-control mr-2', 'onchange' => 'this.form.submit()']);
echo '</form>';

// 2. Eşleme Tablosu Formu
if ($scaleid && !empty($scaleitems)) {
    echo '<form method="post" action="scale_mapping.php">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
    echo '<input type="hidden" name="scaleid" value="' . $scaleid . '">';
    echo '<input type="hidden" name="save" value="1">';

    echo '<table class="generaltable table table-bordered table-striped">';
    echo '<thead class="thead-dark"><tr>';
    echo '<th>#' . get_string('index', 'local_yetkinlik') . '</th>';
    echo '<th>' . get_string('scaleitem', 'local_yetkinlik') . '</th>';
    echo '<th>' . get_string('minpercent', 'local_yetkinlik') . ' (%)</th>';
    echo '<th>' . get_string('maxpercent', 'local_yetkinlik') . ' (%)</th>';
    echo '</tr></thead><tbody>';

    foreach ($scaleitems as $idx => $itemname) {
        $itemindex = $idx + 1;
        $minval = isset($currentmapping[$itemindex]) ? $currentmapping[$itemindex]['min'] : ($idx * (100 / count($scaleitems)));
        $maxval = isset($currentmapping[$itemindex]) ? $currentmapping[$itemindex]['max'] : (($idx + 1) * (100 / count($scaleitems)));

        echo '<tr>';
        echo '<td>' . $itemindex . '</td>';
        echo '<td><strong>' . trim($itemname) . '</strong></td>';
        echo '<td><input type="number" step="0.01" min="0" max="100" name="minpercent[]" value="' . $minval . '" class="form-control" required></td>';
        echo '<td><input type="number" step="0.01" min="0" max="100" name="maxpercent[]" value="' . $maxval . '" class="form-control" required></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '<button type="submit" class="btn btn-primary">' . get_string('savechanges', 'core') . '</button>';
    echo '</form>';
} else if ($scaleid) {
    echo '<div class="alert alert-warning">' . get_string('noscaleitemsfound', 'local_yetkinlik') . '</div>';
}

echo '</div>';

echo $OUTPUT->footer();