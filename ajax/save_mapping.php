<?php
require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

$courseid = required_param('courseid', PARAM_INT);
$questionid = required_param('questionid', PARAM_INT);
$competencyid = required_param('competencyid', PARAM_INT);

$context = context_course::instance($courseid);
require_capability('moodle/question:editall', $context);

global $DB;

$existing = $DB->get_record('local_yetkinlik_qmap', [
    'courseid' => $courseid,
    'questionid' => $questionid
]);

if ($competencyid == 0) {
    if ($existing) {
        $DB->delete_records('local_yetkinlik_qmap', ['id' => $existing->id]);
    }
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
        'competencyid' => $competencyid
    ]);
}

echo json_encode(['status' => 'ok']);
