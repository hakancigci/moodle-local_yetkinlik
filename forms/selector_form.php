<?php
/**
 * Selector form for competency report.
 * @package    local_yetkinlik
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class local_yetkinlik_selector_form extends moodleform {

    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $context = context_course::instance($courseid);

        // 1. Öğrenci Seçimi (Sadece Öğrenciler)
        $users = [0 => "--- Öğrenci Seçin ---"];
        
        // Kursa kayıtlı tüm kullanıcıları alalım
        $enrolled = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.department');
        
        if (!empty($enrolled)) {
            foreach ($enrolled as $u) {
                // EĞER kullanıcı yöneticiyse veya kursu düzenleme yetkisi (öğretmen vb.) varsa listede gösterme
                if (is_siteadmin($u->id) || has_capability('moodle/course:update', $context, $u->id)) {
                    continue; 
                }

                $name = fullname($u);
                if (!empty($u->department)) {
                    $name .= " (" . $u->department . ")";
                }
                $users[$u->id] = $name;
            }
        }

        $mform->addElement('autocomplete', 'userid', "Öğrenci Seçin", $users, [
            'placeholder' => "İsim veya Bölüm Yazın...",
            'multiple' => false
        ]);
        $mform->setType('userid', PARAM_INT);

        // 2. Yetkinlik Seçimi
        $competencies = [0 => "--- Tüm Yetkinlikler ---"];
        $sql = "SELECT DISTINCT c.id, c.shortname 
                FROM {competency} c
                JOIN {local_yetkinlik_qmap} m ON m.competencyid = c.id
                WHERE m.courseid = :courseid";
        
        $records = $DB->get_records_sql($sql, ['courseid' => $courseid]);
        if ($records) {
            foreach ($records as $record) {
                $competencies[$record->id] = $record->shortname;
            }
        }

        $mform->addElement('autocomplete', 'competencyid', "Yetkinlik Seçin", $competencies, [
            'placeholder' => "Yetkinlik Adı Yazın...",
            'multiple' => false
        ]);
        $mform->setType('competencyid', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(false, "Filtrele");
    }
}