<?php
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
