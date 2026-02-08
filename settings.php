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
 * @package   local_yetkinlik
 * @copyright 2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later*/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Plugin için ayar sayfası
    $settings = new admin_settingpage('local_yetkinlik', get_string('pluginname', 'local_yetkinlik'));

    if ($ADMIN->fulltree) {
        // AI entegrasyonu aktif/pasif
        $settings->add(new admin_setting_configcheckbox(
            'local_yetkinlik/enable_ai',
            get_string('enable_ai', 'local_yetkinlik'),
            get_string('enable_ai_desc', 'local_yetkinlik'),
            0
        ));
        // API anahtarı
        $settings->add(new admin_setting_configtext(
            'local_yetkinlik/apikey',
            get_string('apikey', 'local_yetkinlik'),
            get_string('apikey_desc', 'local_yetkinlik'),
            ''
        ));

        // Model adı
        $settings->add(new admin_setting_configtext(
            'local_yetkinlik/model',
            get_string('model', 'local_yetkinlik'),
            get_string('model_desc', 'local_yetkinlik'),
            'gpt-4'
        ));
       

    }
     $ADMIN->add('reports', new admin_externalpage(
        'schoolreport',
        get_string('schoolreport', 'local_yetkinlik'),
        $CFG->wwwroot.'/local/yetkinlik/school_report.php',
        'moodle/site:config'
    ));

    $ADMIN->add('reports', new admin_externalpage(
        'schoolpdf',
        get_string('schoolpdf', 'local_yetkinlik'),
        $CFG->wwwroot.'/local/yetkinlik/school_pdf.php',
        'moodle/site:config'
    ));



    // Site yönetimi → Yerel eklentiler altında ekle
    $ADMIN->add('localplugins', $settings);
}
