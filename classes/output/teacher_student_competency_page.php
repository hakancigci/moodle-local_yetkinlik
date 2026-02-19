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

/**
 * Output class for teacher student competency page.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class teacher_student_competency_page implements renderable, templatable {

    /** @var stdClass Data object containing calculation results. */
    protected $data;

    /** @var \local_yetkinlik_teacher_form The filter form instance. */
    protected $mform;

    /**
     * Constructor.
     *
     * @param stdClass $data
     * @param \moodleform $mform
     */
    public function __construct($data, $mform) {
        $this->data = $data;
        $this->mform = $mform;
    }

    /**
     * Export data for the Mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $export = new stdClass();

        // Render the Moodle filter form into HTML.
        $export->form_html = $this->mform->render();

        // Check if both student and competency filters are active.
        $export->has_selection = ($this->data->userid > 0 && $this->data->competencyid > 0);

        // Check if there are results to display.
        $export->has_rows = !empty($this->data->rows);

        // Assign row-level data.
        $export->rows = $this->data->rows;

        // Prepare aggregated totals for the table footer if results exist.
        if ($export->has_rows) {
            $export->total = $this->data->total;
        }

        return $export;
    }
}
