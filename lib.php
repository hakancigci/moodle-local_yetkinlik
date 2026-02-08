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

/**
 * Soru düzenleme sayfasına özel JS ekleme
 */
function local_yetkinlik_before_standard_html_head() {
    global $PAGE;

    if (strpos($PAGE->url->out(false), '/question/edit.php') !== false) {
        $PAGE->requires->js_call_amd('local_yetkinlik/mapping', 'init');
    }
}

/**
 * Sol menü / ders menüsüne link ekler
 */
function local_yetkinlik_extend_navigation_course($navigation, $course, $context) {
    // Öğretmen raporu
    if (has_capability('mod/quiz:viewreports', $context)) {
        if (!$navigation->find('yetkinlik_teacher', navigation_node::TYPE_SETTING)) {
            $url = new moodle_url('/local/yetkinlik/class_report.php', ['courseid'=>$course->id]);
            $navigation->add(
                'Kazanım Analizi',
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'yetkinlik_teacher'
            );
        }
    }

    // Öğrenci yetkinlik analizi (öğretmen bakışı)
    if (has_capability('mod/quiz:viewreports', $context)) {
        if (!$navigation->find('yetkinlik_teacher_student', navigation_node::TYPE_SETTING)) {
            $url = new moodle_url('/local/yetkinlik/teacher_student_competency.php', ['courseid'=>$course->id]);
            $navigation->add(
                'Öğrenci Yetkinlik Analizi',
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'yetkinlik_teacher_student'
            );
        }
    }

    // Grup analizleri (sadece kurs yöneticisi)
    if (has_capability('moodle/course:update', $context)) {
        if (!$navigation->find('groupcompetency', navigation_node::TYPE_SETTING)) {
            $url1 = new moodle_url('/local/yetkinlik/group_competency.php', ['courseid' => $course->id]);
            $navigation->add(
                get_string('groupcompetency', 'local_yetkinlik'),
                $url1,
                navigation_node::TYPE_SETTING,
                null,
                'groupcompetency',
                new pix_icon('i/group', '')
            );
        }

        if (!$navigation->find('groupquizcompetency', navigation_node::TYPE_SETTING)) {
            $url2 = new moodle_url('/local/yetkinlik/group_quiz_competency.php', ['courseid' => $course->id]);
            $navigation->add(
                get_string('groupquizcompetency', 'local_yetkinlik'),
                $url2,
                navigation_node::TYPE_SETTING,
                null,
                'groupquizcompetency',
                new pix_icon('i/quiz', '')
            );
        }
    }

    // Öğrenci menüleri
    if (isloggedin() && !isguestuser()) {
        if (!$navigation->find('yetkinlik_student', navigation_node::TYPE_CUSTOM)) {
            $url = new moodle_url('/local/yetkinlik/student_report.php', ['courseid'=>$course->id]);
            $navigation->add(
                'Karnem',
                $url,
                navigation_node::TYPE_CUSTOM,
                null,
                'yetkinlik_student'
            );
        }

        if (!$navigation->find('yetkinlik_student_exam', navigation_node::TYPE_CUSTOM)) {
            $url = new moodle_url('/local/yetkinlik/student_exam.php', ['courseid'=>$course->id]);
            $navigation->add(
                'Sınav Kazanım Analizim',
                $url,
                navigation_node::TYPE_CUSTOM,
                null,
                'yetkinlik_student_exam'
            );
        }

        if (!$navigation->find('yetkinlik_student_competency', navigation_node::TYPE_CUSTOM)) {
            $url = new moodle_url('/local/yetkinlik/student_competency_exams.php', ['courseid'=>$course->id]);
            $navigation->add(
                'Yetkinlik Bazlı Sınavlarım',
                $url,
                navigation_node::TYPE_CUSTOM,
                null,
                'yetkinlik_student_competency'
            );
        }

        if (!$navigation->find('yetkinlik_student_state', navigation_node::TYPE_CUSTOM)) {
            $url = new moodle_url('/local/yetkinlik/student_class.php', ['courseid'=>$course->id]);
            $navigation->add(
                'Yetkinlik Durumu',
                $url,
                navigation_node::TYPE_CUSTOM,
                null,
                'yetkinlik_student_state'
            );
        }

        // Timeline linki
        if (!$navigation->find('yetkinlik_timeline', navigation_node::TYPE_CUSTOM)) {
            $url = new moodle_url('/local/yetkinlik/timeline.php', ['courseid'=>$course->id]);
            $navigation->add(
                get_string('timeline', 'local_yetkinlik'),
                $url,
                navigation_node::TYPE_CUSTOM,
                null,
                'yetkinlik_timeline',
                new pix_icon('i/calendar', '')
            );
        }
    }
}
