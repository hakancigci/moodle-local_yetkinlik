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
 * Renderable class for teacher's view of student-specific competency performance.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_yetkinlik\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;

class teacher_student_competency_page implements renderable, templatable {
    protected $data;
    protected $mform;

    public function __construct($data, $mform) {
        $this->data = $data;
        $this->mform = $mform;
    }

    public function export_for_template(renderer_base $output) {
        $export = new stdClass();
        $export->form_html = $this->mform->render();
        $export->competencies = $this->data->competencies;
        $export->has_selection = ($this->data->userid > 0 && $this->data->competencyid > 0);
        if ($export->has_selection) {
            $export->description = $this->data->description;
        }
        $export->has_rows = !empty($this->data->rows);
        $export->has_questions = !empty($this->data->question_details);
        $export->rows = $this->data->rows;
        $export->question_details = $this->data->question_details;
        $export->total = $this->data->total ?? null;

        return $export;
    }
}
