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
 * Report for competency.
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

class group_competency_page implements renderable, templatable {
    protected $courseid;
    protected $groupid;
    protected $data;

    public function __construct($courseid, $groupid, $data) {
        $this->courseid = $courseid;
        $this->groupid = $groupid;
        $this->data = $data;
    }

    public function export_for_template(renderer_base $output) {
        $export = new stdClass();
        $export->courseid = $this->courseid;
        $export->has_group = $this->groupid > 0;
        
        // Use array_values to ensure sequential keys (0, 1, 2...) for Mustache loops.
        $export->groups = !empty($this->data->groups) ? array_values((array)$this->data->groups) : [];
        $export->competencies = !empty($this->data->competencies) ? array_values((array)$this->data->competencies) : [];
        $export->students = !empty($this->data->students) ? array_values((array)$this->data->students) : [];
        $export->totals = !empty($this->data->totals) ? array_values((array)$this->data->totals) : [];
        
        return $export;
    }
}