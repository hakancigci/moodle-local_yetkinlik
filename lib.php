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
 * Library functions for the local_yetkinlik plugin.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add custom JS to the question editing page.
 *
 * @return void
*/
function local_yetkinlik_before_standard_html_head() {
    global $PAGE;
    if ($PAGE->url->compare(new moodle_url('/question/edit.php'), URL_MATCH_BASE)) {
        $PAGE->requires->js_call_amd('local_yetkinlik/mapping', 'init');
    }
}

/**
 * Extend course navigation with competency analysis links.
 *
 * @param global_navigation $navigation
 * @param stdClass $course
 * @param context_course $context
 * @return void
 */
function local_yetkinlik_extend_navigation_course($navigation, $course, $context) {
    global $USER;

    // 1. Teacher Reports Section.
    if (has_capability('mod/quiz:viewreports', $context)) {
        if (!$navigation->find('yetkinlik_teacher', navigation_node::TYPE_SETTING)) {
            $url = new moodle_url('/local/yetkinlik/class_report.php', ['courseid' => $course->id]);
            $navigation->add(
                get_string('classreport', 'local_yetkinlik'), // Dil dosyasından alınması önerilir
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'yetkinlik_teacher',
                new pix_icon('i/report', '')
            );
        }

        if (!$navigation->find('yetkinlik_teacher_student', navigation_node::TYPE_SETTING)) {
            $url = new moodle_url('/local/yetkinlik/teacher_student_competency.php', ['courseid' => $course->id]);
            $navigation->add(
                get_string('studentanalysis', 'local_yetkinlik'),
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'yetkinlik_teacher_student',
                new pix_icon('i/users', '')
            );
        }
    }

    // 2. Group & Course Management Analysis.
    if (has_capability('moodle/course:update', $context)) {
        if (!$navigation->find('groupcompetency', navigation_node::TYPE_SETTING)) {
            $url = new moodle_url('/local/yetkinlik/group_competency.php', ['courseid' => $course->id]);
            $navigation->add(
                get_string('groupcompetency', 'local_yetkinlik'),
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'groupcompetency',
                new pix_icon('i/group', '')
            );
        }

        if (!$navigation->find('groupquizcompetency', navigation_node::TYPE_SETTING)) {
            $url = new moodle_url('/local/yetkinlik/group_quiz_competency.php', ['courseid' => $course->id]);
            $navigation->add(
                get_string('groupquizcompetency', 'local_yetkinlik'),
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'groupquizcompetency',
                new pix_icon('i/quiz', '')
            );
        }
    }

    // 3. Student Specific Menus.
    if (isloggedin() && !isguestuser()) {
        // Create a parent node for student reports if it doesn't exist to clean up the menu.
        $studentnode = $navigation->find('yetkinlik_student_parent', navigation_node::TYPE_CUSTOM);
        if (!$studentnode) {
            $studentnode = $navigation->add(
                get_string('mycompetencies', 'local_yetkinlik'),
                null,
                navigation_node::TYPE_CUSTOM,
                null,
                'yetkinlik_student_parent',
                new pix_icon('i/stats', '')
            );
        }

        // Student Report (Karnem).
        $studentnode->add(
            get_string('myreportcard', 'local_yetkinlik'),
            new moodle_url('/local/yetkinlik/student_report.php', ['courseid' => $course->id]),
            navigation_node::TYPE_CUSTOM,
            null,
            'yetkinlik_student'
        );

        // Exam Analysis (Sınav Kazanım Analizim).
        $studentnode->add(
            get_string('myexamanalysis', 'local_yetkinlik'),
            new moodle_url('/local/yetkinlik/student_exam.php', ['courseid' => $course->id]),
            navigation_node::TYPE_CUSTOM,
            null,
            'yetkinlik_student_exam'
        );

        // Competency Based Exams (Yetkinlik Bazlı Sınavlarım).
        $studentnode->add(
            get_string('mycompetencyexams', 'local_yetkinlik'),
            new moodle_url('/local/yetkinlik/student_competency_exams.php', ['courseid' => $course->id]),
            navigation_node::TYPE_CUSTOM,
            null,
            'yetkinlik_student_competency'
        );

        // Competency State (Yetkinlik Durumu).
        $studentnode->add(
            get_string('mycompetencystate', 'local_yetkinlik'),
            new moodle_url('/local/yetkinlik/student_class.php', ['courseid' => $course->id]),
            navigation_node::TYPE_CUSTOM,
            null,
            'yetkinlik_student_state'
        );

        // Timeline.
        $studentnode->add(
            get_string('timeline', 'local_yetkinlik'),
            new moodle_url('/local/yetkinlik/timeline.php', ['courseid' => $course->id]),
            navigation_node::TYPE_CUSTOM,
            null,
            'yetkinlik_timeline',
            new pix_icon('i/calendar', '')
        );
    }
}
