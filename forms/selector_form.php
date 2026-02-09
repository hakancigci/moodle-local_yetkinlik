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
 * Selector form for competency report.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Selector form class for local_yetkinlik competency analysis.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_yetkinlik_selector_form extends moodleform {

    /**
     * Define the form elements.
     */
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'];
        $context = context_course::instance($courseid);

        // 1. Öğrenci Seçimi (Sadece Öğrenciler).
        $users = [0 => get_string('selectuser', 'local_yetkinlik')];

        // Kursa kayıtlı tüm kullanıcıları alalım.
        $enrolled = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname, u.department');

        if (!empty($enrolled)) {
            foreach ($enrolled as $u) {
                // Eğer kullanıcı yöneticiyse veya kursu düzenleme yetkisi (öğretmen vb.) varsa listede gösterme.
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

        $mform->addElement('autocomplete', 'userid', get_string('selectuser', 'local_yetkinlik'), $users, [
            'placeholder' => get_string('searchuserorprept', 'local_yetkinlik'),
            'multiple' => false,
        ]);
        $mform->setType('userid', PARAM_INT);

        // 2. Yetkinlik Seçimi.
        $competencies = [0 => get_string('allcompetencies', 'local_yetkinlik')];
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

        $mform->addElement('autocomplete', 'competencyid', get_string('selectcompetency', 'local_yetkinlik'), $competencies, [
            'placeholder' => get_string('searchcompetency', 'local_yetkinlik'),
            'multiple' => false,
        ]);
        $mform->setType('competencyid', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons(false, get_string('filter', 'local_yetkinlik'));
    }
}
