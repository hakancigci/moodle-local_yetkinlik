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
 * Ajax handler for competency mapping.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');

// Güvenlik kontrolleri.
require_login();
require_sesskey();

$action = required_param('action', PARAM_ALPHANUMEXT);

if ($action === 'save_mapping') {
    $courseid = required_param('courseid', PARAM_INT);
    $questionid = required_param('questionid', PARAM_INT);
    $competencyid = required_param('competencyid', PARAM_INT);

    $context = context_course::instance($courseid);
    require_capability('moodle/question:editall', $context);

    global $DB;

    $existing = $DB->get_record('local_yetkinlik_qmap', [
        'courseid' => $courseid,
        'questionid' => $questionid,
    ]);

    if ($competencyid == 0) {
        if ($existing) {
            $DB->delete_records('local_yetkinlik_qmap', ['id' => $existing->id]);
        }
        header('Content-Type: application/json');
        echo json_encode(['status' => 'deleted']);
        exit;
    }

    if ($existing) {
        $existing->competencyid = $competencyid;
        $DB->update_record('local_yetkinlik_qmap', $existing);
    } else {
        $DB->insert_record('local_yetkinlik_qmap', [
            'courseid' => $courseid,
            'questionid' => $questionid,
            'competencyid' => $competencyid,
        ]);
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit;
}
