<?php
require_once("$CFG->libdir/formslib.php");

class local_yetkinlik_selector_form extends moodleform {
    public function definition() {
        global $DB;
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $context  = context_course::instance($courseid);

        // courseid gizli alan
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        // Öğrenci listesi
        $students = get_enrolled_users($context, 'mod/quiz:attempt');
        $studentoptions = [0 => get_string('allusers','local_yetkinlik')];
        foreach ($students as $s) {
            $studentoptions[$s->id] = fullname($s);
        }

        // Öğrenci autocomplete
        $mform->addElement('autocomplete', 'userid', get_string('user','local_yetkinlik'), $studentoptions);
        $mform->setType('userid', PARAM_INT);
        $mform->setDefault('userid', 0);

        // Yetkinlik listesi (kurs yetkinlikleri)
        $competencies = $DB->get_records_sql_menu(
            "SELECT c.id, c.shortname
               FROM {competency} c
               JOIN {competency_coursecomp} cc ON cc.competencyid = c.id
              WHERE cc.courseid = ?
              ORDER BY c.shortname",
            [$courseid]
        );

        $compoptions = [0 => get_string('allcompetencies','local_yetkinlik')] + $competencies;

        // Yetkinlik autocomplete
        $mform->addElement('autocomplete', 'competencyid', get_string('competency','local_yetkinlik'), $compoptions);
        $mform->setType('competencyid', PARAM_INT);
        $mform->setDefault('competencyid', 0);

        $this->add_action_buttons(false, get_string('show','local_yetkinlik'));
    }
}