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
 * Renderable class for teacher's student-exam analysis view.
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
 * Teacher's student exam analysis output class.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class teacher_student_exam_page implements renderable, templatable {

    /** @var stdClass Data storage for the analysis results and filter options. */
    protected $data;

    /**
     * Constructor.
     *
     * @param stdClass $data
     */
    public function __construct($data) {
        $this->data = $data;
    }

    /**
     * Export data for the Mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $export = new stdClass();
        $export->courseid = $this->data->courseid;
        $export->userid = $this->data->userid;
        $export->quizid = $this->data->quizid;

        // Process Student List for the filter dropdown.
        $export->students = [];
        foreach ($this->data->students as $s) {
            $export->students[] = [
                'id' => $s->id,
                'name' => fullname($s),
                'selected' => ($s->id == $this->data->userid),
            ];
        }

        // Process Quiz List for the filter dropdown.
        $export->quizzes = [];
        foreach ($this->data->quizzes as $q) {
            $export->quizzes[] = [
                'id' => $q->id,
                'name' => format_string($q->name),
                'selected' => ($q->id == $this->data->quizid),
            ];
        }

        $export->rows = [];
        $labels = [];
        $values = [];

        if (!empty($this->data->rows)) {
            foreach ($this->data->rows as $r) {
                // Calculate raw achievement rate.
                $rawrate = $r->attempts ? ($r->correct / $r->attempts) * 100 : 0;

                // Assign Bootstrap contextual classes based on performance thresholds.
                $rowclass = 'table-danger';
                if ($rawrate >= 70) {
                    $rowclass = 'table-success';
                } else if ($rawrate >= 50) {
                    $rowclass = 'table-warning';
                }

                $export->rows[] = [
                    'shortname' => s($r->shortname),
                    'attempts'  => number_format($r->attempts, 0),
                    'correct'   => number_format($r->correct, 1),
                    'rate'      => number_format($rawrate, 1),
                    'rowclass'  => $rowclass,
                ];

                // Arrays for chart visualization data.
                $labels[] = $r->shortname;
                $values[] = round($rawrate, 1);
            }
            $export->has_rows = true;
        }

        // JSON configuration for the JavaScript visualization module.
        $export->chart_config = json_encode([
            'labels' => $labels,
            'values' => $values,
            'label'  => get_string('successrate', 'local_yetkinlik') . ' %',
        ]);

        return $export;
    }
}
