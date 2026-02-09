<?php
/**
 * Class Report for Competency Matching.
 * @package    local_yetkinlik
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/forms/selector_form.php');

require_login();

$courseid = required_param('courseid', PARAM_INT);
$context  = context_course::instance($courseid);
require_capability('moodle/course:view', $context);

global $DB, $CFG, $OUTPUT, $PAGE, $COURSE;

$COURSE = get_course($courseid);

$PAGE->set_url('/local/yetkinlik/class_report.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($COURSE);
$PAGE->set_pagelayout('course');
$PAGE->set_title("Yetkinlik Analiz Raporu");
$PAGE->set_heading($COURSE->fullname . " - Yetkinlik Analizi");

echo $OUTPUT->header();

// 1. Parametre Yönetimi
$userid     = optional_param('userid', 0, PARAM_INT);
$competency = optional_param('competencyid', 0, PARAM_INT);

$mform = new local_yetkinlik_selector_form(null, ['courseid' => $courseid]);

// Form gönderildiyse değerleri formdan güncelle
if ($data = $mform->get_data()) {
    $userid     = $data->userid;
    $competency = $data->competencyid;
}

// Formun seçili kalmasını sağla
$mform->set_data(['userid' => $userid, 'competencyid' => $competency]);

// PDF Butonu
echo '<div class="mb-3">';
echo '<a class="btn btn-secondary" target="_blank" href="' . $CFG->wwwroot . '/local/yetkinlik/pdf_report.php?courseid=' . $courseid . '">PDF Rapor Al</a>';
echo '</div>';

$mform->display();

// 2. VERİ HESAPLAMA (SQL)

// --- Kurs Ortalaması ---
$courseSql = "SELECT c.id, c.shortname, 
                     CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS attempts, 
                     CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
              FROM {quiz_attempts} quiza
              JOIN {question_usages} qu ON qu.id = quiza.uniqueid
              JOIN {question_attempts} qa ON qa.questionusageid = qu.id
              JOIN {quiz} quiz ON quiz.id = quiza.quiz
              JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
              JOIN {competency} c ON c.id = m.competencyid
              JOIN (SELECT MAX(fraction) AS fraction, questionattemptid FROM {question_attempt_steps} GROUP BY questionattemptid) qas ON qas.questionattemptid = qa.id
              WHERE quiz.course = :courseid AND quiza.state = 'finished' " 
              . ($competency ? " AND c.id = :competencyid " : "") . 
              " GROUP BY c.id, c.shortname";

$courseData = $DB->get_records_sql($courseSql, ['courseid' => $courseid, 'competencyid' => $competency]);

$classData = [];
$studentData = [];

if ($userid) {
    // --- Sınıf (Bölüm) Ortalaması ---
    $user_dept = $DB->get_field('user', 'department', ['id' => $userid]);
    
    if (!empty($user_dept)) {
        $classSql = "SELECT c.id, c.shortname, CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS attempts, CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
                     FROM {quiz_attempts} quiza
                     JOIN {user} u ON quiza.userid = u.id
                     JOIN {question_usages} qu ON qu.id = quiza.uniqueid
                     JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                     JOIN {quiz} quiz ON quiz.id = quiza.quiz
                     JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
                     JOIN {competency} c ON c.id = m.competencyid
                     JOIN (SELECT MAX(fraction) AS fraction, questionattemptid FROM {question_attempt_steps} GROUP BY questionattemptid) qas ON qas.questionattemptid = qa.id
                     WHERE quiz.course = :courseid AND u.department = :dept AND quiza.state = 'finished' " 
                     . ($competency ? " AND c.id = :competencyid " : "") . 
                     " GROUP BY c.id, c.shortname";
        $classData = $DB->get_records_sql($classSql, ['courseid' => $courseid, 'dept' => $user_dept, 'competencyid' => $competency]);
    }

    // --- Bireysel Öğrenci Ortalaması ---
    $studentSql = "SELECT c.id, c.shortname, CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS attempts, CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
                   FROM {quiz_attempts} quiza
                   JOIN {user} u ON quiza.userid = u.id
                   JOIN {question_usages} qu ON qu.id = quiza.uniqueid
                   JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                   JOIN {quiz} quiz ON quiz.id = quiza.quiz
                   JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
                   JOIN {competency} c ON c.id = m.competencyid
                   JOIN (SELECT MAX(fraction) AS fraction, questionattemptid FROM {question_attempt_steps} GROUP BY questionattemptid) qas ON qas.questionattemptid = qa.id
                   WHERE quiz.course = :courseid AND u.id = :userid AND quiza.state = 'finished' " 
                   . ($competency ? " AND c.id = :competencyid " : "") . 
                   " GROUP BY c.id, c.shortname";
    $studentData = $DB->get_records_sql($studentSql, ['courseid' => $courseid, 'userid' => $userid, 'competencyid' => $competency]);
}

// 3. TABLO VE GRAFİK ÇIKTISI

echo '<table class="generaltable mt-4" style="width:100%">';
echo '<thead><tr><th>Yetkinlik Adı</th><th>Kurs Ort.</th><th>Sınıf Ort.</th><th>Öğrenci Ort.</th></tr></thead>';
echo '<tbody>';

$labels = []; $courseRates = []; $classRates = []; $studentRates = [];

foreach ($courseData as $cid => $c) {
    $courseRate = $c->attempts ? round(($c->correct / $c->attempts) * 100, 1) : 0;
    $classRate  = (isset($classData[$cid]) && $classData[$cid]->attempts) ? round(($classData[$cid]->correct / $classData[$cid]->attempts) * 100, 1) : 0;
    $studRate   = (isset($studentData[$cid]) && $studentData[$cid]->attempts) ? round(($studentData[$cid]->correct / $studentData[$cid]->attempts) * 100, 1) : 0;

    echo "<tr>
            <td>{$c->shortname}</td>
            <td class='font-weight-bold'>%$courseRate</td>
            <td class='text-muted'>%$classRate</td>
            <td class='text-primary font-weight-bold'>%$studRate</td>
          </tr>";

    $labels[] = $c->shortname;
    $courseRates[] = $courseRate;
    $classRates[] = $classRate;
    $studentRates[] = $studRate;
}
echo '</tbody></table>';
?>

<div class="mt-5">
    <canvas id="competencyChart" height="100"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('competencyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                { label: 'Kurs Ort.', data: <?php echo json_encode($courseRates); ?>, backgroundColor: 'rgba(156, 39, 176, 0.6)' },
                { label: 'Sınıf Ort.', data: <?php echo json_encode($classRates); ?>, backgroundColor: 'rgba(76, 175, 80, 0.6)' },
                { label: 'Öğrenci Ort.', data: <?php echo json_encode($studentRates); ?>, backgroundColor: 'rgba(33, 150, 243, 0.6)' }
            ]
        },
        options: { 
            responsive: true,
            scales: { y: { beginAtZero: true, max: 100 } } 
        }
    });
});
</script>

<?php
echo $OUTPUT->footer();